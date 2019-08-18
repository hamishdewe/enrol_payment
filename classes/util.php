<?php
/**
 * Payment enrolment plugin utility class.
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder - based on enrol_paypal code by Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_payment;

defined('MOODLE_INTERNAL') || die();

final class util {

    public static function myURLEncode($string) {
        $entities = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        $replacements = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        return str_replace($entities, $replacements, $string);
    }

    /**
     * Alerts site admin of potential problems.
     *
     * @param string   $subject email subject
     * @param stdClass $data    PayPal IPN data
     */
    public static function message_paypal_error_to_admin($subject, $data) {
        $admin = get_admin();
        $site = get_site();

        $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

        foreach ($data as $key => $value) {
            $message .= "$key => $value\n";
        }

        $eventdata = new \stdClass();
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_payment';
        $eventdata->name              = 'payment_enrolment';
        $eventdata->userfrom          = $admin;
        $eventdata->userto            = $admin;
        $eventdata->subject           = "PAYPAL ERROR: ".$subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }

    /**
     * Silent exception handler.
     *
     * @return callable exception handler
     */
    public static function get_exception_handler() {
        return function($ex) {
            $info = get_exception_info($ex);

            $logerrmsg = "enrol_payment IPN exception handler: ".$info->message;
            if (debugging('', DEBUG_NORMAL)) {
                $logerrmsg .= ' Debug: '.$info->debuginfo."\n".format_backtrace($info->backtrace, true);
            }
            error_log($logerrmsg);

            if (http_response_code() == 200) {
                http_response_code(500);
            }

            exit(0);
        };
    }
}

require_once($CFG->libdir.'/editorlib.php');
require_once($CFG->libdir.'/adminlib.php');
/**
 * General text area with html editor. No default info displayed.
 */
class admin_setting_confightmleditor_nodefaultinfo extends \admin_setting_configtextarea {
    private $cols;
    private $rows;

    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param mixed $defaultsetting string or array
     * @param mixed $paramtype
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $cols='60', $rows='8') {
        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype, $cols, $rows);
        $this->cols = $cols;
        $this->rows = $rows;
        $this->set_force_ltr(false);
        editors_head_setup();
    }

    /**
     * Returns an XHTML string for the editor
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the editor
     */
    public function output_html($data, $query='') {
        global $OUTPUT;
        $editor = editors_get_preferred_editor(FORMAT_HTML);
        $editor->set_text($data);
        $editor->use_editor($this->get_id(), array('noclean'=>true));

        $default = $this->get_defaultsetting();
        $defaultinfo = '';
        if (!is_null($default) and $default !== '') {
            $defaultinfo = "\n".$default;
        }

        $context = (object) [
            'cols' => $this->cols,
            'rows' => $this->rows,
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => $data,
            'forceltr' => $this->get_force_ltr(),
        ];
        $element = $OUTPUT->render_from_template('core_admin/setting_configtextarea', $context);

        return format_admin_setting_nodefaultinfo($this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query);
    }
}

function format_admin_setting_nodefaultinfo($setting, $title='', $form='', $description='', $label=true, $warning='', $defaultinfo=NULL, $query='') {
    global $CFG, $OUTPUT;

    $context = (object) [
        'name' => empty($setting->plugin) ? $setting->name : "$setting->plugin | $setting->name",
        'fullname' => $setting->get_full_name(),
    ];

    // Sometimes the id is not id_s_name, but id_s_name_m or something, and this does not validate.
    if ($label === true) {
        $context->labelfor = $setting->get_id();
    } else if ($label === false) {
        $context->labelfor = '';
    } else {
        $context->labelfor = $label;
    }

    $form .= $setting->output_setting_flags();

    $context->warning = $warning;
    $context->override = '';
    if (empty($setting->plugin)) {
        if (array_key_exists($setting->name, $CFG->config_php_settings)) {
            $context->override = get_string('configoverride', 'admin');
        }
    } else {
        if (array_key_exists($setting->plugin, $CFG->forced_plugin_settings) and array_key_exists($setting->name, $CFG->forced_plugin_settings[$setting->plugin])) {
            $context->override = get_string('configoverride', 'admin');
        }
    }

    $defaults = array();
    if (!is_null($defaultinfo)) {
        if ($defaultinfo === '') {
            $defaultinfo = get_string('emptysettingvalue', 'admin');
        }
        $defaults[] = $defaultinfo;
    }

    $context->default = null;
    $setting->get_setting_flag_defaults($defaults);

    $context->error = '';
    $adminroot = admin_get_root();
    if (array_key_exists($context->fullname, $adminroot->errors)) {
        $context->error = $adminroot->errors[$context->fullname]->error;
    }

    $context->id = 'admin-' . $setting->name;
    $context->title = highlightfast($query, $title);
    $context->name = highlightfast($query, $context->name);
    $context->description = highlight($query, markdown_to_html($description));
    $context->element = $form;
    $context->forceltr = $setting->get_force_ltr();

    return $OUTPUT->render_from_template('core_admin/setting', $context);
}
