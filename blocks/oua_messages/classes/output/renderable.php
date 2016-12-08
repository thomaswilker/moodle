<?php
namespace block_oua_messages\output;
defined('MOODLE_INTERNAL') || die;

use renderer_base;
use stdClass;
use user_picture;
use ArrayIterator;
use moodle_url;

class renderable implements \renderable, \templatable {
    private $messages;

    public function __construct($userid) {
        $this->messages = $this->get_messages($userid);
    }

    protected function get_messages($userid) {
        global $DB;

        $userfields = user_picture::fields('uf', array('lastaccess'), 'userfromid', 'userfrom');

        $sql = "SELECT m.id AS messageid, m.*, $userfields
                FROM (
                        (SELECT id, useridfrom, useridto, subject, smallmessage, notification, timecreated, 1 as unread
                                FROM {message} m
                                WHERE notification = 0
                                AND m.useridto = :notread_useridto
                            )
                        UNION ALL
                        (SELECT id, useridfrom, useridto, subject, smallmessage, notification, timecreated, 0 as unread
                                FROM {message_read} mr
                                WHERE notification = 0
                                AND mr.useridto = :read_useridto
                        )
                    ) m

                JOIN {user} uf ON uf.id = m.useridfrom
                     WHERE m.notification = 0
                     AND m.useridto = :useridto
                     ORDER BY m.timecreated DESC
                ";

        return $DB->get_records_sql($sql, array('useridto'=>$userid, 'read_useridto'=>$userid, 'notread_useridto'=>$userid));
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $countnewmessages = 0;
        foreach ($this->messages as &$message) {
            // Update userdate date for message.
            $message->formatted_date = userdate($message->timecreated, get_string('messagedateformat', 'block_oua_messages'));

            $userfrom = user_picture::unalias($message, array('lastaccess'), 'userfromid', 'userfrom');
            $message->userfromfullname = fullname($userfrom, true);
            $message->userfromprofileurl = new moodle_url('/user/profile.php', array(
                'id' => $message->useridfrom
            ));
            $message->userfromavatar = $output->user_picture(
                $userfrom,
                array(
                    'link'                   => false,
                    'visibletoscreenreaders' => false,
                    'class'                  => 'profilepicture',
                    'size'                   => '50'
                )
            );
            if ($message->unread) {
                $countnewmessages++;
                $message->readstatus = 'new-message';
            } else {
                $message->readstatus = '';
            }
        }
        $data->message_count = $countnewmessages;
        $data->message_list = array_values($this->messages);

        return $data;
    }
}