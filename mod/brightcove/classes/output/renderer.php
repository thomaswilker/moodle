<?php
namespace mod_brightcove\output;

defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    public function display_brightcove_player($playerparameters) {
        $html = '';
        $playerparametersobj = new \ArrayIterator($playerparameters);
        $html .= $this->render_from_template('mod_brightcove/brightcove_player', $playerparametersobj);

        return $html;
    }
}

