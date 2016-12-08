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
 *  Unit tests for message broadcast block
 *
 * @package    blocks
 * @subpackage message_broadcast
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

class block_oua_course_progress_bar_testcase extends advanced_testcase {

    protected function setUp() {
        global $CFG;
    }

    static function setAdminUser() {
        global $USER;
        parent::setAdminUser();
        // The logged in user needs email, country and city to do certain things
        $USER->email = 'khoi.le@open.edu.au';
        $USER->country = 'AU';
        $USER->city = 'Melbourne';
    }

    /**
     * GIVEN We have created a progress bar in the $SITE course
     * WHEN we retrieve the output
     * THEN the percentage is 0 and there is no end date.
     *
     * @test
     */
    public function test_block_content_with_no_completion() {
        global $PAGE;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        $PAGE->set_url('/blocks/test');

        $block = $this->getDataGenerator()->create_block('oua_course_progress_bar');
        $block = block_instance('oua_course_progress_bar', $block);

        $html = $block->get_content()->text;

        $expected = <<<BLOCK
<div class="completion_bar_wrapper" title="0% complete">
    <h2 class="progress">0%</h2>
    <div class="course_completion_bar">
        <div class="course_completion_bar_keylines">&nbsp;</div>
        <div class="course_completion_bar_internal" style="width: 0%">&nbsp;</div>
    </div>
        <div class="completition_bar_dates">
            <span class="startdate custom_2">Jan  1</span>
        </div>
</div>
BLOCK;

        $this->assertEquals($expected, $html, "The html output of the message should be in the format expected");
    }






    /**
     * GIVEN listing of the $SITE completed courses
     *  AND all complete enabled activities are PARTIALLY completed
     * WHEN we view the completed units (courses)
     * THEN the progress bar should display the % of activities completed, which is less than 100%
     */
    public function test_invisible_progress_is_NOT_100_when_course_is_complete() {

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

        // mark course as completed because we are testing progress bar for completed courses
        $completion = new completion_completion();
        $completion->userid = $student->id;
        $completion->course = $course->id;
        $completion->mark_complete();

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course->id), array('completion' => 1, 'visible' => 0));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);

        // Forum completed
        $courseActivity = new completion_info($course);
        $courseActivity->update_state($cmforum, COMPLETION_COMPLETE);

        $activitiesResult = core_completion_external::get_activities_completion_status($course->id, $student->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $activitiesResult = external_api::clean_returnvalue(core_completion_external::get_activities_completion_status_returns(), $activitiesResult);


        // We added 4 activities, but only 3 with completion enabled and one of those is hidden.
        $this->assertCount(2, $activitiesResult['statuses']);

        $activitiesfound = 0;
        foreach ($activitiesResult['statuses'] as $status) {
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

        // need to assert that the course has a completed status
        $courseInfo = new completion_info($course);
        $this->assertTrue($courseInfo->is_course_complete($student->id));

        // course is completed, 2 completion activities enabled, 1 (forum) is completed
        $expectedPercent = round(1*100/2);

        $completion = new \local_oua_completion\oua_completion_info($course);
        $userProgress = $completion->get_user_progress($student->id);

        $this->assertEquals($expectedPercent, $userProgress);
    }

    /**
     * GIVEN listing of the $SITE completed courses
     *  AND all complete enabled activities are completed
     * WHEN we view the completed units (courses)
     * THEN the progress bar should display the % of activities completed, which is 100%
     */
    public function test_invisible_progress_is_100_when_course_is_complete() {
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

        // mark course as completed because we are testing progress bar for completed courses
        $completion = new completion_completion();
        $completion->userid = $student->id;
        $completion->course = $course->id;
        $completion->mark_complete();

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course->id), array('completion' => 1, 'visible' => 0));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);

        // Forum completed
        $courseActivity = new completion_info($course);
        $courseActivity->update_state($cmforum, COMPLETION_COMPLETE);

        // Data completed
        $courseActivity = new completion_info($course);
        $courseActivity->update_state($cmdata, COMPLETION_COMPLETE);

        $activitiesResult = core_completion_external::get_activities_completion_status($course->id, $student->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $activitiesResult = external_api::clean_returnvalue(core_completion_external::get_activities_completion_status_returns(), $activitiesResult);


        // We added 4 activities, but only 3 with completion enabled and one of those is hidden.
        $this->assertCount(2, $activitiesResult['statuses']);

        $activitiesfound = 0;
        foreach ($activitiesResult['statuses'] as $status) {
            if ($status['cmid'] == $forum->cmid and $status['modname'] == 'forum' and $status['instance'] == $forum->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_COMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            } else if ($status['cmid'] == $data->cmid and $status['modname'] == 'data' and $status['instance'] == $data->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_COMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            }
        }
        $this->assertEquals(2, $activitiesfound);

        // need to assert that the course has a completed status
        $courseInfo = new completion_info($course);
        $this->assertTrue($courseInfo->is_course_complete($student->id));

        // course is completed, 2 completion activities enabled, 1 (forum) is completed, 1 (data) is completed 100%
        $expectedPercent = 100;

        $completion = new \local_oua_completion\oua_completion_info($course);
        $userProgress = $completion->get_user_progress($student->id);

        $this->assertEquals($expectedPercent, $userProgress);
    }

    /**
     * GIVEN listing of the $SITE completed courses
     *  AND all complete enabled activities are NOT completed
     * WHEN we view the completed units (courses)
     * THEN the progress bar should display the % of activities completed, which is 0%
     */
    public function test_invisible_progress_is_0_when_course_is_complete() {
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

        // mark course as completed because we are testing progress bar for completed courses
        $completion = new completion_completion();
        $completion->userid = $student->id;
        $completion->course = $course->id;
        $completion->mark_complete();

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course->id), array('completion' => 1, 'visible' => 0));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $this->setUser($student);

        $activitiesResult = core_completion_external::get_activities_completion_status($course->id, $student->id);
        // We need to execute the return values cleaning process to simulate the web service server.
        $activitiesResult = external_api::clean_returnvalue(core_completion_external::get_activities_completion_status_returns(), $activitiesResult);

        // We added 4 activities, but only 3 with completion enabled and one of those is hidden.
        $this->assertCount(2, $activitiesResult['statuses']);

        $activitiesfound = 0;
        foreach ($activitiesResult['statuses'] as $status) {
            if ($status['cmid'] == $forum->cmid and $status['modname'] == 'forum' and $status['instance'] == $forum->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_INCOMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            } else if ($status['cmid'] == $data->cmid and $status['modname'] == 'data' and $status['instance'] == $data->id) {
                $activitiesfound++;
                $this->assertEquals(COMPLETION_INCOMPLETE, $status['state']);
                $this->assertEquals(COMPLETION_TRACKING_MANUAL, $status['tracking']);
            }
        }
        $this->assertEquals(2, $activitiesfound);

        // need to assert that the course has a completed status
        $courseInfo = new completion_info($course);
        $this->assertTrue($courseInfo->is_course_complete($student->id));

        // course is completed, 2 completion activities enabled, none completed
        $expectedPercent = 0;

        $completion = new \local_oua_completion\oua_completion_info($course);
        $userProgress = $completion->get_user_progress($student->id);

        $this->assertEquals($expectedPercent, $userProgress);
    }
}
