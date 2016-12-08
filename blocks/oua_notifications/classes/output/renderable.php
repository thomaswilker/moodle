<?php
namespace block_oua_notifications\output;
defined('MOODLE_INTERNAL') || die;

class renderable implements \renderable, \templatable {
    private $notifications;

    public function __construct($userid) {
        $this->notifications = $this->get_notifications($userid);
    }
    protected function get_notifications($userid) {
        global $DB;

        $notifications = $DB->get_records_sql("SELECT id, id as notificationid, useridfrom, useridto, subject, fullmessage,
                                                      fullmessageformat, fullmessagehtml, smallmessage, notification, timecreated, 1 as unread
                                                  FROM {message} m
                                                 WHERE m.notification = 1
                                                   AND m.useridto = ?
                                              UNION ALL
                                              SELECT id, id as notificationid, useridfrom, useridto, subject, fullmessage,
                                                      fullmessageformat, fullmessagehtml, smallmessage, notification, timecreated, 0 as unread
                                                  FROM {message_read} mr
                                                 WHERE mr.notification = 1
                                                   AND mr.useridto = ?
                                              ORDER BY timecreated DESC, id DESC", array($userid, $userid));

        return $notifications;
    }
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $data->dismiss_all_notification_ids = array();
        $unreadnotificationscount = 0;
        foreach($this->notifications as &$notification) {
            $notification->iconclass = 'fa-bell-o';

            // Update userdate date for notification
            $notification->formatted_date = userdate($notification->timecreated, get_string('notificationdateformat', 'block_oua_notifications'));
            if (strpos($notification->fullmessagehtml, 'connect_request') !== false) {
                // Exclude Connection requests from dismiss all.
                $notification->isconnectionrequest = 1;
                $notification->iconclass = 'fa-user-plus';
            } else {
                // Build notification list to dismiss on dismiss all.
                $data->dismiss_all_notification_ids[] = $notification->id;
            }

            if (strpos($notification->fullmessagehtml, 'connect_accept') !== false) {
               $notification->iconclass = 'fa-users';
            }
            if ($notification->unread == 1) {
                $notification->readstatus = 'new-message';
                $unreadnotificationscount++;
            } else {
                $notification->readstatus = '';
            }
        }
        $data->dismiss_all_count = count($data->dismiss_all_notification_ids);
        if( $data->dismiss_all_count > 1) {
            $data->display_dismiss_all = true;
        } else {
            $data->display_dismiss_all = false;
        }
        $data->dismiss_all_notification_ids = json_encode( $data->dismiss_all_notification_ids);
        $data->notification_count = count($this->notifications);
        $data->notification_list = array_values($this->notifications);
        $data->unread_notifications_count = $unreadnotificationscount;
        return $data;
    }
}
