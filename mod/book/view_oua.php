<?php
defined('MOODLE_INTERNAL') || die;

$bookpage = new \mod_book\output\book_view_page($cm, $book, $edit, $viewhidden);

if ($allowedit && !$bookpage->get_chapters()) {
    redirect('edit.php?cmid=' . $cm->id); // No chapters - add new one.
}

// No content in the book.
if (!$bookpage->get_chapters()) {
    $PAGE->set_url('/mod/book/view.php', array('id' => $id));
    notice(get_string('nocontent', 'mod_book'));
}

$PAGE->set_url('/mod/book/view.php', array('id' => $id));

$startpagenumber = 0;
if ($chapterid != 0) {
    // Make Page load at specific chapter.
    $startpagenumber = $bookpage->get_page_number_for_chapterx($chapterid);
}
$params = array($startpagenumber, $book->bookanimationspeed);
$PAGE->requires->js_call_amd("mod_book/book", 'initialise', $params);

$pagetitle = $book->name;
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

book_view($book, null, false, $course, $cm, $context);

// =====================================================
// Book display render
// =====================================================
$pagetitle = format_string($book->name);

$output = $PAGE->get_renderer('mod_book');
echo $output->header();
echo $output->heading($pagetitle);
echo $output->render($bookpage);
echo $output->footer();
return;
