<?php

$string['pluginname'] = 'OUA Connections';
$string['messageprovider:requestconnection'] = 'Connection Request Message';
$string['messageprovider:acceptrequest'] = 'Connection Connected Message';
$string['oua_connections'] = 'Student Connections';
$string['title'] = 'Connections';
$string['oua_connections:addinstance'] = 'Add a new connections block.';
$string['oua_connections:myaddinstance'] = 'Add a new connections block.';
$string['seemorestudents'] = 'See more';
$string['message'] = 'Message';
$string['connect'] = 'Connect';
$string['requested'] = 'Requested';
$string['my_connections'] = 'My Connections';
$string['suggested_connections'] = 'Suggested Connections';
$string['viewallconnections'] = 'View All Connections';
$string['deleteconnection'] = 'Remove';
$string['deleteconnectionconfirm'] = 'Delete this connection?';

$string['connect_request_body_html'] = '<p class="connect_request" data-userid="{$a->userfromid}"><span class="studentfromname"><a href="{$a->userfromprofileurl}">{$a->studentfrom}</a></span> has invited you to connect.</p>';
$string['connect_request_body'] = '{$a->studentfrom} has invited you to connect.';
$string['connect_request_body_small'] = '{$a->studentfrom} has invited you to connect.';
$string['connect_request_subject'] = 'Connection Request';

$string['connect_accept_body_html'] = '<span class="connect_accept">You are now connected with {$a->userfrom}</span>';
$string['connect_accept_body'] = 'You are now connected with {$a->userfrom}';
$string['connect_accept_body_small'] = 'You are now connected with {$a->userfrom}';
$string['connect_accept_subject'] = 'You are now connected with {$a->userfrom}';

$string['nomyconnections'] = 'You have no connections.';
$string['seesuggestedconnectionstab'] = 'See the <a href="#suggestedconnections">\'Suggested Connections\'</a> tab to make new connections';
$string['seesuggestedconnectionstabdashboard'] = 'See the <a href="{$a}#suggestedconnections">\'Suggested Connections\'</a> tab on the dashboard to make new connections';

$string['viewprofilecontactrole'] = 'Role for Viewing Contact Profile';
$string['viewprofilecontactroledesc'] = 'A role with the \'moodle/user:viewdetails\' capability, that can be assigned to the \'User\' Context type. This role will be assigned to users personal contexts to allow them to see other users profiles.';

$string['eventcontactconnected'] = 'Contacts connected event';
$string['eventcontactconnecteddescription'] = 'The user with id \'{$a->userid}\' has connected to the user with id \'{$a->relateduserid}\'.';

