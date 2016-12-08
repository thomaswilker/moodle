<?php

namespace block_oua_notifications;
use \cache;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer.
 *
 * @package    block_oua_notifications
 */
class observer
{

    /**
     * Clear cache when user read or receive notifications.
     * This cache clear also applicable to messages since notification is a special type of message.
     *
     * @param \core\event\base $event
     */
    public static function unread_notifications_clear(\core\event\base $event)
    {
        $eventdata = $event->get_data();

        $eventname = '';
        if (isset($eventdata['eventname'])) {
            $eventname = $eventdata['eventname'];
        }

        $useridto = null;

        if ($eventname == '\core\event\message_viewed') {
            if (isset($eventdata['userid'])) {
                $useridto = $eventdata['userid'];
            }

        } elseif ($eventname == '\core\event\message_sent') {
            if (isset($eventdata['relateduserid'])) {
                $useridto = $eventdata['relateduserid'];
            }
        }

        if ($useridto) {
            $cache = cache::make('block_oua_notifications', 'unreadnotifications');
            $cachekey = $useridto . '_unreadnotifications';
            $cache->delete($cachekey);
        }
    }
}
