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
 * TESTING PAGE for autocomplete field.
 */

require('config.php');
require_once("$CFG->libdir/formslib.php");

class test_form extends moodleform {
    function definition() {
        $mform  = $this->_form;

        $mform->addElement('header','general', 'Test user selector');

        $options = array(
            'ajax' => 'tool_lp/form-user-selector'
        );
        $mform->addElement('autocomplete', 'link', 'Single', array(), $options);

        $options = array(
            'ajax' => 'tool_lp/form-user-selector',
            'multiple' => true
        );
        $mform->addElement('autocomplete', 'link2', 'Multiple', array(), $options);

        $options = array(
            'ajax' => 'tool_lp/form-user-selector',
            'multiple' => true,
            'data-capability' => 'tool/lp:planmanage'
        );
        $mform->addElement('autocomplete', 'link3', 'Filtered by capability', array(), $options);

        $this->add_action_buttons();

    }

    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        var_dump($data);
        var_dump($errors);

        if (isset($data['link35']) && in_array('showerror', $data['link35'])) {
            $errors['link35'] = 'Custom error - does it display?';
        }

        return $errors;
    }
}


$PAGE->set_url('/test.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());
require_login();

$mform = new test_form();

if ($mform->is_cancelled()) {
    die('Cancelled');

} else if ($data = $mform->get_data()) {
    var_dump($data);
    die('Submitted');
}

$PAGE->set_heading('Test');
$PAGE->set_title('User selector');

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
