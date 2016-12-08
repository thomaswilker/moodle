<?php
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/lib.php");
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);
$url = new moodle_url('/mod/brightcove/view.php', array('id' => $id));
$PAGE->set_url($url);

if (!($cm = get_coursemodule_from_id('brightcove', $id))) {
    print_error('invalidcoursemodule');
}
if (!($bc = $DB->get_record('brightcove', array('id' => $cm->instance), '*', MUST_EXIST))) {
    print_error('invalidcoursemodule');
}
if (!($course = $DB->get_record("course", array("id" => $cm->course)))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$videoparams = array();
$brightcoveconfig = get_config('brightcove');
$videoparams['accountid'] = $brightcoveconfig->accountid;
$videoparams['threeplayapikey'] = $brightcoveconfig->threeplayapikey;
$videoparams['threeplayprojectid'] = $brightcoveconfig->threeplayprojectid;

$videoparams['title'] = $cm->name;
$videoparams['videoid'] = $bc->videoid;
$videoparams['playerid'] = $bc->playerid;
$videoparams['experienceid'] = 'experience' . $bc->videoid;

// We always load over https as all environment except developer machines will always be https.
// The below line gets an autoappended .js
$videoparams['bcurl'] = "https://players.brightcove.net/{$videoparams['accountid']}/{$videoparams['playerid']}_default/index.min";
$PAGE->requires->js(new moodle_url('https://p3.3playmedia.com/p3.js')); // There is a 3Play async function that will load transcript

$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);
$renderer = $PAGE->get_renderer('mod_brightcove');
echo $OUTPUT->header();
echo $renderer->display_brightcove_player($videoparams);
echo $OUTPUT->footer();