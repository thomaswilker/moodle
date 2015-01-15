<?php

namespace local_demo\output;
defined('MOODLE_INTERNAL') || die();

class horse implements \renderable {

    public $name;

    public function __construct($name) {
        $this->name = $name;
    }
}
