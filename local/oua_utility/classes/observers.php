<?php

namespace local_oua_utility;

/**
 * Handles events.
 */
class observers {
    /**
     * Handle role_assigned event
     *
     * Does the following:
     *     - check if the user is a global teacher, if so add them to the global teacher role.
     *
     * @param \core\event\role_assigned $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_role_changed($event) {
        $globalteacherroleid = get_config('local_oua_utility', 'globalteacherroleid');

        if (empty($globalteacherroleid)) {
            return true;
        }
        return global_capability::sync_user_to_global_teacher_role($event->relateduserid, $globalteacherroleid);
    }
}
