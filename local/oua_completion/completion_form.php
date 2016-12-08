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
 * Edit course completion settings - the form definition.
 *
 * @package     core_completion
 * @category    completion
 * @copyright   2009 Catalyst IT Ltd
 * @author      Aaron Barnes <aaronb@catalyst.net.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

/**
 * Defines the course completion settings form.
 */
class course_completion_form extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $USER, $CFG, $DB;

        $courseconfig = get_config('moodlecourse');
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $completion = new \local_oua_completion\oua_completion_info($course);

        $params = array(
            'course'  => $course->id
        );

        // Completion progress.
        $label = get_string('completionprogressoverrides', 'local_oua_completion');
        $mform->addElement('header', 'activitiespercentoverride', $label);
        $mform->setExpanded('activitiespercentoverride');

        $activities = $completion->get_activities();
        // Calculate the percentages.
        if (!empty($activities)) {
            foreach ($activities as $activity) {
                $percentagetext = $activity->modfullname.': '.$activity->name.'; ('.$completion->get_percentage($activity->id).'%)';
                $mform->addElement('text', 'percent_activity['.$activity->id.']', $percentagetext, array('size' => 3));
                $mform->setType('percent_activity['.$activity->id.']', PARAM_RAW);
                // Set the default if it's already included.
                $percentage = $completion->get_custom_percentage($activity->id);
                if (isset($percentage)) {
                    $mform->setDefault('percent_activity['.$activity->id.']', $percentage);
                }
            }
        } else {
            $mform->addElement('static', 'noactivities', '', get_string('err_noactivities', 'completion'));
        }

        // Add common action buttons.
        $this->add_action_buttons();

        // Add hidden fields.
        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $course = $this->_customdata['course'];
        $completion = new \local_oua_completion\oua_completion_info($course);
        $firstcm = null;

        foreach ($data['percent_activity'] as $cmid => $value) {
            if ($firstcm === null) {
                $firstcm = $cmid;
            }

            if ($value < 0 || $value > 100) {
                 $errors['percent_activity['.$cmid.']'] = 'The percentage is < 0% or > 100% for the subject, please adjust them.';
                 continue;
            }

            if ($value == '') {
                $value = null;
            }
            $completion->set_custom_percentage($cmid, $value);
        }

        // There was a failure to successfully set the override, something is wrong with the process of the updates.
        // Due to the order we process in, we may need to just suck it up and calculate the completion at the end.
        if (!$completion->calculate_assigned_percentages()) {
            // We have an invalid set of percentages, we need to put these back in the form and redisplay it.
            $errors['percent_activity['.$firstcm.']'] = 'The total percentages don\'t add to 100% or less';
        }
        return $errors;
    }
}
