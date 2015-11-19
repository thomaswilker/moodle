<?php

require_once('config.php');

$PAGE->set_url('/test.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());
require_login();

$PAGE->set_heading('User Picker');
$PAGE->set_title('User Picker');

echo $OUTPUT->header();

echo '<div class="well">';
echo '<span id="usersselected"> No users selected </span>';
echo '<button class="btn btn-mini" id="selectusers">Select users...</button>';
echo '</div>';

$js = <<<EOF
require(['jquery', 'core/userpicker'], function($, UserPicker) {
    var nodes = $(document.getElementById('selectusers'));
    var picker = new UserPicker([], nodes, 'Select Users...');
});
EOF;

$PAGE->requires->js_amd_inline($js);
echo $OUTPUT->footer();
