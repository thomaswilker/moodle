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

namespace block_oua_messages;
use \block_oua_messages\output\renderable as message_list;
use context_system;
/**
 * API exposed by block_oua_messages
 *
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class api {

    /**
     * Delete a message given the messageid.
     *
     * @param string $messageid Id of message to delete
     *
     * @return array[string]not sure yet.
     */
    public static function delete_message($messageid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        $managemessages = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        $managemessages->delete_message($messageid);

        $ouamessages = new message_list($USER->id);
        $messagerenderer = $PAGE->get_renderer('block_oua_messages');
        $messagelist = $messagerenderer->render($ouamessages);
        return array('message_list' => $messagelist);
    }

    public static function mark_message_read($messageid)
    {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        // This is required because we call a renderer, context is not auto set.
        $PAGE->set_context(context_system::instance());

        $managemessages = new manage($CFG, $DB, $OUTPUT, $PAGE, $USER);
        $messageidread = $managemessages->mark_message_read($messageid);

        return array('messageidread'=>$messageidread);
    }
}
