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

defined('MOODLE_INTERNAL') || die;

class course_format_invisible_testcase extends advanced_testcase
{

    /**
     * Setup a reflection class for testing methods.
     * @param $classname Class name
     * @param $functionname Function within the class.
     * @return ReflectionMethod Return the method to run.
     */
    protected function getmethod($classname, $functionname) {
        $class = new ReflectionClass($classname);
        $method = $class->getMethod($functionname);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Setup for these tests.
     */
    public function setup() {
        global $CFG;

        require_once($CFG->dirroot . '/course/format/invisible/lib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        parent::setUp();
    }

    /**
     * GIVEN a completion activity is set in the configuration of the course format
     * WHEN we ask for the module that is to be displayed after course completion
     * THEN we must get the module we configured returned to us.
     */
    public function test_cm_after_course_completion_reports_the_correct_module() {
        $this->resetAfterTest(true);

        // Setup course that has format_invisible as the course format.
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));

        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        $forum1 = $forumgenerator->create_instance(array('course' => $course->id));
        $forum2 = $forumgenerator->create_instance(array('course' => $course->id));
        $forum3 = $forumgenerator->create_instance(array('course' => $course->id, 'visible' => 0));
        $forum4 = $forumgenerator->create_instance(array('course' => $course->id));

        $modinfo = get_fast_modinfo($course);

        $reflect = $this->getmethod('format_invisible', 'cm_after_course_completion');
        $invisible = course_get_format($course->id);

        $invisible->update_course_format_options(array('coursepreviewactivity' => $forum4->cmid));

        // Set the completion activity to 3.
        $invisible->update_course_format_options(array('coursecompleteactivity' => $forum2->cmid));
        $data = $reflect->invokeArgs($invisible, array($modinfo));
        $this->assertEquals($forum2->cmid, $data->id);
        $this->assertEquals($forum2->id, $data->instance, 'You must get the expected instance of the forum activity.');

        $invisible->update_course_format_options(array('coursecompleteactivity' => $forum3->cmid));
        $data = $reflect->invokeArgs($invisible, array($modinfo));
        $this->assertNull($data, 'Invalid item, should return null.');

        $invisible->update_course_format_options(array('coursecompleteactivity' => $forum1->cmid));
        $data = $reflect->invokeArgs($invisible, array($modinfo));
        $this->assertEquals($forum1->cmid, $data->id);
        $this->assertEquals($forum1->id, $data->instance, 'You must get the expected instance of the forum activity.');

        $invisible->update_course_format_options(array('coursecompleteactivity' => null));
        $data = $reflect->invokeArgs($invisible, array($modinfo));

        $this->assertNull($data, 'When no module is set as a completion module we expect null.');
    }

    /**
     * GIVEN an start activity is set in the configuration of the course format
     * WHEN we ask for the module that is to be displayed before the start of the course
     * THEN we must get the module we configured returned to us.
     */
    public function test_cm_before_course_start_returns_the_correct_module_before_course_start() {
        $this->resetAfterTest(true);

        // Setup course that has format_invisible as the course format.
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));

        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        $forum1 = $forumgenerator->create_instance(array('course' => $course->id));
        $forum2 = $forumgenerator->create_instance(array('course' => $course->id));
        $forum3 = $forumgenerator->create_instance(array('course' => $course->id, 'visible' => 0));
        $forum4 = $forumgenerator->create_instance(array('course' => $course->id));

        $modinfo = get_fast_modinfo($course);

        $reflect = $this->getmethod('format_invisible', 'cm_before_course_start');
        $invisible = course_get_format($course->id);

        $invisible->update_course_format_options(array('coursecompleteactivity' => $forum4->cmid));

        // Set the completion activity to 3.
        $invisible->update_course_format_options(array('coursepreviewactivity' => $forum2->cmid));
        $data = $reflect->invokeArgs($invisible, array($modinfo));
        $this->assertEquals($forum2->cmid, $data->id);
        $this->assertEquals($forum2->id, $data->instance, 'You must get the expected instance of the forum activity.');

        $invisible->update_course_format_options(array('coursepreviewactivity' => $forum3->cmid));
        $data = $reflect->invokeArgs($invisible, array($modinfo));
        $this->assertNull($data, 'Invalid item, should return null.');

        $invisible->update_course_format_options(array('coursepreviewactivity' => $forum1->cmid));
        $data = $reflect->invokeArgs($invisible, array($modinfo));
        $this->assertEquals($forum1->cmid, $data->id);
        $this->assertEquals($forum1->id, $data->instance, 'You must get the expected instance of the forum activity.');

        $invisible->update_course_format_options(array('coursepreviewactivity' => null));
        $data = $reflect->invokeArgs($invisible, array($modinfo));

        $this->assertNull($data, 'When no module is set as a preview module we expect null.');
    }

    /**
     * GIVEN we have a course with a start date
     * WHEN the time is less than the start date
     * THEN the course must not have started.
     *
     * GIVEN we have a course with a start date
     * WHEN the time is greater than or equal to the start date
     * THEN the course must have started.
     */
    public function test_course_start_is_processed_correctly() {

        $this->resetAfterTest(true);

        // Setup course that has format_invisible as the course format.
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible', 'startdate' => 100));

        $reflect = $this->getmethod('format_invisible', 'course_started');
        $invisible = course_get_format($course->id);

        $invisible->update_course_format_options(array('courseenddate' => 1500));

        $data = $reflect->invokeArgs($invisible, array());
        $this->assertTrue($data, 'This course uses internal start date and should have started.');

        $data = $reflect->invokeArgs($invisible, array(50));
        $this->assertFalse($data, 'Course start date is after 50 seconds, it should not be open.');

        $data = $reflect->invokeArgs($invisible, array(100));
        $this->assertTrue($data, 'This course should be open.');

        $data = $reflect->invokeArgs($invisible, array(1600));
        $this->assertTrue($data, 'This course should be open.');
    }

    /**
     * GIVEN we have a course with an end date
     * WHEN the time is less than the end date
     * THEN the course must not have finished.
     *
     * GIVEN we have a course with an end date
     * WHEN the time is greater than the end date
     * THEN the course must have finished.
     */
    public function test_course_completed_returns_whether_the_couse_is_finished() {
        $this->resetAfterTest(true);

        // Setup course that has format_invisible as the course format.
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible', 'startdate' => 100));

        $reflect = $this->getmethod('format_invisible', 'course_completed');
        $invisible = course_get_format($course->id);

        $invisible->update_course_format_options(array('courseenddate' => 1500));

        $data = $reflect->invokeArgs($invisible, array(0, 50));
        $this->assertFalse($data, 'Course before the start should not be completed.');

        $data = $reflect->invokeArgs($invisible, array(0, 1499));
        $this->assertFalse($data, 'Course should still be open.');
        $data = $reflect->invokeArgs($invisible, array(0, 1500));
        $this->assertFalse($data, 'Course should still be open.');
        $data = $reflect->invokeArgs($invisible, array(0, 1501));
        $this->assertTrue($data, 'Course should still be closed as the date has passed.');
    }


    /**
     * GIVEN a user is enrolled in a course
     *   AND the course started
     *   AND the course has not ended
     * WHEN the user completed the final activity
     * THEN the course will be considered finished.
     */
    public function test_user_sent_to_end_of_the_course_when_course_completed_by_course_completion() {
        // User, enrol user in course.
        // Complete items in course.
        // Test if completed.
        // Also make dates passed, and test completed.
        $this->markTestIncomplete('This has not been implemented, we require course completion configuration.');
    }

    /**
     * GIVEN we have the required modules
     * WHEN we ask for the next/previous or both.
     * THEN the correct next and/or previous module are returned.
     */
    public function test_module_return() {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible', 'startdate' => time(), 'idnumber' => 1));

        $reflect = $this->getmethod('format_invisible', 'module_return');
        $invisible = course_get_format($course->id);
        $cmnext = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance(array('course' => $course->id));
        $cmprev = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance(array('course' => $course->id));

        try {
            $reflect->invokeArgs($invisible, array('what', null, null));
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Type now valid, must be "next","previous","both".', $e->getMessage());
        }

        $data = $reflect->invokeArgs($invisible, array('next', null, null));
        $this->assertNull($data);
        $data = $reflect->invokeArgs($invisible, array('next', null, $cmnext));
        $this->assertEquals($cmnext, $data);
        $data = $reflect->invokeArgs($invisible, array('next', $cmprev, null));
        $this->assertNull($data);

        $data = $reflect->invokeArgs($invisible, array('previous', null, null));
        $this->assertNull($data);
        $data = $reflect->invokeArgs($invisible, array('previous', null, $cmnext));
        $this->assertNull($data);
        $data = $reflect->invokeArgs($invisible, array('previous', $cmprev, null));
        $this->assertEquals($cmprev, $data);

        $data = $reflect->invokeArgs($invisible, array('both', null, null));
        $this->assertEquals(array(null, null), $data);
        $data = $reflect->invokeArgs($invisible, array('both', null, $cmnext));
        $this->assertEquals(array(null, $cmnext), $data);
        $data = $reflect->invokeArgs($invisible, array('both', $cmprev, $cmnext));
        $this->assertEquals(array($cmprev, $cmnext), $data);
        $data = $reflect->invokeArgs($invisible, array('both', $cmprev, null));
        $this->assertEquals(array($cmprev, null), $data);
    }

    /**
     * GIVEN a developer is attempting to use module_to_display
     * WHEN they put invalid parameters into the function call
     * THEN the develop will receive an exception about invalid coding.
     */
    public function test_module_to_display_invalid_parameters() {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible', 'startdate' => time(), 'idnumber' => 1));

        $invisible = course_get_format($course->id);
        $this->setExpectedException('coding_exception', 'If using $restricttosection, you must send a $currentcmid.');
        $invisible->module_to_display(null, false, true, true);
    }

    /**
     * GIVEN a user is navigating through a course
     * WHEN the click to each page
     * THEN the previous and next links are correct.
     */
    public function test_module_to_display_progression() {
        global $CFG;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible',
            'startdate' => time() - 5000,
            'idnumber' => 7000,
            'enablecompletion' => COMPLETION_ENABLED));

        $moduledata = array('completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiongradeitemnumber' => 0, // Null to allow manual completion.
            'completionview' => 1,
            'completionexpected' => 1200,
            'completiongradeitemnumber' => null,
        );

        $courseformat = course_get_format($course);

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);

        $cmfirstlook = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record, $moduledata);
        $cminvisible = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatainvisible);
        $cm1 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledata);
        $this->indent_module($cmexcluded);
        $cm2 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);

        $courseformat->update_course_format_options(array('coursepreviewactivity' => $cmfirstlook->cmid));

        $invisible = course_get_format($course->id);
        $modinfo = get_fast_modinfo($course);

        $next = $invisible->module_to_display($modinfo);
        $url = $invisible->get_view_url(null);
        $this->assertEquals($next->id, $cmfirstlook->cmid, 'With no firstlook module, we get null.');
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $url);

        // When a project is set with that module, we should get the wiki module.
        $next = $invisible->module_to_display($modinfo);
        $url = $invisible->get_view_url(null);
        $this->assertEquals($next->id, $cmfirstlook->cmid);
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $url);
        $completion = new completion_info($course);

        // Default module with cmfirstlook is complete is cm1.
        $completion->set_module_viewed($next);
        $next = $invisible->module_to_display($modinfo);
        $url = $invisible->get_view_url(null);
        $this->assertNotEquals($cminvisible->id, $next->id, 'This module should have been excluded.');
        $this->assertEquals($modinfo->get_cm($cm1->cmid)->url, $url);
        $this->assertEquals($next->id, $cm1->cmid);

        // Default module with cm1 complete is cm2.
        $completion->set_module_viewed($next);
        $next = $invisible->module_to_display($modinfo);
        $url = $invisible->get_view_url(null);
        $this->assertNotEquals($cmexcluded->id, $next->id, 'This module should have been excluded.');
        $this->assertEquals($modinfo->get_cm($cm2->cmid)->url, $url);
        $this->assertEquals($cm2->cmid, $next->id);

        // Module after cm2 is still cm2 as it's the end of the course.
        $completion->set_module_viewed($next);
        $next = $invisible->module_to_display($modinfo);
        $url = $invisible->get_view_url(null);
        $this->assertEquals($cm2->cmid, $next->id, 'This is the end of the course, should be the same id.');
        $this->assertEquals($modinfo->get_cm($cm2->cmid)->url, $url);

        // Module after firstlook is cm1.
        $next = $invisible->module_to_display($modinfo, $cmfirstlook->cmid);
        $url = $invisible->get_view_url(null, array('moduleafter' => $cmfirstlook->cmid));
        $this->assertEquals($cm1->cmid, $next->id);
        $this->assertEquals($modinfo->get_cm($cm1->cmid)->url, $url);

        // Module after cm1 is cm2.
        $next = $invisible->module_to_display($modinfo, $cm1->cmid);
        $url = $invisible->get_view_url(null, array('moduleafter' => $cm1->cmid));
        $this->assertEquals($cm2->cmid, $next->id);
        $this->assertEquals($modinfo->get_cm($cm2->cmid)->url, $url);

        // Add an invisible completion module.
        $cmcomplete = $this->getDataGenerator()->get_plugin_generator('mod_url')
            ->create_instance($record, $moduledatainvisible);
        $courseformat->update_course_format_options(array('coursepreviewactivity' => $cmcomplete->cmid));
        $modinfo = get_fast_modinfo($course);
        $next = $invisible->module_to_display($modinfo);
        $url = $invisible->get_view_url(null);
        $this->assertNotEquals($cmcomplete->cmid, $next->id, 'Completion is hidden, should be $cm2');
        $this->assertEquals($cm2->cmid, $next->id, 'Should still be the visible end of the course');
        $this->assertEquals($modinfo->get_cm($cm2->cmid)->url, $url);

        // Add a visible completion module, we should go there.
        $cmcomplete2 = $this->getDataGenerator()->get_plugin_generator('mod_url')
            ->create_instance($record, $moduledata);
        $courseformat->update_course_format_options(array('coursepreviewactivity' => $cmcomplete2->cmid));
        $modinfo = get_fast_modinfo($course);
        $next = $invisible->module_to_display($modinfo);
        $this->assertEquals($cmcomplete2->cmid, $next->id, 'Completion module should be displayed.');

        // Complete the completion module.  It should still be displayed as it's the last item.
        $completion->set_module_viewed($next);
        $next = $invisible->module_to_display($modinfo);
        $this->assertEquals($cmcomplete2->cmid, $next->id, 'Completion module should be displayed.');

        // Confirm correct previous and next modules.
        list($prev, $next) = $invisible->module_to_display($modinfo, $cm2->cmid, false, false, 'both');
        $this->assertEquals($cm1->cmid, $prev->id, 'Previous module should be cm1 as it is before cm2.');
        $this->assertEquals($cmcomplete2->cmid, $next->id, 'Completion screen is after cm2.');

        // Confirm null for next.
        list($prev, $next) = $invisible->module_to_display($modinfo, $cmcomplete2->cmid, false, false, 'both');
        $this->assertNull($next, 'The should be no next module.');
        $this->assertEquals($cm2->cmid, $prev->id, 'Previous module should be cm1.');

        // Confirm null for prev.
        list ($prev, $next) = $invisible->module_to_display($modinfo, $cmfirstlook->cmid, false, false, 'both');
        $this->assertEquals($cm1->cmid, $next->id, 'Next should be cm1.');
        $this->assertNull($prev, 'There is nothing before the first module.');
    }

    /**
     * Indent a created module.  Moodle's generator function doesn't handle indent correctly.
     * @param $cm stdClass The course module to indent.
     */
    private function indent_module($cm) {
        global $DB;
        // Moodle 2.9 ignores indent as an option when creating plugins, we need to update the database.
        $coursemoduledata = $DB->get_record('course_modules', array('id' => $cm->cmid));
        $coursemoduledata->indent = 5;
        $DB->update_record('course_modules', $coursemoduledata);
        rebuild_course_cache($cm->course);
    }

    public function test_module_is_hidden_when_indented_5_times() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));

        $courseformat = course_get_format($course);

        $record = array('course' => $course->id);

        $cm = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record);

        $modinfo = get_fast_modinfo($course);

        $this->assertFalse($courseformat->module_is_hidden_from_view($modinfo->get_cm($cm->cmid)));

        $this->indent_module($cm);
        $modinfo = get_fast_modinfo($course);
        $this->assertTrue($courseformat->module_is_hidden_from_view($modinfo->get_cm($cm->cmid)));
    }

    /**
     * Ensure we limit the output of next and previous to section
     * boundaries when calling module_to_display.  get_view_url
     * does not consider section boundaries so it's not included.
     * This type of restrictions is used for next/previous type buttons.
     *
     * GIVEN a user is navigating through a course and
     *   AND they are using next/previous in a section
     * WHEN the click to each page
     * THEN the previous and next links are correct.
     */
    public function test_module_to_display_section_restriction() {
        global $CFG;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible',
            'startdate' => time() - 5000,
            'idnumber' => 7000,
            'enablecompletion' => COMPLETION_ENABLED,
            'numsections' => 5
        ));

        $moduledata = array('completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiongradeitemnumber' => 0, // Null to allow manual completion.
            'completionview' => 1,
            'completionexpected' => 1200,
            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);

        $moduledatasection0 = array_merge($moduledata, array('section' => 0));
        $moduledatasection1 = array_merge($moduledata, array('section' => 1));
        $moduledatasection2 = array_merge($moduledata, array('section' => 2));
        $moduledatasection3 = array_merge($moduledata, array('section' => 3));
        $moduledatasection4 = array_merge($moduledata, array('section' => 4));

        // Initial section has a firstlook and a course forum in it.
        $cmfirstlook = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record, $moduledatasection0);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection0);
        $this->indent_module($cmexcluded);

        // First section has a forum and 3 pages.
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection1);
        $this->indent_module($cmexcluded);
        $cms11 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);
        $cms12 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);
        $cms13 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);

        // Second section has forum and 2 pages.
        $cms21 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection2);
        $cms22 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection2);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection2);
        $this->indent_module($cmexcluded);

        // Third section has a forum and single page.
        $cms31 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection3);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection3);
        $this->indent_module($cmexcluded);

        // Forth section is empty.

        // Now test the progression of module after with section restriction.
        $invisible = course_get_format($course->id);

        $modinfo = get_fast_modinfo($course->id);
        try {
            $invisible->module_to_display($modinfo, false, false, true, 'both');
            $this->fail('Must throw an exception when using section restriction and no current cmid.');
        } catch (coding_exception $e) {
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: '.
                'If using $restricttosection, you must send a $currentcmid.', $e->getMessage());
        }

        // Section 0 verification.
        list($prev, $next) = $invisible->module_to_display($modinfo, $cmfirstlook->cmid, false, true, 'both');
        $this->assertNull($prev, 'There should be no previous module in this section.');
        $this->assertNull($next, 'There should be no next module in this section.');

        // Section 1 verification.
        list($prev, $next) = $invisible->module_to_display($modinfo, $cms11->cmid, false, true, 'both');
        $this->assertNull($prev, 'There should be no previous module in this section.');
        $this->assertEquals($cms12->cmid, $next->id);
        list($prev, $next) = $invisible->module_to_display($modinfo, $cms12->cmid, false, true, 'both');
        $this->assertEquals($cms11->cmid, $prev->id);
        $this->assertEquals($cms13->cmid, $next->id);
        list($prev, $next) = $invisible->module_to_display($modinfo, $cms13->cmid, false, true, 'both');
        $this->assertEquals($cms12->cmid, $prev->id);
        $this->assertNull($next, 'There should be no next module in this section.');

        // Section 2 verification.
        list($prev, $next) = $invisible->module_to_display($modinfo, $cms21->cmid, false, true, 'both');
        $this->assertNull($prev, 'There should be no previous module in this section.');
        $this->assertEquals($cms22->cmid, $next->id);
        list($prev, $next) = $invisible->module_to_display($modinfo, $cms22->cmid, false, true, 'both');
        $this->assertEquals($cms21->cmid, $prev->id);
        $this->assertNull($next, 'There should be no next module in this section.');

        // Section 3 verification.
        list($prev, $next) = $invisible->module_to_display($modinfo, $cms31->cmid, false, true, 'both');
        $this->assertNull($prev, 'There should be no previous module in this section.');
        $this->assertNull($next, 'There should be no next module in this section.');
    }

    /**
     * GIVEN we have a course with modules that can be viewed.
     * AND the course has not yet started
     * THEN we will be redirected to the before start module when set.
     */
    public function test_correct_module_selected_before_course_start() {
        //call page_set_course
        //call page_set_cm
        // the above is what the code does, we should be redirected unless we set the cm we are on.
        global $CFG, $PAGE;

        $defaultpage = clone($PAGE);

        $PAGE->set_url('/course/view.php');

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible',
            'startdate' => time() + 5000,
            'idnumber' => 7000,
            'enablecompletion' => COMPLETION_ENABLED));

        $moduledata = array('completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiongradeitemnumber' => 0, // Null to allow manual completion.
            'completionview' => 1,
            'completionexpected' => 1200,
            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);
        $cmfirstlook = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatainvisible);
        $cm1 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);

        $invisible = course_get_format($course->id);
        $invisible->update_course_format_options(array('coursepreviewactivity' => $cm1->cmid));

        // Redirect on different module.
        $PAGE->set_url('/mod/other/url.php');

        // Unit tests and CLI scripts don't support redirects.  As we will be redirecting,
        // we catch the thrown exception to know that we did redirect.
        try {
            $PAGE->set_cm(get_coursemodule_from_id('wiki', $cmfirstlook->cmid));
            $this->fail('We should have redirected and thrown an exception.');
        } catch (moodle_exception $e) {
            $this->assertEquals('Unsupported redirect detected, script execution terminated', $e->getMessage());
            $trace = $e->getTrace();
            $url = $trace[0]['args'][0];
            $modinfo = get_fast_modinfo($course);
            $this->assertEquals($modinfo->get_cm($cm1->cmid)->url, $url, 'Should redirect to page.');
        }

        try {
            // Confirm module is still set to the one when we are before the course start.
            $_GET['moduleafter'] = $cmfirstlook->cmid;
            $PAGE->set_cm(get_coursemodule_from_id('wiki', $cmfirstlook->cmid));
            $this->fail('We should have redirected and thrown and exception.');
        } catch (moodle_exception $e) {
            $this->assertEquals('Unsupported redirect detected, script execution terminated', $e->getMessage());
            $trace = $e->getTrace();
            $url = $trace[0]['args'][0];
            $modinfo = get_fast_modinfo($course);
            $this->assertEquals($modinfo->get_cm($cm1->cmid)->url, $url, 'Should redirect to visible page.');
            unset($_GET['moduleafter']);
        }

        // Reset $PAGE so we can assign a course module again.
        $PAGE = $defaultpage;

        // Confirm no redirect on correct module.  This allows any page of that module to be used, eg forum.
        // There are no asserts, if this fails you will be redirected and that is the condition that should not happen.
        $PAGE->set_cm(get_coursemodule_from_id('page', $cm1->cmid));
    }

    /**
     * GIVEN we have a course with modules that can be viewed.
     * WHEN we attempt to view those modules
     * THEN we are directed to the appropriate module.
     */
    public function test_module_to_display_page_set_course() {
        global $CFG, $PAGE;

        $PAGE->set_url('/course/view.php');

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible',
            'startdate' => time() - 5000,
            'idnumber' => 7000,
            'enablecompletion' => COMPLETION_ENABLED));

        $moduledata = array('completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiongradeitemnumber' => 0, // Null to allow manual completion.
            'completionview' => 1,
            'completionexpected' => 1200,
            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);
        $cmfirstlook = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatainvisible);
        $cm1 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);

        $invisible = course_get_format($course->id);

        // Unit tests and CLI scripts don't support redirects.  As we will be redirecting,
        // we catch the thrown exception to know that we did redirect.
        try {
            $invisible->page_set_course($PAGE);
            $this->fail('We should have redirected and thrown an exception.');
        } catch (moodle_exception $e) {
            $this->assertEquals('Unsupported redirect detected, script execution terminated', $e->getMessage());
            $trace = $e->getTrace();
            $url = $trace[0]['args'][0];
            $modinfo = get_fast_modinfo($course);
            $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $url, 'Should redirect to wiki.');
        }

        try {
            // Confirm module after behaves as expected.
            $_GET['moduleafter'] = $cmfirstlook->cmid;
            $invisible->page_set_course($PAGE);
            $this->fail('We should have redirected and thrown and exception.');
        } catch (moodle_exception $e) {
            $this->assertEquals('Unsupported redirect detected, script execution terminated', $e->getMessage());
            $trace = $e->getTrace();
            $url = $trace[0]['args'][0];
            $modinfo = get_fast_modinfo($course);
            $this->assertEquals($modinfo->get_cm($cm1->cmid)->url, $url, 'Should redirect to visible page.');
            unset($_GET['moduleafter']);
        }

        // Non course view continues.
        $PAGE->set_url('/course/edit.php');
        $return = $invisible->page_set_course($PAGE);
        $this->assertNull($return, 'Should obtain null and no redirect normal page_set_course');
    }

    /**
     * GIVEN we fully configured course with startdate, enddate, coursepreviewactivity, coursecompleteactivity and the cobranding logo
     * WHEN we backup and restore the course
     * THEN the startdate, enddate, coursepreviewactivity, coursecompleteactivity and logo are set correctly in the new course.
     */
    public function test_course_format_options_restore() {
        global $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $CFG->enablecompletion = true;

        $starttime = strtotime('1990-01-01');
        $endtime = strtotime('1990-01-02');

        // Create a course with some availability data set.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'invisible', 'numsections' => 3, 'startdate' => $starttime,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));

        $courseobject = format_base::instance($course->id);
        $record = array('course' => $course->id);
        $cmpreview = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record);
        $cmcomplete = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record);

        $data = array('courseenddate' => $endtime,
                'coursepreviewactivity' => $cmpreview->cmid,
                'coursecompleteactivity' => $cmcomplete->cmid,
                'cobrandingname' => 'Cobranding Backup Name');
        $courseobject->update_course_format_options($data);
        $this->add_branding_logo($courseobject);
        // Backup and restore it, and ensure the time is >= 1 day as start date only moves by 1 day.
        $newcourseid = $this->backup_and_restore($course, $starttime + 5*86400);
        $newmodinfo = get_fast_modinfo($newcourseid);
        $newcourseobject = format_base::instance($newcourseid);

        $options = $newcourseobject->get_format_options();
        $this->assertArrayHasKey('courseenddate', $options);
        $this->assertArrayHasKey('coursepreviewactivity', $options);
        $this->assertArrayHasKey('coursecompleteactivity', $options);

        $this->assertEquals($endtime + 5*86400, $options['courseenddate'], 'Course end date must be rolled forward.');
        // How do I determine the items.
        $instancelist = $newmodinfo->get_instances_of('wiki');
        $newpreview = reset($instancelist);
        $instancelist = $newmodinfo->get_instances_of('page');
        $newcomplete = reset($instancelist);

        $this->assertEquals($cmpreview->name, $newpreview->name, 'Names of preview must be the same.');
        $this->assertNotEquals($cmpreview->cmid, $newpreview->id, 'You must get a different course module id.');
        $this->assertNotEquals($cmpreview->id, $newpreview->instance, 'You must get a different instance after restore.');
        $this->assertNotEquals($cmpreview->cmid, $options['coursepreviewactivity'], 'You must get a different course module id.');
        $this->assertEquals($newpreview->id, $options['coursepreviewactivity'], 'The new must match the expected restore.');

        $this->assertEquals($cmcomplete->name, $newcomplete->name, 'Names of complete module must be the same.');
        $this->assertNotEquals($cmcomplete->cmid, $newcomplete->id, 'You must get a different course module id.');
        $this->assertNotEquals($cmcomplete->id, $newcomplete->instance, 'You must get a different instance after restore.');
        $this->assertNotEquals($cmcomplete->cmid, $options['coursecompleteactivity'], 'You must get a different course module id.');
        $this->assertEquals($newcomplete->id, $options['coursecompleteactivity'], 'The new must match the expected restore.');

        $this->assertEquals('Cobranding Backup Name', $options['cobrandingname'], 'The name should be backed up and restored.');
        $this->assertNotNull($newcourseobject->get_branding_url(), 'The brand logo should be backed up and restored, url must exist.');
        $url = $newcourseobject->get_branding_url();
        $this->assertInstanceOf('moodle_url', $url);
        $this->assertEquals('/moodle/pluginfile.php', $url->get_path(false));
        $this->assertStringEndsWith('/format_invisible/cobrandinglogo/0/logo.jpg', $url->get_path());
    }

    public function test_branding_url_gives_null_when_no_file_exists() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'invisible', 'numsections' => 3, 'startdate' => time(),
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));

        $courseobject = format_base::instance($course->id);
        $this->assertNull($courseobject->get_branding_url(), 'No branding it set, we must be null.');
    }

    public function test_branding_url_gives_a_url_when_a_file_exists() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'invisible', 'numsections' => 3, 'startdate' => time(),
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));

        $courseobject = format_base::instance($course->id);

        // Upload a file into the files area for the course format.
        $this->add_branding_logo($courseobject);

        $this->assertNotNull($courseobject->get_branding_url(), 'Branding image is set, url must exist.');
        $url = $courseobject->get_branding_url();

        $this->assertInstanceOf('moodle_url', $url);
        $this->assertEquals('/moodle/pluginfile.php', $url->get_path(false));
        $this->assertStringEndsWith('/format_invisible/cobrandinglogo/0/logo.jpg', $url->get_path());
    }

    protected function add_branding_logo($courseobject) {
        // Add files.
        $fs = get_file_storage();
        $context = context_course::instance($courseobject->get_courseid());
        $filerecord = array('component' => 'format_invisible', 'filearea' => 'cobrandinglogo',
            'contextid' => $context->id, 'itemid' => 0, 'filepath' => '/');
            $filerecord['filename'] = 'logo.jpg';

        // Generate random binary data (different for each file so it
        // doesn't compress unrealistically).
        $data = str_repeat('0', 1000);
        $fs->create_file_from_string($filerecord, $data);
    }

    public function test_course_content_header() {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'invisible', 'numsections' => 3, 'startdate' => time(),
                'enablecompletion' => COMPLETION_ENABLED, 'shortname' => 'XYZ',
                'fullname' => 'Long course name'),
            array('createsections' => true));

        $courseobject = format_base::instance($course->id);
        $renderer = new \format_invisible\output\renderer($PAGE, RENDERER_TARGET_GENERAL);
        $output = $renderer->render_contentheader($courseobject->course_content_header());

        $courseid = $courseobject->get_courseid();

        $expectedoutput = <<<EXPECTED
<div class="course-header">
    <div class="course-header-content">
        <div class="course-class-logo">
        </div>
        <div class="course-class-summary">
            <span class="course-nav-scroll" id="course-nav-scroll" data-target="#navtabs" title="Classroom Navigation"><i class="fa fa-chevron-circle-down"></i></span>
            <span class="course-name">Long course name</span>
        </div>
        <span class="toggle">
            <span class="expand"><i class="fa fa-expand"></i>Expand Screen</span>
            <span class="expanded"><i class="fa fa-compress"></i>Show Menu</span>
        </span>
    </div>
</div>
EXPECTED;

        $this->assertEquals($expectedoutput, $output);
    }

    /**
     * Provide a backup/restore interface for unit testing with.
     *
     * @param stdClass $srccourse The course record of a course to backup.
     * @param null $newdate The new start date of the restored course.
     * @param null $dstcourse If the new course is optional, the course record of that course.
     * @return int The id of the restored course.
     */
    protected function backup_and_restore($srccourse, $newdate = null, $dstcourse = null) {
        global $USER, $CFG;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $srccourse->id,
                    backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT,
                    $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to new course with default settings.
        if ($dstcourse !== null) {
            $newcourseid = $dstcourse->id;
        } else {
            $newcourseid = restore_dbops::create_new_course(
            $srccourse->fullname, $srccourse->shortname . '_2', $srccourse->category);
        }
        $rc = new restore_controller($backupid, $newcourseid,
                    backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
                    backup::TARGET_NEW_COURSE);
        if ($newdate) {
            $rc->get_plan()->get_setting('course_startdate')->set_value($newdate);
        }

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
