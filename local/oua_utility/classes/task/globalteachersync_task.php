<?php
namespace local_oua_utility\task;

use local_oua_utility\global_capability;
use moodle_exception;
use context_system;
/**
 * Scheduled task to sync users with global teacher role.
 */
class globalteachersync_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_globalteachersync', 'local_oua_utility');
    }

    /**
     * Do the job.
     */
    public function execute() {
        global $DB;
        $globalteacherroleid = get_config('local_oua_utility', 'globalteacherroleid');
        if (empty($globalteacherroleid)) {
            return true;
        }

        // Exit early dont run through everyone if role doesn't exist.
        $assignableroles = get_assignable_roles(context_system::instance());
        if (!array_key_exists($globalteacherroleid, $assignableroles)) {
            throw new moodle_exception('invalidrole');
        }

        // Get all users on the system.
        $users = $DB->get_recordset_sql("SELECT *
                                            FROM {user}
                                           WHERE confirmed = 1 AND deleted = 0");
        foreach ($users as $user) {
            // Check if they are a teacher anywhere.
            // If they are then add or remove them to the sitewide role.
            global_capability::sync_user_to_global_teacher_role($user->id, $globalteacherroleid);
        }
        return true;
    }
}
