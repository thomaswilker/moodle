<?php

namespace theme_clean\output\local_rendertest;

class test_renderer extends \renderer_base {

    public function render_test(\local_rendertest\output\test_renderable $test) {
        echo 'CLEAN:' . $test->debugtext;
    }
}
