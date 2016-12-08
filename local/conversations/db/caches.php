<?php
$definitions = array(
    'unreadmessages' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true, // No hashing required, key is uniquely safe.
        'staticaccelerationsize' => 1 // I only store my data, so make it static.
    ),
);
