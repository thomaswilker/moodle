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
 * Unit tests for detecting the forum hidden activity and removal of the footer navigation.
 *
 * @package    block_oua_navigation
 * @category   phpunit
 * @copyright  2016 Khoi Le
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/oua_navigation/block_oua_navigation.php');
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
/**
 * Unit tests hidden forum activity Previous and Next links.
 * The page context of each test needed to be reset and moodle only allows me to set the context once but not
 * dynamically reset it. Each test reset the context of the page.
 *
 *
 * @copyright  2016 Khoi Le
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oua_navigation_hidden_forum_navigation_testcase extends oua_advanced_testcase {

    private static $course_5_modules_2_hidden = null;

    public function setup()
    {
        parent::setUp();
        self::$course_5_modules_2_hidden = $this->make_course_5_modules_2_hidden();
    }


    /**
     * Create a course
     * Create 5 activities
     * Make 2 activities hidden
     * @return stdClass
     */
    private function make_course_5_modules_2_hidden(){

        global $DB;

        $course = $this->getDataGenerator()->create_course(
            array(
                'format' => 'invisible',
                'startdate' => time() - 5000
            )
        );
        $record = array('course'=>$course->id);

        /**
         * There are 5 moudles 1,2,3,4,5
         * There are 3 forums 2, 4, 5 -- forum 4 and 5 is hidden
         * forum 2 should appear in normal order
         * forum 4 should appear in the navigation list first but with no prev / next as it is the first hidden forum
         * forum 5 should not appear
         */
        $data = new stdClass();
        $data->course = $course;
        $data->mod1data = $this->getDataGenerator()->create_module('data', $record);
        $data->mod2forum = $this->getDataGenerator()->create_module('forum', $record);
        $data->mod3assign = $this->getDataGenerator()->create_module('assign', $record);
        $data->mod4forum = $this->getDataGenerator()->create_module('forum', $record);
        $DB->set_field('course_modules', 'indent', 5, array('id' => $data->mod4forum->cmid));
        $data->mod5forum = $this->getDataGenerator()->create_module('forum', $record);
        $DB->set_field('course_modules', 'indent', 5, array('id' => $data->mod5forum->cmid));
        rebuild_course_cache($course->id);
        return $data;
    }

    private function get_header_content_output($cmid){
        global $PAGE;

        $data = self::$course_5_modules_2_hidden;
        $invisible = course_get_format($data->course->id);
        $modinfo = get_fast_modinfo($data->course);
        $cm = $modinfo->cms[$cmid];

        $PAGE->set_cm($cm);

        $renderer = new \format_invisible\output\renderer($PAGE, RENDERER_TARGET_GENERAL);
        return $renderer->render_contentheader($invisible->course_content_header());
    }

    // Test hidden modules by inspecting the navigation
    public function test_forum_hidden_oua_navigation(){
        global $PAGE;

        $this->resetAfterTest(true);
        self::setAdminUser();

        $data = self::$course_5_modules_2_hidden;

        $coursecontext = context_course::instance($data->course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($data->course);
        $navigation = block_instance('oua_navigation', $navigation);
        $content = $navigation->get_content();
        $html = $content->text;

        // the indented, hide and show worked
        $this->assertContains('Forum 1', $html);
        $this->assertContains('Forum 2', $html);
        $this->assertNotContains('Forum 3', $html);

        /*
         * Forum1 is the first hidden forum, so it is the recent discussion forum.
         * The first hidden forum displays as the first navigation item in the section.
         */
        $expectedContent = array('Forum 2', 'Database 1', 'Forum 1', 'Assignment 1');
        $this->assertXPathGetNodesWithClassesEquals($expectedContent, 'topicname', $content->text, "Navigation is in unexpected order");

    }


    public function test_hiddenforum_4_has_noprevnext(){

        $this->resetAfterTest(true);
        self::setAdminUser();

        $cmid = self::$course_5_modules_2_hidden->mod4forum->cmid;

        $output = $this->get_header_content_output($cmid);
        $this->assertNotContains(get_string('previous', 'format_invisible'), $output, 'Expect no Prev button hidden module');
        $this->assertNotContains(get_string('next', 'format_invisible'), $output, 'Expect no Next button hidden module');
    }

    public function test_hiddenforum_5_has_noprevnext(){

        $this->resetAfterTest(true);
        self::setAdminUser();

        $cmid = self::$course_5_modules_2_hidden->mod5forum->cmid;

        $output = $this->get_header_content_output($cmid);
        $this->assertNotContains(get_string('previous', 'format_invisible'), $output, 'Expect no Prev button hidden module');
        $this->assertNotContains(get_string('next', 'format_invisible'), $output, 'Expect no Next button hidden module');
    }


    public function test_visibleforum_2_has_correctprevnext(){

        $this->resetAfterTest(true);
        self::setAdminUser();

        $cmid = self::$course_5_modules_2_hidden->mod2forum->cmid;

        $output = $this->get_header_content_output($cmid);
        $this->assertContains(get_string('previous', 'format_invisible'), $output, 'Expect Prev button last visible module');
        $this->assertContains(get_string('next', 'format_invisible'), $output, 'Expect no Next button last visible module');
    }

    public function test_no_navigation_to_hidden_forum(){

        $this->resetAfterTest(true);
        self::setAdminUser();

        $cmid = self::$course_5_modules_2_hidden->mod1data->cmid;

        $output = $this->get_header_content_output($cmid);
        $this->assertNotContains(get_string('previous', 'format_invisible'), $output, 'Expect Prev button last visible module');
        $this->assertContains(get_string('next', 'format_invisible'), $output, 'Expect no Next button last visible module');
    }

}
