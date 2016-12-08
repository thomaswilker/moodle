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
use block_oua_course_progress_bar\output\renderable as courseprogressbar_renderable;


class block_oua_course_progress_bar extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_oua_course_progress_bar');
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

        if (isset($this->content)) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // If we aren't on a course page, then the progress bar will be empty.
        if (!isset($this->page->course->id)) {
            return $this->content;
        }

        $progressbar = new courseprogressbar_renderable($this->page->course->id);
        $renderer = $this->page->get_renderer('block_oua_course_progress_bar');
        $this->content->text = $renderer->render($progressbar);

        return $this->content;
    }
}
