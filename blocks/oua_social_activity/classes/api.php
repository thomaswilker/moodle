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

namespace block_oua_social_activity;
use \block_oua_social_activity\output\social_events_list;
use \context_system;
/**
 * API exposed by block_oua_social_activity
 *
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class api {
    /**
     * Get more recent social activity
     *
     * @param $lasteventid the event id of event that was last listed
     *
     * @return array social activity event list rendered for display
     */

    public static function get_more_events($lasteventid) {
        // TODO: LT-2113 , this funciton will change substantially, when getting events from multiple sources.
        global $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.
        $socialactivities = new social_events_list($USER->id, $lasteventid);
        $notificationrenderer = $PAGE->get_renderer('block_oua_social_activity');
        $socialactivitieslist = $notificationrenderer->render($socialactivities);
        return array('social_activity_list' => $socialactivitieslist);
    }
}
