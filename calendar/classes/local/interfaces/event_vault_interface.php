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
 * Event vault interface
 *
 * @package    core_calendar
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\interfaces;

/**
 * Interface for an event vault class
 *
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface event_vault_interface {
    /**
     * Retrieve an event for the given id.
     *
     * @param in $id The event id
     * @return event_interface|false
     */
    public function get_event_by_id(int $id);

    /**
     * Retrieve an array of events for the given user and time constraints.
     *
     * @param \stdClass       $user         The user for whom the events belong
     * @param int             $timesortfrom Events with timesort from this value (inclusive)
     * @param int             $timesortto   Events with timesort until this value (inclusive)
     * @param event_interface $afterevent   Only return events after this one
     * @param int             $limitnum     Return at most this number of events
     * @return event_interface
     */
    public function get_action_events_by_timesort(
        \stdClass $user,
        int $timesortfrom,
        int $timesortto,
        event_interface $afterevent,
        int $limitnum
    );
}