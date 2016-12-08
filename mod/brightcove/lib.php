<?php
/**
 * Library of functions and constants for module brightcove
 *
 * @package    mod
 * @subpackage brightcove
 */

defined('MOODLE_INTERNAL') || die;

function brightcove_cron() {
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global       object
 *
 * @param object $brightcove
 *
 * @return bool|int
 */
function brightcove_add_instance($brightcove) {
    global $DB;

    $brightcove->timecreated = time();
    $brightcove->timemodified = time();
    $brightcove->id = $DB->insert_record('brightcove', $brightcove);

    return $brightcove->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global       object
 *
 * @param object $brightcove
 *
 * @return bool
 */
function brightcove_update_instance($brightcove) {
    global $DB;

    $brightcove->timemodified = time();
    $brightcove->id = $brightcove->instance;

    return $DB->update_record('brightcove', $brightcove);
}

/**
 * Given an ID of a brightcove instance, Permanently remove it from the database.
 * This will take care of all dependent tables to ensure cleanup.
 *
 * @global    object
 *
 * @param int $id Instance id to be deleted.
 *
 * @return bool Successful or not.
 */
function brightcove_delete_instance($id) {
    global $DB;

    if (!$brightcove = $DB->get_record("brightcove", array("id" => $id))) {
        return false;
    }

    $result = true;

    if (!$DB->delete_records("brightcove", array("id" => $brightcove->id))) {
        $result = false;
    }

    return $result;
}

/**
 * @return array
 */
function brightcove_get_view_actions() {
    return array();
}

/**
 * @return array
 */
function brightcove_get_post_actions() {
    return array();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 *
 * @return array status array
 */
function brightcove_reset_userdata($data) {
    return array();
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function brightcove_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 *
 * @param string $feature FEATURE_xx constant for requested feature
 *
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function brightcove_supports($feature) {
    switch ($feature) {
        case FEATURE_IDNUMBER:
            return false;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_NO_VIEW_LINK:
            return false;

        default:
            return null;
    }
}