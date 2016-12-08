<?php
/**
* Event observer.
*
* @package   block_oua_connections
* @category  event
* @copyright 2015 Ben Kelada (ben.kelada@open.edu.au)
*/

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array ('eventname' => '\block_oua_connections\event\contact_connected',
           'callback'  => '\block_oua_social_activity\observer::save_connection_event',
           'internal'  => true,
           'priority'  => 1000,
    ),
    array ('eventname' => '\core\event\message_contact_removed',
           'callback'  => '\block_oua_social_activity\observer::remove_connection_events',
           'internal'  => true,
           'priority'  => 1000,
    ),
    array ('eventname' => '\core\event\message_contact_blocked',
           'callback'  => '\block_oua_social_activity\observer::remove_connection_events',
           'internal'  => true,
           'priority'  => 1000,
    ),
    array ('eventname' => '\core\event\user_deleted',
           'callback'  => '\block_oua_social_activity\observer::remove_events_for_user',
           'internal'  => true,
           'priority'  => 1000,
    ),
);
