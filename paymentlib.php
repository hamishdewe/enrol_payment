<?php

namespace paymentlib;

/**
 * Library for handling payment tokens for the enrol_payment plugin.
 *
 * @package enrol_payment
 * @copyright 2018 Seth Yoder
 * @author Seth Yoder <seth.a.yoder@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../config.php');
require_once("$CFG->libdir/moodlelib.php");
require_once(dirname(__FILE__).'/lang/en/enrol_payment.php');

global $DB;

function enrol_payment_get_payment_from_token($prepayToken) {
    global $DB;
    return $DB->get_record_sql('SELECT * FROM {enrol_payment_session}
                                  WHERE ' .$DB->sql_compare_text('prepaytoken') . ' = ? ',
                              array('prepaytoken' => $prepayToken));
}

function enrol_payment_normalize_percent_discount($instance) {
    $amount = $instance->customdec1;
    if($instance->customint3 == 1 && $amount > 1.0) {
        return $amount * 0.01;
    } else {
        return $amount;
    }
}

/**
 * @param $instance enrol_payment instance
 * @param $payment payment object from enrol_payment_session
 * @return object with "subtotal" and "subtotal_localised" fields.
 */
function enrol_payment_calculate_cost($instance, $payment, $addtax=false) {
    $discount_threshold = $instance->customint8;
    $discount_code_required = $instance->customint7;
    $discount_amount = 0.0;
    //$ret["discount_amount"] = $discount_amount;
    $cost = $payment->original_cost;
    $subtotal = $cost;

    if($discount_amount < 0.00) {
        throw new Exception(get_string("negativediscount", "enrol_payment"));
    }

    if($payment->units < 1) {
        throw new Exception(get_string("notenoughunits", "enrol_payment"));
    }

    //If conditions have been met for a discount, apply it.
    /**
     * This is not the most concise way to write this logic, but it is the most understandable in my opinion.
     *
     * Assuming the discount theshold is met:
     * * If a discount code isn't required, apply the discount.
     * * If a discount code is required and the user has provided it, apply the discount.
     */
    $apply_discount = 0;
    if($payment->units >= $discount_threshold) {
        if(!$discount_code_required || ($discount_code_required && $payment->code_given)) {
            $apply_discount = $instance->customint3;
            $discount_amount = $instance->customdec1;
        }
    }

    $oc_discounted = $cost;
    $normalized_discount = enrol_payment_normalize_percent_discount($instance);

    switch ($apply_discount) {
        case 0:
            $subtotal = $cost * $payment->units;
            break;
        case 1:
            if($discount_amount > 100) {
                throw new Exception(get_string("percentdiscountover100error", "enrol_payment"));
            }

            //Percentages over 1 converted to a float between 0 and 1.
            //Per-unit cost is the difference between the full cost and the percent discount.
            $per_unit_cost = $cost - ($cost * $normalized_discount);
            $subtotal = $per_unit_cost * $payment->units;

            $oc_discounted = $per_unit_cost;

            break;
        case 2:
            $oc_discounted = $cost - $discount_amount;
            $subtotal = ($cost - $discount_amount) * $payment->units;

            break;
        default:
            throw new Exception(get_string("discounttypeerror", "enrol_payment"));
            break;
    }

    if($payment->tax_percent && $addtax) {
        $tax_amount = $subtotal * $payment->tax_percent;
        $subtotal_taxed = $subtotal + $tax_amount;
    } else {
        $tax_amount = 0;
        $subtotal_taxed = $subtotal;
    }

    $ret['subtotal'] = format_float($subtotal, 2, false);
    $ret['subtotal_localised'] = format_float($subtotal, 2, true);
    $ret['subtotal_taxed'] = format_float($subtotal_taxed, 2, true);
    $ret['tax_amount'] = format_float($tax_amount, 2, true);
    $ret['oc_discounted'] = format_float($oc_discounted, 2, true);
    $ret['percent_discount'] = floor($normalized_discount * 100); 

    return $ret;
}
