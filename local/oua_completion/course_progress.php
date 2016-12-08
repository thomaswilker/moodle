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
 * Edit course completion settings
 *
 * @package     core_completion
 * @category    completion
 * @copyright   2009 Catalyst IT Ltd
 * @author      Aaron Barnes <aaronb@catalyst.net.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_self.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_unenrol.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_duration.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_grade.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/local/oua_completion/completion_form.php');

$id = required_param('id', PARAM_INT);       // Course id.

// Perform some basic access control checks.
if ($id) {

    if ($id == SITEID) {
        // Don't allow editing of 'site course' using this form.
        print_error('cannoteditsiteform');
    }

    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourseid');
    }
    require_login($course);
    require_capability('moodle/course:update', context_course::instance($course->id));

} else {
    require_login();
    print_error('needcourseid');
}

// Set up the page.
$PAGE->set_course($course);
$PAGE->set_url('/local/oua_completion/course_progress.php', array('id' => $course->id));
$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

// Create the settings form instance.
$form = new course_completion_form('course_progress.php?id='.$id, array('course' => $course));

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);

} else if ($data = $form->get_data()) {
    $completion = new \local_oua_completion\oua_completion_info($course);

    foreach ($data->percent_activity as $cmid => $value) {
        if ($value == '') {
            $value = null;
        }
        $completion->set_custom_percentage($cmid, $value);
    }
    $completion->save_assigned_percentages();




    // Log changes.
    add_to_log($course->id, 'oua_completion', 'progress overrides updated', 'course_progress.php?id='.$course->id);

    // Redirect to the course main page.
    $url = new moodle_url('/course/view.php', array('id' => $course->id));
#    $url = new moodle_url('/local/oua_completion/course_progress.php', array('id' => $course->id));
    redirect($url);
}

// Print the form.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editcoursecompletionsettings', 'core_completion'));

$form->display();

echo $OUTPUT->footer();
