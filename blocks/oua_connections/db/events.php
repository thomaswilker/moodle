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
    array (
    'eventname' => '\core\event\message_contact_added',
    'callback'  => '\block_oua_connections\observer::add_privilege',
    'internal'  => true,
    'priority'  => 1000,
    ),
    array('eventname' => '\core\event\message_contact_removed',
    'callback'  => '\block_oua_connections\observer::remove_privilege',
    'internal'  => true,
    'priority'  => 1000,
    ),
    array('eventname' => '\core\event\message_contact_blocked',
    'callback'  => '\block_oua_connections\observer::remove_privilege',
    'internal'  => true,
    'priority'  => 1000,
    ),
);
