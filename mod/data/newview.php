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
 * This page will replace the 2005 era view.php for DB.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$cmid = optional_param('id', 0, PARAM_INT);
$databaseid = optional_param('d', 0, PARAM_INT);
$current = optional_param('mode', 'list', PARAM_ALPHA);

$cm = null;
$course = null;
if ($cmid) {
    list($course, $cminfo) = get_course_and_cm_from_cmid($cmid, 'data');
    $cm = $cminfo->get_course_module_record();
    $databaseid = $cm->instance;
} else if ($databaseid) {
    list($course, $cm) = get_course_and_cm_from_instance($databaseid, 'data');
    $cmid = $cm->id;
} else {
    print_error('invalidcoursemodule');
}
$database = \mod_data\api::get_database($databaseid);

$context = $database->get_context();
$title = $context->get_context_name(false);

// Set up the page.
$url = new moodle_url("/mod/data/newview.php", ['d' => $databaseid]);

$PAGE->set_context($context);
require_login($course, false, $cm);

$coursetitle = format_string($course->fullname, true, ['context' => context_course::instance($course->id)]);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($coursetitle);

// Record the view event.
\mod_data\api::database_viewed($database, $course, $cm, $context);

// Render the page.
$output = $PAGE->get_renderer('mod_data');
echo $output->header();
echo $output->heading($title);
$page = new \mod_data\output\page($database, $context, $course, $cm, $current);
echo $output->render($page);
echo $output->footer();
