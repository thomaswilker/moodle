<?php

$functions = array(
    'block_oua_forum_get_discussions' => array( // External services description.
        'classname'    => 'block_oua_forum_recent_posts\external',
        'methodname'   => 'get_oua_forum_discussions_with_posts_paginated',
        'classpath'    => '',
        'description'  => 'Get recent discussions',
        'type'         => 'read',
        'capabilities' => '',
         'ajax'        => true,
    ),
    'block_oua_forum_get_discussion_by_id' => array( // External services description.
        'classname'    => 'block_oua_forum_recent_posts\external',
        'methodname'   => 'get_oua_forum_discussion_by_id_with_post',
        'classpath'    => '',
        'description'  => 'Get discussion thread with post',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
    'block_oua_forum_add_discussion' => array(
        'classname'    => 'block_oua_forum_recent_posts\external',
        'methodname'   => 'oua_forum_add_discussion',
        'classpath'    => '',
        'description'  => 'Add a new discussion into an existing forum.',
        'type'         => 'write',
        'capabilities' => 'mod/forum:startdiscussion',
        'ajax'         => true,
    ),
    'block_oua_forum_add_discussion_post' => array(
        'classname'    => 'block_oua_forum_recent_posts\external',
        'methodname'   => 'oua_forum_add_discussion_post',
        'classpath'    => '',
        'description'  => 'Add a new post into an existing discussion.',
        'type'         => 'write',
        'capabilities' => 'mod/forum:replypost',
        'ajax'         => true,
    ),
    'block_oua_forum_delete_discussion_post' => array(
        'classname'    => 'block_oua_forum_recent_posts\external',
        'methodname'   => 'oua_forum_delete_discussion_post',
        'classpath'    => '',
        'description'  => 'Delete new posts into an existing discussion.',
        'type'         => 'write',
        'capabilities' => 'mod/forum:deleteownpost, mod/forum:deleteanypost',
        'ajax'         => true,
    ),
);
