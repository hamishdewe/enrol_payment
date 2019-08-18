<?php
/**
 * Listens for Instant Payment Notification from Stripe
 *
 * This script waits for Payment notification from Stripe,
 * then double checks that data by sending it back to Stripe.
 * If Stripe verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_payment
 * @copyright  2018 Dualcube, Arkaprava Midya, Parthajeet Chakraborty, Seth Yoder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require_once("lib.php");
require_once("paymentlib.php");
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/group/lib.php');

require_login();
// Stripe does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler('enrol_payment_charge_exception_handler');

// Keep out casual intruders.
if (empty(required_param('stripeToken', PARAM_RAW))) {
    print_error(get_string('stripe_sorry', 'enrol_payment'));
}


$data = new stdClass();

$req = "";

foreach ($_POST as $key => $value) {
    if ($key !== clean_param($key, PARAM_ALPHANUMEXT)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, $key);
    }
    if (is_array($value)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Unexpected array param: '.$key);
    }
    $req .= "&$key=".urlencode($value);
    $data->$key = fix_utf8($value);
}
//$data->prov is now set to the user's msn field.

$data->payment_gross    = $data->amount;
$data->payment_currency = $data->currency_code;

$shippingRequired = required_param('shippingRequired', PARAM_INT);
$data->no_shipping = !$shippingRequired;

if (empty($data->custom)) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Missing request param: custom');
}

$payment = paymentlib\enrol_payment_get_payment_from_token($data->custom);

unset($data->custom);

$data->userid           = (int)$payment->userid;
$data->courseid         = (int)$payment->courseid;
$data->instanceid       = (int)$payment->instanceid;
$multiple         = (bool)$payment->multiple;

if ($multiple) {
    $multiple_userids = explode(',',$payment->multiple_userids);
    if(empty($multiple_userids)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, "Multiple purchase specified, but no userids found.");
    }
}

// Get the user and course records.

if (! $user = $DB->get_record("user", array("id" => $data->userid))) {
    message_payment_error_to_admin("Not a valid user id", $data);
    redirect($CFG->wwwroot);
}

// For later redirect
$purchaser = $user;

if (! $course = $DB->get_record("course", array("id" => $data->courseid))) {
    message_payment_error_to_admin("Not a valid course id", $data);
    redirect($CFG->wwwroot);
}

if (! $context = context_course::instance($course->id, IGNORE_MISSING)) {
    message_payment_error_to_admin("Not a valid context id", $data);
    redirect($CFG->wwwroot);
}

$PAGE->set_context($context);

if (! $plugin_instance = $DB->get_record("enrol", array("id" => $data->instanceid, "status" => 0))) {
    message_payment_error_to_admin("Not a valid instance id", $data);
    redirect($CFG->wwwroot);
}

 // If currency is incorrectly set, then someone may be trying to cheat the system.

if ($data->courseid != $plugin_instance->courseid) {
    message_payment_error_to_admin("Course Id does not match to the course settings, received: ".$data->courseid, $data);
    redirect($CFG->wwwroot);
}

$plugin = enrol_get_plugin('payment');

// Check that amount paid is the correct amount
if ((float) $plugin_instance->cost <= 0.0 ) {
    $original_cost = (float) $plugin->get_config('cost');
} else {
    $original_cost = (float) $plugin_instance->cost;
}

// Use the same rounding of floats as on the enrol form.
$original_cost = format_float($original_cost, 2, false);

//What should the user have paid? Verify using info stored in the
//database.
$cost = paymentlib\enrol_payment_calculate_cost($plugin_instance, $payment, true)["subtotal_taxed"];

if ($data->amount + 0.01 < $cost) {
    //This shouldn't happen unless the user spoofs their requests, but
    //if it does, the discount is just invalid.
    \enrol_payment\util::message_paypal_error_to_admin("Amount paid is not enough ($data->amount < $cost))", $data);
    die;
}

try {

    require_once('Stripe/lib/Stripe.php');

    Stripe::setApiKey($plugin->get_config('stripesecretkey'));
    $charge1 = Stripe_Customer::create(array(
        "email" => required_param('stripeEmail', PARAM_EMAIL),
        "description" => get_string('charge_description1', 'enrol_payment')
    ));
    $charge = Stripe_Charge::create(array(
      "amount" => $cost * 100,
      "currency" => $plugin_instance->currency,
      "card" => required_param('stripeToken', PARAM_RAW),
      "description" => get_string('charge_enrolment', 'enrol_payment') . required_param('item_name', PARAM_TEXT),
      "receipt_email" => required_param('stripeEmail', PARAM_EMAIL)
    ));

    // Send the file, this line will be reached if no error was thrown above.
    $data->txn_id = $charge->balance_transaction;
    $data->memo = $charge->id;
    $data->payment_status = $charge->status;
    $data->pending_reason = $charge->failure_message;
    $data->reason_code = $charge->failure_code;


    // ALL CLEAR !

    $DB->insert_record("enrol_payment_transaction", $data);

    if ($plugin_instance->enrolperiod) {
        $timestart = time();
        $timeend   = $timestart + $plugin_instance->enrolperiod;
    } else {
        $timestart = 0;
        $timeend   = 0;
    }

    if (!$multiple) {
        //Make a singleton array so that we can do this whole thing in a foreach loop.
        $multiple_userids = [$user->id];
    }

    $mailstudents = $plugin->get_config('mailstudents');
    $mailteachers = $plugin->get_config('mailteachers');
    $mailadmins   = $plugin->get_config('mailadmins');
    $shortname = $course->shortname;

    // Pass $view=true to filter hidden caps if the user cannot see them.
    if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
        $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
    } else {
        $teacher = false;
    }

    foreach($multiple_userids as $uid) {
        if (!$user = $DB->get_record('user', array('id'=>$uid))) {   // Check that user exists
            \enrol_payment\util::message_paypal_error_to_admin("User $data->userid doesn't exist", $data);
            die;
        }
        // Enrol user.
        $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

        if ($plugin_instance->customint1 != ENROL_DO_NOT_SEND_EMAIL) {
            $plugin->email_welcome_message($plugin_instance, $user);
        }

        // If group selection is not null
        if ($plugin_instance->customint2) {
            groups_add_member($plugin_instance->customint2, $user);
        }

        if (!empty($mailstudents)) {
                $a = new stdClass();
                $a->coursename = $course->fullname;
                $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

                $eventdata = new \core\message\message();
                $eventdata->modulename        = 'moodle';
                $eventdata->component         = 'enrol_payment';
                $eventdata->name              = 'payment_enrolment';
                $eventdata->userfrom          = core_user::get_support_user();
                $eventdata->userto            = $user;
                $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = '';
                $eventdata->smallmessage      = '';
                message_send($eventdata);
        }

        if (!empty($mailteachers) && !empty($teacher)) {
                $a->course = $course->fullname;
                $a->user = fullname($user);

                $eventdata = new \core\message\message();
                $eventdata->modulename        = 'moodle';
                $eventdata->component         = 'enrol_payment';
                $eventdata->name              = 'payment_enrolment';
                $eventdata->userfrom          = $user;
                $eventdata->userto            = $teacher;
                $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = '';
                $eventdata->smallmessage      = '';
                message_send($eventdata);
        }

        if (!empty($mailadmins)) {
            $a->course = $course->fullname;
            $a->user = fullname($user);
            $admins = get_admins();
            foreach ($admins as $admin) {
                $eventdata = new \core\message\message();
                $eventdata->modulename        = 'moodle';
                $eventdata->component         = 'enrol_payment';
                $eventdata->name              = 'payment_enrolment';
                $eventdata->userfrom          = $user;
                $eventdata->userto            = $admin;
                $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = '';
                $eventdata->smallmessage      = '';
                message_send($eventdata);
            }
        }
    }

    $fullname = $course->fullname;
    if($multiple) {
        if(in_array(strval($purchaser->id), $multiple_userids)) {
            $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
            redirect($destination, get_string('paymentthanks', '', $fullname));
        } else {
            $destination = "$CFG->wwwroot/enrol/payment/return.php?id=$course->id&token=$payment->prepaytoken";
            redirect($destination);
        }
    } else {
        $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
        redirect($destination, get_string('paymentthanks', '', $fullname));
    }

    //if (is_enrolled($context, null, '', true)) {

    //} else {   // Somehow they aren't enrolled yet!
    //    $PAGE->set_url($destination);
    //    echo $OUTPUT->header();
    //    $a = new stdClass();
    //    $a->teacher = get_string('defaultcourseteacher');
    //    $a->fullname = $fullname;
    //    notice(get_string('paymentsorry', '', $a), $destination);
    //}
} catch (\Stripe\Error\Card $e) {
    $PAGE->set_url($destination);
    echo $OUTPUT->header();
    $a = new stdClass();
    $a->teacher = get_string('defaultcourseteacher');
    $a->fullname = $fullname;
    notice(get_string('paymentsorry', '', $a), $destination);
}

// Catch the errors in any way you like.

catch (Stripe_InvalidRequestError $e) {
    // Invalid parameters were supplied to Stripe's API.
    echo 'Invalid parameters were supplied to Stripe\'s API';

} catch (Stripe_AuthenticationError $e) {
    // Authentication with Stripe's API failed
    // (maybe you changed API keys recently).
    echo 'Authentication with Stripe\'s API failed';

} catch (Stripe_ApiConnectionError $e) {
    // Network communication with Stripe failed.
    echo 'Network communication with Stripe failed';
} catch (Stripe_Error $e) {

    // Display a very generic error to the user, and maybe send
    // yourself an email.
    echo 'Stripe Error';
} catch (Exception $e) {

    // Something else happened, completely unrelated to Stripe.
    echo 'Something else happened, completely unrelated to Stripe';
    echo '<pre>';
    echo $e->getMessage();
    echo '</pre>';
}


    // --- HELPER FUNCTIONS --------------------------------------------------------------------------------------!

/**
 * Send payment error message to the admin.
 *
 * @param string $subject
 * @param stdClass $data
 */
function message_payment_error_to_admin($subject, $data) {
    $admin = get_admin();
    $site = get_site();

    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

    foreach ($data as $key => $value) {
        $message .= s($key) ." => ". s($value)."\n";
    }

    $eventdata = new stdClass();
    $eventdata->modulename        = 'moodle';
    $eventdata->component         = 'enrol_payment';
    $eventdata->name              = 'payment_enrolment';
    $eventdata->userfrom          = $admin;
    $eventdata->userto            = $admin;
    $eventdata->subject           = "STRIPE PAYMENT ERROR: ".$subject;
    $eventdata->fullmessage       = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';
    $eventdata->smallmessage      = '';
    message_send($eventdata);
}
