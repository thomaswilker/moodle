<?php

$functions = array(

    'block_oua_connections_request_connection' => array( // External services description.
        'classname'    => 'block_oua_connections\external',
        'methodname'   => 'request_connection',
        'classpath'    => '',
        'description'  => 'Request connection to another user',
        'type'         => 'write',
        'capabilities' => '',
         'ajax'        => true,
    ),
    'block_oua_connections_accept_request_connection' => array(
        'classname'   => 'block_oua_connections\external',
        'methodname'  => 'accept_request_connection',
        'classpath'   => '',
        'description' => 'Accept a connection request from another user',
        'type'        => 'write',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'block_oua_connections_ignore_request_connection' => array(
        'classname'   => 'block_oua_connections\external',
        'methodname'  => 'ignore_request_connection',
        'classpath'   => '',
        'description' => 'Ignore a connection request from another user',
        'type'        => 'write',
        'capabilities'=> '',
        'ajax'        => true,
    ),
    'block_oua_connections_delete_connection' => array(
        'classname'   => 'block_oua_connections\external',
        'methodname'  => 'delete_connection',
        'classpath'   => '',
        'description' => 'Delete an existing connection',
        'type'        => 'write',
        'capabilities'=> '',
        'ajax'        => true,
    ),
);

