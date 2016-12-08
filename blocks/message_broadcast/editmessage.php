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
 * Create a new broadcast message
 *
 * @package    blocks
 * @subpackage message_broadcast
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();

$messageformcontroller = new \block_message_broadcast\form_controller(true);

$PAGE->set_title(get_string('editmessage', 'block_message_broadcast'));
$PAGE->set_url($messageformcontroller->get_redirecturl());

// The params are what we use by default for new forms.
$mform = new \block_message_broadcast\form(null, $messageformcontroller->get_formcustomdata(), 'post', '', array('autocomplete' => 'on', 'class' => 'messagebroadcastedit'));

if ($mform->is_cancelled()) {
    redirect($messageformcontroller->get_managemessageurl());
} else if ($data = $mform->get_data()) {
    if (!$messageformcontroller->admin) {
        $data->courseids = array($messageformcontroller->courseid); // Force course edit for non admin.
    } else if (in_array('0', $data->courseids)) {
        $data->courseids = array(0); // Force system wide message if selected.
    }
    $managemessages = new \block_message_broadcast\manage();
    $managemessages->edit_message($data);
    redirect($messageformcontroller->get_managemessageurl());
}

// Start output
echo $OUTPUT->header();
echo $OUTPUT->heading($messageformcontroller->pageheading);

$mform->display();

echo $OUTPUT->footer();
