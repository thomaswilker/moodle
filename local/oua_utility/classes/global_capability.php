<?php
namespace local_oua_utility;
use context_system;

class global_capability {
    /**
     * Does the given user have a teacher role anywhere in the system.
     *
     * WARNING: does not handle dirty contexts, if enrolment/capability changes, it will not take immediate effect.
     * Need to logout/in to reset session, to guarantee update.
     *
     * @param $user
     * @return bool
     */
    public static function is_teacher_anywhere($user = null) {
        return static::has_capability_any_context('moodle/grade:export', $user);
    }

    /**
     * Check if the given user has the given capability anywhere in the system.
     * This ignores prohibited permissions, it only checks if the permission is set to 1.
     * Even if you are prohibited from the privilege somewhere in the system, if you have it somewhere else
     * This function will return true.
     *
     * WARNING: does not handle dirty contexts, if enrolment/capability changes, it will not take immediate effect.
     * Need to logout/in to reset session and guarantee update.
     *
     * @param $capability the capability to check
     * @param null $user the user to check, will use current user if null.
     * @return bool
     */
    public static function has_capability_any_context($capability, $user = null) {
        global $USER, $ACCESSLIB_PRIVATE;

        // Capability must exist.
        if (!$capinfo = get_capability_info($capability)) {
            debugging('Capability "' . $capability . '" was not found! This has to be fixed in code.');
            return false;
        }
        $hascap = false;
        // make sure there is a real user specified
        if ($user === null) {
            $userid = $USER->id;
        } else {
            $userid = is_object($user) ? $user->id : $user;
        }
        if ($USER->id == $userid) {
            if (!isset($USER->access)) {
                load_all_capabilities();
            }
            $access =& $USER->access;
        } else {
            // Make sure user accessdata is really loaded.
            get_user_accessdata($userid, true);
            $access =& $ACCESSLIB_PRIVATE->accessdatabyuser[$userid];
        }
        if (isset($access['rdef'])) {
            foreach ($access['rdef'] as $contextpath) {
                if (array_key_exists($capability, $contextpath) && $contextpath[$capability] === 1) {
                    $hascap = true;
                    break;
                }
            }
        }
        return $hascap;
    }

    /**
     * If $userid passes is_teacher_anywhere then assign them to the given global teacher role.
     * If they are not is_teacher_anywhere and they have the global teacher role, then remove them.
     *
     * @param $userid
     * @param null $globalteacherroleid
     * @return bool
     */
    public static function sync_user_to_global_teacher_role($userid, $globalteacherroleid = null) {
        if ($globalteacherroleid == null) {
            $globalteacherroleid = get_config('local_oua_utility', 'globalteacherroleid');
        }

        if (empty($globalteacherroleid)) {
            return true;
        }
        $autoremoverole = get_config('local_oua_utility', 'autoremoverole');
        $systemcontext = context_system::instance();
        $hasroleteacheranywhere = user_has_role_assignment($userid, $globalteacherroleid, $systemcontext->id);
        $isteacheranywhere = global_capability::is_teacher_anywhere($userid);

        if (!$hasroleteacheranywhere && $isteacheranywhere) {
            $assignableroles = get_assignable_roles($systemcontext);
            if (!array_key_exists($globalteacherroleid, $assignableroles)) {
                throw new \moodle_exception('invalidrole');
            }

            // If use is a global teacher anywhere on system and doesnt already have role.
            role_assign($globalteacherroleid, $userid, $systemcontext);
        } else if ($hasroleteacheranywhere && !$isteacheranywhere && $autoremoverole) {
            // Teacher has been removed as a teacher, remove them from teacher role.
            role_unassign($globalteacherroleid, $userid, $systemcontext->id);
        }
        return true;
    }
}
