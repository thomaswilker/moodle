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

use block_oua_forum_recent_posts\output\renderable as forum_recent_posts_renderable;

require_once($CFG->dirroot . '/mod/forum/lib.php');
// Do not use log table if possible, it may be huge and is expensive to join with other tables.
require_once($CFG->dirroot . '/mod/forum/externallib.php');

class block_oua_forum_recent_posts extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_oua_forum_recent_posts');
    }

    public function hide_header() {
        return false;
    }

    public function instance_allow_multiple() {
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

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => true, 'mod' => true);
    }

    public function get_content() {
        global $USER;
        if (!isset($this->config)) {
            $this->config = new stdClass();
        }

        if (empty($this->config->numberofpoststodisplay)) {
            $this->config->numberofpoststodisplay = 5;
        }
        if (empty($this->config->fullscreennumberofposts)) {
            /*
             * Default to 20, 50 takes too long to load, but needs to feel like a full page forum.
             */
            $this->config->fullscreennumberofposts = 20;
        }

        if (isset($this->content)) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        list($context, $course, $cm) = get_context_info_array($this->page->context->id);
        // If i'm not in a course module, just return.
        if ($cm === null) {
            return $this->content;
        }
        $parentsectionid = $cm->section;

        // Get me the first hidden forum from the same section as this module.
        $courseforums = forum_get_readable_forums($USER->id, $course->id);
        $firsthiddenforum = null;

        foreach ($courseforums as $cmforum) {
            if ($cmforum->cm->section == $parentsectionid && $cmforum->cm->uservisible && $this->module_is_hidden($cmforum->cm)) {
                $firsthiddenforum = $cmforum;
                break;
            }
        }
        if ($firsthiddenforum === null) {
            return $this->content;
        }

        $this->title = get_string('recentpostblocktitle', 'block_oua_forum_recent_posts', $cmforum->name);

        $studentcount = $this->get_enrolled_students_count($context);
        // We now load with ajax.
        $recentposts = array();
        $renderer = $this->page->get_renderer('block_oua_forum_recent_posts');
        $canmanageforum = has_capability('mod/forum:pindiscussions', $cmforum->context) || has_capability('mod/forum:editanypost',
                                                                                                          $cmforum->context);

        $numberofpoststodisplay = $this->config->numberofpoststodisplay;

        if ($this->page && $this->page->cm && $this->page->cm->id && $this->page->cm->id == $firsthiddenforum->cm->id){
            if ($this->page->pagelayout == 'inlineforum') {
                $numberofpoststodisplay =  $this->config->fullscreennumberofposts;
            } else {
                return $this->content; // When in real forum, dont show inline posting block.
            }
        }
        $this->content->text = $renderer->display_forum_posts($firsthiddenforum, $recentposts, $studentcount,
                                                              $numberofpoststodisplay, $canmanageforum);

        return $this->content;
    }

    /**
     * Determine if this forum is on that should be included in the list of invisible forums.
     *
     * Unless the course format used supports the module_is_visible function, then all forums
     * will be visible and included in this block.
     */
    protected function module_is_hidden($cm) {
        // The course format needs to tell us if it's hidden or not.
        $courseformat = course_get_format($this->page->course);
        $cancall = is_callable(array($courseformat, 'module_is_hidden_from_view'));

        if (!$cancall) {
            return false;
        }

        return $courseformat->module_is_hidden_from_view($cm);
    }

    public function get_enrolled_students_count($context) {
        global $CFG;
        $cache = cache::make('block_oua_forum_recent_posts', 'student_count');
        $count = $cache->get($context->id);
        if (empty($count)) {
            require_once($CFG->dirroot . '/lib/accesslib.php');
            $count = count_enrolled_users($context, "mod/assign:submit"); // Only count students.
            // cache the data.
            $cache->set($context->id, $count);
        }
        return $count;
    }
}
