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

/**
 * This is the external API for notifications
 *
 * @package    block_oua_notifications
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_notifications;

global $CFG;
require_once($CFG->libdir . "/externallib.php");
use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;


class external extends external_api {

    /**
     * Deletes the given notification id
     *
     * @param $notificationid Id of notification to delete
     * @return array
     */
    public static function delete_notifications($notificationids) {
        $params = self::validate_parameters(self::delete_notifications_parameters(),
                                            array('notificationids' => $notificationids));

        return api::delete_notifications($notificationids);
    }


    /**
     * Returns description of request_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_notifications_parameters() {
        return new external_function_parameters(
                array('notificationids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Notification ids')
                    , 'List of notification ids to delete',
                    VALUE_REQUIRED)
                )
        );
    }
    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function delete_notifications_returns() {
        return new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'JSON object containing rendered notification list'));
    }


    /**
     * Mark the given notification as read.
     * @param $notificationid
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function mark_notification_read($notificationid)
    {
        $params = self::validate_parameters(self::mark_notification_read_parameters(),
            array('notificationid' => $notificationid));

        return api::mark_notification_read($notificationid);
    }

    /**
     * Return description of notification mark as read parameters.
     * @return external_function_parameters
     */
    public static function mark_notification_read_parameters()
    {
        return new external_function_parameters(array('notificationid' => new external_value(PARAM_INT,
            'Notification id of marked read notification')));
    }


    /**
     * Return description of notification mark as read.
     * @return external_multiple_structure
     */
    public static function mark_notification_read_returns()
    {
        return new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'JSON object containing nofication mark as read flag'));
    }

}
