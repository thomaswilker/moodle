<?php

/**
 * OUA CUSTOM:
 * Add a page to forum to view inline fourms on one page.
 * @package   mod_forum
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);       // Course Module ID
$f = optional_param('f', 0, PARAM_INT);        // Forum ID
$mode = optional_param('mode', 0, PARAM_INT);     // Display mode (for single forum)
$showall = optional_param('showall', '', PARAM_INT); // show all discussions on one page
$changegroup = optional_param('group', -1, PARAM_INT);   // choose the current group
$page = optional_param('page', 0, PARAM_INT);     // which page to show
$search = optional_param('search', '', PARAM_CLEAN);// search string

$params = array();
if ($id) {
    $params['id'] = $id;
} else {
    $params['f'] = $f;
}
if ($page) {
    $params['page'] = $page;
}
if ($search) {
    $params['search'] = $search;
}
$PAGE->set_url('/mod/forum/viewinline.php', $params);

if ($id) {
    if (!$cm = get_coursemodule_from_id('forum', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$forum = $DB->get_record("forum", array("id" => $cm->instance))) {
        print_error('invalidforumid', 'forum');
    }
    if ($forum->type == 'single') {
        $PAGE->set_pagetype('mod-forum-discuss');
    }
    // move require_course_login here to use forced language for course
    // fix for MDL-6926
    require_course_login($course, true, $cm);
    $strforums = get_string("modulenameplural", "forum");
    $strforum = get_string("modulename", "forum");
} else if ($f) {

    if (!$forum = $DB->get_record("forum", array("id" => $f))) {
        print_error('invalidforumid', 'forum');
    }
    if (!$course = $DB->get_record("course", array("id" => $forum->course))) {
        print_error('coursemisconf');
    }

    if (!$cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
        print_error('missingparameter');
    }
    // move require_course_login here to use forced language for course
    // fix for MDL-6926
    require_course_login($course, true, $cm);
    $strforums = get_string("modulenameplural", "forum");
    $strforum = get_string("modulename", "forum");
} else {
    print_error('missingparameter');
}

if (!$PAGE->button) {
    $PAGE->set_button(forum_search_form($course, $search));
}

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

/// Print header.

$PAGE->set_title($forum->name);
$PAGE->add_body_class('forumtype-' . $forum->type);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('inlineforum');
/// Some capability checks.
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    notice(get_string("activityiscurrentlyhidden"));
}

if (!has_capability('mod/forum:viewdiscussion', $context)) {
    notice(get_string('noviewdiscussionspermission', 'forum'));
}

echo $OUTPUT->header();

// No content on this page.

echo $OUTPUT->footer($course);
