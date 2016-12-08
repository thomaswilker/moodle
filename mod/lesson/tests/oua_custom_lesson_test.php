<?php
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
require_once($CFG->dirroot.'/mod/lesson/locallib.php');

class mod_lesson_oua_custom_lesson_test extends oua_advanced_testcase {


    /** @var stdClass the course used for testing */
    private $course;

    /** @var lesson the lesson used for testing */
    private $lesson;

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $this->course->id));

        // Convert to a lesson object.
        $this->lesson = new lesson($lesson);
    }


    /**
     * Basic html test with menu and progress bar.
     * Lesson content is empty.
     *
     */
    public function test_content_page_viewed() {
        global $DB, $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, $studentrole->id);


        $this->setUser($user);
        // Set up a generator to create content.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');
        // Create a content page.
        $pagerecord = $generator->create_content($this->lesson);
        // Get the lesson page information.
        $page = $this->lesson->load_page($pagerecord->id);
        // Get the coursemodule record to setup the $PAGE->cm.
        $coursemodule = $DB->get_record('course_modules', array('id' => $this->lesson->properties()->cmid));
        // Set the $PAGE->cm.
        $PAGE->set_cm($coursemodule);
        // Get the appropriate renderer.
        $lessonoutput = $PAGE->get_renderer('mod_lesson');

        $content = $lessonoutput->display_page($this->lesson, $page, false);


        $this->lesson->progressbar = true;
        $this->lesson->displayleft = true;
        $html = $lessonoutput->header($this->lesson, $coursemodule, 0, 0, 0, '');
        $oualessoncontent = new \mod_lesson\output\oua_lesson_content($coursemodule, $this->lesson, $content);
        $html .= $lessonoutput->render($oualessoncontent);
        $html .= $lessonoutput->footer();
        $this->assertValidHtml($html);
    }
}
