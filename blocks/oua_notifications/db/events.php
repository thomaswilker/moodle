<?php

/**
 * Event observer.
 *
 * @package   block_oua_notifications
 * @category  event
 * @copyright 2016 Open Universities Australia
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\message_viewed',
        'callback' => '\block_oua_notifications\observer::unread_notifications_clear',
    ),
    array(
        'eventname' => '\core\event\message_sent',
        'callback' => '\block_oua_notifications\observer::unread_notifications_clear',
    ),
);
