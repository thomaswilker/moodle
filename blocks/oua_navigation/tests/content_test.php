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
 * Unit tests for (some of) mod/quiz/editlib.php.
 *
 * @package    block_oua_navigation
 * @category   phpunit
 * @copyright  2014 Russell Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/oua_navigation/block_oua_navigation.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

/**
 * Unit tests for due dates on the navigation block.
 *
 * @copyright  2014 Russell Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oua_navigation_content_testcase extends oua_advanced_testcase {

    static function setAdminUser() {
        global $USER;

        parent::setAdminUser();

        $USER->email = 'admin@example.com';
        $USER->country = 'AU';
        $USER->city = 'Sydney';
    }

    public function setup() {
        global $CFG;

        require_once($CFG->libdir . '/completionlib.php');
    }

    public function test_content_with_completion() {
        global $CFG, $DB, $PAGE;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('startdate'        => time() - 5000,
                                                                 'idnumber'         => 7000,
                                                                 'format'           => 'invisible',
                                                                 'enablecompletion' => COMPLETION_ENABLED));

        $moduledata = array('completion'                => COMPLETION_TRACKING_AUTOMATIC,
                            'section'                   => 2,
                            'completiongradeitemnumber' => 0, // Null to allow manual completion.
                            'completionview'            => 1,
                            'completionexpected'        => 1200,
                            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);

        $page1 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $lti = $this->getDataGenerator()->get_plugin_generator('mod_lti')->create_instance($record, $moduledatainvisible);
        $page2 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledata);
        $DB->set_field('course_modules', 'indent', 5, array('id' => $forum->cmid));
        $quiz = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_instance($record, $moduledata);

        $coursecontext = context_course::instance($course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($course);

        $navigation = block_instance('oua_navigation', $navigation);
        $content = $navigation->get_content();

        $this->assertObjectHasAttribute('text', $content);
        $this->assertNotContains('LTI', $content->text, 'LTI should be hidden as it is an invisible module.');

        /*
         * Forum1 is the first hidden forum, so it is the recent discussion forum.
         * The first hidden forum displays as the first navigation item in the section.
         */
        $expectedContent = array($forum->name, $page1->name, $page2->name, $quiz->name);
        $this->assertXPathGetNodesWithClassesEquals($expectedContent, 'topicname', $content->text, "Navigation is in unexpected order");
        $this->assertValidHtml($content->text);

        $this->assertDebuggingNotCalled();
    }

    public function test_content_with_inprogress_unit() {
        global $CFG, $PAGE, $DB, $USER;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('startdate'        => time() - 5000,
                                                                 'idnumber'         => 7000,
                                                                 'format'           => 'invisible',
                                                                 'enablecompletion' => COMPLETION_ENABLED));

        $moduledata = array('completion'                => COMPLETION_TRACKING_AUTOMATIC,
                            'section'                   => 2,
                            'completionview'            => 1,
                            'completionexpected'        => 1200,
                            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);

        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_lti')->create_instance($record, $moduledatainvisible);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledata);
        $DB->set_field('course_modules', 'indent', 5, array('id' => $forum->cmid));
        $quiz = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_instance($record, $moduledata);
        $assign = $this->getDataGenerator()->get_plugin_generator('mod_assign')->create_instance($record, $moduledata);
        $DB->set_field('course_modules', 'completiongradeitemnumber', '0', array('id' => $assign->cmid));

        // Set up completion data for quiz and mailasses modules.
        $quizcm = $DB->get_record('course_modules', array('id' => $quiz->cmid), '*', MUST_EXIST);
        $assigncm = $DB->get_record('course_modules', array('id' => $assign->cmid), '*', MUST_EXIST);

        $completion = new completion_info($course);
        $completion->set_module_viewed($quizcm, $USER->id);
        $completion->set_module_viewed($assigncm, $USER->id);

        $coursecontext = context_course::instance($course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($course);
        $navigation = block_instance('oua_navigation', $navigation);
        $content = $navigation->get_content();

        $this->assertObjectHasAttribute('text', $content);
        $this->assertContains('incomplete page', $content->text, 'Pages should be visible.');
        $this->assertNotContains('incomplete lti', $content->text, 'LTI should be hidden as it is an invisible module.');
        $this->assertNotContains('incomplete forum', $content->text, 'Forum is excluded.');
        $this->assertContains('complete quiz', $content->text, 'Quizzes should be show and complete');
        $this->assertContains('inprogress assign', $content->text, 'assignments should be show and in progress');
        $this->assertContains('collapsed complete', $content->text, 'SEction should be collapsed and complete');
        $this->assertValidHtml($content->text);
        $this->markTestIncomplete('Need to add a test for assessments that have a grade.');
    }

    public function test_content_with_completed_unit() {
        global $CFG, $PAGE, $DB, $USER;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('startdate'        => time() - 5000,
                                                                 'idnumber'         => 7000,
                                                                 'format'           => 'invisible',
                                                                 'enablecompletion' => COMPLETION_ENABLED));

        $moduledata = array('completion'                => COMPLETION_TRACKING_AUTOMATIC,
                            'section'                   => 2,
                            'completiongradeitemnumber' => 0, // Null to allow manual completion.
                            'completionview'            => 1,
                            'completionexpected'        => 1200,
                            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_lti')->create_instance($record, $moduledatainvisible);
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledata);
        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledata);
        $DB->set_field('course_modules', 'indent', 5, array('id' => $forum->cmid));
        $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_instance($record, $moduledata);
        $this->getDataGenerator()->get_plugin_generator('mod_assign')->create_instance($record, $moduledata);

        // Set up completion data for quiz and assign modules.
        $quizmod = $DB->get_record('modules', array('name' => 'quiz'), '*', MUST_EXIST);
        $assignmod = $DB->get_record('modules', array('name' => 'assign'), '*', MUST_EXIST);

        $quiz = $DB->get_record('course_modules', array('module' => $quizmod->id), '*', MUST_EXIST);
        $assign = $DB->get_record('course_modules', array('module' => $assignmod->id), '*', MUST_EXIST);

        $completion = new completion_info($course);
        $completion->set_module_viewed($quiz, $USER->id);
        $completion->set_module_viewed($assign, $USER->id);

        $coursecontext = context_course::instance($course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($course);
        $navigation = block_instance('oua_navigation', $navigation);
        $content = $navigation->get_content();

        $this->assertObjectHasAttribute('text', $content);
        $this->assertContains('incomplete page', $content->text, 'Pages should be visible.');
        $this->assertNotContains('incomplete lti', $content->text, 'LTI should be hidden as it is an invisible module.');
        $this->assertNotContains('incomplete forum', $content->text, 'Forum is excluded.');
        $this->assertContains('complete quiz', $content->text, 'Quizzes should be show and complete');
        $this->assertContains('complete assign', $content->text, 'assign should be show and complete');
        $this->assertContains('collapsed complete', $content->text, 'Section should be complete and colapsed.');
        $this->assertValidHtml($content->text);
    }

    /**
     * GIVEN I have a course with a mix of sections including units and non-units.
     * WHEN I check if the section should have "Unit" in the title.
     * THEN It will be included for items with unitid's and excluded when one isn't present.
     *
     * @test
     */
    public function display_unit_prefix_is_for_unitid_empty_and_not_empty() {
        global $PAGE;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('startdate'        => time() - 5000,
                                                                 'idnumber'         => 7000,
                                                                 'format'           => 'invisible',
                                                                 'enablecompletion' => COMPLETION_ENABLED));

        $section1 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 1));
        $section2 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 2));
        $section3 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 3));

        course_get_format($course->id)->update_section_format_options(array('id' => $section1->id, 'unitid' => ''));
        course_get_format($course->id)->update_section_format_options(array('id' => $section2->id, 'unitid' => 'AAA'));
        course_get_format($course->id)->update_section_format_options(array('id' => $section3->id, 'unitid' => 'AAA'));

        $PAGE->set_course($course);
        $coursecontext = context_course::instance($course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $navigation = block_instance('oua_navigation', $navigation);
        $this->assertFalse($navigation->display_unit_prefix(1));
        $this->assertTrue($navigation->display_unit_prefix(2));
        $this->assertTrue($navigation->display_unit_prefix(3));
    }

    /**
     * GIVEN a mix of unit and non-unit sections in a course.
     * WHEN the navigation block is rendered.
     * THEN units have a prefix and non-units do not.
     *
     * @test
     */
    public function navigation_includes_unit_correctly_in_the_output() {
        global $CFG, $DB, $PAGE;

        $this->resetAfterTest(true);

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $course = $this->getDataGenerator()->create_course(array('format'           => 'invisible',
                                                                 'enablecompletion' => COMPLETION_ENABLED));

        $section1 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 1));
        $section2 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 2));
        $section3 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 3));
        $section4 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 4));
        $section5 = $this->getDataGenerator()->create_course_section(array('course' => $course->id, 'section' => 5));
        $DB->update_record('course_sections', (object)array('id' => $section1->id, 'name' => 'First'));
        $DB->update_record('course_sections', (object)array('id' => $section2->id, 'name' => 'Second'));
        $DB->update_record('course_sections', (object)array('id' => $section3->id, 'name' => 'AAA Third'));
        $DB->update_record('course_sections', (object)array('id' => $section4->id, 'name' => 'AAA Fourth'));
        $DB->update_record('course_sections', (object)array('id' => $section5->id, 'name' => 'BBA Fifth'));
        rebuild_course_cache($course->id);

        course_get_format($course->id)->update_section_format_options(array('id' => $section1->id, 'unitid' => ''));
        course_get_format($course->id)->update_section_format_options(array('id' => $section2->id, 'unitid' => 'AAA'));
        course_get_format($course->id)->update_section_format_options(array('id' => $section3->id, 'unitid' => ''));
        course_get_format($course->id)->update_section_format_options(array('id' => $section4->id, 'unitid' => 'AAA'));
        course_get_format($course->id)->update_section_format_options(array('id' => $section5->id, 'unitid' => 'BBB'));

        // Add modules as they are required for sections to be visible in the block.
        $moduledata = array('completion'                => COMPLETION_TRACKING_AUTOMATIC,
                            'completiongradeitemnumber' => 0, // Null to allow manual completion.
                            'completionview'            => 1,
                            'completionexpected'        => 1200,
                            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $pagegenerator->create_instance($record, array_merge(array('section' => 1), $moduledata));
        $pagegenerator->create_instance($record, array_merge(array('section' => 2), $moduledata));
        $pagegenerator->create_instance($record, array_merge(array('section' => 3), $moduledata));
        $pagegenerator->create_instance($record, array_merge(array('section' => 4), $moduledata));
        $pagegenerator->create_instance($record, array_merge(array('section' => 5), $moduledata));
        $PAGE->set_course($course);

        $coursecontext = context_course::instance($course->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $navigation = block_instance('oua_navigation', $navigation);
        $content = $navigation->get_content();
        $this->assertContains('                        First
                    </a>
                </h4>', $content->text);/*
        $this->assertContains('<span class="module">Unit 1 - Second</span>', $content->text);
        $this->assertContains('<span class="module">AAA Third</span>', $content->text);
        $this->assertContains('<span class="module">Unit 2 - Fourth</span>', $content->text);
        $this->assertNotContains('<span class="module">Unit 2 - AAA Fourth</span>', $content->text,
            'The current code strips the first word as it is usually the unitid.  Change the test if that is updated');
        $this->assertContains('<span class="module">Unit 3 - BBA Fifth</span>', $content->text,
            'The unit code should only be stripped if it matched the unitid for the section.');*/
        $this->assertValidHtml($content->text);
        $this->markTestIncomplete('Removed unit name from code and tests.');
    }

    /**
     * GIVEN a section that is a unit
     * WHEN the title is generated
     * THEN it will have "Unit x - " prefixed to it.
     *
     * @test
     */
    public function section_title_includes_unit_when_displayprefix_is_set() {
        global $SITE, $PAGE;
        $this->markTestSkipped(" SEction title is just the section title now.");
        $this->resetAfterTest(true);

        $coursecontext = context_course::instance($SITE->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($SITE);
        $navigation = block_instance('oua_navigation', $navigation);
        $title = $navigation->get_section_title(null, 1, 'Section', true);
        $this->assertEquals('<span class="module">Unit 1 - Section</span>', $title);
    }

    /**
     * GIVEN a section that is not a unit
     * WHEN The title is generated
     * THEN it is returned without "Unit"
     *
     * @test
     */
    public function section_title_excludes_unit_when_displayprefix_is_not_set() {
        global $SITE, $PAGE;
        $this->markTestSkipped(" SEction title is just the section title now.");
        $this->resetAfterTest(true);

        $coursecontext = context_course::instance($SITE->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($SITE);
        $navigation = block_instance('oua_navigation', $navigation);
        $title = $navigation->get_section_title(null, 1, 'Section', false);
        $this->assertEquals('<span class="module">Section</span>', $title);
    }

    /**
     * GIVEN I have a unitintro in a section
     * WHEN I get the title of the section
     * THEN the title is hyperlinked to the unitintro.
     *
     * @test
     */
    public function section_title_is_hyperlinked_when_unitintro_is_supplied() {
        global $SITE, $PAGE;
        $this->markTestSkipped("Hyperlinked section title is a todo item.");
        $this->resetAfterTest(true);
        $unitintro = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance(array('course' => $SITE->id));
        $modinfo = get_fast_modinfo($SITE->id);
        $unitintrocm = $modinfo->get_cm($unitintro->cmid);

        $coursecontext = context_course::instance($SITE->id);
        $navigation = $this->getDataGenerator()->create_block('oua_navigation', array('parentcontextid' => $coursecontext->id));
        $PAGE->set_course($SITE);
        $navigation = block_instance('oua_navigation', $navigation);
        $title = $navigation->get_section_title($unitintrocm, 1, 'Section', false);
        $this->assertContains('<span class="module">Section</span>', $title);
        $this->assertContains('<a href', $title);

        $title = $navigation->get_section_title($unitintrocm, 3, 'Section', true);
        $this->assertContains('<span class="module">Unit 3 - Section</span>', $title);
        $this->assertContains('<a href', $title);
    }
}
