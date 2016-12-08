<?php
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_oua_utility\task\globalteachersync_task',
        'disabled' => 1,
        'blocking' => 0,
        'minute' => '1',
        'hour' => '1',
        'day' => '*',
        'dayofweek' => '3',
        'month' => '*'
    ),

);
