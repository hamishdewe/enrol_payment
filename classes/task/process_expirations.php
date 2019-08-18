<?php
/**
 * Process expirations task.
 *
 * @package   enrol_payment
 * @author    Farhan Karmali <farhan6318@gmail.com>
 * @copyright Farhan Karmali (originally for enrol_paypal)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_payment\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Process expirations task.
 *
 * @package   enrol_payment
 * @author    Farhan Karmali <farhan6318@gmail.com>
 * @copyright Farhan Karmali
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_expirations extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('processexpirationstask', 'enrol_payment');
    }

    /**
     * Run task for processing expirations.
     */
    public function execute() {
        $enrol = enrol_get_plugin('payment');
        $trace = new \text_progress_trace();
        $enrol->process_expirations($trace);
    }

}
