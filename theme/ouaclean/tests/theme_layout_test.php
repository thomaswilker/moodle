<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

use theme_ouaclean\output\layout\course_layout;

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class theme_layout_testcase extends advanced_testcase {
    public function test_were_not_written_due_to_time() {
        // We need to test the course layout class does what it should produce.
        // The key is that it parses and produces valid output.

        // In a more ideal case we can add blocks to the correct regions and expect them to be rendered
        // on the output page.

        $testlayout = new course_layout('coursecontext', 'doctype');
        $this->markTestIncomplete('Time constraints meant we did not expand testing for UI.');
    }

    // COURSE VIEW SIDE TABS RENDERING FOR STUDENT AND ADMIN

    private function course_layout_render(){
        global $CFG, $DB, $PAGE, $OUTPUT, $USER;

        require_once($CFG->dirroot . '/theme/bootstrap/renderers.php');

        $CFG->theme = 'ouaclean';

        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $course = $this->getDataGenerator()->create_course(
            array(
                'format' => 'invisible',
            )
        );
        // create a page activity
        $pagedata = $this->getDataGenerator()
            ->create_module('page', array('course' => $course->id), array('visible' => 1));
        $cm = get_coursemodule_from_id('page', $pagedata->cmid);

        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        if (is_primary_admin($USER->id)) {
            $url = new moodle_url('/course/view.php', array('id'=>$course->id)); // admin course view
        } else {
            $this->setUser($student);
            $url = new moodle_url('/mod/page/view.php', array('id'=>$cm->id));
        }
        $PAGE->set_url($url);

        $contextcourse = context_course::instance($course->id);
        $doctype = $OUTPUT->doctype();
        $layout = new course_layout($contextcourse, $doctype);
        $renderer = $PAGE->get_renderer('theme_ouaclean', 'core', RENDERER_TARGET_GENERAL);

        return $layout->export_for_template($renderer);
    }

    public function test_student_module_view(){
        $this->resetAfterTest(true);
        $output = $this->course_layout_render(); // get output for student by default

        $this->assertFalse($output->showtab1, 'Activities tab should not displayed for student');
        $this->assertFalse($output->showtab2, 'Resources tab should not displayed for student');
        $this->assertFalse($output->showtab3, 'Student tab should not displayed for student');
        $this->assertFalse($output->showtabs, 'Expected showtabs to be false when no tab1|2|3 are shown');
    }

    public function test_admin_course_view(){
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $output = $this->course_layout_render(); // admin

        $this->assertFalse($output->showtab1, 'Activities tab should not displayed for admin');
        $this->assertFalse($output->showtab2, 'Resources tab should not displayed for admin');
        $this->assertFalse($output->showtab3, 'Student tab should not displayed for admin');
        $this->assertFalse($output->showtabs, 'Expected showtabs to be false when no tab1|2|3 are shown');
    }

    public function test_admin_edit_course_view(){
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $USER->editing = 1; // simulate admin editing, all tabs should show
        $output = $this->course_layout_render(); // admin

        $this->assertTrue($output->showtab1, 'Activities tab should displayed for admin');
        $this->assertTrue($output->showtab2, 'Resources tab should displayed for admin');
        $this->assertTrue($output->showtab3, 'Student tab should displayed for admin');
        $this->assertTrue($output->showtabs, 'Expected showtabs to be true when any or all of tab1|2|3 are shown');
    }

    /**
     * This test should include some resource block under the Resource tab
     * and ensure the Resource tab and its content are visible for student
     */
    public function test_student_course_view_with_resource_tab(){

        $this->markTestIncomplete('TODO: Ensure Resource tab and its content are visible to student when content is available');
    }

    /**
     * Should exciplitly test other layouts
     */
    public function test_other_layouts(){

        $this->markTestIncomplete('TODO: Should also do one test each other layout: dashboard and columns2');
    }

    /**
     * May be useful for future reference.
     *
     * Scenario: User updating password
     * GIVEN user initiated a reset password change or forgotten password
     *  AND user is in the system
     *  AND user can change the password
     * WHEN user click on link in the email sent by the system
     *  AND user presented with an update password form
     *  AND user enters and submits update password
     * THEN user see a message of 'Your password has been set' on a new page
     *  AND user can click continue to go to the user dashboard OR user can wait for the system to redirect to the dashboard
     */
    public function test_password_reset()
    {
        /**
         * Scenario: user click on a link to update password
         *  And Moodle creates a session id
         *  And loads an updating password form
         * When user enters and submits password update
         * Then Moodles updates the new password
         *  And change session id to prevent fixation attack from the same domain
         *  And log user in using the new session id
         *  And shows a new page with the message 'Your password has been set.' with the Continue button
         *  And user can click on this button to continue to my Dashboard OR wait for auto redirect to my Dashboard.
         */
        $this->markTestIncomplete('Moodle redirects URL and changes session id, could not replicate this behaviour in test');
    }
}