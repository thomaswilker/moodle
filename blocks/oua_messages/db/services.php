<?php

$functions = array(
    'block_oua_messages_delete_message' => array(
        'classname'   => '\block_oua_messages\external',
        'methodname'  => 'delete_message',
        'classpath'   => '',
        'description' => 'Delete a message',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to delete your own messages */
        'ajax'        => true,
    ),
    'block_oua_messages_mark_message_read' => array(
        'classname'   => '\block_oua_messages\external',
        'methodname'  => 'mark_message_read',
        'classpath'   => '',
        'description' => 'Mark message read',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to move your own message to messaage_read */
        'ajax'        => true,
    ),
);

