<?php

/**
 * AJAX handler to enrol a single user
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @author     Seth Yoder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once("$CFG->libdir/moodlelib.php");
require_once(dirname(__FILE__).'/../lang/en/enrol_payment.php');
require_once(dirname(__FILE__).'/util.php');
require_once(dirname(__FILE__).'/../paymentlib.php');

global $DB;

$prepayToken = required_param('prepaytoken', PARAM_ALPHANUM);
$instanceid = required_param('instanceid', PARAM_RAW);

$ret = array();

try {
    $instance = $DB->get_record('enrol', array("id" => $instanceid), '*', MUST_EXIST);

    $payment = paymentlib\enrol_payment_get_payment_from_token($prepayToken);
    update_payment_data(false, null, $payment);
    $ret = paymentlib\enrol_payment_calculate_cost($instance, $payment, true);
    $ret["success"] = true;
} catch (Exception $e) {
    $ret["success"] = false;
    $ret["failmessage"] = "Payment UUID ".$prepayToken." not found in database.";
}

echo json_encode($ret);
