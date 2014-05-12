<?php

namespace local_rendertest\output;

class test_renderer extends \renderer_base {

    public function render_test(test_renderable $test) {
        echo $test->debugtext;
    }
}
