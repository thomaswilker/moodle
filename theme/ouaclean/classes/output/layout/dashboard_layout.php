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
class dashboard_layout extends base_layout implements renderable, templatable {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

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


        $data->body_attributes = $output->body_attributes();
        $data->regionmain = $regionmain;

        $data->blocks_side_tabhead = $output->blocks('side-tabhead');
        $data->blocks_side_tabfoot = $output->blocks('side-tabfoot');
        $data->blocks_side_tabx = $output->blocks('side-tabx');
        $data->blocks_side_taba = $output->blocks('side-taba');
        $data->blocks_side_tabb = $output->blocks('side-tabb');
        $data->blocks_side_tabc = $output->blocks('side-tabc');
        $data->blocks_side_a = $output->blocks('side-a');
        $data->blocks_side_b = $output->blocks('side-b');
        $data->blocks_side_c = $output->blocks('side-c');
        $data->blocks_side_d = $output->blocks('side-d');

        $data->showtab1 = $this->region_has_content_or_user_editing('side-taba', $output);
        $data->showtab2 = $this->region_has_content_or_user_editing('side-tabb', $output);
        $data->showtab3 = $this->region_has_content_or_user_editing('side-tabc', $output);

        $data->pagelayout =
            $output->render_from_template('theme_ouaclean/layout_dashboard', $data);

        return $data;
    }
}
