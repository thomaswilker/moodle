<?php

function xmldb_message_ouamessage_install()
{
    global $DB;
    $result = true;

    $processor = new stdClass();
    $processor->name = 'ouamessage';
    $DB->insert_record('message_processors', $processor);
    return $result;
}