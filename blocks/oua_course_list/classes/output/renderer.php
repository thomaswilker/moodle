<?php
namespace block_oua_course_list\output;
defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;
use stdClass;
use ArrayIterator;

class renderer extends plugin_renderer_base {
    public function render_course_list($page) {
        $data = $page->export_for_template($this);
        $html = $this->render_from_template('block_oua_course_list/course_list', $data);

        return $html;
    }
}

