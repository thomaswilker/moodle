<?php
namespace theme_ouaclean\output\quizsummarytable;

class renderer extends \plugin_renderer_base {

    public function render_renderable($page) {

        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/quizsummarytable', $data);
    }
}

