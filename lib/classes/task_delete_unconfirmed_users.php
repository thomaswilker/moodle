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
 * Scheduled task abstract class.
 *
 * @package    core
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Simple task to delete user accounts for users who have not confirmed in time.
 */
class core_task_delete_unconfirmed_users extends core_scheduled_task {

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        $timenow = time();

        // Delete users who haven't confirmed within required period
        if (!empty($CFG->deleteunconfirmed)) {
            $cuttime = $timenow - ($CFG->deleteunconfirmed * 3600);
            $rs = $DB->get_recordset_sql ("SELECT *
                                             FROM {user}
                                            WHERE confirmed = 0 AND firstaccess > 0
                                                  AND firstaccess < ?", array($cuttime));
            foreach ($rs as $user) {
                delete_user($user); // we MUST delete user properly first
                $DB->delete_records('user', array('id'=>$user->id)); // this is a bloody hack, but it might work
                mtrace(" Deleted unconfirmed user for ".fullname($user, true)." ($user->id)");
            }
            $rs->close();
        }
    }

}
