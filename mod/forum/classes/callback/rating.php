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
 * Callbacks for ratings API.
 *
 * @package    mod_forum
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\callback;

use \core_rating\callback\can_see_item_ratings;
use \rating_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for ratings API.
 *
 * @package    mod_forum
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating {

    /**
     * Can the current user see ratings for a given itemid?
     * @param can_see_item_ratings $callback
     * @throws coding_exception
     * @throws rating_exception
     */
    public static function can_see_item_ratings(can_see_item_ratings $callback) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/rating/lib.php');
        // We build params here so that the code resembles the code before the move to the new callbacks API. (good for diffs).
        $params = [
            'component' => $callback->get_component(),
            'ratingarea' => $callback->get_ratingarea(),
            'itemid' => $callback->get_itemid(),
            'contextid' => $callback->get_contextid(),
            'scaleid' => $callback->get_scaleid(),
        ];

        // Check the component is mod_forum.
        if (!isset($params['component']) || $params['component'] != 'mod_forum') {
            throw new rating_exception('invalidcomponent');
        }

        // Check the ratingarea is post (the only rating area in forum).
        if (!isset($params['ratingarea']) || $params['ratingarea'] != 'post') {
            throw new rating_exception('invalidratingarea');
        }

        if (!isset($params['itemid'])) {
            throw new rating_exception('invaliditemid');
        }

        $post = $DB->get_record('forum_posts', array('id' => $params['itemid']), '*', MUST_EXIST);
        $discussion = $DB->get_record('forum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id , false, MUST_EXIST);

        // Perform some final capability checks.
        $callback->set_visible(forum_user_can_see_post($forum, $discussion, $post, $USER, $cm));
        return;
    }
}
