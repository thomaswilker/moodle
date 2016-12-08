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
 * Form for editing settings for individual social activity instances.
 *
 * @package   block_oua_social_activity
 * @copyright 2015 Ben Kelada (ben.kelada@open.edu.au)
  */

/**
 * Form for editing settings for individual social activity instances.
 *
 * @copyright 2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class block_oua_social_activity_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $defaults = array(20 => 'default (20)', 25 => 25, 30 => 30, 40 => 40, 50 => 50);
        $mform->addElement('select', 'config_numberofsocialevents', get_string('numberofsocialevents', 'block_oua_social_activity'), $defaults);
        $mform->setDefault('config_numberofsocialevents', 20);

        $defaults = array(20 => 'default (20)', 30 => 30, 60 => 60, 120 => 120);
        $mform->addElement('select', 'config_numberofdaysback', get_string('numberofdaysback', 'block_oua_social_activity'), $defaults);
        $mform->setDefault('config_numberofdaysback', 20);
    }
}
