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
 * Delete a broadcast message
 *
 * @package    blocks
 * @subpackage message_broadcast
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();
$admin = false;
if (has_capability('block/message_broadcast:send_broadcast_message', context_system::instance())) {
    $PAGE->set_context(context_system::instance());
    $admin = true;
} else {
    $courseid = required_param('courseid', PARAM_INT);
    $coursecontext = context_course::instance($courseid);
    $PAGE->set_context($coursecontext);
    require_capability('block/message_broadcast:send_broadcast_message', $coursecontext);
}

$PAGE->set_url('/block/message_broadcast/deletemessage.php');
$PAGE->set_title(get_string('deletemessage', 'block_message_broadcast'));
$PAGE->set_pagelayout('admin');

$messageid = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', false, PARAM_BOOL);

$message = $DB->get_record('message_broadcast', array('id' => $messageid));
$urlparams = array();
if (!$admin) {
    $urlparams['courseid'] = $courseid;
}

if ($delete) {
    $managemessage = new \block_message_broadcast\manage();
    $managemessage->delete_message($message->id);
    $managemessagesurl = new moodle_url("$CFG->wwwroot/blocks/message_broadcast/managemessages.php", $urlparams);
    redirect($managemessagesurl);
}
$urlparams = array_merge($urlparams, array('id' => $messageid, 'delete' => true));

$confirm = get_string('deleteconfirm', 'block_message_broadcast', $message->headingtitle);

$confirmurl = new moodle_url("/blocks/message_broadcast/deletemessage.php", $urlparams);
$cancelurl = new moodle_url("/blocks/message_broadcast/managemessages.php");
$yesbutton = new single_button($confirmurl, get_string('yes'));
$nobutton = new single_button($cancelurl, get_string('no'));

echo $OUTPUT->header();
echo $OUTPUT->confirm($confirm, $yesbutton, $nobutton);
echo $OUTPUT->footer();
