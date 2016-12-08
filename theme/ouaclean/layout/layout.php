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
 * @author    Ben Kelada
 */

$contextcourse = context_course::instance(SITEID);
$doctype = $OUTPUT->doctype();


// Define the renderable for the layout page.
$renderable = '\\theme_ouaclean\\output\\layout\\' . $PAGE->theme->layouts[$PAGE->pagelayout]['renderable'];
$page = new $renderable($contextcourse, $doctype);

// Moodle redirects and changes session id to prevent session fixation attack, when user reset password.
// The layout is then loaded as a new object, which will throw an error.
// The $OUTPUT global however will keep a correct reference to the new object.
echo $OUTPUT->render($page);
