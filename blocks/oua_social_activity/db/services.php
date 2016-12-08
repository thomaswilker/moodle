<?php

$functions = array(
    'block_oua_social_activity_get_more_events' => array(
        'classname'   => '\block_oua_social_activity\external',
        'methodname'  => 'get_more_events',
        'classpath'   => '',
        'description' => 'Get more social activity events',
        'type'        => 'read',
        'capabilities'=> '', /* Don't need a capability to delete your own messages */
        'ajax'        => true,
    ),

);

