<?php

/**
 * Paypal enrolments plugin settings and presets.
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @author     Seth Yoder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once("$CFG->libdir/moodlelib.php");
require_once(dirname(__FILE__).'/../lang/en/enrol_payment.php');

global $DB;

/**
 * When switching between Single and Multiple mode, make the necessary
 * adjustments to our payment row in the database.
 */
function update_payment_data($multiple, $users, &$payment) {
    global $DB;

    $userids = array();
    if($multiple) {
        foreach($users as $u) {
            array_push($userids, $u["id"]);
        }
    }

    $payment->multiple = $multiple;
    $payment->multiple_userids = $multiple ? implode(",",$userids) : null;
    $payment->units = $multiple ? sizeof($userids) : 1;
    $DB->update_record("enrol_payment_session", $payment);
}

/**
 * Return true if the corresponding transaction is pending
 */
function payment_pending($paymentid) {
    global $DB;
    $payment = $DB->get_record("enrol_payment_session", array('id' => $paymentid));
    $transaction = $DB->get_record("enrol_payment_transaction", array('txn_id' => $payment->paypal_txn_id));

    if($transaction) {
        return ($transaction->payment_status == "Pending");
    } else {
        return false;
    }
}

function get_payment_status($paymentid) {
    global $DB;
    $payment = $DB->get_record("enrol_payment_session", array('id' => $paymentid));
    $transaction = $DB->get_record("enrol_payment_transaction", array('txn_id' => $payment->paypal_txn_id));

    if($transaction) {
        return $transaction->payment_status;
    } else {
        return false;
    }
}

function get_moodle_users_by_emails($emails) {
    global $DB;
    $notfound = array();
    $users = array();

    foreach($emails as $email) {
        $user = $DB->get_record('user', array('email' => $email), "id, email, firstname, lastname");
        if($user) {
            $userdata = [ "id" => $user->id
                        , "email" => $email
                        , "name" => ($user->firstname . " " . $user->lastname)
                        ];
            array_push($users, $userdata);
        } else {
            array_push($notfound, $email);
        }

    }

    if (!empty($notfound)) {
        throw new Exception(get_string("usersnotfoundwithemail", "enrol_payment") . implode("<li>", $notfound) . "</ul>");
    }

    return $users;
}

function pretty_print_user($u) {
    return $u["name"] . " &lt;" . $u["email"] . "&gt;";
}
