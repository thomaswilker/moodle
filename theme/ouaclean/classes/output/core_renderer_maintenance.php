<?php

namespace theme_ouaclean\output;

defined('MOODLE_INTERNAL') || die;

class core_renderer_maintenance extends \theme_bootstrap_core_renderer_maintenance {

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_maintenance_layout(layout\maintenance_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

}
