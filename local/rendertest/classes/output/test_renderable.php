<?php

namespace local_rendertest\output;

class test_renderable implements \renderable {

    public $debugtext = '';

    function __construct($text) {
        $this->debugtext = $text;
    }
}
