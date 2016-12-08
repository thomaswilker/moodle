<?php
/**
 * Teacher Course list block.
 *
 * @package    block_oua_course_list_teacher
 */
use block_oua_course_list_teacher\output\course_list_teacher_renderable as course_list_teacher;

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');

class block_oua_course_list_teacher extends block_base {
    private static $outputcache;

    function init() {
        $this->title = get_string('pluginname', 'block_oua_course_list_teacher');
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
        if (empty($this->config->hiddencategoryid)) {
            $this->config->hiddencategoryid = null;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // Teachers (those with edit gradebook capability anywhere in the system) view the teacher block.
        $isteacher = \local_oua_utility\global_capability::is_teacher_anywhere($USER);

        if (empty($CFG->disablemycourses) && isloggedin() && $isteacher) {
            $courselist = new course_list_teacher($this->config, $USER->id);
            $renderer = $this->page->get_renderer('block_oua_course_list_teacher');
            $this->content->text = $renderer->render_course_list_teacher($courselist);
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


