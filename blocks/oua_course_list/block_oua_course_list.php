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
 * Course list block.
 *
 * @package    block_oua_course_list
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_oua_course_list\output\course_list_renderable as course_list;

include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');

class block_oua_course_list extends block_base {
    private static $outputcache;

    function init() {
        $this->title = get_string('pluginname', 'block_oua_course_list');
    }

    public function hide_header() {
        return true;
    }

    function instance_allow_multiple() {
        return true;
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

    function has_config() {
        return true;
    }
    /**
     * Override refresh content to clear cache
     */
    public function refresh_content() {
        self::$outputcache = null;
        return parent::refresh_content();
    }

    function get_content() {
        global $CFG, $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        if(!empty(self::$outputcache)) {
            /**
             * Cache the output of the block as it is displayed multiple times on the dashboard
             * and it is relatively slow to generate (~2seconds or ~4 seconds with results tab)
             *
             */
           $this->content = self::$outputcache;
           return $this->content;
        }
        if (!isset($this->config)) {
            $this->config = new stdClass();
        }

        if (empty($this->config->defaultcourselistlength)) {
            $this->config->defaultcourselistlength = 10;
        }
        if (empty($this->config->displayresultstab)) {
            $this->config->displayresultstab = false;
        }
        if (empty($this->config->hiddencategoryid)) {
            $this->config->hiddencategoryid = null;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // Teachers (those with edit gradebook capability anywhere in the system) view the teacher block.
        $isteacher = \local_oua_utility\global_capability::is_teacher_anywhere($USER);


        if (empty($CFG->disablemycourses) and isloggedin() and !$isteacher and
                                                               !(has_capability('moodle/course:update', context_system::instance()))) {
            $courselist = new course_list($this->config, $USER->id);
            $renderer = $this->page->get_renderer('block_oua_course_list');
            $this->content->text = $renderer->render_course_list($courselist);
        }
        self::$outputcache = $this->content;
        return $this->content;

    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     *
     */
    public function get_aria_role() {
        return 'navigation';
    }

    public function html_attributes() {
        $attributes = parent::html_attributes();
        // Common css block class between student and teacher dashboards.
        $attributes['class'] .= ' dashboard_course_list';
        return $attributes;
    }
}


