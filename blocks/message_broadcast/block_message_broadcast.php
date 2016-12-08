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


class block_message_broadcast extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_message_broadcast');
    }

    public function hide_header() {
        return true;
    }

    function instance_allow_multiple() {
        return false;
    }

    public function instance_can_be_docked() {
        return false;
    }

    public function instance_can_be_hidden() {
        return false;
    }

    public function instance_can_be_collapsed() {
        return false;
    }

    public function get_content() {
        global $CFG, $PAGE, $USER;

        $PAGE->requires->js_call_amd('block_message_broadcast/dismissmessage', 'mbinitialise');

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $coursecontext = $this->page->context->get_course_context(false);

        if (empty($this->instance) || !isloggedin() || isguestuser() || empty($CFG->messaging)
                    || ($coursecontext != false && !is_viewing($coursecontext) && !is_enrolled($coursecontext, $USER))) {
            return $this->content;
        }
        $managemessage = new \block_message_broadcast\manage();

        // Dismiss messages without javascript
        $dissmissmessageid = optional_param('dismissmessage', null, PARAM_INT);
        if ($dissmissmessageid !== null) {
            $managemessage->mark_read($USER, $dissmissmessageid);
        }
        $allcontexts = $this->page->context->get_parent_context_ids(true);
        // Get messages for this page context.
        $unreadmessages = $managemessage->get_unread_messages($USER->id, $allcontexts);

        if (!empty($unreadmessages)) {
            $renderer = $this->page->get_renderer('block_message_broadcast');
            $this->content->text = $renderer->display_unread_messages($unreadmessages);
        }

        return $this->content;
    }

}


