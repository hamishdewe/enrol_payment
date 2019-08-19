<?php

/**
 * Notify the user that their PayPal payment is pending
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @author     Seth Yoder <seth.a.yoder@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require("../../config.php");

$id=required_param('id', PARAM_INT);
$reason=required_param('reason', PARAM_TEXT);

$context = context_course::instance($id, MUST_EXIST);
$PAGE->set_context($context);

$PAGE->set_url("$CFG->wwwroot/enrol/payment/paypalPending.php");

$a = new stdClass();
$a->supportemaillink = $CFG->supportemail ? "<a href=\"mailto:$CFG->supportemail\">contact</a>" : "contact";
$a->reason = $reason;

echo $OUTPUT->header();
echo '<div style="text-align: center;" class="paypal-pending">';
echo $OUTPUT->box(get_string('errorpaymentpending', 'enrol_payment', $a), 'generalbox', 'notice');
echo '</div>';
echo $OUTPUT->footer();
