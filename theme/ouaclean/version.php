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
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version = 2015101400;
$plugin->requires = 2015111600;
$plugin->component = 'theme_ouaclean';
$plugin->dependencies = array(
    'theme_bootstrap' => 2014110400,
    'block_oua_course_progress_bar' => 2015061000, // We require this block to render custom progress bar outputs.
);
