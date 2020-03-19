<?php
/**
 * Payment enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @copyright  based on work by 2010 Eugene Venter (originally for enrol_paypal)
 * @author     Seth Yoder - based on code by Eugene Venter, Martin Dougiamas and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('classes/util.php');
require_once('currencyCodes.php');
require_once('paymentlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/completionlib.php');

defined('MOODLE_INTERNAL') || die();

define('ENROL_PAYMENT_RESTRICTION_NONE', 0);
define('ENROL_PAYMENT_RESTRICTION_ALL', 1);
define('ENROL_PAYMENT_RESTRICTION_ANY', 2);

/**
 * Payment enrolment plugin implementation.
 * @copyright  2018 Seth Yoder
 * @copyright  based on work by 2010 Eugene Venter (originally for enrol_paypal)
 * @author     Seth Yoder - based on code by Eugene Venter, Martin Dougiamas and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_payment_plugin extends enrol_plugin {

    public function get_currencies() {
        // See https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside,
        // 3-character ISO-4217: https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_currency_codes
        $codes = array(
            'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY',
            'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD');
        $currencies = array();
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }

        return $currencies;
    }

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        $found = false;
        foreach ($instances as $instance) {
            if (!$this->check_restrictions($instance)) {
                continue;
            }
            if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
                continue;
            }
            if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
                continue;
            }
            $found = true;
            break;
        }
        if ($found) {
            return array(new pix_icon('icon', get_string('pluginname', 'enrol_payment'), 'enrol_payment'));
        }
        return array();
    }

    public function roles_protected() {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // users with unenrol cap may unenrol other users manually - requires enrol/payment:unenrol
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // users with manage cap may tweak period and status - requires enrol/payment:manage
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Returns true if the user can add a new instance in this course.
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/payment:config', $context)) {
            return false;
        }

        // multiple instances supported - different cost for different roles
        return true;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Returns serialized restrictions object
     * @param object or array
     * @return string
     */
    public function serialize_restrictions($fields) {

      $fields = (array) $fields;
      $restrictions = (object) [
        'prerequisite'=>[
          'mode'=>$fields['prerequisite_mode'] || null,
          'id'=>$fields['prerequisite_id']
        ],
        'corequisite'=>[
          'mode'=>$fields['corequisite_mode'],
          'id'=>$fields['corequisite_id']
        ],
        'conflicting'=>[
          'mode'=>$fields['conflicting_mode'],
          'id'=>$fields['conflicting_id']
        ],
        'cohortmember'=>[
          'mode'=>$fields['cohortmember_mode'],
          'id'=>$fields['cohortmember_id']
        ],
        'cohortnonmember'=>[
          'mode'=>$fields['cohortnonmember_mode'],
          'id'=>$fields['cohortnonmember_id']
        ]
      ];
      return serialize($restrictions);
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        if ($fields && !empty($fields['cost'])) {
            $fields['cost'] = unformat_float($fields['cost']);
        }
        $fields['customtext2'] = $this->serialize_restrictions($fields);
        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        if ($data) {
            $data->cost = unformat_float($data->cost);
        }
        $data->customtext2 = $this->serialize_restrictions($data);
        return parent::update_instance($instance, $data);
    }

    /**
     * Get the "from" contact which the email will be sent from.
     *
     * @param int $sendoption send email from constant ENROL_SEND_EMAIL_FROM_*
     * @param $context context where the user will be fetched
     * @return mixed|stdClass the contact user object.
     */
    private function get_welcome_email_contact($sendoption, $context) {
        global $CFG;

        $contact = null;
        // Send as the first user assigned as the course contact.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_COURSE_CONTACT) {
            $rusers = array();
            if (!empty($CFG->coursecontact)) {
                $croles = explode(',', $CFG->coursecontact);
                list($sort, $sortparams) = users_order_by_sql('u');
                // We only use the first user.
                $i = 0;
                do {
                    $allnames = get_all_user_name_fields(true, 'u');
                    $rusers = get_role_users($croles[$i], $context, true, 'u.id,  u.confirmed, u.username, '. $allnames . ',
                    u.email, r.sortorder, ra.id', 'r.sortorder, ra.id ASC, ' . $sort, null, '', '', '', '', $sortparams);
                    $i++;
                } while (empty($rusers) && !empty($croles[$i]));
            }
            if ($rusers) {
                $contact = array_values($rusers)[0];
            }
        } else if ($sendoption == ENROL_SEND_EMAIL_FROM_KEY_HOLDER) {
            // Send as the first user with enrol/self:holdkey capability assigned in the course.
            list($sort) = users_order_by_sql('u');
            $keyholders = get_users_by_capability($context, 'enrol/payment:holdkey', 'u.*', $sort);
            if (!empty($keyholders)) {
                $contact = array_values($keyholders)[0];
            }
        }

        // If send welcome email option is set to no reply or if none of the previous options have
        // returned a contact send welcome message as noreplyuser.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_NOREPLY || empty($contact)) {
            $contact = core_user::get_noreply_user();
        }

        return $contact;
    }

    /**
     * Send welcome email to specified user.
     *
     * @param stdClass $instance
     * @param stdClass $user user record
     * @return void
     */
    public function email_welcome_message($instance, $user) {
        global $CFG, $DB;

        $course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $a = new stdClass();
        $a->coursename = $course->fullname;
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$course->id";

        if (trim($instance->customtext1) !== '') {
            $message = $instance->customtext1;
            $key = array('{$a->coursename}', '{$a->profileurl}', '{$a->fullname}', '{$a->email}');
            $value = array($a->coursename, $a->profileurl, fullname($user), $user->email);
            $message = str_replace($key, $value, $message);
            if (strpos($message, '<') === false) {
                // Plain text only.
                $messagetext = $message;
                $messagehtml = text_to_html($messagetext, null, false, true);
            } else {
                // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                $messagehtml = format_text($message, FORMAT_MOODLE, array('context'=>$context, 'para'=>false, 'newlines'=>true, 'filter'=>true));
                $messagetext = html_to_text($messagehtml);
            }
        } else {
            $messagetext = get_string('welcometocoursetext', 'enrol_self', $a);
            $messagehtml = text_to_html($messagetext, null, false, true);
        }

        $subject = get_string('welcometocourse', 'enrol_self', $course->fullname);

        $sendoption = $instance->customint1;
        $contact = $this->get_welcome_email_contact($sendoption, $context);

        // Directly emailing welcome message rather than using messaging.
        email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
    }

    function output_transfer_instructions($cost, $coursefullname, $courseshortname) {
        if ($this->get_config("allowbanktransfer")) {
            $instructions = $this->get_config("transferinstructions");
            $instructions = str_replace("{{AMOUNT}}", "<span id=\"banktransfer-cost\">$cost</span>", $instructions);
            $instructions = str_replace("{{COURSESHORTNAME}}", $courseshortname, $instructions);
            $instructions = str_replace("{{COURSEFULLNAME}}", $coursefullname, $instructions);
            echo '<span id="interac-text">';
            echo $instructions;
            echo '</span>';
        }
    }

    function get_tax_info($cost) {
        global $USER;
        if($this->get_config('definetaxes')) {
            $taxdefs = $this->get_config('taxdefinitions');

            $taxdef_lines = explode("\n", $taxdefs);

            foreach($taxdef_lines as $l) {
                $pieces = explode(":", $l);
                if (sizeof($pieces) == 2) {
                    $province = strtolower(trim($pieces[0]));
                    $taxrate = trim($pieces[1]);

                    if($province == strtolower(trim($USER->msn))) {
                        if(is_numeric($taxrate)) {
                            try {
                                $float_taxrate = floatval($taxrate);
                                return [ "tax_percent" => $float_taxrate
                                       , "tax_string" => "(" . floor($float_taxrate * 100) . "% tax)"
                                       ];

                            } catch (Exception $e) {
                                debugging("Could not convert tax value for $province into a float.");
                            }
                        } else {
                            debugging('Encountered non-numeric tax value.');
                        }
                    }
                } else {
                    debugging('Incorrect tax definition format.');
                }
            }

        }

        return [ "tax_percent" => 0
               , "tax_string" => ""
               ];
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    function enrol_page_hook(stdClass $instance) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        if (!$this->check_restrictions($instance)) {
          return;
        }

        ob_start();

        if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', array('id'=>$instance->courseid));
        $context = context_course::instance($course->id);

        if($this->get_config('stripelogo')) {
            $stripelogourl = (string) moodle_url::make_pluginfile_url(1, "enrol_payment", "stripelogo", null, "/", str_replace('/', '', $this->get_config('stripelogo')));
        } else {
            $stripelogourl = null;
        }

        $shortname = format_string($course->shortname, true, array('context' => $context));
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ( (float) $instance->cost <= 0 ) {
            $original_cost = (float) $this->get_config('cost');
        } else {
            $original_cost = (float) $instance->cost;
        }

        $tax_string = "";

        $tax_info = $this->get_tax_info($original_cost);
        $tax_string = $tax_info["tax_string"];
        $tax_percent = $tax_info["tax_percent"];

        if (abs($original_cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
            echo '<p>'.get_string('nocost', 'enrol_payment').'</p>';
        } else {

            $wwwroot = $CFG->wwwroot;

            $multiple_enabled = ($this->get_config('allowmultipleenrol') && $instance->customint5);
            $paypal_enabled = (bool) trim($this->get_config('paypalbusiness'));
            $stripe_enabled = ((bool) trim($this->get_config('stripesecretkey'))) && ((bool) trim($this->get_config('stripepublishablekey')));
            $gateways_enabled = ((int) $paypal_enabled) + ((int) $stripe_enabled);
            $stripepublishablekey = $stripe_enabled ? $this->get_config('stripepublishablekey') : null;

            if (isguestuser()) { // force login only for guest user, not real users with guest role
                //echo '<div class="mdl-align"><p>'.get_string('paymentrequired').'</p>';
                //echo '<p><b>'.get_string('cost').": $instance->currency $localisedcost".'</b></p>';
                echo '<p><a href="'.$wwwroot.'/login/">'.get_string('loginsite').'</a></p>';
                echo '</div>';
            } else {
                //Used to verify payment data so that it can't be spoofed.
                $prepayToken = bin2hex(random_bytes(16));
                $discountCodeRequired = $instance->customint7;
                $discountThreshold = $instance->customint8;

                $paymentdata = [ 'prepaytoken' => $prepayToken
                               , 'userid' => $USER->id
                               , 'courseid' => $course->id
                               , 'instanceid' => $instance->id
                               , 'multiple' => false
                               , 'multiple_userids' => null
                               , 'code_given' => false
                               , 'units' => 1
                               , 'original_cost' => $original_cost
                               , 'tax_percent' => $tax_percent
                               , 'paypal_txn_id' => null
                               ];

                $payment_id = $DB->insert_record("enrol_payment_session", $paymentdata);

                // Calculate localised and "." cost, make sure we send PayPal/Stripe the same value,
                // please note PayPal expects amount with 2 decimal places and "." separator.
                $payment_obj = $DB->get_record("enrol_payment_session", array("id" => $payment_id));

                $calculatecost = paymentlib\enrol_payment_calculate_cost($instance,$payment_obj,true);
                $calculatecost_untaxed = paymentlib\enrol_payment_calculate_cost($instance,$payment_obj,false);
                $localisedcost = $calculatecost['subtotal_localised'];
                $localisedcost_untaxed = $calculatecost_untaxed['subtotal_localised'];

                //If percentage discount, get the percentage amount to display
                if($instance->customint3 == 1) {
                    $percentDisplay = $calculatecost['percent_discount'];
                }

                $original_cost = format_float($original_cost, 2, false);
                $nonlocalised_untaxed_cost = paymentlib\enrol_payment_calculate_cost($instance,$payment_obj,false)['subtotal'];

                $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                $enableDiscountCodes = $this->get_config('enablediscounts') && $instance->customint7 && $instance->customint3; //Are discounts enabled in the admin settings?
                $validatezipcode = $this->get_config('validatezipcode');
                $billingAddressRequired = $this->get_config('billingaddress');
                $discountAmount = format_float($instance->customdec1, 2, true);

                $symbol = enrol_payment_get_currency_symbol($instance->currency);

                $js_data = [ $instance->id
                           , $stripepublishablekey
                           , $original_cost
                           , $prepayToken
                           , htmlspecialchars_decode($coursefullname)
                           , $instance->customint4
                           , $stripelogourl
                           , $tax_percent
                           , $localisedcost_untaxed
                           , $validatezipcode
                           , $billingAddressRequired
                           , $USER->email
                           , $instance->currency
                           , $symbol
                           , $discountCodeRequired
                           , $discountThreshold
                           ];
                $PAGE->requires->js_call_amd('enrol_payment/enrolpage', 'init', $js_data);
                $PAGE->requires->css('/enrol/payment/style/styles.css');

                //Sanitise some fields before building the PayPal form
                $courseshortname   = $shortname;
                $userfullname      = fullname($USER);
                $userfirstname     = $USER->firstname;
                $userlastname      = $USER->lastname;
                $useraddress       = $USER->address;
                $usercity          = $USER->city;
                $paypalShipping    = $instance->customint4 ? 2 : 1;
                $stripeShipping    = $instance->customint4;
                $instancename      = $this->get_instance_name($instance);
                $tax_amount_string = format_float($tax_percent * $original_cost, 2, true);
                $tax_amount        = format_float($tax_percent * $original_cost, 2, false);

                include($CFG->dirroot.'/enrol/payment/enrol.html');
            }

        }

        return $OUTPUT->box(ob_get_clean());
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = array(
                'courseid'   => $data->courseid,
                'enrol'      => $this->get_name(),
                'roleid'     => $data->roleid,
                'cost'       => $data->cost,
                'currency'   => $data->currency,
            );
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }

    /**
     * Return an array of valid options for the roleid.
     *
     * @param stdClass $instance
     * @param context $context
     * @return array
     */
    protected function get_roleid_options($instance, $context) {
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $this->get_config('roleid'));
        }
        return $roles;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $DB, $COURSE;
        //Add "float2" element for float formatting
        require_once('HTML/QuickForm.php');
        MoodleQuickForm::registerElementType('float2', dirname(__FILE__) . '/classes/float2.php', "MoodleQuickForm_float2");

        /**
         * Custom fields:
         *
         * customint1 - Send course welcome message (bool)
         * customint2 - Enrol user into a group (Group id)
         * customint3 - Discount type (0: No discount, 1: Percentage discount, 2: Value discount)
         * customint4 - require shipping info at checkout (bool)
         * customint5 - allow multiple enrollments (bool)
         * customint6 - Enable custom tax calculation based on province in "msn" field (bool)
         * customint7 - Discount code required (bool)
         *
         * customtext1 - Custom welcome message
         * customtext2 - Discount code
         *
         * customdec1 - Discount amount
         */

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_payment'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_payment'), array('size' => 4));
        $mform->setType('cost', PARAM_RAW);
        $mform->setDefault('cost', format_float($this->get_config('cost'), 2, true));

        $paypalcurrencies = $this->get_currencies();
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_payment'), $paypalcurrencies);
        $mform->setDefault('currency', $this->get_config('currency'));

        $roles = $this->get_roleid_options($instance, $context);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_payment'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        $options = array('optional' => true, 'defaultunit' => 86400);
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_payment'), $options);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_payment');

        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_payment'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_payment');

        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_payment'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_payment');

        $mform->addElement('select', 'customint1',
                           get_string('sendcoursewelcomemessage', 'enrol_payment'),
                           enrol_send_welcome_email_options());
        $mform->setDefault('customint1', $this->get_config('sendcoursewelcomemessage'));
        $mform->addHelpButton('customint1', 'sendcoursewelcomemessage', 'enrol_payment');

        $options = array('cols' => '60', 'rows' => '8');
        $mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_payment'), $options);
        $mform->setDefault('customtext1', $this->get_config('defaultcoursewelcomemessage'));
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_payment');

        $groups = groups_get_all_groups($instance->courseid);
        $options = array();
        $options[0] = get_string('enrolnogroup', 'enrol_payment');
        foreach($groups as $group) {
            $options[$group->id] = $group->name;
        }
        $mform->addElement('select', 'customint2', get_string('enrolgroup', 'enrol_payment'), $options);

        $mform->addElement('advcheckbox', 'customint4', get_string('requireshipping', 'enrol_payment'));
        $mform->setType('customint4', PARAM_INT);

        if($this->get_config('allowmultipleenrol')) {
            $mform->addElement('advcheckbox', 'customint5', get_string('allowmultipleenrol', 'enrol_payment'));
            $mform->setType('customint5', PARAM_INT);
        }

        // prerequisite, corequisite, conflicting
        $restrictions = $this->get_restrictions($instance);

        $mform->addElement('header', 'header_prerequisite', get_string('prerequisite', 'enrol_payment'));
        $mform->setExpanded('header_prerequisite');
        $radio=array();
        $radio[] = $mform->createElement('radio', 'prerequisite_mode', '', get_string('none'), 0);
        $radio[] = $mform->createElement('radio', 'prerequisite_mode', '', get_string('all'), 1);
        $radio[] = $mform->createElement('radio', 'prerequisite_mode', '', get_string('any'), 2);
        $mform->addGroup($radio, 'prereqradio', '', array(' '), false);
        $mform->addElement('course', 'prerequisite_id', '',
          ['multiple' => true, 'exclude' => $COURSE->id, 'onlywithcompletion' => true]);
        $mform->addHelpButton('header_prerequisite', 'prerequisite', 'enrol_payment');
        $mform->hideIf('prerequisite_id[]', 'prerequisite_mode', 'eq', 0);

        $mform->addElement('header', 'header_corequisite', get_string('corequisite', 'enrol_payment'));
        $mform->setExpanded('header_corequisite');
        $radio=array();
        $radio[] = $mform->createElement('radio', 'corequisite_mode', '', get_string('none'), 0);
        $radio[] = $mform->createElement('radio', 'corequisite_mode', '', get_string('all'), 1);
        $radio[] = $mform->createElement('radio', 'corequisite_mode', '', get_string('any'), 2);
        $mform->addGroup($radio, 'coreqradio', '', array(' '), false);
        $mform->addElement('course', 'corequisite_id', '', ['multiple' => true, 'exclude' => $COURSE->id]);
        $mform->addHelpButton('header_corequisite', 'corequisite', 'enrol_payment');
        $mform->hideIf('corequisite_id[]', 'corequisite_mode', 'eq', 0);

        $mform->addElement('header', 'header_conflicting', get_string('conflicting', 'enrol_payment'));
        $mform->setExpanded('header_conflicting');
        $radio=array();
        $radio[] = $mform->createElement('radio', 'conflicting_mode', '', get_string('none'), 0);
        $radio[] = $mform->createElement('radio', 'conflicting_mode', '', get_string('any'), 2);
        $mform->addGroup($radio, 'conflictingradio', '', array(' '), false);
        $mform->addElement('course', 'conflicting_id', '', ['multiple' => true, 'exclude' => $COURSE->id]);
        $mform->addHelpButton('header_conflicting', 'conflicting', 'enrol_payment');
        $mform->hideIf('conflicting_id[]', 'conflicting_mode', 'eq', 0);

        $cohorts = $DB->get_records_sql_menu('select id, name from {cohort} order by name asc');
        $mform->addElement('header', 'header_cohortmember', get_string('cohortmember', 'enrol_payment'));
        $mform->setExpanded('header_cohortmember');
        $radio=array();
        $radio[] = $mform->createElement('radio', 'cohortmember_mode', '', get_string('none'), 0);
        $radio[] = $mform->createElement('radio', 'cohortmember_mode', '', get_string('all'), 1);
        $radio[] = $mform->createElement('radio', 'cohortmember_mode', '', get_string('any'), 2);
        $mform->addGroup($radio, 'cohortmemberradio', '', array(' '), false);
        $mform->addElement('autocomplete', 'cohortmember_id', '', $cohorts, ['multiple' => true]);
        $mform->addHelpButton('header_cohortmember', 'cohortmember', 'enrol_payment');
        $mform->hideIf('cohortmember_id[]', 'cohortmember_mode', 'eq', 0);

        $mform->addElement('header', 'header_cohortnonmember', get_string('cohortnonmember', 'enrol_payment'));
        $mform->setExpanded('header_cohortnonmember');
        $radio=array();
        $radio[] = $mform->createElement('radio', 'cohortnonmember_mode', '', get_string('none'), 0);
        $radio[] = $mform->createElement('radio', 'cohortnonmember_mode', '', get_string('all'), 1);
        $radio[] = $mform->createElement('radio', 'cohortnonmember_mode', '', get_string('any'), 2);
        $mform->addGroup($radio, 'cohortnonmemberradio', '', array(' '), false);
        $mform->addElement('autocomplete', 'cohortnonmember_id', '', $cohorts, ['multiple' => true]);
        $mform->addHelpButton('header_cohortnonmember', 'cohortnonmember', 'enrol_payment');
        $mform->hideIf('cohortnonmember_id[]', 'cohortnonmember_mode', 'eq', 0);

        if (!is_null($restrictions)) {
          $mform->setDefault('prerequisite_mode', $restrictions->prerequisite['mode']);
          $mform->setDefault('prerequisite_id', $restrictions->prerequisite['id']);
          $mform->setDefault('corequisite_mode', $restrictions->corequisite['mode']);
          $mform->setDefault('corequisite_id', $restrictions->corequisite['id']);
          $mform->setDefault('conflicting_mode', $restrictions->conflicting['mode']);
          $mform->setDefault('conflicting_id', $restrictions->conflicting['id']);
          $mform->setDefault('cohortmember_mode', $restrictions->cohortmember['mode']);
          $mform->setDefault('cohortmember_id', $restrictions->cohortmember['id']);
          $mform->setDefault('cohortnonmember_mode', $restrictions->cohortnonmember['mode']);
          $mform->setDefault('cohortnonmember_id', $restrictions->cohortnonmember['id']);
        }

        if (enrol_accessing_via_instance($instance)) {
            $warningtext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warningtext);
        }
    }

    public function get_restrictions($instance) {

      return isset($instance->customtext2) ? unserialize($instance->customtext2) : null;
    }

    public function get_complete($ids, $mode) {
      global $USER;

      $allcomplete = true;
      foreach ($ids as $courseid) {
        $ccompletion = new completion_completion([
          'userid' => $USER->id,
          'course' => $courseid]);
        $allcomplete = $allcomplete && $ccompletion->is_complete();
      }
      return $allcomplete;
    }

    public function get_enrolled($ids, $mode) {
      $courses = enrol_get_my_courses(null, null, 0, $ids);
      if ($mode === 'any') {
        return count($courses) > 0;
      } else if ($mode === 'all') {
        return count($courses === count($ids));
      }
    }

    public function check_restrictions($instance) {
      global $USER, $DB;

      $allowed = true;
      $condition = $this->get_restrictions($instance);
      if (!$condition) {
        return true;
      }
      // check prerequisites
      switch ($condition->prerequisite['mode']) {
        case ENROL_PAYMENT_RESTRICTION_NONE: {
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ALL: {
          $allowed = $allowed && $this->get_complete($condition->prerequisite['id'], 'all');
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ANY: {
          $allowed = $allowed && $this->get_complete($condition->prerequisite['id'], 'any');
          break;
        }
        default: {
          throw new Exception('No restriction mode specified');
        }
      }
      // check corequisites
      switch ($condition->corequisite['mode']) {
        case ENROL_PAYMENT_RESTRICTION_NONE: {
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ALL: {
          $allowed = $allowed && $this->get_enrolled($condition->corequisite['id'], 'all');
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ANY: {
          $allowed = $allowed && $this->get_enrolled($condition->corequisite['id'], 'any');
          break;
        }
        default: {
          throw new Exception('No restriction mode specified');
        }
      }
      // check conflicting
      switch ($condition->conflicting['mode']) {
        case ENROL_PAYMENT_RESTRICTION_NONE: {
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ANY: {
          $allowed = $allowed && $this->get_complete($condition->conflicting['id'], 'any');
          $allowed = $allowed && $this->get_enrolled($condition->conflicting['id'], 'any');
          break;
        }
        default: {
          throw new Exception('No restriction mode specified');
        }
      }
      // check cohortmember
      switch ($condition->cohortmember['mode']) {
        case ENROL_PAYMENT_RESTRICTION_NONE: {
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ALL: {
          $allcohort = true;
          foreach ($condition->cohortmember['id'] as $cohortid) {
            $allcohort = $allcohort && cohort_is_member($cohortid, $USER->id);
          }
          $allowed = $allowed && $allcohort;
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ANY: {
          $allcohort = true;
          foreach ($condition->cohortmember['id'] as $cohortid) {
            $allcohort = $allcohort || cohort_is_member($cohortid, $USER->id);
          }
          $allowed = $allowed && $allcohort;
          break;
        }
        default: {
          throw new Exception('No restriction mode specified');
        }
      }
      // check cohortnonmember
      switch ($condition->cohortnonmember['mode']) {
        case ENROL_PAYMENT_RESTRICTION_NONE: {
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ALL: {
          $allcohort = true;
          foreach ($condition->cohortmember['id'] as $cohortid) {
            $allcohort = $allcohort && !cohort_is_member($cohortid, $USER->id);
          }
          $allowed = $allowed && $allcohort;
          break;
        }
        case ENROL_PAYMENT_RESTRICTION_ANY: {
          $allcohort = false;
          foreach ($condition->cohortmember['id'] as $cohortid) {
            $allcohort = $allcohort || !cohort_is_member($cohortid, $USER->id);
          }
          $allowed = $allowed && $allcohort;
          break;
        }
        default: {
          throw new Exception('No restriction mode specified');
        }
      }
      return $allowed;
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = array();

        if(!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_payment');
        }

        if (count(array_intersect($data['prerequisite_id'], $data['corequisite_id'])) > 0) {
          $errors['prerequisite_id'] = get_string('prerequisite_corequisite_overlap', 'enrol_payment');
          $errors['corequisite_id'] = get_string('prerequisite_corequisite_overlap', 'enrol_payment');
        }
        if (count(array_intersect($data['prerequisite_id'], $data['conflicting_id'])) > 0) {
          $errors['prerequisite_id'] = get_string('prerequisite_conflicting_overlap', 'enrol_payment');
          $errors['conflicting_id'] = get_string('prerequisite_conflicting_overlap', 'enrol_payment');
        }
        if (count(array_intersect($data['corequisite_id'], $data['conflicting_id'])) > 0) {
          $errors['corequisite_id'] = get_string('corequisite_conflicting_overlap', 'enrol_payment');
          $errors['conflicting_id'] = get_string('corequisite_conflicting_overlap', 'enrol_payment');
        }
        if (count(array_intersect($data['cohortmember_id'], $data['cohortnonmember_id'])) > 0) {
          $errors['cohortmember_id'] = get_string('cohort_overlap', 'enrol_payment');
          $errors['cohortnonmember_id'] = get_string('cohort_overlap', 'enrol_payment');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if(!is_numeric($cost)) {
            $errors['cost'] = get_string('costerror', 'enrol_payment');
        }

        $validstatus = array_keys($this->get_status_options());
        $validcurrency = array_keys($this->get_currencies());
        $validroles = array_keys($this->get_roleid_options($instance, $context));
        $tovalidate = array(
            'name' => PARAM_TEXT,
            'status' => $validstatus,
            'currency' => $validcurrency,
            'roleid' => $validroles,
            'enrolperiod' => PARAM_INT,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT
        );

        $typeerrors = $this->validate_param_types($data, $tovalidate);

        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace) {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/payment:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/payment:config', $context);
    }

}

/**
 * Serve the files from the MYPLUGIN file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function enrol_payment_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'stripelogo') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.

    $filepath = '/';

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'enrol_payment', $filearea, 0, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_file($file, 86400, 0, $forcedownload, $options);
}
