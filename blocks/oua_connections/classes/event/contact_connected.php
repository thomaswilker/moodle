<?php
/**
 * Message contact connected event.
 *
 * @package    block_oua_connection
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */

namespace block_oua_connections\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Message contact connected event.
 *
 * @package    block_oua_connection
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class contact_connected extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'message_contacts';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcontactconnected', 'block_oua_connections');
    }

    /**
     * Returns description of what happened (not stored).
     * Only used for error messages not localised
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has connected to the user with id '$this->relateduserid'.";
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }
}
