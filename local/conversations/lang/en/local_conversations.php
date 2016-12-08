<?php
$string['pluginname'] = 'Conversations Plugin';
$string['local_conversations'] = 'Conversations';
$string['title'] = 'messages';

$string['config_conversationrefreshtime'] = 'Conversation Auto refresh time in ms';
$string['config_conversationrefreshtime_details'] = "The time taken for the current conversation panel to refresh itself (on all messages page). Given in ms (e.g. 90000 = 90 seconds = 1.5 minutes).\nMinimum value is 1000ms (1s), if lower or invalid, it will refresh at 60000 (1min)";
$string['config_headerrefreshtime'] = 'Time to refresh the notification headers in ms';
$string['config_headerrefreshtime_details'] = "The time taken for the message and notification alerts to refresh. Given in ms (e.g. 120000 = 120 seconds = 2 minutes).\nMinimum value is 1000ms (1s), if lower or invalid, it will refresh at 120000 (2min)";

$string['messagedateformat'] = '<span class="date">%d</span><span class="month">%b</span>';
$string['reply'] = 'Reply';
$string['delete'] = 'Delete';
$string['deleteall'] = 'Delete All';

$string['deleteconfirmmessage'] = 'Delete Message?';
$string['deleteconfirm'] = 'Confirm Delete';
$string['nomessages'] = 'You currently have no messages to view';
$string['cachedef_unreadmessages'] = 'Caches 5 newest unread messages and total count of all unread messages';
$string['mymessages'] = 'Conversations';
$string['send'] = 'Send';
$string['newmessage'] = 'New Message';
$string['cancelmessage'] = 'Cancel';
$string['validationmessageempty'] = 'Message empty. Please enter a message to send.';
$string['loggedout'] = "Error accessing moodle, You have probably been logged out.\nClick \"Reload\", to refresh your page";
$string['newmessagesearch'] = "To";
$string['searchcontacts'] = "Search contacts";
$string['searchhelp'] = "Start typing in the box above to search for contacts to message.";
$string['nocontactsfound'] = "No Contacts found, please try searching again.";
$string['searchresults'] = "Search results:";
$string['conversationhelp'] = 'Start a conversation with {$a} by typing a message in the box below.';
$string['noconversations'] = 'You haven\'t started any conversations here yet. Select New Message above to get started.';
$string['strftimemessagetimeshort'] = '%d %b';
$string['deleteconversation'] = 'Delete Conversation';
$string['deleteconfirmconverstationtitle'] = "Delete conversation?";
$string['deleteconfirmconverstation'] = "Selecting delete will remove every message in this conversation.\n\nThis action cannot be undone.";
$string['conversationmenu'] = "Conversation Actions";

$string['notificationactions'] = "Notification Actions";


$string['mynotifications'] = 'Notifications';
$string['notificationdateformat'] = '<span class="date">%d</span><span class="month">%b</span>';
$string['deleteconfirmnotificationtitle'] = 'Delete Notification?';
$string['deleteconfirmallnotificationtitle'] = 'Delete {$a} Notifications?';
$string['nonotifications'] = 'You currently have no notifications to view';
$string['cachedef_unreadnotifications'] = 'Caches total count of all unread notifications';
$string['connect'] = 'Connect';
$string['ignore'] = 'Ignore';
$string['viewprofile'] = 'View Profile';
$string['markasread'] = 'Mark as Read';
$string['markallnotificationsasreadtitle'] = 'Mark All As Read?';
$string['markallnotificationsasread'] = 'Mark All As Read';

$string['unreadmessages'] = 'Unread Messages';
$string['nounreadmessages'] = 'You have no unread messages';
$string['viewallmessages'] = 'View All Messages';

$string['unreadnotifications'] = 'Unread Notifications';
$string['nounreadnotifications'] = 'You have no unread notifications';
$string['viewallnotifications'] = 'View All Notifications';
