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
 * Completion lib advanced test case.
 *
 * This file contains the advanced test suite for completion lib.
 *
 * @package    core_completion
 * @category   phpunit
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/oua_completion/classes/oua_completion_info.php');

use \local_oua_completion\oua_completion_info as local_oua_completion;

class ouacompletionlib_advanced_testcase extends advanced_testcase {

    public function test_get_activities() {
        global $CFG;

        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);

        // Create a course with mixed auto completion data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);
        $completionmanual = array('completion' => COMPLETION_TRACKING_MANUAL);
        $completionnone = array('completion' => COMPLETION_TRACKING_NONE);
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), $completionauto);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course->id), $completionauto);
        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), $completionmanual);

        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), $completionnone);
        $page2 = $this->getDataGenerator()->create_module('page', array('course' => $course->id), $completionnone);
        $data2 = $this->getDataGenerator()->create_module('data', array('course' => $course->id), $completionnone);

        // Create data in another course to make sure it's not considered.
        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $c2forum = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id), $completionauto);
        $c2page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id), $completionmanual);
        $c2data = $this->getDataGenerator()->create_module('data', array('course' => $course2->id), $completionnone);

        $c = new local_oua_completion($course);
        $activities = $c->get_activities();
        $this->assertEquals(3, count($activities));
        $this->assertTrue(isset($activities[$forum->cmid]));
        $this->assertEquals($activities[$forum->cmid]->name, $forum->name);
        $this->assertTrue(isset($activities[$page->cmid]));
        $this->assertEquals($activities[$page->cmid]->name, $page->name);
        $this->assertTrue(isset($activities[$data->cmid]));
        $this->assertEquals($activities[$data->cmid]->name, $data->name);

        $this->assertFalse(isset($activities[$forum2->cmid]));
        $this->assertFalse(isset($activities[$page2->cmid]));
        $this->assertFalse(isset($activities[$data2->cmid]));
    }

    public function test_has_activities() {
        global $CFG;
        $this->resetAfterTest(true);
        $CFG->enablecompletion = true;

        // Create a course with mixed auto completion data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);
        $completionnone = array('completion' => COMPLETION_TRACKING_NONE);
        $c1forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), $completionauto);
        $c2forum = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id), $completionnone);

        $c1 = new local_oua_completion($course);
        $c2 = new local_oua_completion($course2);

        $this->assertTrue($c1->has_activities());
        $this->assertFalse($c2->has_activities());
    }

    public function test_calculate_assigned_percentages() {
        global $DB, $CFG;

        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Manage completion tracking issue!!!!

        // Create a course with mixed auto completion data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);
        $completionmanual = array('completion' => COMPLETION_TRACKING_MANUAL);
        $completionnone = array('completion' => COMPLETION_TRACKING_NONE);

        // All these are ignored (Contribute 0 percentage to progress unless overridden.
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id), $completionauto);
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course->id), $completionauto);
        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id), $completionmanual);
        //$lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course->id, 'indent' => 1), $completionnone);

        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id), $completionauto);

        // Weights of 1 because it is completable
        $lti  = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionnone);
        // Weights of 0 because it is completable
        $lti2 = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionauto);
        //$scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $course->id), $completionnone);
        //$youtube = $this->getDataGenerator()->create_module('youtube', array('course' => $course->id), $completionnone);

        // Weights of 4.
        //$lesson2 = $this->getDataGenerator()->create_module('lesson', array('course' => $course->id, 'indent' => 1), $completionnone);

        // Add completion percentage overrides into the db.
        $ltioverride = $DB->insert_record('oua_course_mod_completion', array('coursemoduleid' => $lti->cmid, 'progresspercent' => 5000,
                                                                             'timecreated' => 0, 'timemodified' => 0));

        $lti2override = $DB->insert_record('oua_course_mod_completion', array('coursemoduleid' => $lti2->cmid, 'progresspercent' => 3000,
                                                                              'timecreated' => 0, 'timemodified' => 0));

        $c = new local_oua_completion($course);
        $this->assertTrue($c->calculate_assigned_percentages(), 'There is only 80% overridden, but there are default weight modules, so we don\'t fail the calculation of percentages');

        // When the database changes, we need to reload the completion data as it's cached inside the class.
        $c = new local_oua_completion($course);
        $lti3 = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionauto);
        $c->set_custom_percentage($lti->cmid, 40);
        $this->assertEquals(0, $c->get_percentage($lti->cmid),  'LTI 1 was overridden with 40 but fails because it is not completable');
        $c->set_custom_percentage($lti2->cmid, 60);
        $this->assertEquals(60, $c->get_percentage($lti2->cmid), 'LTI 2 was overridden with 60');
        $c->set_custom_percentage($lti3->cmid, 60);
        $this->assertEquals(false, $c->get_percentage($lti3->cmid), 'LTI 3 was overridden with 60 which fails as it adds to > 100');

        $this->assertFalse($c->calculate_assigned_percentages(), 'Percentages that add up to over 100 should fail');
        $c->set_custom_percentage($lti2->cmid, 60);
        $c->set_custom_percentage($lti3->cmid, 40);
        $this->assertTrue($c->calculate_assigned_percentages(), 'Percentages must add up to 100 when everything is overridden.');
        $this->assertEquals(60, $c->get_percentage($lti2->cmid), 'LTI 2 was overridden with 60');
        $this->assertEquals(40, $c->get_percentage($lti3->cmid), 'LTI 3 was overridden with 40');

        $c->set_custom_percentage($lti2->cmid, null);
        $this->assertTrue($c->calculate_assigned_percentages(),   'Percentages must add up to 100 when calculations are made.');
        $this->assertEquals(12,  $c->get_percentage($forum->cmid), 'Forum must be 0%');
        $this->assertEquals(12, $c->get_percentage($page->cmid),  'Page must be 20%');
        $this->assertEquals(12,  $c->get_percentage($data->cmid),  'Data must be 0%');
        $this->assertEquals(12, $c->get_percentage($quiz->cmid),  'Quiz must be 20%');
        $this->assertEquals(0,  $c->get_percentage($lti->cmid),   'Not completable lti is 0%');
        $this->assertEquals(12, $c->get_percentage($lti2->cmid),  'Automatic lti is 20%');
        $this->assertEquals(40, $c->get_percentage($lti3->cmid),  'Overridden lti is 40%');

        // Remove a single override and confirm we calculate correctly.
        $c->set_custom_percentage($lti3->cmid, null);
        $c = new local_oua_completion($course);
        $this->assertTrue($c->calculate_assigned_percentages(), 'Percentages must add up to 100 when calculations are made.');

        // Remove the second override and confirm we calculate correctly.
        $DB->delete_records('oua_course_mod_completion', array('id' => $lti2override));
        $c = new local_oua_completion($course);
        $this->assertTrue($c->calculate_assigned_percentages(), 'Percentages must add up to 100 when calculations are made.');

        // Test the result with no overrides.
        $DB->delete_records('oua_course_mod_completion', array('id' => $ltioverride));
        $c = new local_oua_completion($course);
        $this->assertTrue($c->calculate_assigned_percentages(), 'Percentages must add up to 100 when there are no overrides.');

        // Now test 3 activities of 16.66 and one of 50 override.
        $ltioverride = $DB->insert_record('oua_course_mod_completion', array('coursemoduleid' => $lti->cmid, 'progresspercent' => 5000,
                                                                             'timecreated' => 0, 'timemodified' => 0));
        $lti4 = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionnone);
        $c = new local_oua_completion($course);
        $this->assertTrue($c->calculate_assigned_percentages(), 'Percentages must add up to 100 when calculations are made.');

        // Test the division of 7 works as well.
        $DB->delete_records('oua_course_mod_completion', array('id' => $ltioverride));
        $lti5 = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionnone);
        $lti6 = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionnone);
        $lti7 = $this->getDataGenerator()->create_module('lti', array('course' => $course->id), $completionnone);
        $c = new local_oua_completion($course);
        $this->assertTrue($c->calculate_assigned_percentages(), 'Percentages must add up to 100 when there are no overrides.');
    }

    public function test_loadoverrides() {
        $reflectedclass = new ReflectionClass("local_oua_completion\\oua_completion_info");

        $method = $reflectedclass->getMethod("load_overrides");
        $method->setAccessible(true);
        $property = $reflectedclass->getProperty('cmoverridepercentage');
        $property->setAccessible(true);

        $course = new stdClass();
        $course->id = 1;

        $completion = new local_oua_completion($course);

        $method->invoke($completion);
        $this->assertEmpty($property->getValue($completion), 'Completion overrides must be empty.');
        $this->assertInternalType('array', $property->getValue($completion), 'Completion overrides must be empty.');
        $this->assertNotNull($property->getValue($completion), 'Completion overrides must be empty.');
    }

}
