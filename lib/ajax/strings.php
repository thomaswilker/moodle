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
 * Code to bulk load some language strings from javascript.
 *
 * You should not send requests to this script directly. Instead use the strings_for_js
 * function in javascript_static.js.
 *
 * @package    core
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

// Check access.
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

$identifiers = required_param_array('identifiers', PARAM_ALPHANUMEXT);
$component = required_param('component', PARAM_COMPONENT);

$strings = array();
$strings[$component] = array();
foreach ($this->identifiers as $identifier) {
    $strings[$component][$identifier] = get_string($identifier, $component);
}
echo html_writer::script(js_writer::set_variable('M.str', $strings));
