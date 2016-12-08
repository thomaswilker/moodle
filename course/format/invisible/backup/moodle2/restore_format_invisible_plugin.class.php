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
 * @package format_invisible
 * @subpackage backup-moodle2
 */

/**
 * Specialised restore task for the invisible format.
 * (using execute_after_tasks for recoding of start and finishing activities and dates)
 *
 * TODO: Finish phpdocs
 */
class restore_format_invisible_plugin extends restore_format_plugin {


    protected function define_course_plugin_structure() {
        $path = $this->get_pathfor('/');

        $paths = array(new restore_path_element('invisible', $path));

        return $paths;
    }

    public function process_invisible($data) {
        // Nothing to do as we don't process our own data, only data in courseformatoptions.
        return;
    }

    public function after_restore_course() {
        $courseid = $this->task->get_courseid();
        $courseobject = course_get_format($courseid);

        // Course format should only run our restore options if we are the target format.
        if ($courseobject->get_format() !== 'invisible') {
            return;
        }

        $restoredata = $courseobject->get_format_options();

        // Roll forward the date for the course end.
        $restoredata['courseenddate'] = $this->apply_date_offset($restoredata['courseenddate']);

        $newpreviewid = $this->get_mappingid('course_module', $restoredata['coursepreviewactivity'], null);
        $newcompleteid = $this->get_mappingid('course_module', $restoredata['coursecompleteactivity'], null);
        $restoredata['coursepreviewactivity'] = $newpreviewid;
        $restoredata['coursecompleteactivity'] = $newcompleteid;

        $courseobject->update_course_format_options($restoredata);

        $this->add_related_files('format_invisible', 'cobrandinglogo', null);
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }
}
