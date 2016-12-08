<?php

/**
 * Call back for announcement attachments accessing/download.
 * File by Moodle, modified for block_message_broadcast.
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 * @throws coding_exception
 * @throws require_login_exception
 * @throws require_login_session_timeout_exception
 */
function block_message_broadcast_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $USER;
    // Attachments store at system context.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== \block_message_broadcast\form::ATTACHMENTS_AREA) {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login();

    $coursecontext = $context->get_course_context(false);

    if (($coursecontext != false && !is_viewing($coursecontext) && !is_enrolled($coursecontext, $USER))) {
        // If the file is saved to a course context, then user must be able to view course to view file.
        // WARNING: Currently all files are saved to system context because of multi-course and system messages.
        return false;
    }



    // Announcement message id.
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_message_broadcast', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - no caching.
    send_stored_file($file, 0, 0, $forcedownload, $options);

    exit;
}
