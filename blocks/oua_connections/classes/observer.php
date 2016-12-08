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
 * Event observer.
 *
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_connections;

defined('MOODLE_INTERNAL') || die();
use context_user;
/**
 * Event observer.
 * On a contact add / remove / block event, ad/remove privileges.
 *
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class observer {

    public static function add_privilege(\core\event\base $event) {
        $config = get_config('block_oua_connections');
        if (!isset($config->viewprofilecontactrole) || $config->viewprofilecontactrole == '') {
            return;
        }

        $usercontext = context_user::instance($event->userid);
        $assignableroles = get_assignable_roles($usercontext, ROLENAME_BOTH, false, get_admin());

        if (!array_key_exists($config->viewprofilecontactrole, $assignableroles)) {
            debugging('The role to allow users to view other users profile when in their contact list is not assignable in the user context level');
        }
        role_assign($config->viewprofilecontactrole, $event->relateduserid, $usercontext->id);
    }

    public static function remove_privilege(\core\event\base $event) {
        $config = get_config('block_oua_connections');
        if (!isset($config->viewprofilecontactrole) || $config->viewprofilecontactrole == '') {
            return;
        }

        $usercontext = context_user::instance($event->userid);
        $assignableroles = get_assignable_roles($usercontext, ROLENAME_BOTH, false, get_admin());

        if (!array_key_exists($config->viewprofilecontactrole, $assignableroles)) {
            debugging('The role to allow users to view other users profile when in their contact list is not assignable in the user context level');
        }
        role_unassign($config->viewprofilecontactrole, $event->relateduserid, $usercontext->id);
    }
}
