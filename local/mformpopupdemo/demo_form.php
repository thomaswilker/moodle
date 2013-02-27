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
 * Mform popup demo form
 *
 * @package    local_mformpopupdemo
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');


/**
 * Demo mform.
 *
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mformpopupdemo_form extends moodleform {
    protected function definition() {
        global $path;
        $mform = $this->_form;

        $mform->addElement('text', 'text', get_string('text', 'local_mformpopupdemo'));
        $mform->addElement('editor', 'editor', get_string('editor', 'local_mformpopupdemo'));
//        $mform->addRule('editor', 'Required', 'required', null, 'client');

        $mform->addElement('submit', 'submitbutton', get_string('submit', 'local_mformpopupdemo'));
        $mform->disable_form_change_checker();
    }
}
