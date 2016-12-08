<?php

defined('MOODLE_INTERNAL') || die;
/**
 * Adds "New Broadcast Message" link to course admin navigation menu
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 * @throws coding_exception
 */
function local_message_broadcast_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;
    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('block/message_broadcast:send_broadcast_message', context_course::instance($PAGE->course->id))) {
        return;
    }
    $coursenode = $settingsnav->get('courseadmin');

    if ($coursenode) {
        $coursenode->add(get_string('managecourseannouncements', 'block_message_broadcast'), new moodle_url('/blocks/message_broadcast/managemessages.php', array('courseid' => $PAGE->course->id)));
    }
}