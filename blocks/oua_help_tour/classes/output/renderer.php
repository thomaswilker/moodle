<?php
namespace block_oua_help_tour\output;

defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    public function render_social_events_list($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_oua_help_tour/help_tour_block', $data);
    }
}
