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
 * @package    block_oua_social_activity
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_social_activity;

global $CFG;
require_once($CFG->libdir . "/externallib.php");
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;


class external extends external_api {

    /**
     * Deletes the given notification id
     *
     * @param $lasteventid Id of last event in previous list
     * @return array
     */
    public static function get_more_events($lasteventid) {
        $params = self::validate_parameters(self::get_more_events_parameters(),
                                            array('lasteventid' => $lasteventid));

        return api::get_more_events($lasteventid);
    }


    /**
     * Returns description of request_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function get_more_events_parameters() {
        return new external_function_parameters(
                array('lasteventid' =>
                    new external_value(PARAM_INT, 'Last Event Id')
                )
        );
    }
    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function get_more_events_returns() {
        return new external_single_structure(
                array('social_activity_list' => new external_value(PARAM_RAW, 'JSON object containing rendered events list')));
    }
}
