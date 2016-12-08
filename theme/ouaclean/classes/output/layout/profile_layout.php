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

namespace theme_ouaclean\output\layout;

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Class containing data for mustache layouts
 *
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */
class profile_layout extends base_layout implements renderable, templatable {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $SCRIPT;
        // Rely on page created variables as we can't control them being passed in.
        global $user, $course, $currentuser, $usercontext, $courseid;


        $regionmain = 'dashboard-page';
        $sidepost = '';
        $sidea = '';
        $sideb = '';

        // Reset layout mark-up for RTL languages.
        if (right_to_left()) {
            $regionmain = 'dashboard-page';
            $sidepost = '';
        }

        $data = parent::export_for_template($output);


        $editurl = null;
        if($SCRIPT == '/user/profile.php') {
            if ($usercontext == null) {
                $usercontext = \context_user::instance($user->id, MUST_EXIST);
            }
            if (empty($courseid)) {
                $courseid = SITEID;
            }
            $systemcontext = \context_system::instance();
    
            // Edit profile.
            if (isloggedin() && !isguestuser($user) && !is_mnet_remote_user($user)) {
                if (($currentuser || is_siteadmin($USER) || !is_siteadmin($user)) && has_capability('moodle/user:update', $systemcontext)) {
                    $editurl = new \moodle_url('/user/editadvanced.php',
                                               array('id' => $user->id, 'course' => $courseid, 'returnto' => 'profile'));
                } else if ((has_capability('moodle/user:editprofile',
                                           $usercontext) && !is_siteadmin($user)) || ($currentuser && has_capability('moodle/user:editownprofile',
                                                                                                                     $systemcontext))
                ) {
                    $userauthplugin = false;
                    if (!empty($user->auth)) {
                        $userauthplugin = get_auth_plugin($user->auth);
                    }
                    if ($userauthplugin && $userauthplugin->can_edit_profile()) {
                        $editurl = $userauthplugin->edit_profile_url();
                        if (empty($editurl)) {
                            if (empty($course)) {
                                $editurl = new \moodle_url('/user/edit.php', array('userid' => $user->id, 'returnto' => 'profile'));
                            } else {
                                $editurl = new \moodle_url('/user/edit.php',
                                                           array('userid' => $user->id, 'course' => $course->id, 'returnto' => 'profile'));
                            }
                        }
                    }
                }
            }
        }
        $data->editprofileurl = $editurl;

        $data->body_attributes = $output->body_attributes();
        $data->regionmain = $regionmain;

        $data->blocks_side_tabhead = $output->blocks('side-tabhead');
        $data->blocks_side_tabfoot = $output->blocks('side-tabfoot');

        $data->blocks_side_a = $output->blocks('side-a');
        $data->blocks_side_b = $output->blocks('side-b');
        $data->blocks_side_c = $output->blocks('side-c');
        $data->blocks_side_d = $output->blocks('side-d');

        $data->pagelayout =
            $output->render_from_template('theme_ouaclean/layout_profile', $data);

        return $data;
    }
}
