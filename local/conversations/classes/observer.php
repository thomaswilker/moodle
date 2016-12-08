<?php
namespace local_conversations;
defined('MOODLE_INTERNAL') || die();
use cache;

/**
 * Event observer.
 *
 * @package    local_conversations
 */
class observer {

    /**
     * Clear cache when user read message or send message.
     * This cache clear also applicable to notification since notification is a special type of message.
     *
     * @param \core\event\base $event
     */
    public static function unread_messages_clear(\core\event\base $event) {
        $eventdata = $event->get_data();

        $eventname = '';
        if (isset($eventdata['eventname'])) {
            $eventname = $eventdata['eventname'];
        }

        $useridto = null;

        if (($eventname == '\core\event\message_viewed'  || $eventname == '\core\event\message_deleted')
                && isset($eventdata['userid'])) {
            $useridto = $eventdata['userid'];
        } else if ($eventname == '\core\event\message_sent'
                   && isset($eventdata['relateduserid'])) {
            $useridto = $eventdata['relateduserid'];
        }

        if ($useridto) {
            api::clear_unread_message_cache($useridto);
        }
    }
}
