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
 * Display all connection data
 *
 * @package    block_oua_connections
 */

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_login();
$url = new moodle_url('/blocks/oua_connections/all_connections.php');
$PAGE->set_url($url);
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_title(get_string('my_connections','block_oua_connections'));
$PAGE->set_heading(get_string('my_connections','block_oua_connections'));

/**
 * This code replicates the dashboard blocks, but is broken, because the dashboard main content area
 * is coupled to the dashboard tab content.
 * it must be run before any output renderers are used.
 */
/*
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
// Copy My Moodle page info.  So we can use the blocks from dashboard
if (!$currentpage = my_get_page($USER->id, MY_PAGE_PRIVATE)) {
    print_error('mymoodlesetup');
}
$PAGE->set_subpage($currentpage->id);
*/


$myconnections = new \block_oua_connections\output\my_connections(0, 'firstname');
if (isloggedin() && has_capability('moodle/site:sendmessage', $PAGE->context)
    && !empty($CFG->messaging) && !isguestuser()) {
    require_once($CFG->dirroot . '/message/lib.php');
    message_messenger_requirejs();
}

// Start output
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('block_oua_connections');
echo $renderer->display_all_connections_page($myconnections->export_for_template($renderer));
echo $OUTPUT->footer();
