<?php

function message_popup_show_unread_messages() {
    global $PAGE, $USER, $CFG, $DB;

    // There are unread messages so now do a more complex but slower query.
    $messagesql = "SELECT m.id, c.blocked
                     FROM {message} m
                     JOIN {message_working} mw ON m.id=mw.unreadmessageid
                     JOIN {message_processors} p ON mw.processorid=p.id
                     JOIN {user} u ON m.useridfrom=u.id
                     LEFT JOIN {message_contacts} c ON c.contactid = m.useridfrom
                                                   AND c.userid = m.useridto
                    WHERE m.useridto = :userid
                      AND p.name='popup'";

    // If the user was last notified over an hour ago we can re-notify them of old messages
    // so don't worry about when the new message was sent.
    $lastnotifiedlongago = $USER->message_lastpopup < (time()-3600);
    if (!$lastnotifiedlongago) {
        $messagesql .= 'AND m.timecreated > :lastpopuptime';
    }

    $waitingmessages = $DB->get_records_sql($messagesql, array('userid' => $USER->id, 'lastpopuptime' => $USER->message_lastpopup));

    $validmessages = 0;
    foreach ($waitingmessages as $messageinfo) {
        if ($messageinfo->blocked) {
            // Message is from a user who has since been blocked so just mark it read.
            // Get the full message to mark as read.
            $messageobject = $DB->get_record('message', array('id' => $messageinfo->id));
            message_mark_message_read($messageobject, time());
        } else {
            $validmessages++;
        }
    }

    if ($validmessages > 0) {
        $strmessages = get_string('unreadnewmessages', 'message', $validmessages);
        $strgomessage = get_string('gotomessages', 'message');
        $strstaymessage = get_string('ignore', 'admin');

        $notificationsound = null;
        $beep = get_user_preferences('message_beepnewmessage', '');
        if (!empty($beep)) {
            // Browsers will work down this list until they find something they support.
            $sourcetags =  html_writer::empty_tag('source', array('src' => $CFG->wwwroot.'/message/bell.wav', 'type' => 'audio/wav'));
            $sourcetags .= html_writer::empty_tag('source', array('src' => $CFG->wwwroot.'/message/bell.ogg', 'type' => 'audio/ogg'));
            $sourcetags .= html_writer::empty_tag('source', array('src' => $CFG->wwwroot.'/message/bell.mp3', 'type' => 'audio/mpeg'));
            $sourcetags .= html_writer::empty_tag('embed',  array('src' => $CFG->wwwroot.'/message/bell.wav', 'autostart' => 'true', 'hidden' => 'true'));

            $notificationsound = html_writer::tag('audio', $sourcetags, array('preload' => 'auto', 'autoplay' => 'autoplay'));
        }

        $url = $CFG->wwwroot.'/message/index.php';

        $content =  html_writer::start_tag('div', array('id' => 'newmessageoverlay', 'class' => 'mdl-align')).
                        html_writer::start_tag('div', array('id' => 'newmessagetext')).
                            $strmessages.
                        html_writer::end_tag('div').

                        $notificationsound.
                        html_writer::start_tag('div', array('id' => 'newmessagelinks')).
                        html_writer::link($url, $strgomessage, array('id' => 'notificationyes')).'&nbsp;&nbsp;&nbsp;'.
                        html_writer::link('', $strstaymessage, array('id' => 'notificationno')).
                        html_writer::end_tag('div');
                    html_writer::end_tag('div');

        $PAGE->requires->js_init_call('M.core_message.init_notification', array('', $content, $url));

        $USER->message_lastpopup = time();
    }

}
