<?php
namespace block_oua_connections\output;

defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    public function render_my_connections($page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('block_oua_connections/my_connections', $data);

    }

    public function render_suggested_connections($page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('block_oua_connections/suggested_connections', $data);
    }

    public function display_connections_tabs($htmlcontent) {
        return $this->render_from_template('block_oua_connections/tabs', $htmlcontent);
    }
    public function display_all_connections_page($htmlcontent) {
        return $this->render_from_template('block_oua_connections/all_my_connections', $htmlcontent);
    }
}
