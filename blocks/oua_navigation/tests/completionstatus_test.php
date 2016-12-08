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
 * Unit tests for completionstatus functions.
 *
 * @package    block_oua_navigation
 * @category   phpunit
 * @copyright  2014 Tim Price <timprice@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/oua_navigation/block_oua_navigation.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

/**
 * Unit tests for due dates on the navigation block.
 *
 * @copyright  2014 Russell Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oua_navigation_completionstatus_testcase extends advanced_testcase {

    static function setAdminUser() {
        global $USER;

        parent::setAdminUser();

        $USER->email    = 'admin@example.com';
        $USER->country  = 'AU';
        $USER->city     = 'Melbourne';
    }

    public function setup() {
        global $CFG;

        require_once($CFG->libdir . '/completionlib.php');
    }

    public function test_unit_completion_status() {
        global $PAGE;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('startdate' => time() - 5000,
            'idnumber' => 7000,
            'format' => 'invisible',
            'enablecompletion' => COMPLETION_ENABLED));
                $coursecontext = context_course::instance($course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($course);
        $navigation = block_instance('oua_navigation', $navigation);

        // Set the variable counts for the assessments.
        $assessmentcount = 0;
        $assessmentcompletecount = 0;
        $modulecount = 0;
        $completecount = 0;
        $inprogresscount = 0;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('', $unitcompletestatus, 'There should be no status for a unit with no modules.');

        // In progress.
        $assessmentcount = 1;
        $assessmentcompletecount = 0;
        $modulecount = 2;
        $completecount = 1;
        $inprogresscount = 1;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('inprogress', $unitcompletestatus, 'Status for the unit should be in progress.');

        $assessmentcount = 2;
        $assessmentcompletecount = 1;
        $modulecount = 0;
        $completecount = 0;
        $inprogresscount = 0;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('', $unitcompletestatus, 'Status for the unit should be empty.');

        // In progress.
        $assessmentcount = 0;
        $assessmentcompletecount = 0;
        $modulecount = 2;
        $completecount = 1;
        $inprogresscount = 0;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('inprogress', $unitcompletestatus, 'Status for the unit should be in progress.');

        // Complete.
        $assessmentcount = 0;
        $assessmentcompletecount = 0;
        $modulecount = 2;
        $completecount = 2;
        $inprogresscount = 0;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('complete', $unitcompletestatus, 'Status for the unit should be in complete.');

        // Complete.
        $assessmentcount = 2;
        $assessmentcompletecount = 2;
        $modulecount = 0;
        $completecount = 0;
        $inprogresscount = 0;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('complete', $unitcompletestatus, 'Status for the unit should be in complete.');

        // Complete.
        $assessmentcount = 2;
        $assessmentcompletecount = 2;
        $modulecount = 5;
        $completecount = 5;
        $inprogresscount = 0;

        $unitcompletestatus = $navigation->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                                            $modulecount, $completecount, $inprogresscount);

        $this->assertEquals('complete', $unitcompletestatus, 'Status for the unit should be in complete.');
    }
}
