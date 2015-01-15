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

require_once('../../config.php');

$url = new moodle_url('/local/demo/view.php');

require_login($SITE);
$PAGE->set_url($url);

$renderer = $PAGE->get_renderer('local_demo');

$PAGE->set_title(get_string('pluginname', 'assign'));
$PAGE->set_heading($PAGE->course->fullname);

echo $renderer->header();
echo $renderer->heading('Demo');

$horse1 = new \local_demo\output\horse('Black Caviar');

echo $renderer->render($horse1);

echo $renderer->footer();
