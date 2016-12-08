<?php
namespace block_oua_navigation\output;
defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;
use stdClass;
use ArrayIterator;

class renderer extends plugin_renderer_base {
    public function display_course_navigation($coursecontent) {
        $html = $this->render_from_template('block_oua_navigation/subject_navigation', $coursecontent);

        return $html;
    }
}

