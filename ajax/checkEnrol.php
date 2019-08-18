<?php

/**
 * AJAX handler for checking enrolment status based on payment data
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @author     Seth Yoder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once("$CFG->libdir/moodlelib.php");
require_once('util.php');

$courseid = required_param('courseid', PARAM_INT);
$paymentid = required_param('paymentid', PARAM_INT);

$context = context_course::instance($courseid, MUST_EXIST);

if (is_enrolled($context, NULL, '', true)) {
    echo json_encode([
        "status" => "success",
        "result" => true
    ]);

} else if(payment_pending($paymentid)) {
    echo json_encode([
        "status" => "success",
        "result" => false,
        "reason" => "Pending"
    ]);
} else {
    $reason = get_payment_status($paymentid);
    echo json_encode([
        "status" => "success",
        "result" => false,
        "reason" => $reason
    ]);
}
