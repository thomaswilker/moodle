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

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class mod_quiz_holding_page_testcase extends advanced_testcase {
    /** @const Default number of students to create */
    const DEFAULT_STUDENT_COUNT = 3;
    /** @const Default number of teachers to create */
    const DEFAULT_TEACHER_COUNT = 2;
    /** @const Default number of editing teachers to create */
    const DEFAULT_EDITING_TEACHER_COUNT = 2;
    /** @const Optional extra number of students to create */
    const EXTRA_STUDENT_COUNT = 40;
    /** @const Optional number of suspended students */
    const EXTRA_SUSPENDED_COUNT = 10;
    /** @const Optional extra number of teachers to create */
    const EXTRA_TEACHER_COUNT = 5;
    /** @const Optional extra number of editing teachers to create */
    const EXTRA_EDITING_TEACHER_COUNT = 5;
    /** @const Number of groups to create */
    const GROUP_COUNT = 6;

    public function setUp() {
        global $CFG, $DB, $PAGE;
        $CFG->theme = 'ouaclean';
        $this->renderer = $PAGE->get_renderer('mod_quiz', null, RENDERER_TARGET_GENERAL); // Ensure not cli renderer

        $this->resetAfterTest(true);

        $this->startbtn = '<input type="submit" value="' . get_string('attemptquiznow', 'quiz') . '" class=" primary_btn rightarrow"';
        $this->continueattemptbtn = '<input type="submit" value="' . get_string('continueattemptquiz', 'quiz') . '" class=" primary_btn rightarrow"';
        $this->reattemptbtn = '<input type="submit" value="' . get_string('reattemptquiz', 'quiz') . '" class=" primary_btn rightarrow"';

        /*
         * This setup is copied from the Assign test, it setups upa a category, course, teachers and students.
           This is needed because to view/attempt a quiz students require a privilege that is given when they are enrolled
         */
        $this->category = $this->getDataGenerator()->create_category(array('name' => 'Template'));
        $this->course = $this->getDataGenerator()->create_course(array('category' => $this->category->id));
        $this->teachers = array();
        for ($i = 0; $i < self::DEFAULT_TEACHER_COUNT; $i++) {
            array_push($this->teachers, $this->getDataGenerator()->create_user());
        }

        $this->editingteachers = array();
        for ($i = 0; $i < self::DEFAULT_EDITING_TEACHER_COUNT; $i++) {
            array_push($this->editingteachers, $this->getDataGenerator()->create_user());
        }

        $this->students = array();
        for ($i = 0; $i < self::DEFAULT_STUDENT_COUNT; $i++) {
            array_push($this->students, $this->getDataGenerator()->create_user());
        }

        $this->groups = array();
        for ($i = 0; $i < self::GROUP_COUNT; $i++) {
            array_push($this->groups, $this->getDataGenerator()->create_group(array('courseid' => $this->course->id)));
        }

        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        foreach ($this->teachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                $this->course->id,
                $teacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        foreach ($this->editingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user($editingteacher->id,
                $this->course->id,
                $editingteacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $editingteacher);
        }

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        foreach ($this->students as $i => $student) {
            $this->getDataGenerator()->enrol_user($student->id,
                $this->course->id,
                $studentrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $student);
        }
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has a start date in the future
     *  AND does not have a close date.
     * THEN The awesomebar displays submission status
     *  AND the awesomebar displays open date
     *  AND the awesomebar displays countdown to open
     *  AND start assessment button is NOT shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *  AND no close date message should be displayed
     *
     * @test
     */
    public function test_quiz_not_open() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        $this->resetAfterTest(true);

        $this->setUser($this->editingteachers[0]);
        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array(
            'questionsperpage' => 0,
            'grade'            => 100.0,
            'sumgrades'        => 2,
            'timeopen'         => $oneweek));

        // Create a quiz for this user.
        $quizobj = quiz::create($quiz->id, $this->students[0]->id);
        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Test the awesomebar displays Correctly.
        $this->assertNotContains(get_string('quizisclosed', 'mod_quiz'), $output, 'Submission closed message not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus', 'theme_ouaclean'), $output, 'Submission status header not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus_notyetopen', 'theme_ouaclean'), $output, 'Submission status must show "Not yet open" string when submissions not open');
        $this->assertContains(get_string('assess:submissionopendate', 'theme_ouaclean'), $output, 'The header for quiz open date is not as expected');
        $this->assertContains(userdate($oneweek, '%e %B %Y, %l:%M %p'), $output, 'The Open date is not being displayed or is in an unexpected format');
        $this->assertContains(get_string('assess:submissionopenin', 'theme_ouaclean'), $output, 'Assessment opens string is not displayed in header as expected');
        $countdowndate = userdate($oneweek, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;

        $this->assertContains($countdown, $output, 'Countdown timer is not found in the awesomebar');

        // Test the rest of the page

        // Test number of questions shows in the table
        // $this->assertEquals(3, substr_count($output, '</tr>'), 'There should be two table rows for questions');

        // Shows number of questions in quiz.
        $this->assertContains(get_string('numquestionsx', 'quiz', 2), $output, 'Number of questions is not displayed or is in a different format than expected');

        // Shows attempts allowed for quiz
        $this->assertContains(get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts), $output, 'Number of attempts is not displayed or is in a different format than expected');

        libxml_use_internal_errors(true);
        $xmltestoutput = "<html>$output</html>";
        $dom = simplexml_load_string($xmltestoutput);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltestoutput . "\nErrors: " . var_export(libxml_get_errors(), true));

        $this->assertEmpty($viewobj->buttontext);
        // Buttons Don't show.
        $this->assertContains('<div class="box quizattempt btn-container">', $output, 'The Button Container does not exist (it should exist without buttons)');
        $this->assertNotContains('primary_btn', $output, 'There should be NO buttons displayed');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has a start date
     *  AND the user has not yet attempted the quiz
     * THEN The awesomebar displays submission status as "Not submitted"
     *  AND the awesomebar displays the due date
     *  AND the awesomebar displays countdown to due date
     *  AND start assessment button is shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_open_not_attempted() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        $this->resetAfterTest(true);
        $this->setUser($this->editingteachers[0]);
        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'timeopen'         => $yesterday,
                                                                                      'timeclose'        => $oneweek));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);
        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Test the awesomebar displays Correctly.
        $this->assertContains(get_string('assess:submissionstatus', 'theme_ouaclean'), $output, 'Submission status header not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus_new', 'theme_ouaclean'), $output, 'Submission status must show "Not Submitted" string when submissions open but not submitted');
        $this->assertContains(get_string('assess:submissionduedate', 'theme_ouaclean'), $output, 'Due Date header is not displayed as expected');
        $this->assertContains(userdate($oneweek, '%e %B %Y, %l:%M %p'), $output, 'The due date is not being displayed or is in an unexpected format');
        $this->assertContains(get_string('assess:timeremaining', 'theme_ouaclean'), $output, 'Time remaining should be displayed in 3rd box header');
        $countdowndate = userdate($oneweek, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertContains($countdown, $output, 'Countdown timer is displayed');

        // Test number of questions shows in the table
        // $this->assertEquals(3, substr_count($output, '</tr>'), 'There should be two table rows for questions');

        // Shows number of questions in quiz.
        $this->assertContains(get_string('numquestionsx', 'quiz', 2), $output, 'Number of questions is not displayed or is in a different format than expected');

        // Shows attempts allowed for quiz
        $this->assertContains(get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts), $output, 'Number of attempts is not displayed or is in a different format than expected');
        libxml_use_internal_errors(true);

        $xmltestoutput = "<html>$output</html>";
        $dom = simplexml_load_string($xmltestoutput);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltestoutput . "\nErrors: " . var_export(libxml_get_errors(), true));

        // Buttons Show correctly.

        $this->assertContains(get_string('attemptquiznow', 'quiz'), $viewobj->buttontext);
        $this->assertContains($this->startbtn, $output, 'The start quiz button does not exist');
        $this->assertNotContains($this->continueattemptbtn, $output, 'There continue button should not be displayed');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has a start date
     *  AND the user has not yet completed the quiz
     *  AND there are less than two days but more than one day before quiz closes
     * THEN The awesomebar has the due2days class applied
     *  AND The awesomebar displays submission status as "Not submitted"
     *  AND the awesomebar displays the due date
     *  AND the awesomebar displays countdown to due date
     *  AND start assessment button is shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_open_not_attempted_2days_to_go() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;

        // Test 2 days to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'timeopen'         => $yesterday,
                                                                                      'timeclose'        => $twodays));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);
        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);
        $this->assertContains(get_string('attemptquiznow', 'quiz'), $viewobj->buttontext);
        $this->assertContains($this->startbtn, $output, 'The start quiz button does not exist');
        $this->assertContains('due2days', $output, 'Due2days class is NOT applied');
        $this->assertNotContains('due1day', $output, 'Due1day class is applied');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has a start date
     *  AND the user has not yet completed the quiz
     *  AND there is less than one day before quiz closes
     * THEN The awesomebar has the due1day class applied
     *  AND The awesomebar displays submission status as "Not submitted"
     *  AND the awesomebar displays the due date
     *  AND the awesomebar displays countdown to due date
     *  AND start assessment button is shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_open_not_attempted_1day_to_go() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test 1 day to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'timeopen'         => $yesterday,
                                                                                      'timeclose'        => $tomorrow));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);
        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);
        $this->assertContains(get_string('attemptquiznow', 'quiz'), $viewobj->buttontext);
        $this->assertContains($this->startbtn, $output, 'The start quiz button does not exist');
        $this->assertNotContains('due2days', $output, 'Due2days class is applied and should not be');
        $this->assertContains('due1day', $output, 'Due1day class is NOT applied');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has no due date and has started
     *  AND the user has not yet completed the quiz
     *  AND there is less than one day before quiz closes
     * THEN The awesomebar displays submission status as "Not submitted"
     *  AND the awesomebar displays -- instead of the due date
     *  AND the awesomebar displays nothing instead of the countdown to due date
     *  AND start assessment button is shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_open_not_attempted_noduedate() {
        global $DB, $PAGE;
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test no due date
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'timeopen'         => $yesterday));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);
        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);
        $this->assertContains('--', $output, 'Display -- when no due date');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has started
     *  AND only 1 quiz attempt is allowed
     *  AND the user has completed the quiz attempt
     *  AND the quiz is graded
     * THEN The awesomebar displays submission status as "Submitted"
     *  AND the awesomebar displays the submitted date (last modified)
     *  AND the awesomebar displays the grade if graded
     *  AND start assessment button is NOT shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_submitted_only_1_attempt_allowed() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test 1 day to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'attempts'         => 1,
                                                                                      'timeopen'         => $yesterday,
                                                                                      'timeclose'        => $tomorrow));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);

        // Start the attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $this->students[0]->id);

        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $this->assertEquals('1,2,0', $attempt->layout);

        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertFalse($attemptobj->has_response_to_at_least_one_graded_question());

        $prefix1 = $quba->get_field_prefix(1);
        $prefix2 = $quba->get_field_prefix(2);

        $tosubmit = array(1 => array('answer' => 'frog'),
                          2 => array('answer' => '3.14'));

        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Test the awesomebar displays Correctly.
        $this->assertContains(get_string('assess:submissionstatus', 'theme_ouaclean'), $output, 'Submission status header not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus_submitted', 'theme_ouaclean'), $output, 'Submission status must show "Submitted" string when submissions submitted');
        $this->assertContains(get_string('assess:submitted', 'theme_ouaclean'), $output, 'Submitted header is not displayed as expected');
        $this->assertContains(userdate($timenow, '%e %B %Y, %l:%M %p'), $output, 'The submitted date is not being displayed or is in an unexpected format');
        $this->assertContains(get_string('grade'), $output, 'Grade should be displayed in 3rd box header');
        $this->assertContains((string) $viewobj->mygrade, $output, 'Grade should be displayed in 3rd box header');
        $this->assertNotContains('<div data-countdown=', $output, 'Countdown timer should NOT be displayed');

        // Test number of questions shows in the table
        // $this->assertEquals(3, substr_count($output, '</tr>'), 'There should be two table rows for questions');

        // Shows number of questions in quiz.
        $this->assertContains(get_string('numquestionsx', 'quiz', 2), $output, 'Number of questions is not displayed or is in a different format than expected');

        // Shows attempts allowed for quiz
        $this->assertContains(get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts), $output, 'Number of attempts is not displayed or is in a different format than expected');
        libxml_use_internal_errors(true);

        $xmltestoutput = "<html>$output</html>";
        $dom = simplexml_load_string($xmltestoutput);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltestoutput . "\nErrors: " . var_export(libxml_get_errors(), true));

        $this->assertEmpty($viewobj->buttontext, 'There should be no button text, as no more attempts');
        $this->assertNotContains($this->startbtn, $output, 'The start quiz button should not exist');
        $this->assertContains(get_string('nomoreattempts', 'quiz'), $output, 'No more attempts string is not being displayed');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has started
     *  AND multiple quiz attempts are allowed
     *  AND the user has completed the quiz attempt
     *  AND the quiz is graded
     * THEN The awesomebar displays submission status as "Submitted"
     *  AND the awesomebar displays the submitted date (last modified)
     *  AND the awesomebar displays the grade if graded
     *  AND start another assessment button is shown
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_submitted_more_attempts_allowed() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test 1 day to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'attempts'         => 3,
                                                                                      'timeopen'         => $yesterday,
                                                                                      'timeclose'        => $oneweek));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);

        // Start the attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $this->students[0]->id);

        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $this->assertEquals('1,2,0', $attempt->layout);

        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertFalse($attemptobj->has_response_to_at_least_one_graded_question());

        $prefix1 = $quba->get_field_prefix(1);
        $prefix2 = $quba->get_field_prefix(2);

        $tosubmit = array(1 => array('answer' => 'frog'),
                          2 => array('answer' => '3.14'));

        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Test the awesomebar displays Correctly.
        $this->assertContains(get_string('assess:submissionstatus', 'theme_ouaclean'), $output, 'Submission status header not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus_submitted', 'theme_ouaclean'), $output, 'Submission status must show "Submitted" string when submissions submitted');
        $this->assertContains(get_string('assess:submissionduedate', 'theme_ouaclean'), $output, 'Due Date header is not displayed as expected');
        $this->assertContains(userdate($oneweek, '%e %B %Y, %l:%M %p'), $output, 'The due date is not being displayed or is in an unexpected format');
        $this->assertContains(get_string('assess:timeremaining', 'theme_ouaclean'), $output, 'Time remaining should be displayed in 3rd box header');
        $countdowndate = userdate($oneweek, '%Y/%m/%d %T');
        $countdown = <<<COUNTDOWN
<div data-countdown="$countdowndate" class="cm-countdown" id="countdown"></div>
COUNTDOWN;
        $this->assertContains($countdown, $output, 'Countdown timer is displayed');

        // Shows summary of previous attempts
        // $this->assertEquals(3, substr_count($output, '</tr>'), 'There should be two table rows for questions');

        // Shows number of questions in quiz.
        $this->assertContains(get_string('numquestionsx', 'quiz', 2), $output, 'Number of questions is not displayed or is in a different format than expected');

        // Shows attempts allowed for quiz
        $this->assertContains(get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts), $output, 'Number of attempts is not displayed or is in a different format than expected');
        libxml_use_internal_errors(true);

        $xmltestoutput = "<html>$output</html>";
        $dom = simplexml_load_string($xmltestoutput);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltestoutput . "\nErrors: " . var_export(libxml_get_errors(), true));

        $this->assertNotEmpty($viewobj->buttontext, 'There should be a try another attempt button');
        $this->assertContains($this->reattemptbtn, $output, 'The start quiz button should exist');
        $this->assertContains(get_string('reviewthisattempt', 'quiz'), $output, 'Review attempt link should be displayed for previous attempts');

        $this->assertNotContains(get_string('nomoreattempts', 'quiz'), $output, 'No more attempts string is not being displayed');
    }

    /**
     * GIVEN we have a quiz that has questions with manual grading
     * WHEN the quiz has started
     *  AND the user has completed the quiz attempt
     *  AND the quiz attempt has not been graded
     * THEN The awesomebar displays submission status as "Submitted"
     *  AND the awesomebar displays the submitted date (last modified)
     *  AND the awesomebar displays "not yet graded" string
     *  AND number of questions in the quiz is shown
     *  AND number of attempts allowed for the quiz is shown
     *
     * @test
     */
    public function test_quiz_submitted_and_not_graded() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test 1 day to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 3,
                                                                                      'attempts'         => 1,
                                                                                      'timeopen'         => $yesterday,
                                                                                      'timeclose'        => $oneweek,
                                                                                      'essayq'           => true));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);

        // Start the attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $this->students[0]->id);

        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $this->assertEquals('1,2,3,0', $attempt->layout);

        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertFalse($attemptobj->has_response_to_at_least_one_graded_question());

        $prefix1 = $quba->get_field_prefix(1);
        $prefix2 = $quba->get_field_prefix(2);

        $tosubmit = array(1 => array('answer' => 'frog'),
                          2 => array('answer' => '3.14'),
                          3 => array('answerformat' => 'this is my essayq'));

        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);

        $attemptobj->process_finish($timenow, false);

        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Test the awesomebar displays Correctly.
        $this->assertContains(get_string('assess:submissionstatus', 'theme_ouaclean'), $output, 'Submission status header not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus_submitted', 'theme_ouaclean'), $output, 'Submission status must show "Submitted" string when submissions submitted');
        $this->assertContains(get_string('assess:submitted', 'theme_ouaclean'), $output, 'Submitted header is not displayed as expected');
        $this->assertContains(userdate($timenow, '%e %B %Y, %l:%M %p'), $output, 'The submitted date is not being displayed or is in an unexpected format');
        $this->assertContains(get_string('grade'), $output, 'Grade should be displayed in 3rd box header');
        $this->assertContains(quiz_format_grade($quiz, $viewobj->mygrade), $output, 'Grade should be displayed in 3rd box header');
        $this->assertNotContains('<div data-countdown=', $output, 'Countdown timer should NOT be displayed');

        // Test number of questions shows in the table
        // $this->assertEquals(3, substr_count($output, '</tr>'), 'There should be two table rows for questions');

        // Shows number of questions in quiz.
        $this->assertContains(get_string('numquestionsx', 'quiz', 3), $output, 'Number of questions is not displayed or is in a different format than expected');

        // Shows attempts allowed for quiz
        $this->assertContains(get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts), $output, 'Number of attempts is not displayed or is in a different format than expected');
        libxml_use_internal_errors(true);

        $xmltestoutput = "<html>$output</html>";
        $dom = simplexml_load_string($xmltestoutput);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltestoutput . "\nErrors: " . var_export(libxml_get_errors(), true));

        $this->assertEmpty($viewobj->buttontext, 'There should be no button text, as no more attempts');
        $this->assertNotContains($this->startbtn, $output, 'The start quiz button should not exist');
        $this->assertContains(get_string('nomoreattempts', 'quiz'), $output, 'No more attempts string is not being displayed');

        $this->markTestIncomplete(
            'This test has not been fully implemented yet. Need to be able to make an essay question that is not graded immediately.'
        );
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has closed
     *  AND the user has NOT attempted
     * THEN The awesomebar displays submission status as "Not Submitted"
     *  AND the awesomebar displays the (over)due date
     *  AND the awesomebar displays how long overdue the quiz was
     *
     * @test
     */
    public function test_quiz_not_submitted_and_due_date_passed() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test 1 day to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'timeopen'         => $oneweekago,
                                                                                      'timeclose'        => $yesterday));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);
        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Should display Quiz is closed message.
        $this->assertContains(get_string('quizisclosed', 'mod_quiz'), $output, 'Submission closed message not found or in unexpected format');
        $this->assertEmpty($viewobj->buttontext);
        $this->assertNotContains($this->startbtn, $output, 'The start quiz button does not exist');
        $this->assertContains('due1day', $output, 'Due1day class is NOT applied');
    }

    /**
     * GIVEN we have a quiz
     * WHEN the quiz has closed
     *  AND the user has completed the quiz attempt
     *  AND the quiz is graded
     * THEN The awesomebar displays submission status as "Submitted"
     *  AND the awesomebar displays the submitted date (last modified)
     *  AND the awesomebar displays the grade if graded
     *
     * @test
     */
    public function test_quiz_submitted_and_due_date_passed() {
        $now = time();
        $onehour = 60 * 60;
        $tomorrow = ($now + 24 * $onehour) - $onehour;
        $twodays = ($now + 2 * 24 * $onehour) - $onehour;
        $oneweek = $now + 7 * 24 * $onehour;
        $yesterday = $now - 24 * $onehour;
        $oneweekago = $now - 7 * 24 * $onehour;
        // Test 1 day to go class
        $this->setUser($this->editingteachers[0]);

        list($quiz, $cm, $context) = $this->create_quiz_instance_with_questions(array('course'           => $this->course->id,
                                                                                      'questionsperpage' => 0,
                                                                                      'grade'            => 100.0,
                                                                                      'sumgrades'        => 2,
                                                                                      'attempts'         => 3,
                                                                                      'timeopen'         => $oneweekago,
                                                                                      'timeclose'        => $yesterday));

        $quizobj = quiz::create($quiz->id, $this->students[0]->id);

        // Start the attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $this->students[0]->id);

        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $this->assertEquals('1,2,0', $attempt->layout);

        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertFalse($attemptobj->has_response_to_at_least_one_graded_question());

        $prefix1 = $quba->get_field_prefix(1);
        $prefix2 = $quba->get_field_prefix(2);

        $tosubmit = array(1 => array('answer' => 'frog'),
                          2 => array('answer' => '3.14'));

        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        $viewobj = $this->build_quiz_view_obj($this->course, $quizobj, $this->students[0]);
        $output = $this->renderer->view_page($this->course, $quiz, $cm, $context, $viewobj);

        // Should display Quiz is closed message.
        $this->assertContains(get_string('quizisclosed', 'mod_quiz'), $output, 'Submission closed message not found or in unexpected format');

        // Test the awesomebar displays Correctly.
        $this->assertContains(get_string('assess:submissionstatus', 'theme_ouaclean'), $output, 'Submission status header not found or in unexpected format');
        $this->assertContains(get_string('assess:submissionstatus_submitted', 'theme_ouaclean'), $output, 'Submission status must show "Submitted" string when submissions submitted');
        $this->assertContains(get_string('assess:submitted', 'theme_ouaclean'), $output, 'Submitted header is not displayed as expected');
        $this->assertContains(userdate($timenow, '%e %B %Y, %l:%M %p'), $output, 'The submitted date is not being displayed or is in an unexpected format');
        $this->assertContains(get_string('grade'), $output, 'Grade should be displayed in 3rd box header');
        $this->assertContains((string) $viewobj->mygrade, $output, 'Grade should be displayed in 3rd box header');
        $this->assertNotContains('<div data-countdown=', $output, 'Countdown timer should NOT be displayed');

        // Shows summary of previous attempts
        // $this->assertEquals(3, substr_count($output, '</tr>'), 'There should be two table rows for questions');

        // Shows number of questions in quiz.
        $this->assertContains(get_string('numquestionsx', 'quiz', 2), $output, 'Number of questions is not displayed or is in a different format than expected');

        // Shows attempts allowed for quiz
        $this->assertContains(get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts), $output, 'Number of attempts is not displayed or is in a different format than expected');
        libxml_use_internal_errors(true);

        $xmltestoutput = "<html>$output</html>";
        $dom = simplexml_load_string($xmltestoutput);
        $this->assertInstanceOf('SimpleXMLElement', $dom, 'The HTML must be valid well formed HTML. We actually got: ' .
            $xmltestoutput . "\nErrors: " . var_export(libxml_get_errors(), true));

        $this->assertEmpty($viewobj->buttontext, 'There should be no button text, as no more attempts');
        $this->assertNotContains($this->startbtn, $output, 'The start quiz button should not exist');
        $this->assertNotContains(get_string('nomoreattempts', 'quiz'), $output, 'No more attempts string is not being displayed');
    }

    /**
     * This function is a copy of the logic from /mod/quiz/view.php
     * It is long and convoluted.
     *
     * @param $course
     * @param $quizobj
     * @param $userobj
     *
     * @return mod_quiz_view_object
     * @throws coding_exception
     */
    public function build_quiz_view_obj($course, $quizobj, $userobj) {
        $timenow = time();

        $this->setUser($userobj);
        $cm = get_coursemodule_from_instance("quiz", $quizobj->get_quiz()->id, $course->id);
        $context = context_module::instance($cm->id);
        // Cache some other capabilities we use several times.
        $canattempt = has_capability('mod/quiz:attempt', $context);
        $canreviewmine = has_capability('mod/quiz:reviewmyattempts', $context);
        $canpreview = has_capability('mod/quiz:preview', $context);

        $accessmanager = new quiz_access_manager($quizobj, $timenow,
            has_capability('mod/quiz:ignoretimelimits', $context, null, false));
        $quiz = $quizobj->get_quiz();
        $viewobj = new mod_quiz_view_object();
        $viewobj->accessmanager = $accessmanager;
        $viewobj->canreviewmine = $canreviewmine;

        // Get this user's attempts.
        $attempts = quiz_get_user_attempts($quiz->id, $userobj->id, 'finished', true);
        $lastfinishedattempt = end($attempts);
        $unfinished = false;
        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $userobj->id)) {
            $attempts[] = $unfinishedattempt;

            // If the attempt is now overdue, deal with that - and pass isonline = false.
            // We want the student notified in this case.
            $quizobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired(time(), false);

            $unfinished = $unfinishedattempt->state == quiz_attempt::IN_PROGRESS ||
                $unfinishedattempt->state == quiz_attempt::OVERDUE;
            if (!$unfinished) {
                $lastfinishedattempt = $unfinishedattempt;
            }
            $unfinishedattempt = null; // To make it clear we do not use this again.
        }
        $numattempts = count($attempts);

        $viewobj->attempts = $attempts;
        $viewobj->attemptobjs = array();
        foreach ($attempts as $attempt) {
            $viewobj->attemptobjs[] = new quiz_attempt($attempt, $quiz, $cm, $course, false);
        }

// Work out the final grade, checking whether it was overridden in the gradebook.
        if (!$canpreview) {
            $mygrade = quiz_get_best_grade($quiz, $userobj->id);
        } else if ($lastfinishedattempt) {
            // Users who can preview the quiz don't get a proper grade, so work out a
            // plausible value to display instead, so the page looks right.
            $mygrade = quiz_rescale_grade($lastfinishedattempt->sumgrades, $quiz, false);
        } else {
            $mygrade = null;
        }

        $mygradeoverridden = false;
        $gradebookfeedback = '';

        $grading_info = grade_get_grades($course->id, 'mod', 'quiz', $quiz->id, $userobj->id);
        if (!empty($grading_info->items)) {
            $item = $grading_info->items[0];
            if (isset($item->grades[$userobj->id])) {
                $grade = $item->grades[$userobj->id];

                if ($grade->overridden) {
                    $mygrade = $grade->grade + 0; // Convert to number.
                    $mygradeoverridden = true;
                }
                if (!empty($grade->str_feedback)) {
                    $gradebookfeedback = $grade->str_feedback;
                }
            }
        }

// Print table with existing attempts.
        if ($attempts) {
            // Work out which columns we need, taking account what data is available in each attempt.
            list($someoptions, $alloptions) = quiz_get_combined_reviewoptions($quiz, $attempts, $context);

            $viewobj->attemptcolumn = $quiz->attempts != 1;

            $viewobj->gradecolumn = $someoptions->marks >= question_display_options::MARK_AND_MAX &&
                quiz_has_grades($quiz);
            $viewobj->markcolumn = $viewobj->gradecolumn && ($quiz->grade != $quiz->sumgrades);
            $viewobj->overallstats = $lastfinishedattempt && $alloptions->marks >= question_display_options::MARK_AND_MAX;

            $viewobj->feedbackcolumn = quiz_has_feedback($quiz) && $alloptions->overallfeedback;
        }

        $viewobj->timenow = $timenow;
        $viewobj->numattempts = $numattempts;
        $viewobj->mygrade = $mygrade;
        $viewobj->moreattempts = $unfinished ||
            !$accessmanager->is_finished($numattempts, $lastfinishedattempt);
        $viewobj->mygradeoverridden = $mygradeoverridden;
        $viewobj->gradebookfeedback = $gradebookfeedback;
        $viewobj->lastfinishedattempt = $lastfinishedattempt;
        $viewobj->canedit = has_capability('mod/quiz:manage', $context);
        $viewobj->editurl = new moodle_url('/mod/quiz/edit.php', array('cmid' => $cm->id));
        $viewobj->backtocourseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $viewobj->startattempturl = $quizobj->start_attempt_url();
        $viewobj->startattemptwarning = $quizobj->confirm_start_attempt_message($unfinished);
        $viewobj->popuprequired = $accessmanager->attempt_must_be_in_popup();
        $viewobj->popupoptions = $accessmanager->get_popup_options();

// Display information about this quiz.
        $viewobj->infomessages = $viewobj->accessmanager->describe_rules();
        if ($quiz->attempts != 1) {
            $viewobj->infomessages[] = get_string('gradingmethod', 'quiz',
                quiz_get_grading_option_name($quiz->grademethod));
        }

// Determine wheter a start attempt button should be displayed.
        $viewobj->quizhasquestions = $quizobj->has_questions();
        $viewobj->preventmessages = array();
        if (!$viewobj->quizhasquestions) {
            $viewobj->buttontext = '';
        } else {
            if ($unfinished) {
                if ($canattempt) {
                    $viewobj->buttontext = get_string('continueattemptquiz', 'quiz');
                } else if ($canpreview) {
                    $viewobj->buttontext = get_string('continuepreview', 'quiz');
                }
            } else {
                if ($canattempt) {
                    $viewobj->preventmessages = $viewobj->accessmanager->prevent_new_attempt(
                        $viewobj->numattempts, $viewobj->lastfinishedattempt);
                    if ($viewobj->preventmessages) {
                        $viewobj->buttontext = '';
                    } else if ($viewobj->numattempts == 0) {
                        $viewobj->buttontext = get_string('attemptquiznow', 'quiz');
                    } else {
                        $viewobj->buttontext = get_string('reattemptquiz', 'quiz');
                    }
                } else if ($canpreview) {
                    $viewobj->buttontext = get_string('previewquiznow', 'quiz');
                }
            }

            // If, so far, we think a button should be printed, so check if they will be
            // allowed to access it.
            if ($viewobj->buttontext) {
                if (!$viewobj->moreattempts) {
                    $viewobj->buttontext = '';
                } else if ($canattempt
                    && $viewobj->preventmessages = $viewobj->accessmanager->prevent_access()
                ) {
                    $viewobj->buttontext = '';
                }
            }
        }

        $viewobj->showbacktocourse = ($viewobj->buttontext === '' &&
            course_get_format($course)->has_view_page());

        return $viewobj;
    }

    /**
     * Convenience function to create a testable instance of a quiz with two questions.
     *
     * @param array $params Array of parameters to pass to the generator
     *
     * @return array instance of quiz, coursemodule, context
     */
    protected function create_quiz_instance_with_questions($params = array()) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params['course'] = $this->course->id;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('quiz', $instance->id);
        $context = context_module::instance($cm->id);
        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));

        // Add them to the quiz.
        quiz_add_quiz_question($saq->id, $instance);
        quiz_add_quiz_question($numq->id, $instance);
        if (isset($params['essayq'])) {
            // Essay Questions are manually graded. Add an essay question for testing of manually graded questions.
            $essq = $questiongenerator->create_question('essay', null, array('category' => $cat->id));
            quiz_add_quiz_question($essq->id, $instance);
        }

        return array($instance, $cm, $context);
    }

    /**
     * Create a quiz with a random as well as other questions and walk through quiz attempts.
     * This test should be used in future iterations to test the random question generator
     * If not useful can be removed.
     */
    public function test_quiz_with_random_question_attempt_walkthrough() {
        global $SITE;

        $this->resetAfterTest(true);
        question_bank::get_qtype('random')->clear_caches_before_testing();

        $this->setAdminUser();

        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(array('course'    => $SITE->id, 'questionsperpage' => 2, 'grade' => 100.0,
                                                      'sumgrades' => 4));

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // Add two questions to question category.
        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));

        // Add random question to the quiz.
        quiz_add_random_questions($quiz, 0, $cat->id, 1, false);

        // Make another category.
        $cat2 = $questiongenerator->create_question_category();
        $match = $questiongenerator->create_question('match', null, array('category' => $cat->id));

        quiz_add_quiz_question($match->id, $quiz, 0);

        $multichoicemulti = $questiongenerator->create_question('multichoice', 'two_of_four', array('category' => $cat->id));

        quiz_add_quiz_question($multichoicemulti->id, $quiz, 0);

        $multichoicesingle = $questiongenerator->create_question('multichoice', 'one_of_four', array('category' => $cat->id));

        quiz_add_quiz_question($multichoicesingle->id, $quiz, 0);

        foreach (array($saq->id => 'frog', $numq->id => '3.14') as $randomqidtoselect => $randqanswer) {
            // Make a new user to do the quiz each loop.
            $this->students[0] = $this->getDataGenerator()->create_user();
            $this->setUser($this->students[0]);

            $quizobj = quiz::create($quiz->id, $this->students[0]->id);

            // Start the attempt.
            $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
            $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

            $timenow = time();
            $attempt = quiz_create_attempt($quizobj, 1, false, $timenow);

            quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow, array(1 => $randomqidtoselect));
            $this->assertEquals('1,2,0,3,4,0', $attempt->layout);

            quiz_attempt_save_started($quizobj, $quba, $attempt);

            // Process some responses from the student.
            $attemptobj = quiz_attempt::create($attempt->id);
            $this->assertFalse($attemptobj->has_response_to_at_least_one_graded_question());

            $tosubmit = array();
            $selectedquestionid = $quba->get_question_attempt(1)->get_question()->id;
            $tosubmit[1] = array('answer' => $randqanswer);
            $tosubmit[2] = array(
                'frog' => 'amphibian',
                'cat'  => 'mammal',
                'newt' => 'amphibian');
            $tosubmit[3] = array('One' => '1', 'Two' => '0', 'Three' => '1', 'Four' => '0'); // First and third choice.
            $tosubmit[4] = array('answer' => 'One'); // The first choice.

            $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

            // Finish the attempt.
            $attemptobj = quiz_attempt::create($attempt->id);
            $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
            $attemptobj->process_finish($timenow, false);

            // Re-load quiz attempt data.
            $attemptobj = quiz_attempt::create($attempt->id);

            // Check that results are stored as expected.
            $this->assertEquals(1, $attemptobj->get_attempt_number());
            $this->assertEquals(4, $attemptobj->get_sum_marks());
            $this->assertEquals(true, $attemptobj->is_finished());
            $this->assertEquals($timenow, $attemptobj->get_submitted_date());
            $this->assertEquals($this->students[0]->id, $attemptobj->get_userid());
            $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());

            // Check quiz grades.
            $grades = quiz_get_user_grades($quiz, $this->students[0]->id);
            $grade = array_shift($grades);
            $this->assertEquals(100.0, $grade->rawgrade);

            // Check grade book.
            $gradebookgrades = grade_get_grades($SITE->id, 'mod', 'quiz', $quiz->id, $this->students[0]->id);
            $gradebookitem = array_shift($gradebookgrades->items);
            $gradebookgrade = array_shift($gradebookitem->grades);
            $this->assertEquals(100, $gradebookgrade->grade);
        }
    }
}
