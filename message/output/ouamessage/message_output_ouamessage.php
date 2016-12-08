<?php

/**
 * oua message processor
 *
 * @author Open Universities Australia
 * @package message_ouamessage
 */
require_once($CFG->dirroot . '/message/output/lib.php');

class message_output_ouamessage extends message_output
{
    public static $processorid = null;

    public function __construct()
    {
        global $DB;

        // Hold onto the ouamessage processor id because /admin/cron.php sends a lot of messages at once
        if (!self::$processorid) {
            $processor = $DB->get_record('message_processors', array('name' => 'ouamessage'));
            self::$processorid = $processor->id;
        }
    }

    /**
     * Inspired from popup processor.
     *
     * Processes the message.
     * @param object $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     */
    public function send_message($eventdata)
    {

        global $DB;

        // Users can't send notifications to themselves.
        if ($eventdata->userfrom->id != $eventdata->userto->id) {
            $procmessage = new stdClass();
            $procmessage->unreadmessageid = $eventdata->savedmessageid;
            $procmessage->processorid = self::$processorid;

            // If a message is not added to the message working table, then notifications are never marked as "unread" and immediately moved to "read".
            // Messages are removed from the message_working table by the "marked as read" core functions
            $DB->insert_record('message_working', $procmessage);
        }

        return true;
    }

    /**
     * Creates necessary fields in the messaging config form, Moodle default.
     * @param object $mform preferences form class
     */
    public function config_form($preferences)
    {
        global $USER;
        return true;
    }

    /**
     * Parses the form submitted data and saves it into preferences array, Moodle default.
     * @param object $mform preferences form class
     * @param array $preferences preferences array
     */
    public function process_form($form, &$preferences)
    {
        return true;
    }

    /**
     * Loads the config data from database to put on the form (initial load), Moodle default.
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid)
    {
        return true;
    }
}
