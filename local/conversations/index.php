<?php
require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_login();
$url = new moodle_url('/local/conversations/index.php');
$PAGE->set_url($url);
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_title(get_string('mymessages','local_conversations'));
$PAGE->set_heading(get_string('mymessages','local_conversations'));

if (empty($CFG->messaging)) {
    print_error('disabled', 'message');
}

$refreshtime = get_config('local_conversations', 'conversationrefreshtime');
$params = array($refreshtime);

$mymessagespage = new \local_conversations\output\my_messages();
$params = array($refreshtime);
$PAGE->requires->js_call_amd("local_conversations/my_messages", 'initialise', $params);

$renderer = $PAGE->get_renderer('local_conversations');
// Start output
echo $OUTPUT->header();
echo $renderer->render_my_messages($mymessagespage);
echo $OUTPUT->footer();
