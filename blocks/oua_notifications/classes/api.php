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

namespace block_oua_notifications;
use \block_oua_notifications\output\renderable as notification_list;
use \context_system;
use \cache;
/**
 * API exposed by block_oua_notifications
 *
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class api {

    /**
     * Delete one or more notifications given a list of notificationids.
     *
     * @param string $notificationids JSON object of notification ids to delete
     *
     * @return array[string] rendered notification list after delete
     */
    public static function delete_notifications($notificationids) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        $managenotifications = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        $managenotifications->delete_my_notifications($notificationids);

        $ouanotifications = new notification_list($USER->id);
        $notificationrenderer = $PAGE->get_renderer('block_oua_notifications');
        $notificationlist = $notificationrenderer->render($ouanotifications);
        return array('notification_list' => $notificationlist);
    }

    /**
     * Mark notification as read given notification id.
     * @param $notificationid
     * @return array
     */
    public static function mark_notification_read($notificationid)
    {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        // This is required because we call a renderer, context is not auto set.
        if (!isset($PAGE->context)) {
            $PAGE->set_context(context_system::instance());
        }

        $managenotifications = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        $notificationidread = $managenotifications->mark_notification_read($notificationid);

        return array('notificationidread' => $notificationidread);
    }

    /**
     * Cache unread notifications count to avoid db hits.
     * @param $user
     * @return int
     * @throws \coding_exception
     */
    public static function get_cached_count_unread_notifications($user)
    {
        global $DB;

        $cache = cache::make('block_oua_notifications', 'unreadnotifications');
        $cachekey = $user->id . '_unreadnotifications';

        $unreadnotificationscount = $cache->get($cachekey);
        if (false === $unreadnotificationscount) {
            $params = array($user->id);
            $sql = "SELECT count(*)
                      FROM {message}
                     WHERE notification = 1
                       AND useridto = ?";
            $unreadnotificationscount = $DB->count_records_sql($sql, $params);
            $cache->set($cachekey, $unreadnotificationscount);
        }
        return $unreadnotificationscount;
    }

}
