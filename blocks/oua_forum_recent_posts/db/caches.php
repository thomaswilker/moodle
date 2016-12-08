<?php
$definitions = array(
    'student_count' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'invalidationevents' => array('cache_event_enrolment_updated'), // This needs a manual event triggered.
        'ttl' => 3600, // 1 hour cache invalidation if supported.
    ),

);
