<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_oua_connections;
use \block_oua_connections\output\my_connections as my_connections;
use context_system;
/**
 * API exposed by block_oua_connections
 *
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class api {

    /**
     * Request a connection to the given user id.
     *
     * @param string $userid Userid of user to request connection to.
     *
     * @return array[string]not sure yet.
     */
    public static function request_connection($userid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        require_once($CFG->dirroot . '/message/lib.php');
        $success = false;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        // Am I on the users contact list.
        // a) They have already requested me as a friend.
        // b) They could have blocked me.
        $userreversecontact = $DB->get_record('message_contacts', array('userid' => $userid, 'contactid' => $USER->id));
        $userhasblockedme = ($userreversecontact !== false && $userreversecontact->blocked == 1);


        // Only send if I am not blocked by user
        // And user is not already in my contact list, (stops notification spam)
        if (!$userhasblockedme &&
                $DB->get_record('message_contacts', array('userid' => $USER->id, 'contactid' => $userid)) === false) {
            // Add user to contact list.
            if (message_add_contact($userid)) {
                $manageconnections = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
                if ($userreversecontact === false) {
                    // I am not yet on the other users contact list, send a request.
                    // Send message w/ notification , contains a link to connect.
                    $success = $manageconnections->send_connection_request($userid);
                } else {
                    // I am already on the other users contact list, treat as connection accepted and send message.
                    $manageconnections->send_connected_notification($USER->id, $userid);
                }
            }
        }
        return $success;
    }

    /**
     * Accept the connection request from the given user id
     * Only accept if i am in the users contact list.
     * and the user is not already in my contact list.
     * sends a notification if the user is added.
     *
     * @param string $messageid id of message to dismiss.
     * @param string $userid Userid of user to request connection to.
     *
     * @return array[string]not sure yet.
     */
    public static function accept_request_connection($messageid, $userid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.
        require_once($CFG->dirroot . '/message/lib.php');
        // Am I on the users contact list and not blocked
        $userreversecontact = $DB->get_record('message_contacts', array('userid' => $userid, 'contactid' => $USER->id));
        $userhasblockedme = ($userreversecontact !== false && $userreversecontact->blocked == 1);
        $manageconnections = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        if ($userreversecontact !== false && $userreversecontact->blocked == 0) {

            if (($contact = $DB->get_record('message_contacts', array('userid' => $USER->id, 'contactid' => $userid))) === false) {
                // Add user to my contact list, if they are not yet on it.
                if (message_add_contact($userid)) {
                    // Send message w/ notification that connection was successful
                    $manageconnections->send_connected_notification($USER->id, $userid);
                }
            }
        }
        $manageconnections->delete_notification($USER->id, $messageid); // Always Delete notification (e.g. they were blocked).

        return true;
    }

    /**
     * Ignore a connection Request from the given userid.
     *
     * @param string $userid Userid of user to ignore
     *
     * @return array[string]not sure yet.
     */
    public static function ignore_request_connection($messageid, $userid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        $manageconnections = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        $manageconnections->delete_notification($USER->id, $messageid);
        $manageconnections->remove_me_from_users_contact_list($USER->id, $userid);

        return true;
    }
    /**
     * Delete an existing connection given the userid.
     *
     * @param string $userid Userid of user who's connection to remove
     *
     * @return array[string]not sure yet.
     */
    public static function delete_connection($userid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        $manageconnections = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        $manageconnections->remove_me_from_users_contact_list($USER->id, $userid);
        $manageconnections->remove_me_from_users_contact_list($userid, $USER->id);

        $myconnections = new my_connections(0, 'firstname');
        $myconnectionsrenderer = $PAGE->get_renderer('block_oua_connections');
        $allmyconnections =  $myconnectionsrenderer->display_all_connections_page($myconnections->export_for_template($myconnectionsrenderer));
        return array('allmyconnections' => $allmyconnections);
    }
}
