<?php
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');




class oua_custombook_test extends oua_advanced_testcase {
    function test_lib_returns_all_records() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_renderer_outputs_html() {
        global $DB, $PAGE;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecomment' => 1));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        // Test book with 3 chapters.
        $book = $this->getDataGenerator()->create_module('book', array('course' => $course->id));
        $cm = get_coursemodule_from_id('book', $book->cmid);

        $bookgenerator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $chapter1 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 1));
        $chapter2 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 2));
        $subchapter = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 3, "subchapter" => 1));
        $chapter3 = $bookgenerator->create_chapter(array('bookid' => $book->id, "pagenum" => 4, "hidden" => 1));

        $this->setUser($user);
        $output = $PAGE->get_renderer('mod_book');
        $bookpage = new \mod_book\output\book_view_page($cm, $book, 0, false);

        $html = $output->render($bookpage);
        $this->assertNotContains("Chapter 4", $html);
        $this->assertValidHtml($html);

        $this->setAdminUser();
        $bookpage = new \mod_book\output\book_view_page($cm, $book, 1, true);

        $html = $output->render($bookpage);
        $this->assertContains("Chapter 4", $html);
        $this->assertValidHtml($html);

    }

}
