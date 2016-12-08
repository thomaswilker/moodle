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
 * oua_connections block settings
 *
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/blocks/oua_connections/lib.php');

    // User admin users context to get roles assignable to user contexts.
    $assignableroles = get_assignable_roles(context_user::instance($USER->id), ROLENAME_BOTH);
    $assignableroles = array('' => '') + $assignableroles;
    $setting = new admin_setting_configselect('block_oua_connections/viewprofilecontactrole',
                                              new lang_string('viewprofilecontactrole', 'block_oua_connections'),
                                              new lang_string('viewprofilecontactroledesc', 'block_oua_connections'), null,
                                              $assignableroles);
    $setting->set_updatedcallback('block_oua_connections_updatedcallback');
    $settings->add($setting);

}
