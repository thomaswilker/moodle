<?php

/**
 * Event observer.
 *
 * @package   local_conversations
 * @category  event
 * @copyright 2016 Open Universities Australia
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array (
        'eventname' => '\core\event\message_viewed',
        'callback'  => '\local_conversations\observer::unread_messages_clear',
    ),
    array (
        'eventname' => '\core\event\message_sent',
        'callback'  => '\local_conversations\observer::unread_messages_clear',
    ),
    array (
        'eventname' => '\core\event\message_deleted',
        'callback'  => '\local_conversations\observer::unread_messages_clear',
    ),
);
