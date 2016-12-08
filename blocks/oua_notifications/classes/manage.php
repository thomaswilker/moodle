<?php
namespace block_oua_notifications;
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

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
     * Deletes a notification for the given user.
     *
     * @param $notificationid
     */
    public function delete_my_notifications($notificationids) {
        $params = array();
        list($sqlinstmt, $params) = $this->db->get_in_or_equal($notificationids, SQL_PARAMS_NAMED);
        $params['useridto'] =  $this->user->id;
        $this->db->delete_records_select('message_read', "useridto = :useridto AND id $sqlinstmt", $params);
        $this->db->delete_records_select('message', "useridto = :useridto AND id $sqlinstmt", $params);
    }


    /**
     * Mark notification as read.
     *
     * @param $notificationid
     * @return int
     */
    public function mark_notification_read($notificationid, $timeread = null)
    {
        if (null === $timeread) {
            $timeread = time();
        } else {
            $timeread = intval($timeread);
        }
        $params = array('useridto' => $this->user->id, 'id' => $notificationid);
        $notification = $this->db->get_record('message', $params, '*', MUST_EXIST);
        $notificationid = message_mark_message_read($notification, $timeread);

        return $notificationid;
    }
    
}
