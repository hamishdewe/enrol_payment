<?php
/**
 * This file keeps track of upgrades to the enrolment plugin
 *
 * @package    enrol_payment
 * @copyright  2010 Eugene Venter (originally for enrol_paypal)
 * @author     Eugene Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_payment_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2018051401) {

        // Define field instanceid to be added to enrol_payment.
        // For some reason, some Moodle instances that are upgraded from old versions do not have this field.
        $table = new xmldb_table('enrol_payment');
        $field = new xmldb_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Conditionally launch add field instanceid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Paypal savepoint reached.
        upgrade_plugin_savepoint(true, 2018051401, 'enrol', 'payment');
    }

    if ($oldversion < 2018051402) {

        // Define key courseid (foreign) to be added to enrol_payment.
        $table = new xmldb_table('enrol_payment');
        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));

        // Launch add key courseid.
        $dbman->add_key($table, $key);

        // Paypal savepoint reached.
        upgrade_plugin_savepoint(true, 2018051402, 'enrol', 'payment');
    }

    if ($oldversion < 2018051403) {

        // Define key userid (foreign) to be added to enrol_payment.
        $table = new xmldb_table('enrol_payment');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Launch add key userid.
        $dbman->add_key($table, $key);

        // Paypal savepoint reached.
        upgrade_plugin_savepoint(true, 2018051403, 'enrol', 'payment');
    }

    if ($oldversion < 2018051404) {

        // Define key instanceid (foreign) to be added to enrol_payment.
        $table = new xmldb_table('enrol_payment');
        $key = new xmldb_key('instanceid', XMLDB_KEY_FOREIGN, array('instanceid'), 'enrol', array('id'));

        // Launch add key instanceid.
        $dbman->add_key($table, $key);

        // Paypal savepoint reached.
        upgrade_plugin_savepoint(true, 2018051404, 'enrol', 'payment');
    }

    if ($oldversion < 2018051405) {

        $table = new xmldb_table('enrol_payment');

        // Define index business (not unique) to be added to enrol_payment.
        $index = new xmldb_index('business', XMLDB_INDEX_NOTUNIQUE, array('business'));

        // Conditionally launch add index business.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index receiver_email (not unique) to be added to enrol_payment.
        $index = new xmldb_index('receiver_email', XMLDB_INDEX_NOTUNIQUE, array('receiver_email'));

        // Conditionally launch add index receiver_email.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Paypal savepoint reached.
        upgrade_plugin_savepoint(true, 2018051405, 'enrol', 'payment');
    }

    if ($oldversion < 2019013001) {
        $old_transaction_table = new xmldb_table('enrol_payment');
        if($dbman->table_exists($old_transaction_table)) {
            $dbman->rename_table($old_transaction_table, 'enrol_payment_transaction');
        }

        $old_session_table = new xmldb_table('enrol_payment_ipn');
        if($dbman->table_exists($old_session_table)) {
            $dbman->rename_table($old_session_table, 'enrol_payment_session');
        }

        // Rename field discounted on table enrol_payment_session to code_given.
        $table = new xmldb_table('enrol_payment_session');
        $field = new xmldb_field('discounted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'multiple_userids');
        $newfield = new xmldb_field('code_given', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'multiple_userids');

        if(!($dbman->field_exists($table, $newfield))) {
            // Launch rename field code_given.
            $dbman->rename_field($table, $field, 'code_given');
        }

        // Payment savepoint reached.
        upgrade_plugin_savepoint(true, 2019013001, 'enrol', 'payment');
    }

    // There was an upgrade issue in the 2019013001 version, so re-doing those steps here if necessary.
    if($oldversion < 2019013100) {
        $old_transaction_table = new xmldb_table('enrol_payment');
        if($dbman->table_exists($old_transaction_table)) {
            $dbman->rename_table($old_transaction_table, 'enrol_payment_transaction');
        }

        $old_session_table = new xmldb_table('enrol_payment_ipn');
        if($dbman->table_exists($old_session_table)) {
            $dbman->rename_table($old_session_table, 'enrol_payment_session');
        }

        // Rename field discounted on table enrol_payment_session to code_given.
        $table = new xmldb_table('enrol_payment_session');
        $field = new xmldb_field('discounted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'multiple_userids');
        $newfield = new xmldb_field('code_given', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'multiple_userids');

        if(!($dbman->field_exists($table, $newfield))) {
            // Launch rename field code_given.
            $dbman->rename_field($table, $field, 'code_given');
        }

        // Payment savepoint reached.
        upgrade_plugin_savepoint(true, 2019013100, 'enrol', 'payment');
    }

    return true;
}
