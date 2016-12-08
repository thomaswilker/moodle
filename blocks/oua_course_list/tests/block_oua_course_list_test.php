<?php
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

/**
 *  Unit tests for course_list block
 *
 * @package    blocks
 * @subpackage oua_course_list
 */
class block_oua_course_list_testcase extends oua_advanced_testcase {

    protected function setUp() {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
    }

    /**
     * GIVEN We have created a course list block in the $SITE course
     * WHEN we retrieve the output
     * THEN the output passess xml validation
     *
     * @test
     */
    public function test_block_content_xml_is_valid() {
        global $CFG, $DB, $PAGE;

        load_all_capabilities();
        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => COMPLETION_ENABLED,
                                                                 'groupmode'        => SEPARATEGROUPS, 'groupmodeforce' => 1));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = $this->getDataGenerator()
                     ->create_module('page', array('course' => $course->id), array('completion' => 1, 'visible' => 0));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);
        // Forum complete.
        $completion = new completion_info($course);
        $completion->update_state($cmforum, COMPLETION_COMPLETE);

        $result = core_completion_external::get_activities_completion_status($course->id, $student->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(core_completion_external::get_activities_completion_status_returns(), $result);

        // We added 4 activities, but only 3 with completion enabled and one of those is hidden.
        $this->assertCount(2, $result['statuses']);

        $activitiesfound = 0;
        foreach ($result['statuses'] as $status) {
            if ($status['cmid'] == $forum->cmid and $status['modname'] == 'forum' and $status['instance'] == $forum->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_COMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            } else if ($status['cmid'] == $data->cmid and $status['modname'] == 'data' and $status['instance'] == $data->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_INCOMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            }
        }
        $this->assertEquals(2, $activitiesfound);

        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $expected = '<li class="coursecol course-header col-xs-12">';
        $this->assertContains($expected, $html, "The html output of the html should contain the weeks li hidden");
        $this->assertContains(get_string('nocompletedcourses', 'block_oua_course_list'), $html,
                              "The output should contain the no completed courses string.");
    }

    /**
     * This test relies on previous tests to run before hand
     * Tests to ensure the block content returns, when cached.
     */
    public function test_course_list_cache_outputs() {
        $this->resetAfterTest(true);
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);
        $this->assertFalse($block->is_empty(), 'There is no block content returned from cache');
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);
    }
    /**
     * GIVEN We have an enrolled course with invisible format
     * WHEN we view the course list block
     * THEN the output should show details of the course
     *  AND display the next course module
     *
     * @test
     */
    public function test_course_list_displays_next_cm() {
        global $CFG, $DB;

        load_all_capabilities();
        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => COMPLETION_ENABLED,
                                                                 'groupmode'        => SEPARATEGROUPS,
                                                                 'groupmodeforce' => 1,
                                                                 'format' => 'invisible'));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = $this->getDataGenerator()
                     ->create_module('page', array('course' => $course->id), array('completion' => 1, 'visible' => 0));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);
        // Forum complete.
        $completion = new completion_info($course);
        $completion->update_state($cmforum, COMPLETION_COMPLETE);

        $result = core_completion_external::get_activities_completion_status($course->id, $student->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(core_completion_external::get_activities_completion_status_returns(), $result);

        // We added 4 activities, but only 3 with completion enabled and one of those is hidden.
        $this->assertCount(2, $result['statuses']);

        $activitiesfound = 0;
        foreach ($result['statuses'] as $status) {
            if ($status['cmid'] == $forum->cmid and $status['modname'] == 'forum' and $status['instance'] == $forum->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_COMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            } else if ($status['cmid'] == $data->cmid and $status['modname'] == 'data' and $status['instance'] == $data->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_INCOMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            }
        }
        $this->assertEquals(2, $activitiesfound);

        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertXpathDomQueryResultLengthEquals(1, "//*[contains(@class,'whatsnext')]", $html,  "The output should contain a course with whats next label \n\n $html");
    }
    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND we have courses with grades in the results tab
     * WHEN we retrieve the output
     * THEN it should match the output of the moodle core grade report overview
     *
     * @test
     */
    public function test_grade_output_matches_core() {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/grade/report/overview/lib.php');
        require_once($CFG->dirroot . '/blocks/oua_course_list/classes/oua_grade_report_overview.php');
        //  $this->markTestIncomplete('Test development in progress.');
        $this->resetAfterTest(true);
        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;



        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));
        $passstudent = $this->getDataGenerator()->create_user();
        $failstudent = $this->getDataGenerator()->create_user();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($passstudent->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($failstudent->id, $course1->id, $studentrole->id);

        $this->getDataGenerator()->enrol_user($passstudent->id, $course2->id, $studentrole->id);

        // Make a scale and an outcome.
        $scale = $this->getDataGenerator()->create_scale();
        $data = array('courseid' => $course1->id,
                      'fullname' => 'Team work',
                      'shortname' => 'Team work',
                      'scaleid' => $scale->id);
        $outcome = $this->getDataGenerator()->create_grade_outcome($data);


        // Make a quiz with the outcome on.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $data = array('course' => $course1->id,
                      'outcome_'.$outcome->id => 1,
                      'grade' => 100.0,
                      'questionsperpage' => 0,
                      'sumgrades' => 1,
                      'completion' => COMPLETION_TRACKING_AUTOMATIC,
                      'completionpass' => 1);
        $quiz = $quizgenerator->create_instance($data);
        $cm = get_coursemodule_from_id('quiz', $quiz->cmid);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        quiz_add_quiz_question($question->id, $quiz);

        $quizobj = quiz::create($quiz->id, $passstudent->id);

        // Set grade to pass.
        $item = grade_item::fetch(array('courseid' => $course1->id, 'itemtype' => 'mod',
                                        'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'outcomeid' => null));
        $item->gradepass = 80;
        $item->update();

        // Start the passing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $passstudent->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Start the failing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $failstudent->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '0'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);


        $assignment = $this->getDataGenerator()->create_module('assign', array('name' => "Test assign", 'course' => $course1->id));
        $modcontext = get_coursemodule_from_instance('assign', $assignment->id, $course1->id);
        $assignment->cmidnumber = $modcontext->id;

        $student1grade = array('userid' => $passstudent->id, 'rawgrade' => '60');
        $studentgrades = array($passstudent->id => $student1grade);
        assign_grade_item_update($assignment, $studentgrades);


        $assignment1course2 = $this->getDataGenerator()->create_module('assign', array('name' => "Test assign2", 'course' => $course2->id));
        $modcontext = get_coursemodule_from_instance('assign', $assignment1course2->id, $course2->id);
        $assignment1course2->cmidnumber = $modcontext->id;

        $passstudent->grade_last_report[$course1->id] = 'overview';



        require_once $CFG->libdir.'/gradelib.php';
        require_once $CFG->dirroot.'/grade/lib.php';
        require_once $CFG->dirroot.'/grade/report/overview/lib.php';

        $this->setUser($passstudent);
        $PAGE->set_url('/blocks/test');
        //first make sure we have proper final grades - this must be done before constructing of the grade tree
        grade_regrade_final_grades($course1->id);

        $gpr = new stdClass();
        $sitecontext = context_course::instance(SITEID);
        $gradereportoua = new \block_oua_course_list\oua_grade_report_overview($passstudent->id, $gpr, $sitecontext);
        $gradereport = new grade_report_overview($passstudent->id, $gpr, $sitecontext);
        $ouaresultcourse1 = $gradereportoua->get_course_final_grade($course1);
        $ouaresultcourse2 = $gradereportoua->get_course_final_grade($course2);


       ob_start();
       $gradereport->fill_table(false, true);
       $corereportheadhtml = ob_get_clean();


        $corereporthtmlbody =   $gradereport->print_table(true);
        $corereporthtml = $corereportheadhtml.$corereporthtmlbody;

        $course2selectortitle ='grade-report-overview-'.$passstudent->id.'_r0_c0';
        $course2selectorgrade ='grade-report-overview-'.$passstudent->id.'_r0_c1';


        $this->assertXPathGetNodesWithIdsEquals(array('Test course 2'), $course2selectortitle, $corereporthtml, "First result is not course 2");
        $this->assertXPathGetNodesWithIdsEquals(array($ouaresultcourse2), $course2selectorgrade, $corereporthtml, "OUA result output ($ouaresultcourse2) is not the same as core output");

        $course1selectortitle ='grade-report-overview-'.$passstudent->id.'_r1_c0';
        $course1selectorgrade ='grade-report-overview-'.$passstudent->id.'_r1_c1';

        $this->assertXPathGetNodesWithIdsEquals(array('Test course 1'), $course1selectortitle, $corereporthtml, "Second result is not course 1");
        $this->assertXPathGetNodesWithIdsEquals(array($ouaresultcourse1), $course1selectorgrade, $corereporthtml, "OUA result output ($ouaresultcourse1) is not the same as core output");
    }

    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND we have completed course1
     *  AND we have not completed course2
     * WHEN we retrieve the output
     * THEN We should see course1 in the completed units tab
     *  AND we should see course 2 in the current units tab
     *
     * @test
     */
    public function test_course_completed_list() {
        global $DB, $CFG, $COMPLETION_CRITERIA_TYPES;
        require_once($CFG->dirroot . '/completion/cron.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_date.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_unenrol.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_duration.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_grade.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        // Loop through each criteria type and run its update_config() method.

        $criteriadata = new stdClass();
        $criteriadata->id = $course->id;
        $criteriadata->criteria_activity = array();
        // Some activities.
        $criteriadata->criteria_activity[$cmdata->id] = 1;
        $criteriadata->criteria_activity[$cmforum->id] = 1;

        // In a week criteria date value.
        $criteriadata->criteria_date_value = 0;

        // Self completion.
        $criteriadata->criteria_self = 0;

        foreach ($COMPLETION_CRITERIA_TYPES as $type) {
            $class = 'completion_criteria_' . $type;
            $criterion = new $class();
            $criterion->update_config($criteriadata);
        }

        // Handle overall aggregation.
        $aggdata = array('course' => $course->id, 'criteriatype' => null);
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        // Complete the course activities and course.
        $completion = new completion_info($course);

        $modinfo = get_fast_modinfo($course->id);
        $datacm = $modinfo->get_cm($data->cmid);
        $forumcm = $modinfo->get_cm($forum->cmid);

        $completion->update_state($datacm, COMPLETION_COMPLETE, $student->id);
        $completion->update_state($forumcm, COMPLETION_COMPLETE, $student->id);

        $coursecomplete = $completion->is_course_complete($student->id);
        $this->assertFalse($coursecomplete, "Student is not yet marked as complete by completion cron");

        // A user cannot start and complete a course in the same run of completion cron
        // So we have to run, sleep to ensure the time in seconds has advanced, then run it again.

        completion_cron_mark_started();
        completion_cron_criteria();
        sleep(1);
        completion_cron_completions();
        $this->expectOutputRegex('/Marking complete$/', 'The output from cron should end with marking a user as complete.');

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);

        $this->setUser($student);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertNotContains(get_string('nocompletedcourses', 'block_oua_course_list'), $html,
                                 "The output should contain a completed course.");

        $coursecomplete = $completion->is_course_complete($student->id);
        $this->assertTrue($coursecomplete, "Student must be marked as complete by completion cron");

        /*
        $nocertificateneeded = get_string('nocertificateneeded', 'block_oua_course_list');
        $query = "//div[@class='course-certificate-wrapper']//div[@class='download'][text()[contains(.,'".$nocertificateneeded."')]]";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html,  "The output should contain a completed course with NO download certificate link (no div with class course-certificate) \n\n $html");
        */

        // Check for view grade link for completed tab
        $finalgradelink = new moodle_url('/grade/report/user/index.php', array('id' => $course->id,'userid' => $student->id));
        $url = $finalgradelink->out(false); // don't escape the &
        $viewgrade = get_string('viewgrade', 'block_oua_course_list');
        $query = "//div[contains(@class,'viewgrade')]//a[.='$viewgrade']/@href[.='$url']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html, 'Expected 1 "View Grade" link to '.$url);

        $this->markTestIncomplete("Requires test that courses in a configured resources category, are hidden");

    }
    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND we have 5 courses in the current units tab
     *  AND we have configured the block to display only 3 units by default
     * WHEN we retrieve the output
     * THEN We should see 3 shown and 2 hidden courses.
     *
     * @test
     */
    public function test_course_list_hidden() {
        global $DB, $CFG, $COMPLETION_CRITERIA_TYPES;
        require_once($CFG->dirroot . '/completion/cron.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_date.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_unenrol.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_duration.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_grade.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course1->id), array('completion' => 1));

        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course2->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id), array('completion' => 1));


        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course3->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course3->id), array('completion' => 1));


        $course4 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course4->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course4->id), array('completion' => 1));


        $course5 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course5->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course5->id), array('completion' => 1));

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);

        $block->config = new stdClass();
        $block->config->defaultcourselistlength = 3;


        $this->setUser($student);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertContains(get_string('nocompletedcourses', 'block_oua_course_list'), $html,
                                 "The output should contain a completed course.");
        $this->assertXpathDomQueryResultLengthEquals(5, "//div[contains(@class,'current-units')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 5 courses in the current units tab\n\n $html");

        $this->assertXpathDomQueryResultLengthEquals(3, "//div[contains(@class,'current-units')]//ul[contains(@class, 'course-list') and contains(@class, 'visible')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 3 courses visisble by default in the current units tab\n\n $html");
        $this->assertXpathDomQueryResultLengthEquals(2, "//div[contains(@class,'current-units')]//div[contains(@class, 'hidden-courses')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 2 courses hidden by default in the current units tab\n\n $html");

    }
    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND we have 5 courses in the completed units tab
     *  AND we have configured the block to display only 3 units by default
     * WHEN we retrieve the output
     * THEN We should see 3 shown and 2 hidden courses.
     *
     * @test
     */
    public function test_course_list_completed_hidden() {
        global $DB, $CFG, $COMPLETION_CRITERIA_TYPES;
        require_once($CFG->dirroot . '/completion/cron.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_date.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_unenrol.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_duration.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_grade.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, $studentrole->id);

        $page = $this->getDataGenerator()->create_module('page', array('course' => $course1->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course1, array($pagecm));

        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course2->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course2, array($pagecm));

        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course3->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course3->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course3, array($pagecm));

        $course4 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course4->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course4->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course4, array($pagecm));

        $course5 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course5->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course5->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course5, array($pagecm));

        completion_cron_mark_started();
        completion_cron_criteria();
        sleep(1);
        completion_cron_completions();
        $this->expectOutputRegex('/Marking complete$/', 'The output from cron should end with marking a user as complete.');

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);

        $block->config = new stdClass();
        $block->config->defaultcourselistlength = 3;


        $this->setUser($student);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertXpathDomQueryResultLengthEquals(5, "//div[contains(@class,'completed-units')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 5 courses in the completed units tab\n\n $html");

        $this->assertXpathDomQueryResultLengthEquals(3, "//div[contains(@class,'completed-units')]//ul[contains(@class, 'course-list') and contains(@class, 'visible')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 3 courses visisble by default in the completed units tab\n\n $html");
        $this->assertXpathDomQueryResultLengthEquals(2, "//div[contains(@class,'completed-units')]//div[contains(@class, 'hidden-courses')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 2 courses hidden by default in the completed units tab\n\n $html");

    }
    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND we have 5 courses in the current units tab
     *  AND we have configured the block to display only 3 units by default
     * WHEN we retrieve the output
     * THEN We should see 3 shown and 2 hidden courses.
     *
     * @test
     */
    public function test_course_list_result_hidden() {
        global $DB, $CFG, $COMPLETION_CRITERIA_TYPES;
        require_once($CFG->dirroot . '/completion/cron.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_date.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_unenrol.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_duration.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_grade.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');

        $this->resetAfterTest(true);
        $now = time();
        $nextweek = $now + (60*60*24*7);
        $lastweek = $now - (60*60*24*7);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, $studentrole->id);

        $page = $this->getDataGenerator()->create_module('page', array('course' => $course1->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course1, array($pagecm));

        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course2->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($student, $course2, array($pagecm));

        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course3->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course3->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
      //  $this->add_completion_criteria($student, $course3, array($pagecm));

        $startdate = time() - 60 * 60 * 24 * 2; // started yesterday
        $course4 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($student->id, $course4->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course4->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
       // $this->add_completion_criteria($student, $course4, array($pagecm));

        $course5 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1, 'startdate' => $nextweek));
        $this->getDataGenerator()->enrol_user($student->id, $course5->id, $studentrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course5->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
       // $this->add_completion_criteria($student, $course5, array($pagecm));

        completion_cron_mark_started();
        completion_cron_criteria();
        sleep(1);
        completion_cron_completions();
        $this->expectOutputRegex('/Marking complete$/', 'The output from cron should end with marking a user as complete.');

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);

        $block->config = new stdClass();
        $block->config->defaultcourselistlength = 3;
        $block->config->displayresultstab = true;

        $this->setUser($student);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertXpathDomQueryResultLengthEquals(4, "//div[contains(@class, 'course-results-list')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 5 courses in the results tab\n\n $html");
        $this->assertXpathDomQueryResultLengthEquals(3, "//div[contains(@class, 'course-results-list')]//ul[contains(@class, 'course-list') and contains(@class, 'visible')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 3 courses visisble by default in the completed units tab\n\n $html");
        $this->assertXpathDomQueryResultLengthEquals(1, "//div[contains(@class, 'course-results-list')]//div[contains(@class,'hidden-courses')]//div[contains(@class,'course-name-progress-bar')]", $html,  "The output should contain 2 courses hidden by default in the completed units tab\n\n $html");
    }

    private function add_completion_criteria($student, $course, $modules) {
        global $COMPLETION_CRITERIA_TYPES;
        $criteriadata = new stdClass();
        $criteriadata->id = $course->id;
        $criteriadata->criteria_activity = array();
        foreach ($modules as $cm) {
            $criteriadata->criteria_activity[$cm->id] = 1;
            $criteriadata->criteria_date_value = 0;
            $criteriadata->criteria_self = 0;
            foreach ($COMPLETION_CRITERIA_TYPES as $type) {
                $class = 'completion_criteria_' . $type;
                $criterion = new $class();
                $criterion->update_config($criteriadata);
            }
            $aggdata = array('course' => $course->id, 'criteriatype' => null);
            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
            $aggregation->save();

            $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
            $aggregation->save();
            $completion = new completion_info($course);
            $completion->update_state($cm, COMPLETION_COMPLETE, $student->id);
        }
    }


    /**
     * GIVEN we have a course with a certificate module that can be viewed
     *   AND the course is completed and in the completed tab
     *  WHEN we view the course completed tab
     *  THEN we should see the download certificate link
     */
    public function test_certificate_link_shows() {
        global $DB, $CFG, $COMPLETION_CRITERIA_TYPES;

        require_once($CFG->dirroot . '/completion/cron.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_date.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_unenrol.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_duration.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_grade.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $this->setAdminUser();
        $student = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        $certificate = $this->getDataGenerator()->create_module('certificate', array('course' => $course->id, 'visible'=> 0));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        // Loop through each criteria type and run its update_config() method.

        $criteriadata = new stdClass();
        $criteriadata->id = $course->id;
        $criteriadata->criteria_activity = array();
        // Some activities.
        $criteriadata->criteria_activity[$cmdata->id] = 1;
        $criteriadata->criteria_activity[$cmforum->id] = 1;

        // In a week criteria date value.
        $criteriadata->criteria_date_value = 0;

        // Self completion.
        $criteriadata->criteria_self = 0;

        foreach ($COMPLETION_CRITERIA_TYPES as $type) {
            $class = 'completion_criteria_' . $type;
            $criterion = new $class();
            $criterion->update_config($criteriadata);
        }

        // Handle overall aggregation.
        $aggdata = array('course' => $course->id, 'criteriatype' => null);
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        // Complete the course activities and course.
        $completion = new completion_info($course);

        $modinfo = get_fast_modinfo($course->id);
        $datacm = $modinfo->get_cm($data->cmid);
        $forumcm = $modinfo->get_cm($forum->cmid);
        $certcm = $modinfo->get_cm($certificate->cmid);

        $coursecomplete = $completion->is_course_complete($student->id);
        $this->assertFalse($coursecomplete, "Student should not yet be marked as complete by completion cron");

        $completion->update_state($datacm, COMPLETION_COMPLETE, $student->id);
        $completion->update_state($forumcm, COMPLETION_COMPLETE, $student->id);

        // A user cannot start and complete a course in the same run of completion cron
        // So we have to run, sleep to ensure the time in seconds has advanced, then run it again.
        completion_cron_mark_started();
        completion_cron_criteria();
        sleep(1);
        completion_cron_completions();
        $this->expectOutputRegex('/Marking complete$/', 'The output from cron should end with marking a user as complete.');

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);

        $this->setUser($student);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $coursecomplete = $completion->is_course_complete($student->id);
        $this->assertTrue($coursecomplete, "Student must be marked as complete by completion cron");

        $this->assertContains(get_string('nocertificates', 'block_oua_course_list'), $html,
                              "There should be a no certificate error when there is a certificate in the course, but none has been awarded.");

        $settings = new stdClass();
        $settings->defaultcourselistlength = 10;
        $settings->displayresultstab = false;

        $courselist = new \block_oua_course_list\output\course_list_renderable($settings, $student->id);
        list($hascertificatemodule, $certificates) = $courselist->get_course_certificates($course);
        $this->assertTrue($hascertificatemodule);
        $this->assertCount(0, $certificates);


        $this->setAdminUser();
        set_coursemodule_visible($certificate->cmid, 1);

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list');
        $block = block_instance('oua_course_list', $block);

        $this->setUser($student);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        // no course name included
        $query = "//div[@class='tab-pane completed-units']//a[text()[contains(.,'".get_string('downloadcertificate', 'block_oua_course_list')."')]]";
        $this->assertXpathDomQueryResultLengthEquals(1,$query, $html, "The output should contain a completed course with a download certtificate link");

        $settings = new stdClass();
        $settings->defaultcourselistlength = 10;
        $settings->displayresultstab = false;

        $courselist = new \block_oua_course_list\output\course_list_renderable($settings, $student->id);
        list($hascertificatemodule, $certificates) = $courselist->get_course_certificates($course);
        $this->assertCount(1, $certificates);
        $query = "//div[@class='course-certificate-wrapper']//a[text()[contains(.,'".get_string('downloadcertificate', 'block_oua_course_list')."')]]";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html,  "The output should contain a completed course with a download certificate link and wrapper with class course-certificate-wrapper \n\n $html");
    }

    /**
     * TEST SORTING IN CORRECT ORDER AND COUNTED CORRECTLY FOR VISIBLE AND HIDDEN
     *
     * GIVEN show visible is 2
     * GIVEN 11 courses
     *  AND 6 is current status,-- 2 courses with no enddate
     *  AND 5 completed status, -- 1 courses with 1 enddate
     *  AND 8 with startdate less than NOW TIME - 3 future dates, 1 with no enddate
     * WHEN display output
     *  current is sorted by startdate desc
     *  completed is sorted by enddate desc
     *  result is sorted by startdate desc
     */

    public function test_sorted_list() {
        global $CFG, $DB, $PAGE;

        load_all_capabilities();
        $this->resetAfterTest(true);
        $CFG->enablecompletion = true;

        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->setUser($student);

        $defaultvisibleitems = 2;

        $dates = array( // times set relative to NOW time to test for future courses
            // completed index 0-4
            array('startdate'=>strtotime('-9 months'), 'courseenddate'=>strtotime('-9 months +30 days'), 'iscomplete'=>true), // tc_1
            array('startdate'=>strtotime('-11 months'), 'iscomplete'=>true), // tc_2
            array('startdate'=>strtotime('-8 months'), 'courseenddate'=>strtotime('-8 months +30 days'), 'iscomplete'=>true), // tc_3
            array('startdate'=>strtotime('-12 months'), 'iscomplete'=>true), //tc_4
            array('startdate'=>strtotime('-10 months'), 'courseenddate'=>strtotime('-10 months +30 days'), 'iscomplete'=>true), // tc_5

            // not yet completed index 5-7
            array('startdate'=>strtotime('-1 months'), 'iscomplete'=>false), // tc_6
            array('startdate'=>strtotime('-3 months'), 'courseenddate'=>strtotime('+120 days'), 'iscomplete'=>false), // tc_7
            array('startdate'=>strtotime('-2 months'), 'courseenddate'=>strtotime('+90 days'), 'iscomplete'=>false), // tc_8

            // start date is greater than now index 8-10
            array('startdate'=>strtotime('+1 week'), 'courseenddate'=>strtotime('+60 days'), 'iscomplete'=>false), // tc_9
            array('startdate'=>strtotime('+2 weeks'), 'iscomplete'=>false), // tc_10
            array('startdate'=>strtotime('+1 month'), 'courseenddate'=>strtotime('+120 days'), 'iscomplete'=>false), // tc_11
        );

        $cntcourses = count($dates);
        $cntfutures = 0;
        $cntcomplete = 0;

        for ($i = 0; $i < $cntcourses; $i++){
            $course = $this->getDataGenerator()->create_course(
                array('enablecompletion' => COMPLETION_ENABLED,
                'groupmode'        => SEPARATEGROUPS,
                'groupmodeforce' => 1,
                'format' => 'invisible',

                    'startdate'=> $dates[$i]['startdate'],
                    'courseenddate' => isset($dates[$i]['courseenddate'])? $dates[$i]['courseenddate'] :0,
                )
            );

            // assume at least one activity - renderer will check for activity
            $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));

            // marked course as completed
            if ($dates[$i]['iscomplete']){
                $completion = new completion_completion();
                $completion->userid = $student->id;
                $completion->course = $course->id;
                $completion->mark_complete();

                $cntcomplete++;
            }
            if ($dates[$i]['startdate'] > time()){
                $cntfutures++;
            }

            $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        }

        // simulate config from block settings
        $config = new stdClass();
        $config->defaultcourselistlength = $defaultvisibleitems;
        $config->displayresultstab = false;

        $courselist = new \block_oua_course_list\output\course_list_renderable($config, $student->id);
        $renderer = $PAGE->get_renderer('block_oua_course_list');
        $output = $courselist->export_for_template($renderer);

        //$html = $renderer->render_course_list($courselist);
        //var_dump($html);

        $completelist = array_merge($output->completedcourselist, $output->completedcourselisthidden);
        // count is correct
        $this->assertEquals($cntcomplete, count($completelist));
        if (count($completelist) > $defaultvisibleitems){
            $this->assertEquals(count($output->completedcourselist), $defaultvisibleitems);
        }
        // completed sort order enddate DESC tc_3, 1, 5, 2, 4 (2 and 4 depend on which comes first in test data set)
        $this->assertEquals('tc_3', $completelist[0]['title']);
        $this->assertEquals('tc_1', $completelist[1]['title']);
        $this->assertEquals('tc_5', $completelist[2]['title']);
        $this->assertEquals('tc_2', $completelist[3]['title']);
        $this->assertEquals('tc_4', $completelist[4]['title']);

        $currentlist = array_merge($output->courselist, $output->courselisthidden);
        // count is correct
        $this->assertEquals($cntcomplete, count($completelist));
        if (count($currentlist) > $defaultvisibleitems){
            $this->assertEquals(count($output->courselist), $defaultvisibleitems);
        }
        $this->assertEquals($cntcourses-$cntcomplete, count($currentlist));
        // non completed resultlist sort order startdate ASC tc_7, 8, 6, 9, 10, 11

        $this->assertEquals('tc_7', $currentlist[0]['title']);
        $this->assertEquals('tc_8', $currentlist[1]['title']);
        $this->assertEquals('tc_6', $currentlist[2]['title']);
        $this->assertEquals('tc_9', $currentlist[3]['title']);
        $this->assertEquals('tc_10', $currentlist[4]['title']);
        $this->assertEquals('tc_11', $currentlist[5]['title']);

        $resultlist = array_merge($output->courseresultlist, $output->courseresultlisthidden);
        // count is correct
        $this->assertEquals($cntcourses-$cntfutures, count($resultlist));
        if (count($resultlist) > $defaultvisibleitems){
            $this->assertEquals(count($output->courseresultlist), $defaultvisibleitems);
        }
        // result list of startdate < now, sorted startdate DESC most recent first -- exclude all futures
        // exclude tc_11, 10, 9
        // order tc_6, 8, 7, 3, 1, 5, 2, 4
        $print = print_r($resultlist, true);
        $this->assertEquals('tc_6', $resultlist[0]['title'], 'tc_6:'. $print);
        $this->assertEquals('tc_8', $resultlist[1]['title'], 'tc_8:'. $print);
        $this->assertEquals('tc_7', $resultlist[2]['title'], 'tc_7:'. $print);
        $this->assertEquals('tc_3', $resultlist[3]['title'], 'tc_3:'. $print);
        $this->assertEquals('tc_1', $resultlist[4]['title'], 'tc_1:'. $print);
        $this->assertEquals('tc_5', $resultlist[5]['title'], 'tc_5:'. $print);
        $this->assertEquals('tc_2', $resultlist[6]['title'], 'tc_2:'. $print);
        $this->assertEquals('tc_4', $resultlist[7]['title'], 'tc_4:'. $print);
    }

    /**
     * GIVEN We have created/ restored a course
     *  AND there is no course module or not yet created
     * WHEN we view the course list block
     * THEN the output should show the course
     *  AND not throwing errors on accessing course module name
     *
     * @test
     */
    public function test_displaying_course_with_no_cm()
    {
        global $DB, $PAGE;

        load_all_capabilities();
        $this->resetAfterTest(true);

        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        // Course with no module.
        $course = $this->getDataGenerator()->create_course(
            array(
                'format' => 'invisible'
            )
        );

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);

        // Let the system assign the default config of list length etc.
        $config = null;
        $courselist = new \block_oua_course_list\output\course_list_renderable($config, $student->id);
        $renderer = $PAGE->get_renderer('block_oua_course_list');

        // Render the course list with no error thrown.
        $html = $renderer->render_course_list($courselist);
        $this->assertValidHtml($html);

        // Test course listed.
        $querycoursetitle = "//div[contains(@class,'course-name-progress-bar')]/a[.='Test course 1']";
        $this->assertXpathDomQueryResultLengthEquals(1, $querycoursetitle, $html,  "The output should show 'Test course 1' \n\n $html");

        // And there is no next label.
        $querywhatsnext = "//*[contains(@class,'whatsnext')]";
        $this->assertXpathDomQueryResultLengthEquals(0, $querywhatsnext, $html,  "The course should NOT contain whats next label \n\n $html");
    }

}
