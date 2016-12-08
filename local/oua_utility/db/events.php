<?php

$observers = [
    [
        'eventname'   => '\core\event\role_assigned',
        'callback'    => '\local_oua_utility\observers::handle_role_changed',
        'priority'    => 200,
        'internal'    => false,
    ],
    [
        'eventname'   => '\core\event\role_unassigned',
        'callback'    => '\local_oua_utility\observers::handle_role_changed',
        'priority'    => 200,
        'internal'    => false,
    ],
];
