<?php

$functions = array(
    'block_oua_notifications_delete_notifications' => array(
        'classname'   => '\block_oua_notifications\external',
        'methodname'  => 'delete_notifications',
        'classpath'   => '',
        'description' => 'Delete one or more notifications',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to delete your own messages */
        'ajax'        => true,
    ),
    'block_oua_notifications_mark_notification_read' => array(
        'classname'   => '\block_oua_notifications\external',
        'methodname'  => 'mark_notification_read',
        'classpath'   => '',
        'description' => 'Mark notification as read',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to read your own notification */
        'ajax'        => true,
    ),

);

