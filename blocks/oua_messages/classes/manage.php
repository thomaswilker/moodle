<?php
namespace block_oua_messages;
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
use user_picture;
use stdClass;
use core_user;
use context_course;
use core\message\message as core_message;

class manage {
    protected $cfg;
    protected $db;
    protected $output;
    protected $page;
    protected $user;

    public function __construct($cfg, $db, $output, $page, $user) {
        $this->cfg = $cfg;
        $this->db = $db;
        $this->output = $output;
        $this->page = $page;
        $this->user = $user;
    }

    /**
     * Deletes a message for the given user.
     *
     * @param $messageid
     */
    public function delete_message($messageid) {
        $params = array('useridto' => $this->user->id, 'messageid' => $messageid);
        $this->db->delete_records_select('message_read', 'useridto = :useridto AND id = :messageid', $params);
        $this->db->delete_records_select('message', 'useridto = :useridto AND id = :messageid', $params);
    }

    /**
     * User read a message, message is removed from current and inserted into message_read.
     *
     * @param $messageid
     */
    public function mark_message_read($messageid)
    {
        $params = array('useridto' => $this->user->id, 'id' => $messageid);

        $message = $this->db->get_record('message', $params);
        $messagereadid = message_mark_message_read($message, time());

        return $messagereadid;
    }
}
