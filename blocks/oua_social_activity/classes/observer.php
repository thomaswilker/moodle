<?php
/**
 * Event observer.
 *
 * @package    block_oua_social_activity
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_social_activity;

defined('MOODLE_INTERNAL') || die();
use context_user;
/**
 * Event observer.
 * On a contact add / remove / block event, ad/remove privileges.
 *
 * @package    block_oua_social_activity
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class observer {
    /**
     * Save the event to the database to read when looking up events.
     * @param \core\event\base $event
     */
    public static function save_connection_event(\core\event\base $event) {
        global $DB;
        $data = $event->get_data();
        $DB->insert_record('oua_social_activity_events', $data);
        return;
    }
    public static function remove_connection_events(\core\event\base $event) {
        global $DB;
        $DB->delete_records_select('oua_social_activity_events', "((userid = ? AND relateduserid = ?) OR (relateduserid = ? AND userid = ?))", array($event->userid, $event->relateduserid, $event->userid, $event->relateduserid));
        return;
    }
    public static function remove_events_for_user(\core\event\base $event) {
        global $DB;
        $DB->delete_records_select('oua_social_activity_events', "(userid = ? OR relateduserid = ?)", array($event->relateduserid, $event->relateduserid));
        return;
    }
}
