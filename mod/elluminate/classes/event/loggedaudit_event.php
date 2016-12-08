<?php

/**
 * The loggedaudit event.
 *
 * @package    elluminate
 * @copyright  2014 Blackboard Inc. and its subsidiary companies.  All rights reserved.
 */
namespace mod_elluminate\event;
defined('MOODLE_INTERNAL') || die();
/**
 * Warning: This code is not supported by MOODLE before version 2.7.0.
 *
 * The loggedaudit event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 * }
 *
 * @since     Moodle 2.7
 **/
class loggedaudit_event extends \core\event\base {

    /**
     * Set basic properties for the event.
     */
    protected function init() {

        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventloggedaudit', 'elluminate');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id {$this->userid} added a new audit log.";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url($this->data['other']['url']);
    }

    /**
     * Replaces legacy add_to_log() statement data.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    public function get_legacy_logdata() {
        return array(
            $this->data['other']['course'], 
            $this->data['other']['modulename'], 
            $this->data['other']['event'], 
            $this->data['other']['url'], 
			$this->data['other']['info'], 
			$this->data['other']['cmid'], 
            $this->data['userid']);
    }

}
