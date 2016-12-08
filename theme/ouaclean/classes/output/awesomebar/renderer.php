<?php
namespace theme_ouaclean\output\awesomebar;

class renderer extends \plugin_renderer_base {

    public function render_renderable($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/awesomebar', $data);
    }
}

