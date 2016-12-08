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
namespace block_message_broadcast;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/repository/lib.php');

class form extends \moodleform {

    // Directory area where attachments are stored.
    const ATTACHMENTS_AREA = 'attachment';

    // Form element name.
    const ATTACHMENTS_FILEMANAGER = 'attachments_filemanager';

    function definition()
    {
        global $USER;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('hidden', 'uid', $USER->id);
        $mform->setType('uid', PARAM_INT);
        if (isset($customdata['id'])) {
            $mform->addElement('hidden', 'id', $customdata['id']);
            $mform->setType('id', PARAM_INT);
        }
        if (isset($customdata['courseid']) && $customdata['courseid'] !== null) {
            $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
            $mform->setType('courseid', PARAM_INT);
        }

        $mform->addElement('header', '', get_string('addmessage', 'block_message_broadcast'), '');

        $mform->addElement('text', 'headingtitle', get_string('messagetitle', 'block_message_broadcast'));
        $mform->addRule('headingtitle', get_string('missingtitle', 'block_message_broadcast'), 'required', null, 'server');
        $mform->setType('headingtitle', PARAM_NOTAGS);
        $mform->setDefault('headingtitle', $customdata['headingtitle']);

        // Allow user to edit startdate in the past.
        if (isset($customdata['startdate']) && $customdata['startdate'] != 0) {
            $mform->setDefault('startdate', $customdata['startdate']);
            $paststartdate = date('Y', $customdata['startdate']);
        } else {
            $paststartdate = date('Y');
        }

        $startdateopts = array(
            // Not specify the timezone, Moodle will calculate time based on current user timezone.
            // Default to current year.
            'startyear' => $paststartdate,
            // Arbitrary allowing startdate 1 year in future.
            'stopyear' => date('Y') + 1,
            'optional' => false
        );
        $mform->addElement('date_selector', 'startdate', get_string('announcedatelabelstart', 'block_message_broadcast'), $startdateopts);
        $mform->addHelpButton('startdate', 'startdate', 'block_message_broadcast');
        $mform->setDefault('startdate', $customdata['startdate']);

        // Allow user to edit enddate in the past.
        if (isset($customdata['enddate']) && $customdata['enddate'] != 0) {
            $pastenddate = date('Y', $customdata['enddate']);
        } else {
            $pastenddate = date('Y');
        }
        // Message enddate.
        $enddateopts = array(
            // Arbitrary value of announcement expired this year and next year.
            'startyear' => $pastenddate,
            'stopyear' => date('Y') + 1,
            'optional' => true
        );

        $mform->addElement('date_selector', 'enddate', get_string('announcedatelabelexpired', 'block_message_broadcast'), $enddateopts);
        $mform->addHelpButton('enddate', 'enddate', 'block_message_broadcast');
        $mform->setDefault('enddate', $customdata['enddate']);

        $mform->addElement('textarea', 'messagebody', get_string('messagebody', 'block_message_broadcast'), array('rows' => 5, 'cols' => 100));
        $mform->addRule('messagebody', get_string('missingbody', 'block_message_broadcast'), 'required', null, 'server');
        $mform->setDefault('messagebody', $customdata['messagebody']);

        if (isset($customdata['multi_course']) && !empty($customdata['multi_course'])) {
            $courseselect = $mform->addElement('selectgroups', 'courseids',
                get_string('courseselect', 'block_message_broadcast'), $customdata['multi_course']);
            $courseselect->setMultiple(true);
            if (isset($customdata['courseids'])) {
                if (empty($customdata['courseids'])) {
                    $customdata['courseids'] = array(0 => 0);
                }
                $mform->setDefault('courseids', $customdata['courseids']);
            }
            $mform->addRule('courseids', get_string('missingcourse', 'block_message_broadcast'), 'required', null, 'server');
        }

        // Filemanager options.
        $options = array(
            'subdirs' => 0,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL,
        );
        $mform->addElement('filemanager', self::ATTACHMENTS_FILEMANAGER, get_string('attachments', 'block_message_broadcast'), null, $options);

        // Load message announcement attached files into the filemanager if any.
        if (!empty($customdata['id'])) {
            $data = new \stdClass();
            $context = self::get_context();
            $contextid = $context->id;
            $draftid = file_get_submitted_draft_itemid(self::ATTACHMENTS_FILEMANAGER);
            file_prepare_draft_area($draftid, $contextid, 'block_message_broadcast', self::ATTACHMENTS_AREA, $customdata['id'], $options);
            $data->attachments_filemanager = $draftid;
            $this->set_data($data);
        }

        // Buttons
        $this->add_action_buttons(true, get_string('savemessage', 'block_message_broadcast'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['courseids']) && count($data['courseids']) > 1 && in_array("0", $data['courseids'])) {
            $errors['courseids'] = get_string('errorsystemandmulticourse', 'block_message_broadcast');
        }

        return $errors;
    }

    // Attachments require a common context that can be accessed from single/multiple courses but also system wide.
    // CONTEXT_SYSTEM is chosen for this purpose.
    public static function get_context()
    {
        return \context_system::instance();
    }
}
