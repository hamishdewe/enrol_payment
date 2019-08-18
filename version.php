<?php
/**
 * Payment enrolment plugin version specification.
 *
 * @package    enrol_payment
 * @copyright  2018 Seth Yoder
 * @author     Seth Yoder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2019013100;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2018120300;        // Requires this Moodle version
$plugin->component = 'enrol_payment';    // Full name of the plugin (used for diagnostics)
$plugin->maturity  = MATURITY_RC;
$plugin->release   = '1.0.4';
