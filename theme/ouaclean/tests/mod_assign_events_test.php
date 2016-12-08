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
 * Contains the event tests for the module assign.
 *
 * @package   mod_assign
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/events_test.php');

/**
 * Contains the event tests for the module assign.
 *
 * @package   mod_assign
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_ouaclean_mod_assign_events_testcase extends assign_events_testcase {
    /**
     * We re-run all the core tests with our theme enabled.
     */
    public function setUp() {
        global $CFG;
        $CFG->theme = 'ouaclean';

        return parent::setUp();
    }

    /**
     * Verify the parent submission form test
     */
    public function test_parent_submission_confirmation_form_viewed_fails() {
        // We confirm first that the parent method fails as we expect, just in-case we and upgrade
        // changes the behaviour on us.
        try {
            parent::test_submission_confirmation_form_viewed();
        } catch (Exception $e) {
            // Confirm the exception happens where we expect, if not investigate what's changed about the parent test case.
            $this->assertEquals('Trying to get property of non-object', $e->getMessage());
            $this->assertEquals(824, $e->getLine());
            $this->assertStringEndsWith('theme/ouaclean/classes/mod_assign_renderer.php', $e->getFile());
            return;
        }
        $this->fail();
    }

    /**
     * Our renderer requires the course module to be set, so we have reimplemented the 2.9 test here.
     */
    public function test_submission_confirmation_form_viewed() {
        global $PAGE;

        $this->setUser($this->students[0]);

        $assign = $this->create_instance();

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/a_url');
        $PAGE->set_cm($assign->get_course_module());

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assign->view('submit');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_assign\event\submission_confirmation_form_viewed', $event);
        $this->assertEquals($assign->get_context(), $event->get_context());
        $expected = array(
            $assign->get_course()->id,
            'assign',
            'view confirm submit assignment form',
            'view.php?id=' . $assign->get_course_module()->id,
            get_string('viewownsubmissionform', 'assign'),
            $assign->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }
}
