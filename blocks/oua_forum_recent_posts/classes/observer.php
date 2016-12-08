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
 * Event observer.
 *
 * @package    block_oua_forum_recent_posts
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */

defined('MOODLE_INTERNAL') || die();
use block_oua_forum_recent_posts\api;
/**
 * Event observer.
 * On an enrolment event invalidate our student enrollment count cache
 *
 * @package    block_oua_forum_recent_posts
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class block_oua_forum_recent_posts_observer {

    /**
     * Clear the student count cache
     *
     * @param \core\event\base $event
     */
    public static function invalidate_cache(\core\event\base $event) {
        // Trigger purge event defined in caches.php.
        cache_helper::purge_by_event('cache_event_enrolment_updated');
    }

    /**
     * Clear the per forum post cache
     *
     * @param \core\event\base $event
     */
    public static function forum_cache_clear(\core\event\base $event) {
        $eventdata = $event->get_data();
        if (isset($eventdata['other']['forumid'])) {
            $forumid = $eventdata['other']['forumid'];
            $cache = api::make_forum_discussion_cache($forumid);
            $cache->purge();
        }
    }
}
