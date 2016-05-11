<?php

function message_html5_show_unread_messages() {
    global $PAGE, $USER, $CFG, $DB, $OUTPUT;

    // There are unread messages so now do a more complex but slower query.
    $messagesql = "SELECT m.*, c.blocked, m.subject, m.smallmessage, m.contexturl
                     FROM {message} m
                     JOIN {message_working} mw ON m.id=mw.unreadmessageid
                     JOIN {message_processors} p ON mw.processorid=p.id
                     JOIN {user} u ON m.useridfrom=u.id
                     LEFT JOIN {message_contacts} c ON c.contactid = m.useridfrom
                                                   AND c.userid = m.useridto
                    WHERE m.useridto = :userid
                      AND p.name='html5'";

    $validmessages = $DB->get_records_sql($messagesql, array('userid' => $USER->id));

    foreach ($validmessages as $message) {
        if (!$message->blocked) {
            $url = $message->contexturl;
            if (empty($url)) {
                $url = new moodle_url('/message/index.php');
                $url = $url->out();
            }
            $logourl = $PAGE->theme->setting_file_url('smalllogo', 'smalllogo');
            if (!$logourl) {
                $logourl = $OUTPUT->pix_url('notification', 'message_html5')->out();
            }
            $params = array($message->subject, $message->smallmessage, $url, $message->id, $logourl);
            $PAGE->requires->js_call_amd('message_html5/notification', 'notify', $params);
        }
        message_mark_message_read($message, time());
    }
}
