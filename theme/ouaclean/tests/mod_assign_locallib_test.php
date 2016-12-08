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

/**
 * Unit tests for (some of) mod/assign/locallib.php.
 *
 * @package    mod_assign
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/assign/tests/locallib_test.php');

/**
 * Unit tests for (some of) mod/assign/locallib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_ouaclean_mod_assign_locallib_testcase extends mod_assign_locallib_testcase {
    public function setUp() {
        global $CFG;
        $CFG->theme = 'ouaclean';

        return parent::setUp();
    }

    /**
     * This is a core test, it is changed to use a language string 'tutor_response' instead of hardcoded 'Feedback'
     *
     * @throws coding_exception
     */
    public function test_show_student_summary() {
        global $CFG, $PAGE;

        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance();
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        // No feedback should be available because this student has not been graded.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Do not show feedback if there is no grade');
        libxml_use_internal_errors(true);
        $dom = simplexml_load_string($output);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $output . "\nErrors: " . var_export(libxml_get_errors(), true));

        // Simulate adding a grade.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        // Now we should see the feedback.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        libxml_use_internal_errors(true);
        $xmltest = "<html>" . str_ireplace(array('&nbsp;'), array(' '), $output) . "</html>";
        $dom = simplexml_load_string($xmltest); //remove &nbsp; and test for xml
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltest . "\nErrors: " . var_export(libxml_get_errors(), true));
        $this->assertNotEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Show feedback if there is a grade');

        // Now hide the grade in gradebook.
        $this->setUser($this->teachers[0]);
        require_once($CFG->libdir . '/gradelib.php');
        $gradeitem = new grade_item(array(
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assign->get_instance()->id,
            'courseid'     => $this->course->id));

        $gradeitem->set_hidden(1, false);

        // No feedback should be available because the grade is hidden.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        libxml_use_internal_errors(true);
        $dom = simplexml_load_string($output);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $output . "\nErrors: " . var_export(libxml_get_errors(), true));
        $this->assertEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Do not show feedback if the grade is hidden in the gradebook');

        // Do the same but add feedback.
        $assign = $this->create_instance(array('assignfeedback_comments_enabled' => 1));

        $this->setUser($this->teachers[0]);
        $grade = $assign->get_user_grade($this->students[0]->id, true);
        $data = new stdClass();
        $data->assignfeedbackcomments_editor = array('text'   => 'Tomato sauce',
                                                     'format' => FORMAT_MOODLE);
        $plugin = $assign->get_feedback_plugin_by_type('comments');
        $plugin->save($grade, $data);

        // Should have feedback but no grade.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        libxml_use_internal_errors(true);
        $dom = simplexml_load_string($output);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $output . "\nErrors: " . var_export(libxml_get_errors(), true));
        $this->assertNotEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Show feedback even if there is no grade');
        $this->assertEquals(false, strpos($output, 'Grade'), 'Do not show grade when there is no grade.');
        $this->assertEquals(false, strpos($output, 'Graded on'), 'Do not show graded date when there is no grade.');

        // Now hide the grade in gradebook.
        $this->setUser($this->teachers[0]);
        $gradeitem = new grade_item(array(
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assign->get_instance()->id,
            'courseid'     => $this->course->id));

        $gradeitem->set_hidden(1, false);

        // No feedback should be available because the grade is hidden.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        libxml_use_internal_errors(true);
        $dom = simplexml_load_string($output);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $output . "\nErrors: " . var_export(libxml_get_errors(), true));
        $this->assertEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Do not show feedback if the grade is hidden in the gradebook');
    }

    public function test_attempt_reopen_method_manual() {
        global $PAGE;

        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('attemptreopenmethod'=>ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
            'maxattempts'=>3,
            'submissiondrafts'=>1,
            'assignsubmission_onlinetext_enabled'=>1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        // Student should be able to see an add submission button.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')));

        // Add a submission.
        $now = time();
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
            'text'=>'Submission text',
            'format'=>FORMAT_MOODLE);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Verify the student cannot make changes to the submission.
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, get_string('addsubmission', 'assign')));

        // Mark the submission.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        // Check the student can see the grade.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, '50.0'));

        // Allow the student another attempt.
        $this->teachers[0]->ignoresesskey = true;
        $this->setUser($this->teachers[0]);
        $result = $assign->testable_process_add_attempt($this->students[0]->id);
        $this->assertEquals(true, $result);

        // Check that the previous attempt is now in the submission history table.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        // Need a better check.
        $this->assertNotEquals(false, strpos($output, 'Submission text'), 'Contains: Submission text');

        // Check that the student now has a button for Add a new attempt".
        $this->assertNotEquals(false, strpos($output, get_string('newattempt', 'theme_ouaclean')));
        // Check that the student now does not have a button for Submit.
        $this->assertEquals(false, strpos($output, get_string('submitassignment', 'assign')));

        // Check that the student now has a submission history.
        $this->assertNotEquals(false, strpos($output, get_string('attempthistory', 'assign')));

        $this->setUser($this->teachers[0]);
        // Check that the grading table loads correctly and contains this user.
        // This is also testing that we do not get duplicate rows in the grading table.
        $gradingtable = new assign_grading_table($assign, 100, '', 0, true);
        $output = $assign->get_renderer()->render($gradingtable);
        $this->assertEquals(true, strpos($output, $this->students[0]->lastname));

        // Should be 1 not 2.
        $this->assertEquals(1, $assign->count_submissions());
        $this->assertEquals(1, $assign->count_submissions_with_status('reopened'));
        $this->assertEquals(0, $assign->count_submissions_need_grading());
        $this->assertEquals(1, $assign->count_grades());

        // Change max attempts to unlimited.
        $formdata = clone($assign->get_instance());
        $formdata->maxattempts = ASSIGN_UNLIMITED_ATTEMPTS;
        $formdata->instance = $formdata->id;
        $assign->update_instance($formdata);

        // Mark the submission again.
        $data = new stdClass();
        $data->grade = '60.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 1);

        // Check the grade exists.
        $grades = $assign->get_user_grades_for_gradebook($this->students[0]->id);
        $this->assertEquals(60, (int)$grades[$this->students[0]->id]->rawgrade);

        // Check we can reopen still.
        $result = $assign->testable_process_add_attempt($this->students[0]->id);
        $this->assertEquals(true, $result);

        // Should no longer have a grade because there is no grade for the latest attempt.
        $grades = $assign->get_user_grades_for_gradebook($this->students[0]->id);
        $this->assertEmpty($grades);

    }

    /**
     * Test reopen behavior when in "Reopen until pass" mode.
     */
    public function test_attempt_reopen_method_untilpass() {
        global $PAGE;
        $newattemptstring = get_string('newattempt', 'theme_ouaclean');

        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('attemptreopenmethod' => ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS,
            'maxattempts' => 3,
            'submissiondrafts' => 1,
            'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        // Set grade to pass to 80.
        $gradeitem = $assign->get_grade_item();
        $gradeitem->gradepass = '80.0';
        $gradeitem->update();

        // Student should be able to see an add submission button.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')));

        // Add a submission.
        $now = time();
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Verify the student cannot make a new attempt.
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, $newattemptstring));

        // Mark the submission as non-passing.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        // Check the student can see the grade.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, '50.0'));

        // Check that the student now has a button for Add a new attempt.
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, $newattemptstring));

        // Check that the student now does not have a button for Submit.
        $this->assertEquals(false, strpos($output, get_string('submitassignment', 'assign')));

        // Check that the student now has a submission history.
        $this->assertNotEquals(false, strpos($output, get_string('attempthistory', 'assign')));

        // Add a second submission.
        $now = time();
        $submission = $assign->get_user_submission($this->students[0]->id, true, 1);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Mark the submission as passing.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '80.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 1);

        // Check that the student does not have a button for Add a new attempt.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, $newattemptstring));

        // Re-mark the submission as not passing.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 1);

        // Check that the student now has a button for Add a new attempt.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, $newattemptstring));

        // Add a submission as a second student.
        $this->setUser($this->students[1]);
        $now = time();
        $submission = $assign->get_user_submission($this->students[1]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[1]->id, true, false);

        // Mark the submission as passing.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '100.0';
        $assign->testable_apply_grade_to_user($data, $this->students[1]->id, 0);

        // Check the student can see the grade.
        $this->setUser($this->students[1]);
        $output = $assign->view_student_summary($this->students[1], true);
        $this->assertNotEquals(false, strpos($output, '100.0'));

        // Check that the student does not have a button for Add a new attempt.
        $output = $assign->view_student_summary($this->students[1], true);
        $this->assertEquals(false, strpos($output, $newattemptstring));

        // Set grade to pass to 0, so that no attempts should reopen.
        $gradeitem = $assign->get_grade_item();
        $gradeitem->gradepass = '0';
        $gradeitem->update();

        // Add another submission.
        $this->setUser($this->students[2]);
        $now = time();
        $submission = $assign->get_user_submission($this->students[2]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_MOODLE);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[2]->id, true, false);

        // Mark the submission as graded.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '0.0';
        $assign->testable_apply_grade_to_user($data, $this->students[2]->id, 0);

        // Check the student can see the grade.
        $this->setUser($this->students[2]);
        $output = $assign->view_student_summary($this->students[2], true);
        $this->assertNotEquals(false, strpos($output, '0.0'));

        // Check that the student does not have a button for Add a new attempt.
        $output = $assign->view_student_summary($this->students[2], true);
        $this->assertEquals(false, strpos($output, $newattemptstring));
    }

    /**
     * Test for group submisisons when done.
     *
     * @throws coding_exception
     */
    public function test_group_submissions_submit_for_marking() {
        global $PAGE;
        $this->markTestSkipped("We Don't do custom renderer for group submissions yet");
        $this->create_extra_users();
        // Now verify group assignments.
        $this->setUser($this->editingteachers[0]);
        $time = time();
        $assign = $this->create_instance(array('teamsubmission'                      => 1,
                                               'assignsubmission_onlinetext_enabled' => 1,
                                               'submissiondrafts'                    => 1,
                                               'requireallteammemberssubmit'         => 0,
                                               'duedate'                             => $time - 2 * 24 * 60 * 60));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        $this->setUser($this->extrastudents[0]);
        // Add a submission.
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text'   => 'Submission text',
                                         'format' => FORMAT_MOODLE);

        $notices = array();
        $assign->save_submission($data, $notices);

        // Check we can see the submit button.
        $output = $assign->view_student_summary($this->extrastudents[0], true);
        $this->assertContains(get_string('submitassignment', 'assign'), $output);
        // $this->assertContains(get_string('assess:timeremaining', 'theme_ouaclean'), $output); // OUA Custom: We dont show time remaining when overdue.
        $difftime = time() - $time;
        $this->assertContains(get_string('assess:submissionoverdueby', 'theme_ouaclean'), $output); // Group submissions are using default renderer
        $this->assertContains(format_time(2 * 24 * 60 * 60 + $difftime), $output);

        $submission = $assign->get_group_submission($this->extrastudents[0]->id, 0, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->extrastudents[0]->id, true, true);

        // Check that the student does not see "Submit" button.
        $output = $assign->view_student_summary($this->extrastudents[0], true);
        $this->assertNotContains(get_string('submitassignment', 'assign'), $output);

        // Change to another user in the same group.
        $this->setUser($this->extrastudents[self::GROUP_COUNT]);
        $output = $assign->view_student_summary($this->extrastudents[self::GROUP_COUNT], true);
        $this->assertNotContains(get_string('submitassignment', 'assign'), $output);

        // Check that time remaining is not overdue.
//        $this->assertContains(get_string('assess:timeremaining', 'theme_ouaclean'), $output); // OUA Custom: We don't show time remaining for overdue.
        $difftime = time() - $time;
        $this->assertContains(format_time(2 * 24 * 60 * 60 + $difftime), $output);

        $submission = $assign->get_group_submission($this->extrastudents[self::GROUP_COUNT]->id, 0, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->extrastudents[self::GROUP_COUNT]->id, true, true);
        $output = $assign->view_student_summary($this->extrastudents[self::GROUP_COUNT], true);
        $this->assertNotContains(get_string('submitassignment', 'assign'), $output);
    }

    public function test_oua_theme_submissions_open_no_due_date_no_start_date() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        /*
         * No start date, No due date.
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status when no due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, '--'), 'Display -- when no due date');
        $this->assertEquals(false, strpos($output, get_string('assess:submissionopenin', 'theme_ouaclean')), 'Dont display countdown when no due date');
    }

    public function test_oua_theme_submissions_open_tommorow_due_next_week() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        /**
         * Open date tommorow, Due date next week
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('allowsubmissionsfromdate'            => $tomorrow,
                                               'duedate'                             => $oneweek,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, $assign->testable_submissions_open($this->students[0]->id));
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_notyetopen', 'theme_ouaclean')), 'Submission status shows "Not yet open"  when submissions not open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionopendate', 'theme_ouaclean')), 'Open Date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($tomorrow, '%e %B %Y, %l:%M %p')), 'The user date is displayed for the open date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionopenin', 'theme_ouaclean')), 'Assessment opens in header is displayed');
        $countdowndate = userdate($tomorrow, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertNotEquals(false, strpos($output, $countdown), 'Countdown timer is displayed');
        $this->assertEquals(false, strpos($output, 'due1day'), 'Due1day class is not applied');
        $this->assertEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is NOT displayed');
    }

    public function test_oua_theme_submissions_open_no_start_date_due_1day() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        /**
         * No Start Date, Due date tomorrow
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $tomorrow,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($tomorrow, '%e %B %Y, %l:%M %p')), 'The user date is displayed for due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is displayed');
        $countdowndate = userdate($tomorrow, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertNotEquals(false, strpos($output, $countdown), 'Countdown timer is displayed');
        $this->assertNotEquals(false, strpos($output, 'due1day'), 'Due1day class is applied');
        $this->assertEquals(false, strpos($output, get_string('assess:submissionopenin', 'theme_ouaclean')), 'Display countdown when due date');
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is displayed');
    }

    public function test_oua_theme_submissions_open_due_2days() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        /**
         * No Start Date, Due date two days
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $twodays,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($twodays, '%e %B %Y, %l:%M %p')), 'The user date is displayed for due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is displayed');
        $countdowndate = userdate($twodays, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertNotEquals(false, strpos($output, $countdown), 'Countdown timer is displayed');
        $this->assertNotEquals(false, strpos($output, 'due2days'), 'Due2days class is applied');
        $this->assertEquals(false, strpos($output, get_string('assess:submissionopenin', 'theme_ouaclean')), 'Display countdown when due date');
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is displayed');
    }

    public function test_oua_theme_submissions_open_due_next_week() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        /**
         * No Start Date, Due date next week
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $oneweek,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($oneweek, '%e %B %Y, %l:%M %p')), 'The user date is displayed for due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is displayed');
        $countdowndate = userdate($oneweek, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertNotEquals(false, strpos($output, $countdown), 'Countdown timer is displayed');
        $this->assertEquals(false, strpos($output, get_string('assess:submissionopenin', 'theme_ouaclean')), 'Display countdown when due date');
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is displayed');
    }

    public function test_oua_theme_submissions_overdue_nocutoff() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        /*
         * No start date,
         * Due date yesterday -> overdue
         * No Cut off date
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $yesterday,
                                               'assignsubmission_onlinetext_enabled' => 1
        ));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($yesterday, '%e %B %Y, %l:%M %p')), 'The user date is displayed for due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionoverdueby', 'theme_ouaclean')), 'Overdueby header is displayed');
        $this->assertNotEquals(false, strpos($output, format_time($now - $yesterday)), 'The overdue time is displayed');
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is displayed');
    }

    public function test_oua_theme_submissions_due_yesterday_cutoff_tommorow() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        /*
         * Due date yesterday, cut off date tomorrow,
         * Student can still submit
         */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $yesterday,
                                               'cutoffdate'                          => $tomorrow,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->setUser($this->students[0]);
        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($yesterday, '%e %B %Y, %l:%M %p')), 'The user date is displayed for due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionoverdueby', 'theme_ouaclean')), 'Overdueby header is displayed');
        $this->assertNotEquals(false, strpos($output, format_time($now - $yesterday)), 'The overdue time is displayed');
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is displayed');
    }

    public function test_oua_theme_submissions_cutoff_date_passed() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        /*
        /* Cutoff date passed */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $oneweekago,
                                               'cutoffdate'                          => $yesterday,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->assertEquals(false, $assign->testable_submissions_open($this->students[0]->id));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($oneweekago, '%e %B %Y, %l:%M %p')), 'The user date is displayed for overdue date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionoverdueby', 'theme_ouaclean')), 'Overdueby header is displayed');
        $this->assertNotEquals(false, strpos($output, format_time($now - $oneweekago)), 'The overdue time is displayed');
        $this->assertNotEquals(false, strpos($output, get_string('submissionsclosed', 'mod_assign')), 'Submissions Closed is displayed');
        $this->assertEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is Not displayed');
    }

    public function test_oua_theme_submissions_cutoff_date_passed_with_student_extension() {
        global $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        /*
            /* When user has been granted an extension, the due date should display , and submisisons should be open
            */
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $oneweekago,
                                               'cutoffdate'                          => $yesterday,
                                               'assignsubmission_onlinetext_enabled' => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));
        $this->assertEquals(false, $assign->testable_submissions_open($this->students[0]->id));
        $assign->testable_save_user_extension($this->students[0]->id, $tomorrow);

        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_new', 'theme_ouaclean')), 'Submission status shows no submission when not submitted and submissions open');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionduedate', 'theme_ouaclean')), 'Due date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($tomorrow, '%e %B %Y, %l:%M %p')), 'The user date is displayed for due date');
        $this->assertNotEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is displayed');
        $countdowndate = userdate($tomorrow, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertNotEquals(false, strpos($output, $countdown), 'Countdown timer is displayed');
        $this->assertNotEquals(false, strpos($output, 'due1day'), 'Due1day class is applied');
        $this->assertEquals(false, strpos($output, get_string('assess:submissionopenin', 'theme_ouaclean')), 'Display countdown when due date');
        $this->assertNotEquals(false, strpos($output, get_string('addsubmission', 'assign')), 'Add Submission button is displayed');
    }

    /**
     * Tests an assignment submission through different stages, by updating the assignment
     *
     * @throws coding_exception
     */
    public function test_oua_theme_submissions_through_different_stages() {
        global $CFG, $PAGE;

        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance(array('duedate'                             => $twodays,
                                               'cutoffdate'                          => $oneweek,
                                               'assignsubmission_onlinetext_enabled' => 1,
                                               'submissiondrafts'                    => 1));
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        $this->assertEquals(true, $assign->testable_submissions_open($this->students[0]->id));

        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $submittedtime = $submission->timemodified;
        // TODO: ASSIGN_SUBMISSION_STATUS_DRAFT Test assign submission status draft

        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submission status shows submitted when  submitted and submissions open, closed cutoff or locked');
        $this->assertNotEquals(false, strpos($output, 'submissionnotgraded'), 'submissionnotgraded class is applied');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submitted date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($submittedtime, '%e %B %Y, %l:%M %p')), 'The user date is displayed for submitted datetime');
        $this->assertEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is NOT displayed');
        $this->assertNotEquals(false, strpos($output, get_string('assess:gradingstatus', 'theme_ouaclean')), 'Grading status header is displayed');

        $this->setUser($this->editingteachers[0]);
        $this->assertEquals(false, $assign->testable_submissions_open($this->students[0]->id));

        // Test Submitted view one day past due date
        $this->setUser($this->editingteachers[0]);
        $updateassign = $assign->get_instance();
        $updateassign->instance = $updateassign->id;
        $updateassign->duedate = $yesterday;
        $assign->update_instance($updateassign);
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submission status shows submitted when  submitted and submissions open, closed cutoff or locked');
        $this->assertNotEquals(false, strpos($output, 'submissionnotgraded'), 'submissionnotgraded class is applied');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submitted date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($submittedtime, '%e %B %Y, %l:%M %p')), 'The user date is displayed for submitted datetime');
        $this->assertEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is NOT displayed');
        $this->assertNotEquals(false, strpos($output, get_string('assess:gradingstatus', 'theme_ouaclean')), 'Grading status header is displayed');

        $this->assertEquals(false, strpos($output, 'due1day'), 'Due1day class is NOT applied');

        $this->setUser($this->editingteachers[0]);
        $this->assertEquals(false, $assign->testable_submissions_open($this->students[0]->id));

        // Test Submitted view one week past due date
        $this->setUser($this->editingteachers[0]);
        $updateassign = $assign->get_instance();
        $updateassign->instance = $updateassign->id;
        $updateassign->duedate = $oneweekago;
        $assign->update_instance($updateassign);
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submission status shows submitted when  submitted and submissions open, closed cutoff or locked');
        $this->assertNotEquals(false, strpos($output, 'submissionnotgraded'), 'submissionnotgraded class is applied');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submitted date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($submittedtime, '%e %B %Y, %l:%M %p')), 'The user date is displayed for submitted datetime');
        $this->assertEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is NOT displayed');
        $this->assertNotEquals(false, strpos($output, get_string('assess:gradingstatus', 'theme_ouaclean')), 'Grading status header is displayed');

        $this->assertEquals(false, strpos($output, 'due1day'), 'Due1day class is NOT applied');

        // Test view one week past cutoff date
        $this->setUser($this->editingteachers[0]);
        $updateassign = $assign->get_instance();
        $updateassign->instance = $updateassign->id;
        $updateassign->duedate = $oneweekago;
        $updateassign->cutoffdate = $oneweekago;
        $assign->update_instance($updateassign);
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submission status shows submitted when  submitted and submissions open, closed cutoff or locked');
        $this->assertNotEquals(false, strpos($output, 'submissionnotgraded'), 'submissionnotgraded class is applied');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submitted date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($submittedtime, '%e %B %Y, %l:%M %p')), 'The user date is displayed for submitted datetime');
        $this->assertEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is NOT displayed');
        $this->assertNotEquals(false, strpos($output, get_string('assess:gradingstatus', 'theme_ouaclean')), 'Grading status header is displayed');

        $this->assertEquals(false, strpos($output, 'due1day'), 'Due1day class is NOT applied');

        // Test Graded.
        $this->setUser($this->editingteachers[0]);
        $data = new StdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);
        $this->assertEquals(true, $assign->testable_is_graded($this->students[0]->id));
        $this->assertEquals(false, $assign->testable_is_graded($this->students[1]->id));

        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);

        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus', 'theme_ouaclean')), '1st box header shows Submission status');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submission status shows submitted when  submitted and submissions open, closed cutoff or locked');
        $this->assertNotEquals(false, strpos($output, 'submissiongraded'), 'submissiongraded class is applied');
        $this->assertNotEquals(false, strpos($output, get_string('assess:submissionstatus_submitted', 'theme_ouaclean')), 'Submitted date header is displayed');
        $this->assertNotEquals(false, strpos($output, userdate($submittedtime, '%e %B %Y, %l:%M %p')), 'The user date is displayed for submitted datetime');
        $this->assertEquals(false, strpos($output, get_string('assess:timeremaining', 'theme_ouaclean')), 'Time remaining header is NOT displayed');
        $this->assertNotEquals(false, strpos($output, get_string('grade')), 'Grade header is displayed');
        $this->assertNotEquals(false, strpos($output, $assign->display_grade('50.0', 0, $this->students[0]->id)), 'Grade is displayed');

        $this->assertEquals(false, strpos($output, 'due1day'), 'Due1day class is NOT applied');

        // Hide grade if grade is hidden in gradebook
        $this->setUser($this->editingteachers[0]);
        require_once($CFG->libdir . '/gradelib.php');
        $gradeitem = new grade_item(array(
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assign->get_instance()->id,
            'courseid'     => $this->course->id));

        $gradeitem->set_hidden(1, false);
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('grade')), 'Grade header is displayed');
        $this->assertNotEquals(false, strpos($output, get_string(ASSIGN_GRADING_STATUS_GRADED, 'assign')), 'Grading status graded is displayed');
        $this->assertEquals(false, strpos($output, $assign->display_grade('50.0', 0, $this->students[0]->id)), 'Grade is NOT displayed');
    }

    /**
     * GIVEN we have an assignment submission
     * WHEN there is feedback
     * THEN feedback should be either shown or hidden depending on grade statuses
     *
     * @throws coding_exception
     */
    public function test_different_statuses_on_show_student_summary() {
        global $CFG, $PAGE;

        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance();
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        // No feedback should be available because this student has not been graded.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Do not show feedback if there is no grade');
        // Simulate adding a grade.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        // Now we should see the feedback.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Show feedback if there is a grade');

        // Now hide the grade in gradebook.
        $this->setUser($this->teachers[0]);
        require_once($CFG->libdir . '/gradelib.php');
        $gradeitem = new grade_item(array(
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assign->get_instance()->id,
            'courseid'     => $this->course->id));

        $gradeitem->set_hidden(1, false);

        // No feedback should be available because the grade is hidden.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Do not show feedback if the grade is hidden in the gradebook');

        // Do the same but add feedback.
        $assign = $this->create_instance(array('assignfeedback_comments_enabled' => 1));

        $this->setUser($this->teachers[0]);
        $grade = $assign->get_user_grade($this->students[0]->id, true);
        $data = new stdClass();
        $data->assignfeedbackcomments_editor = array('text'   => 'Tomato sauce',
                                                     'format' => FORMAT_MOODLE);
        $plugin = $assign->get_feedback_plugin_by_type('comments');
        $plugin->save($grade, $data);

        // Should have feedback but no grade.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertNotEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Show feedback even if there is no grade');
        $this->assertEquals(false, strpos($output, 'Grade'), 'Do not show grade when there is no grade.');
        $this->assertEquals(false, strpos($output, 'Graded on'), 'Do not show graded date when there is no grade.');

        // Now hide the grade in gradebook.
        $this->setUser($this->teachers[0]);
        $gradeitem = new grade_item(array(
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assign->get_instance()->id,
            'courseid'     => $this->course->id));

        $gradeitem->set_hidden(1, false);

        // No feedback should be available because the grade is hidden.
        $this->setUser($this->students[0]);
        $output = $assign->view_student_summary($this->students[0], true);
        $this->assertEquals(false, strpos($output, get_string('tutor_response', 'theme_ouaclean')), 'Do not show feedback if the grade is hidden in the gradebook');
    }

}

