<?php
/**
 * Task definition for enrol_payment.
 * @author    Farhan Karmali <farhan6318@gmail.com>
 * @copyright Farhan Karmali (originally for enrol_paypal)
 * @package   enrol_payment
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => '\enrol_payment\task\process_expirations',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    )
);

