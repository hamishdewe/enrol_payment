<?php
/**
 * Payment utility script
 *
 * @package    enrol_payment
 * @author     Seth Yoder
 * @copyright  2018 Seth Yoder (based on enrol_paypal code by 2004 Martin Dougiamas (http://dougiamas.com))
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require("../../config.php");
require("paymentlib.php");
require_once("$CFG->dirroot/enrol/paypal/lib.php");

$id = required_param('id', PARAM_INT);
$token = required_param('token', PARAM_RAW);
$userid = $USER->id;
$payment = paymentlib\enrol_payment_get_payment_from_token($token);
$purchasing_for_self = true;

if (!$course = $DB->get_record("course", array("id"=>$id))) {
    redirect($CFG->wwwroot);
}

$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);

require_login();

if (!empty($SESSION->wantsurl)) {
    $destination = $SESSION->wantsurl;
    unset($SESSION->wantsurl);
} else {
    $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
}

if($payment->multiple) {
    $userids = explode(',',$payment->multiple_userids);

    if(!in_array(strval($userid), $userids)) {
        $purchasing_for_self = false;
    }
}

$fullname = format_string($course->fullname, true, array('context' => $context));

if($purchasing_for_self) {
    if (is_enrolled($context, NULL, '', true)) { // TODO: use real paypal check
        redirect($destination, get_string('paymentthanks', '', $fullname));
    } else {   /// IPN is slow, and doesn't always complete immediately...
        $ajaxurl = "$CFG->wwwroot/enrol/payment/ajax/checkEnrol.php";

        $PAGE->requires->css('/enrol/payment/style/styles.css');
        $PAGE->requires->js_call_amd('enrol_payment/return', 'init', array($destination, $ajaxurl, $course->id, $payment->id));
        $PAGE->set_url($destination);

        echo $OUTPUT->header();
        $a = new stdClass();
        $a->teacher = get_string('defaultcourseteacher');
        $a->fullname = $fullname;
        echo '<div style="text-align: center;" class="paypal-wait">';
        echo $OUTPUT->box(get_string('paypalwait', 'enrol_payment', $course->fullname), 'generalbox', 'notice');
        echo '</div>';
        echo '<div id="spin-container"></div>';
        echo $OUTPUT->footer();
        //notice(get_string('paymentsorry', '', $a), $destination);
    }
} else {
    $PAGE->set_url($destination);
    echo $OUTPUT->header();
    echo '<div style="text-align: center;">';
    echo $OUTPUT->box(get_string('thanksforpaypal', 'enrol_payment', $course->fullname), 'generalbox', 'notice');
    echo '</div>';
    echo $OUTPUT->footer();
}
