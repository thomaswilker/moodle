<?php
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

class global_capability_testcase extends oua_advanced_testcase {

    /**
     * GIVEN a user is enrolled as an editing teacher in a course
     *  WHEN is_teacher is called
     *  THEN function returns true
     *
     * GIVEN a user is enrolled as a student in a course
     *  WHEN is_teacher is called
     *  THEN function returns false
     *
     * GIVEN an Admin user is not enrolled anywhere as a teacher
     *  WHEN is_teacher is called
     *  THEN function returns false
     *
     * GIVEN an Admin user is logged in a a user with a teacher role
     *  WHEN is_teacher is called
     *  THEN function returns true
     *
     * GIVEN an Admin user is logged in as a user with a student role
     *  WHEN is_teacher is called
     *  THEN function returns false
     *
     * Test is teach functionality
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_is_teacher() {
        global $DB, $USER;

        $this->resetAfterTest();
        // Generate data.
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $capabilitysubmit = 'mod/assign:submit';
        $capabilitygradexport = 'moodle/grade:export';

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);

        $this->assertFalse(has_capability($capabilitysubmit, $coursecontext, $student->id));
        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                           "Teacher user should not yet identify as teacher");
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->assertTrue(has_capability($capabilitysubmit, $coursecontext, $student->id));
        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere($student),
                           "Student role should not identify as teacher");

        $this->assertTrue(has_capability($capabilitygradexport, $coursecontext, $teacher->id));
        $this->assertTrue(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                          "Teacher role should identify as teacher");

        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere($USER),
                           "Admin should return as not having a teacher capability unless enrolled");

        // admin Logged in as teacher
        // $this->setUser($user1);
        \core\session\manager::loginas($teacher->id, context_system::instance());
        $this->assertTrue(\local_oua_utility\global_capability::is_teacher_anywhere(),
                          "Admin logged in as teacher should identify as teacher");

        \core\session\manager::loginas($student->id, context_system::instance());
        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere(),
                           "Admin logged in as student should NOT identify as teacher");
    }

    /**
     * GIVEN a user has a role with capability in any context
     *  WHEN has_capability_any_context is called (without specifying a context
     *  THEN function will return true.
     */
    public function test_has_capability_anywhere() {
        global $DB;

        $this->resetAfterTest();
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $capabilitygradexport = 'moodle/grade:export';
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);
        $this->assertFalse(\local_oua_utility\global_capability::has_capability_any_context($capabilitygradexport, $teacher),
                           "Teacher should not yet have the teacher capability anywhere");
        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                           "Teacher user should not yet identify as teacher");
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $coursecontext->reload_if_dirty(); // Requires reload due to enrolment.
        $this->assertTrue(\local_oua_utility\global_capability::has_capability_any_context($capabilitygradexport, $teacher),
                          "Teacher should have grade export capability");
        $this->assertTrue(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                          "Teacher role should identify as teacher");
    }

    /**
     * GIVEN a user has a capability prohibited at site context
     *   AND a user has a capability assigned at course context
     *  WHEN has_capability_any_context is called
     *  THEN function should return true.
     */
    public function test_has_capability_when_prohibited_in_parent_context() {
        global $DB;

        $this->resetAfterTest();
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $capabilitygradexport = 'moodle/grade:export';
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $capabilitygradexport = 'moodle/grade:export';
        $systemcontext = context_system::instance();
        assign_capability($capabilitygradexport, CAP_PROHIBIT, $studentrole->id, $systemcontext->id);
        role_assign($studentrole->id, $teacher->id, $systemcontext->id);

        $this->assertFalse(\local_oua_utility\global_capability::has_capability_any_context($capabilitygradexport, $teacher),
                           "Teacher should not yet have the teacher capability anywhere");
        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                           "Teacher user should not yet identify as teacher");
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, $teacherrole->id);

        $coursecontext1->reload_if_dirty(); // Requires reload due to enrolment.
        $this->assertTrue(\local_oua_utility\global_capability::has_capability_any_context($capabilitygradexport, $teacher),
                          "Teacher should have grade export capability");
        $this->assertTrue(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                          "Teacher role should identify as teacher");
    }


    public function test_user_sync_and_cron_run() {
        global $DB;

        $this->resetAfterTest();
        // Create a user who has a "Teacher" role
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $capabilitygradexport = 'moodle/grade:export';
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);
        $this->assertFalse(\local_oua_utility\global_capability::has_capability_any_context($capabilitygradexport, $teacher),
                           "Teacher should not yet have the teacher capability anywhere");
        $this->assertFalse(\local_oua_utility\global_capability::is_teacher_anywhere($teacher),
                           "Teacher user should not yet identify as teacher");
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $coursecontext->reload_if_dirty(); // Requires reload due to enrolment.



        // Create a global teacher role.
        $globalroleid = $this->getDataGenerator()->create_role();
        set_role_contextlevels($globalroleid, array(CONTEXT_SYSTEM));


        $systemcontext = \context_system::instance()->id;
        $this->setAdminUser();

        // Execute the cron task.
        $task = new \local_oua_utility\task\globalteachersync_task();


        // If no global role set, then no role will be assigned.
        $task->execute();
        $coursecontext->reload_if_dirty();
        $this->assertFalse(user_has_role_assignment($teacher->id, $globalroleid, $systemcontext));


        // Set an invalid role to global roleid.
        set_config('globalteacherroleid', 555555, 'local_oua_utility');
        try {
            $task->execute();
            $this->fail('Exception expected due to invalid global role set.');
        } catch (moodle_exception $e) {
            $this->assertEquals('invalidrole', $e->errorcode);
        }

        // Set role to be the global teacher role in settings.
        set_config('globalteacherroleid', $globalroleid, 'local_oua_utility');
        $task->execute();
        $coursecontext->reload_if_dirty();
        // Ensure cron task assigned teacher the global teacher role.
        $this->assertTrue(user_has_role_assignment($teacher->id, $globalroleid, $systemcontext));

        // Remove teacher role.
        $manplugin = enrol_get_plugin('manual');
        $enrol = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $manplugin->unenrol_user($enrol, $teacher->id);
        $coursecontext->reload_if_dirty();
        // Ensure cron task doesnt remove teacher role when setting is off
        set_config('autoremoverole', '0', 'local_oua_utility');
        $task->execute();
        $coursecontext->reload_if_dirty();
        $this->assertTrue(user_has_role_assignment($teacher->id, $globalroleid, $systemcontext));

        // Ensure cron task removes teacher role when setting is on
        set_config('autoremoverole', '1', 'local_oua_utility');
        $task->execute();
        $coursecontext->reload_if_dirty();
        $this->assertFalse(user_has_role_assignment($teacher->id, $globalroleid, $systemcontext));


    }
}
