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
 * Manage broadcast messages
 *
 * @package    blocks
 * @subpackage message_broadcast
 * @copyright  2015 Open Universities Australia  ben.kelada@open.edu.au
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();

$courseid = null;
$coursecontextid = null;
$urlparams = array();
if (has_capability('block/message_broadcast:send_broadcast_message', context_system::instance())) {
    // admin or has system level broadcast capability
    $PAGE->set_context(context_system::instance());
} else {
    $courseid = required_param('courseid', PARAM_INT);
    $coursecontext = context_course::instance($courseid);
    $coursecontextid = $coursecontext->id;

    require_login($courseid);

    $PAGE->set_context($coursecontext);
    require_capability('block/message_broadcast:send_broadcast_message', $coursecontext);
    $urlparams['courseid'] = $courseid;
}

$title = get_string('managemessages', 'block_message_broadcast');
$url = new moodle_url('/blocks/message_broadcast/managemessages.php', $urlparams);
$PAGE->set_url($url);

$PAGE->set_title($title);
$PAGE->set_pagelayout('incourse');

$output = $PAGE->get_renderer('block_message_broadcast');

echo $output->header();
echo $output->heading($title);

$managemessagepage = new \block_message_broadcast\output\manage_messages_page($courseid);

echo $output->render($managemessagepage);

echo $output->footer();
