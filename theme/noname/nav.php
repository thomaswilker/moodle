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
 * This page provides navigation for nodes in the nav tree with no real page.
 *
 * @package core
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$contextparam = required_param('context', PARAM_INT);
$pathparam = required_param('path', PARAM_RAW);

$context = context::instance_by_id($contextparam);
$PAGE->navigation->require_admin_tree();
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$path = explode('/', $pathparam);


$PAGE->set_url('/theme/noname/nav.php', array('context' => $contextparam, 'path' => $pathparam));

$node = $PAGE->settingsnav;

$currentactive = $node->search_for_active_node();
if ($currentactive) {
    $currentactive->make_inactive();
}

$current = end($path);
$search = $node->find($current, null);
if ($search) {
    $node = $search;
}

$node->make_active();

$PAGE->set_title($node->text);
$PAGE->set_heading($node->text);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('theme_noname/nav_branch_placeholder', ['title' => $node->get_title()]);

echo $OUTPUT->footer();
