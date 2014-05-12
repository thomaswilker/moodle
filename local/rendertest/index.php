<?php

require_once("../../config.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/rendertest/index.php');
$PAGE->set_title('Renderer test');
$PAGE->set_heading('Testing renderers');
echo $OUTPUT->header();

$pluginoutput = $PAGE->get_renderer('local_rendertest', 'test');

$debug = new \local_rendertest\output\test_renderable('Debug text');
echo $pluginoutput->render($debug);

echo $OUTPUT->footer();
