<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_login();
$url = new moodle_url('/local/conversations/notifications.php');
$PAGE->set_url($url);
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_title(get_string('mynotifications','local_conversations'));
$PAGE->set_heading(get_string('mynotifications','local_conversations'));

if (empty($CFG->messaging)) {
    print_error('disabled', 'message');
}

$mynotificationspage = new \local_conversations\output\my_notifications();
$params = array();

// Start output
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('local_conversations');
echo $renderer->render_my_notifications($mynotificationspage);
echo $OUTPUT->footer();
