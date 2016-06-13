<?php

require('config.php');

global $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/test.php'));
$PAGE->set_context(context_system::instance());

$PAGE->set_title('Test');
require_login();

echo $OUTPUT->header();
echo $OUTPUT->heading('Test');

echo '<canvas id="myChart" width="400" height="400"></canvas>';

$PAGE->requires->js_call_amd('core/chart', 'init');

echo $OUTPUT->footer();
