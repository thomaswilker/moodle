<?php
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

/**
 *  Unit tests for course_list_teacher block
 *
 * @package    blocks
 * @subpackage oua_course_list_teacher
 */
class block_oua_course_list_teacher_testcase extends oua_advanced_testcase {

    protected function setUp() {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
    }

    /**
     * GIVEN We have created a course list block in the $SITE course
     * WHEN we retrieve the output
     * THEN the output is populated for a teacher and passes xml validation
     *  AND the block is empty for a student
     *
     * @test
     */
    public function test_block_content_xml_is_valid() {
        global $CFG, $DB;

        load_all_capabilities();
        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => COMPLETION_ENABLED,
                                                                 'groupmode'        => SEPARATEGROUPS, 'groupmodeforce' => 1));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);

        $block = $this->getDataGenerator()->create_block('oua_course_list_teacher');
        $block = block_instance('oua_course_list_teacher', $block);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertEmpty($html); // Student should not see block

        $this->setUser($teacher);

        $block = $this->getDataGenerator()->create_block('oua_course_list_teacher');
        $block = block_instance('oua_course_list_teacher', $block);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html); // Student should not see block

        $expected = '<li class="coursecol course-header col-xs-12">';
        $this->assertContains($expected, $html, "The html output of the html should contain the weeks li hidden");
        $this->assertContains(get_string('nocompletedcourses', 'block_oua_course_list_teacher'), $html,
                              "The output should contain the no completed courses string.");
    }

    /**
     * This test relies on previous tests to run before hand
     * Tests to ensure the block content returns, when cached.
     */
    public function test_course_list_teacher_cache_outputs() {
        $this->resetAfterTest(true);
        $block = $this->getDataGenerator()->create_block('oua_course_list_teacher');
        $block = block_instance('oua_course_list_teacher', $block);
        $this->assertFalse($block->is_empty(), 'There is no block content returned from cache');
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);
    }

    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND course1 has an end date in the past
     *  AND course2 has an end date in the future
     * WHEN we retrieve the output
     * THEN We should see course1 in the previous units tab
     *  AND we should see course2 in the current units tab
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
        $teacher = $this->getDataGenerator()->create_user();


        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1, 'format' => 'invisible'));

        $invisible = course_get_format($course->id);
        $lastweek = time() - (60*60*24*7);
        $invisible->update_course_format_options(array('courseenddate' => $lastweek));



        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

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

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_DATE;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        // Complete the course activities and course.
        $completion = new completion_info($course);

        $modinfo = get_fast_modinfo($course->id);
        $datacm = $modinfo->get_cm($data->cmid);
        $forumcm = $modinfo->get_cm($forum->cmid);

        $completion->update_state($datacm, COMPLETION_COMPLETE, $teacher->id);
        $completion->update_state($forumcm, COMPLETION_COMPLETE, $teacher->id);

        $coursecomplete = $completion->is_course_complete($teacher->id);
        $this->assertFalse($coursecomplete, "Student is not yet marked as complete by completion cron");

        // A user cannot start and complete a course in the same run of completion cron
        // So we have to run, sleep to ensure the time in seconds has advanced, then run it again.

        completion_cron_mark_started();
        completion_cron_criteria();
        sleep(1);
        completion_cron_completions();
        $this->expectOutputRegex('/Marking complete$/', 'The output from cron should end with marking a user as complete.');

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list_teacher');
        $block = block_instance('oua_course_list_teacher', $block);

        $this->setUser($teacher);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertNotContains(get_string('nocompletedcourses', 'block_oua_course_list_teacher'), $html,
                                 "The output should contain a completed course.");

        $coursecomplete = $completion->is_course_complete($teacher->id);
        $this->assertTrue($coursecomplete, "Student must be marked as complete by completion cron");


        // Check for view grade link for completed tab
        $gradebooklink = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
        $url = $gradebooklink->out(false); // don't escape the &
        $viewgrade = get_string('gotogrades', 'block_oua_course_list_teacher');
        $query = "//div[contains(@class,'adminbuttons')]//a[.='$viewgrade']/@href[.='$url']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html, 'Expected 1 "Go to Gradebook" link to '.$url. ' got: '.$html);

        $gradebooklink = new moodle_url('/blocks/message_broadcast/managemessages.php', array('courseid' => $course->id));
        $url = $gradebooklink->out(false); // don't escape the &
        $managemessages = get_string('managemessages', 'block_oua_course_list_teacher');
        $query = "//div[contains(@class,'adminbuttons')]//a[.='$managemessages']/@href[.='$url']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html, 'Expected 1 "Manage Messages" link to '.$url);

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
    public function test_course_list_teacher_hidden() {
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
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course1->id), array('completion' => 1));

        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                 'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id), array('completion' => 1));


        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($teacher->id, $course3->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course3->id), array('completion' => 1));


        $course4 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($teacher->id, $course4->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course4->id), array('completion' => 1));


        $course5 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1));
        $this->getDataGenerator()->enrol_user($teacher->id, $course5->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course5->id), array('completion' => 1));

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list_teacher');
        $block = block_instance('oua_course_list_teacher', $block);

        $block->config = new stdClass();
        $block->config->defaultcourselistlength = 3;


        $this->setUser($teacher);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertContains(get_string('nocompletedcourses', 'block_oua_course_list_teacher'), $html,
                                 "The output should contain a completed course.");
        $this->assertXpathDomQueryResultLengthEquals(5, "//div[contains(@class,'current-units')]//div[contains(@class,'course-name')]", $html,  "The output should contain 5 courses in the current units tab\n\n $html");

        $this->assertXpathDomQueryResultLengthEquals(3, "//div[contains(@class,'current-units')]//ul[contains(@class, 'course-list') and contains(@class, 'visible')]//div[contains(@class,'course-name')]", $html,  "The output should contain 3 courses visisble by default in the current units tab\n\n $html");
        $this->assertXpathDomQueryResultLengthEquals(2, "//div[contains(@class,'current-units')]//div[contains(@class, 'hidden-courses')]//div[contains(@class,'course-name')]", $html,  "The output should contain 2 courses hidden by default in the current units tab\n\n $html");

        $this->markTestIncomplete("Requires test that courses in a configured resources category, are hidden");

    }
    /**
     * GIVEN We have created a course list block in the $SITE course
     *  AND we have 5 courses in the previous units tab
     *  AND we have configured the block to display only 3 units by default
     * WHEN we retrieve the output
     * THEN We should see 3 shown and 2 hidden courses.
     *
     * @test
     */
    public function test_course_list_teacher_completed_hidden() {
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
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1, 'format' => 'invisible'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, $teacherrole->id);
        $invisible = course_get_format($course1->id);
        $lastweek = time() - (60*60*24*7);
        $invisible->update_course_format_options(array('courseenddate' => $lastweek));


        $page = $this->getDataGenerator()->create_module('page', array('course' => $course1->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($teacher, $course1, array($pagecm));

        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1, 'format' => 'invisible'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($teacher, $course2, array($pagecm));
        $invisible = course_get_format($course2->id);
        $lastweek = time() - (60*60*24*7);
        $invisible->update_course_format_options(array('courseenddate' => $lastweek));



        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1, 'format' => 'invisible'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course3->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course3->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($teacher, $course3, array($pagecm));
        $invisible = course_get_format($course3->id);
        $lastweek = time() - (60*60*24*7);
        $invisible->update_course_format_options(array('courseenddate' => $lastweek));




        $course4 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1, 'format' => 'invisible'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course4->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course4->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($teacher, $course4, array($pagecm));
        $invisible = course_get_format($course4->id);
        $lastweek = time() - (60*60*24*7);
        $invisible->update_course_format_options(array('courseenddate' => $lastweek));



        $course5 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'groupmode' => SEPARATEGROUPS,
                                                                  'groupmodeforce'   => 1, 'format' => 'invisible'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course5->id, $teacherrole->id);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course5->id), array('completion' => 1));
        $pagecm = get_coursemodule_from_id('page', $page->cmid);
        $this->add_completion_criteria($teacher, $course5, array($pagecm));
        $invisible = course_get_format($course5->id);
        $lastweek = time() - (60*60*24*7);
        $invisible->update_course_format_options(array('courseenddate' => $lastweek));



        completion_cron_mark_started();
        completion_cron_criteria();
        sleep(1);
        completion_cron_completions();
        $this->expectOutputRegex('/Marking complete$/', 'The output from cron should end with marking a user as complete.');

        // Create a block for the student and verify the list.
        $block = $this->getDataGenerator()->create_block('oua_course_list_teacher');
        $block = block_instance('oua_course_list_teacher', $block);

        $block->config = new stdClass();
        $block->config->defaultcourselistlength = 3;


        $this->setUser($teacher);
        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertXpathDomQueryResultLengthEquals(5, "//div[contains(@class,'completed-units')]//div[contains(@class,'course-name')]", $html,  "The output should contain 5 courses in the completed units tab\n\n $html");

        $this->assertXpathDomQueryResultLengthEquals(3, "//div[contains(@class,'completed-units')]//ul[contains(@class, 'course-list') and contains(@class, 'visible')]//div[contains(@class,'course-name')]", $html,  "The output should contain 3 courses visisble by default in the completed units tab\n\n $html");
        $this->assertXpathDomQueryResultLengthEquals(2, "//div[contains(@class,'completed-units')]//div[contains(@class, 'hidden-courses')]//div[contains(@class,'course-name')]", $html,  "The output should contain 2 courses hidden by default in the completed units tab\n\n $html");

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
     * TEST SORTING IN CORRECT ORDER AND COUNTED CORRECTLY FOR VISIBLE AND HIDDEN
     *
     * GIVEN show visible is 2
     * GIVEN 11 courses
     *  AND 6 is current status,-- 2 courses with no enddate
     *  AND 5 completed status, -- 1 courses with 1 enddate
     *  AND 8 with startdate less than NOW TIME - 3 future dates, 1 with no enddate
     * WHEN display output
     *  completion is determined by enddate
     *  current is sorted by startdate desc
     *  completed is sorted by enddate desc
     */

    public function test_sorted_list() {
        global $CFG, $DB, $PAGE;

        load_all_capabilities();
        $this->resetAfterTest(true);
        $CFG->enablecompletion = true;

        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $this->setUser($teacher);

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
                    'courseenddate' => isset($dates[$i]['courseenddate']) ? $dates[$i]['courseenddate'] : time() + 100,
                )
            );

            // assume at least one activity - renderer will check for activity
            $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));

            // marked course as completed
            if ($dates[$i]['iscomplete']){
                $completion = new completion_completion();
                $completion->userid = $teacher->id;
                $completion->course = $course->id;
                $completion->mark_complete();


            }
            if ($dates[$i]['startdate'] > time()){
                $cntfutures++;
            }
            if (isset($dates[$i]['courseenddate']) && $dates[$i]['courseenddate'] <= time()) {
                $cntcomplete++;
            }

            $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        }

        // simulate config from block settings
        $config = new stdClass();
        $config->defaultcourselistlength = $defaultvisibleitems;

        $courselist = new \block_oua_course_list_teacher\output\course_list_teacher_renderable($config, $teacher->id);
        $renderer = $PAGE->get_renderer('block_oua_course_list_teacher');
        $output = $courselist->export_for_template($renderer);

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

        $currentlist = array_merge($output->courselist, $output->courselisthidden);
        // count is correct
        $this->assertEquals($cntcomplete, count($completelist));
        if (count($currentlist) > $defaultvisibleitems){
            $this->assertEquals(count($output->courselist), $defaultvisibleitems);
        }
        $this->assertEquals($cntcourses-$cntcomplete, count($currentlist));
        // non completed resultlist sort order startdate ASC tc_7, 8, 6, 9, 10, 11
        $this->assertEquals('tc_4', $currentlist[0]['title']);
        $this->assertEquals('tc_2', $currentlist[1]['title']);
        $this->assertEquals('tc_7', $currentlist[2]['title']);
        $this->assertEquals('tc_8', $currentlist[3]['title']);
        $this->assertEquals('tc_6', $currentlist[4]['title']);
        $this->assertEquals('tc_9', $currentlist[5]['title']);
        $this->assertEquals('tc_10', $currentlist[6]['title']);
        $this->assertEquals('tc_11', $currentlist[7]['title']);


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

        $this->setUser($teacher);

        // Let the system assign the default config of list length etc.
        $config = null;
        $courselist = new \block_oua_course_list_teacher\output\course_list_teacher_renderable($config, $teacher->id);
        $renderer = $PAGE->get_renderer('block_oua_course_list_teacher');

        // Render the course list with no error thrown.
        $html = $renderer->render_course_list_teacher($courselist);
        $this->assertValidHtml($html);

        // Test course listed.
        $querycoursetitle = "//div[contains(@class,'course-name')]/a[.='Test course 1']";
        $this->assertXpathDomQueryResultLengthEquals(1, $querycoursetitle, $html,  "The output should show 'Test course 1' \n\n $html");

        // And there is no next label.
        $querywhatsnext = "//*[contains(@class,'whatsnext')]";
        $this->assertXpathDomQueryResultLengthEquals(0, $querywhatsnext, $html,  "The course should NOT contain whats next label \n\n $html");
    }

}
