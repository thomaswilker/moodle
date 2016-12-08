<?php

$functions = array(
    'local_conversations_delete_messages_by_id' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'delete_messages_by_id',
        'classpath'   => '',
        'description' => 'Delete messages by ids',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to delete your own messages */
        'ajax'        => true,
    ),
    'local_conversations_mark_messages_read_by_id' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'mark_messages_read_by_id',
        'classpath'   => '',
        'description' => 'Mark all given message ids as read',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to move your own message to messaage_read */
        'ajax'        => true,
    ),
    'local_conversations_send_message' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'send_message',
        'classpath'   => '',
        'description' => 'Send a message',
        'type'        => 'write',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'local_conversations_get_conversation' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'get_conversation',
        'classpath'   => '',
        'description' => 'Retrieve conversations for a user',
        'type'        => 'read',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'local_conversations_get_all_notifications' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'get_all_notifications',
        'classpath'   => '',
        'description' => 'Retrieve notifications for a user',
        'type'        => 'read',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'local_conversations_get_cached_header_previews' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'get_cached_header_previews',
        'classpath'   => '',
        'description' => 'Retrieve header previews (conversation/notificaiton for user)',
        'type'        => 'read',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'local_conversations_search_contacts' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'search_contacts',
        'classpath'   => '',
        'description' => 'Search user contacts',
        'type'        => 'read',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'local_conversations_delete_conversation' => array(
        'classname'   => '\local_conversations\external',
        'methodname'  => 'delete_conversation',
        'classpath'   => '',
        'description' => 'Delete all messages to/from user in a conversation',
        'type'        => 'write',
        'capabilities'=> '', /* Don't need a capability to mark your own message as deleted */
        'ajax'        => true,
    ),
);

