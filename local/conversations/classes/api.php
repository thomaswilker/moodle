<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_conversations;

use \local_conversations\output\renderable as message_list;
use context_system;
use moodle_url;
use user_picture;
use stdClass;
use cache;

/**
 * API exposed by local_conversations
 *
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class api {

    // Arbitrary choice number of notifications to display in the preview list.
    const NOTIFICATIONS_PREVIEW_NUMBER = 5;

    /**
     * Clear the unread message (notifications & personal messages) cache for given user.
     *
     * @param $useridto
     */
    public static function clear_unread_message_cache($useridto) {
        $cache = cache::make('local_conversations', 'unreadmessages');

        // Messages clear cache.
        $cachekey = $useridto . '_unreadmessagespreview';
        $cache->delete($cachekey);

        // Notifications clear cache.
        $cachekey = $useridto . '_unreadnotificationspreview';
        $cache->delete($cachekey);
    }

    /**
     * Marks a message a "read" given the message id
     *
     * @param $messageid array(int) message id(s) of message to mark read.
     * @return bool
     * @throws \dml_exception
     */
    public static function mark_messages_read_by_id($messageids) {
        global $DB, $PAGE, $USER;

        // This is required because we call a renderer, context is not auto set.
        $PAGE->set_context(context_system::instance());
        if (empty($messageids)) {
            return;
        }
        $params = array();
        list($wheresql, $params) = $DB->get_in_or_equal($messageids, SQL_PARAMS_NAMED);
        $params = array('useridto' => $USER->id) + $params;
        $wheresql = "id $wheresql";

        $messages = $DB->get_recordset_select('message', $wheresql, $params);

        foreach ($messages as $message) {
            message_mark_message_read($message, time());
        }
        return true;
    }

    /**
     * Returns unread conversations and count of unread messages, using the cache
     * also returns list of user id's with unread messages.
     * @param $user User object containing user->id
     * @return false|int|mixed
     * @throws \coding_exception
     */
    public static function get_cached_unread_conversation_preview($user, $forcerefresh = false) {
        global $DB, $PAGE;

        $cache = cache::make('local_conversations', 'unreadmessages');
        $cachekey = $user->id . '_unreadmessagespreview';
        $unreadconversationcache = $cache->get($cachekey);

        if (false === $unreadconversationcache || $forcerefresh) {
            $userfields = user_picture::fields('u', array('lastaccess'));
            // List of unread messages, grouped by conversation, with unread count and lastmessage time and smallmessage
            $sql = "SELECT useridfrom, last_message.smallmessage as lastmessagesnippet, last_message.timecreated, $userfields, count(useridfrom) as unreadcount
                      FROM {message} m
                      JOIN (SELECT m1.id, m1.useridfrom, m1.useridto, m1.smallmessage, m1.timecreated
                              FROM {message} m1
                         LEFT JOIN {message} m2 ON m1.useridfrom = m2.useridfrom
                                               AND m1.useridto = m2.useridto
                                               AND (m1.timecreated < m2.timecreated OR (m1.timecreated = m2.timecreated AND m1.id < m2.id))
                                               AND m2.notification = 0
                             WHERE m1.useridto = ?
                               AND m1.notification = 0
                               AND m2.id IS NULL -- Retrieve only most recent row (i.e. m1 row with no m2 time greater)
                            ) AS last_message USING (useridfrom)
                      JOIN {user} u ON u.id = m.useridfrom
                     WHERE m.useridto = ?  and m.notification = 0
                  GROUP BY useridfrom, last_message.smallmessage, last_message.timecreated, $userfields
                  ORDER BY last_message.timecreated DESC
            ";
            $params = array($user->id, $user->id);
            $unreadconversations = $DB->get_records_sql($sql, $params);
            $totalcount = count($unreadconversations);

            $unreadusers = array_keys($unreadconversations);
            $unreadconversations = array_values($unreadconversations); // Remove keys for mustache.
            $first5unreadconversations = array_slice($unreadconversations, 0,
                                                     5); // Most recent 5 conversations. arbitrary requirement.

            foreach ($first5unreadconversations as &$user)
            { // Only process the first5, can use $unreadconverations, to process all.
                $otheruserpicture = new user_picture($user);
                $otheruserpicture->size = 3; // Size f1.
                $user->otheruserpictureurl = $otheruserpicture->get_url($PAGE)->out(false);
                $otheruserprofileurl = new moodle_url('/user/view.php', array('id' => $user->id));
                $user->otheruserprofileurl = $otheruserprofileurl->out();
                $user->otheruserfullname = fullname($user);
                $user->lastmessagetimeformatted = userdate($user->timecreated,
                                                           get_string('strftimemessagetimeshort', 'local_conversations'));
            }
            $allconversationspage = new moodle_url('/local/conversations/index.php');

            $unreadconversationcache = array('unread_conversation_count' => $totalcount, // Total count for header update
                                             // Conversation preview update for header.
                                             'unread_conversation_preview' => $first5unreadconversations,
                                             'all_conversations_link' => $allconversationspage->out(false),
                                             'users_with_unread' => $unreadusers); // List of unread users to update messages page.

            $cache->set($cachekey, $unreadconversationcache);
        }
        return $unreadconversationcache;
    }

    /**
     * Get conversations (from and to messages) for a user.
     * @param $userid int User id of person to get conversations for
     * @param null $otheruser If null get all conversations to first user, if specified only get conversations to and from this user.
     * @return array list of all messages to and form specified users, in order of time.
     */
    public static function get_user_conversations($userid, $otheruser = null) {
        // if useridfrom is null, then get all conversations from all users.
        global $DB;
        $params = array();
        $oneuserwherefrom = '';
        $oneuserwhereto = '';
        if ($otheruser !== null) {
            $oneuserwherefrom = 'AND m.useridfrom = :other_userid_1';
            $params['other_userid_1'] = $otheruser;

            $oneuserwhereto = 'AND m.useridto = :other_userid_2';
            $params['other_userid_2'] = $otheruser;
        }
        $userfields = user_picture::fields('uf', array('lastaccess'), 'userfromid', 'userfrom');
        $usertofields = user_picture::fields('utf', array('lastaccess'), 'usertoid', 'userto');

        /* This query gets all messages i've sent and all messages i've received in order of time
           Another function (group_user_conversations) is used to then group messages into conversations between a single user
        */
        $sql = "SELECT m.id AS messageid, m.*, $userfields, $usertofields
                FROM  (
                        (SELECT id, useridfrom, useridto, subject, smallmessage, notification, timecreated, timeuserfromdeleted, timeusertodeleted, 1 as unread
                                FROM {message} mx
                                WHERE notification = 0
                            )
                        UNION ALL
                        (SELECT id, useridfrom, useridto, subject, smallmessage, notification, timecreated, timeuserfromdeleted, timeusertodeleted, 0 as unread
                                FROM {message_read} mr
                                WHERE notification = 0
                        )
                    ) m
                JOIN {user} uf ON uf.id = m.useridfrom
                JOIN {user} utf ON utf.id = m.useridto
               WHERE m.notification = 0
                 AND ((m.useridto = :my_userid_1 $oneuserwherefrom AND m.timeusertodeleted = 0)
                      OR (m.useridfrom = :my_userid_2 $oneuserwhereto AND m.timeuserfromdeleted = 0)
                     )

            ORDER BY m.timecreated DESC -- messages ordered with newest at the top.
                ";

        $params['my_userid_1'] = $userid;
        $params['my_userid_2'] = $userid;
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Delete one or more messages/notifications given an array of messageids.
     * If the message deleted is sent to me, it ensures it is moved to the "message_read" table
     * @param int $userid user id of user who's messages to delete.
     * @param array [int] $messageids array  of message ids to delete
     *
     */
    public static function delete_messages_by_id($userid, $messageids) {
        global $DB;
        if (empty($messageids)) {
            return;
        }
        list($sqlinstmt, $params) = $DB->get_in_or_equal($messageids, SQL_PARAMS_NAMED);

        $sql = " SELECT * FROM (
                    (SELECT id, useridfrom, useridto, subject, fullmessage,fullmessageformat,fullmessagehtml, smallmessage, notification, contexturl, contexturlname,
                    timecreated, timeuserfromdeleted, timeusertodeleted, timeread, 0 AS unread
                         FROM {message_read}
                    )
                    UNION ALL
                    (SELECT id, useridfrom, useridto,  subject, fullmessage,fullmessageformat,fullmessagehtml, smallmessage, notification, contexturl, contexturlname,
                    timecreated, timeuserfromdeleted, timeusertodeleted, null AS timeread, 1 AS unread
                         FROM {message}
                    )
                ) m
                WHERE id $sqlinstmt
        ";
        $messages = $DB->get_recordset_sql($sql, $params);

        foreach ($messages as $message) {
            if (message_can_delete_message($message, $userid)) {
                // Mark messages deleted to and from current user.
                message_delete_message($message, $userid);
                if ($message->useridto == $userid && $message->unread == 1) {
                    // Mark my deleted messages as read. This ensures deleted messages are only in message_read table.
                    unset($message->unread);
                    unset($message->timeread);
                    $message->timeusertodeleted = time();
                    message_mark_message_read($message, time());
                }
            }
        }
        $messages->close();
        return true;
    }

    /**
     * Return a list of users that are in my contact list, or I have received a message from (but not sent a message to)
     * this is optionally filtered by the searchstring (searches on firstname / lastname)
     * Sorted by firstname because firstname is first in list.
     *
     * @param $userid user id of persons contacts to search
     * @param string $searchstring optionally filter by these characters.
     * @return array
     */
    public static function search_connected_and_messaged_users($userid, $searchstring = '') {
        global $DB;
        $userfields = user_picture::fields('u', array('lastaccess'));

        $searchsql = '';
        if ($searchstring != '') {
            $searchsql = " AND (u.firstname ilike :search1 OR u.lastname ilike :search2 OR u.firstname || ' ' || u.lastname ilike :search3) ";
            $params['search1'] = "%$searchstring%";
            $params['search2'] = "%$searchstring%";
            $params['search3'] = "%$searchstring%";
        }
        $params['userid'] = $userid;
        $params['userid2'] = $userid;
        $params['userid3'] = $userid;
        $sql = "SELECT DISTINCT $userfields , lower(u.firstname) as sortfirstname, lower(u.lastname) as sortlastname FROM (
                (
                    SELECT $userfields
                    FROM {message_contacts} mc
                    JOIN {message_contacts} mcreturn ON (mcreturn.contactid = mc.userid AND mcreturn.userid = mc.contactid)
                    JOIN {user} u ON mcreturn.userid = u.id
                   WHERE mc.userid = :userid
                     AND mc.blocked = 0
                     AND mcreturn.blocked = 0
                 ) -- all non blocked message contacts
                 UNION ALL
                 (
                    (SELECT $userfields
                                FROM {message} mx
                                JOIN {user} u ON u.id = mx.useridfrom
                                WHERE notification = 0
                                  AND mx.useridto = :userid2
                                  AND mx.timeusertodeleted = 0
                    )
                    UNION ALL
                    (SELECT $userfields
                                FROM {message_read} mr
                                JOIN {user} u ON u.id = mr.useridfrom
                               WHERE notification = 0 and mr.useridto = :userid3
                                 AND mr.timeusertodeleted = 0
                    )
                 ) -- anyone I have received a message from
                ) as u
                WHERE 1=1
                $searchsql
                ORDER BY sortfirstname, sortlastname -- Return search results by alphabetical sort for easy lookup.";

        // Join the contacts list to itself to ensure users are connected both ways.
        $connectedusers = $DB->get_records_sql($sql, $params);
        return $connectedusers;
    }

    /**
     * Format the contact list (usually returned by the search_connected_and_messaged_users function) so it is a
     * correct context for the mustache template for a contact list on all messages page.
     *
     * @param $userlist
     * @return mixed
     */
    public static function format_contact_list($userlist) {
        global $PAGE;

        foreach ($userlist as &$user) {
            $otheruserpicture = new user_picture($user);
            $otheruserpicture->size = 3; // Size f1.
            $user->otheruserpictureurl = $otheruserpicture->get_url($PAGE)->out(false);
            $otheruserprofileurl = new moodle_url('/user/view.php', array('id' => $user->id));
            $user->otheruserprofileurl = $otheruserprofileurl->out();
            $user->otheruserfullname = fullname($user);
        }
        return $userlist;
    }

    /**
     * Group a list of conversations i've sent and received, grouped by "other"
     * The contact who you have last received or sent a message to should be first in the contact list
     * While the most recent message in the message list should be last.
     *
     * @param $messagelist
     * @param $myuser moodle_user
     * @return array
     * @throws \coding_exception
     */
    public static function group_user_conversations($messagelist, $myuser) {
        global $PAGE;
        /* Messages to and from other user, grouped by other user */
        $groupedconversations = array();
        $strftimerecent = get_string('strftimedaydatetime');

        $myuserpicture = new user_picture($myuser);
        $myuserpicture->size = 1; // Size f1.

        $mypictureurl = $myuserpicture->get_url($PAGE)->out(false);
        $myprofileurl = new moodle_url('/user/view.php', array('id' => $myuser->id));
        $myprofileurl = $myprofileurl->out();

        foreach ($messagelist as $message) {
            if ($message->useridfrom == $myuser->id) {
                $message->fromme = true;
                $otheruserid = $message->useridto;
            } else {
                $message->fromme = false;
                $otheruserid = $message->useridfrom;
            }

            /* Create "Other" user picture link , once per "Other" user group */
            if (!isset($groupedconversations[$otheruserid]['otheruserpictureurl'])) {
                // Only load picture once per conversation
                $otheruser = new stdclass();

                if ($message->fromme) {
                    $otheruser->id = $message->useridto;
                    $otheruser = username_load_fields_from_object($otheruser, $message, 'userto',
                                                                  array('picture', 'imagealt', 'email'));
                } else {
                    $otheruser->id = $message->useridfrom;
                    $otheruser = username_load_fields_from_object($otheruser, $message, 'userfrom',
                                                                  array('picture', 'imagealt', 'email'));
                }
                $otheruserpicture = new user_picture($otheruser);
                $otheruserpicture->size = 1; // Size f1.
                $groupedconversations[$otheruserid]['otheruserpictureurl'] = $otheruserpicture->get_url($PAGE)->out(false);
                $otheruserprofileurl = new moodle_url('/user/view.php', array('id' => $otheruser->id));
                $groupedconversations[$otheruserid]['otheruserprofileurl'] = $otheruserprofileurl->out();
                $groupedconversations[$otheruserid]['otheruserfullname'] = fullname($otheruser);
                $groupedconversations[$otheruserid]['messages'] = array();
                $groupedconversations[$otheruserid]['lastmessagesnippet'] = $message->smallmessage; // Most recent (first) message is stored as snippet
            }

            $message->formatted_timecreated = userdate($message->timecreated, $strftimerecent);

            $groupedconversations[$otheruserid]['id'] = $otheruserid; // Get's updated each loop but doesnt matter.
            $groupedconversations[$otheruserid]['firstname'] = $message->userfromfirstname;
            $groupedconversations[$otheruserid]['lastname'] = $message->userfromlastname;

            if ($message->fromme) {
                $message->userpictureurl = $mypictureurl;
                $message->userprofileurl = $myprofileurl;
            } else {
                $message->userpictureurl = $groupedconversations[$otheruserid]['otheruserpictureurl'];
                $message->userprofileurl = $groupedconversations[$otheruserid]['otheruserprofileurl'];
            }
            if ($message->unread == 1 && !$message->fromme) {
                $groupedconversations[$otheruserid]['unreadmessages'] = true;
                isset($groupedconversations[$otheruserid]['unreadcount']) ? $groupedconversations[$otheruserid]['unreadcount']++ : $groupedconversations[$otheruserid]['unreadcount'] = 1;
                $message->unread = 1; // force to boolean for mustache.
            } else {
                $message->unread = 0; //
            }
            array_unshift($groupedconversations[$otheruserid]['messages'], $message); // Add message in reverse order.
        }
        return $groupedconversations;
    }

    /**
     * Delete messages in the conversation that seen by user.
     * Exclude other messages may have arrived but not yet seen..
     *
     * @param $userid int Current user.
     * @param $otheruserid int The other user id in the conversation.
     * @return array
     */
    public static function delete_conversation($userid, $otheruserid) {
        global $DB;

        $params = array('my_userid_1' => $userid, 'other_userid_1' => $otheruserid, 'other_userid_2' => $otheruserid,
                        'my_userid_2' => $userid, 'my_userid_3' => $userid, 'other_userid_3' => $otheruserid);

        // Get all "read" messages $userid has sent and received for deletion
        // AND Get "unread" messages $userid has sent for deletion (but NOT unread messages $userid has received)
        $sql = " SELECT * FROM (
                    (SELECT id, useridfrom, useridto, timeuserfromdeleted, timeusertodeleted, timeread, 0 AS unread
                         FROM {message_read}
                        WHERE notification = 0
                          AND ((useridto = :my_userid_1 AND useridfrom = :other_userid_1)
                           OR (useridto = :other_userid_2 AND useridfrom = :my_userid_2))
                    )
                    UNION ALL
                    (SELECT id, useridfrom, useridto, timeuserfromdeleted, timeusertodeleted, null AS timeread, 1 AS unread
                         FROM {message}
                        WHERE notification = 0
                          AND (useridfrom = :my_userid_3
                          AND useridto = :other_userid_3)
                    )
                ) m
        ";

        $messages = $DB->get_recordset_sql($sql, $params);

        foreach ($messages as $message) {
            if (message_can_delete_message($message, $userid)) {
                // Mark messages deleted to and from current user.
                message_delete_message($message, $userid);
            }
        }
        $messages->close();
        return true;
    }

    /**
     * Returns unread notifications and count of unread notifications, using the cache.
     * @param $user User object containing user->id
     * @return array
     */
    public static function get_cached_unread_notification_preview($user) {
        $cache = cache::make('local_conversations', 'unreadmessages');
        $cachekey = $user->id . '_unreadnotificationspreview';
        $unreadnotificationcache = $cache->get($cachekey);

        if (false === $unreadnotificationcache) {
            $unreadnotifications = static::get_user_notifications($user->id, false);
            $totalcount = count($unreadnotifications);

            $notificationalerts = array_values($unreadnotifications); // Remove keys for mustache template.
            $first5unreadnotifications = array_slice($notificationalerts, 0,
                                                     self::NOTIFICATIONS_PREVIEW_NUMBER); // Most recent 5 notifications. arbitrary requirement.
            $first5unreadnotifications = static::format_notifications_for_template($first5unreadnotifications);
            $allnotificationspage = new moodle_url('/local/conversations/notifications.php');
            $unreadnotificationcache = array('unread_notification_count' => (int)$totalcount,
                                             // Total unread notifications count for header update
                                             'all_notifications_link' => $allnotificationspage->out(false),
                                             'unread_notification_preview' => $first5unreadnotifications,
                                             // Notifications preview update for header.
            );

            $cache->set($cachekey, $unreadnotificationcache);
        }

        return $unreadnotificationcache;
    }

    public static function format_notifications_for_template($notifications) {
        global $PAGE;
        foreach ($notifications as &$notification) {
            $notification->iconclass = 'fa-bell-o';

            // Update userdate date for notification
            $notification->timecreated_formatted = userdate($notification->timecreated,
                                                            get_string('notificationdateformat', 'local_conversations'));
            if (strpos($notification->fullmessagehtml, 'connect_request') !== false) {
                // Exclude Connection requests from dismiss all.
                $notification->isconnectionrequest = 1;
                $notification->simplenotification = 1;
                $notification->iconclass = 'fa-user-plus';
            }

            if (strpos($notification->fullmessagehtml, 'connect_accept') !== false) {
                $notification->simplenotification = 1;
                $notification->iconclass = 'fa-users';
            }
            if (null !== $notification->realuseridfrom) {
                $userfrom = new stdClass();
                $userfrom->id = $notification->useridfrom;
                $userfrom = username_load_fields_from_object($userfrom, $notification, null, array('picture', 'imagealt', 'email'));

                $userpicture = new user_picture($userfrom);
                $userpicture->size = 3; // Size f1.
                $notification->userpictureurl = $userpicture->get_url($PAGE)->out(false);
                $userprofileurl = new moodle_url('/user/view.php', array('id' => $notification->useridfrom));
                $notification->userprofileurl = $userprofileurl->out(false);
                $notification->userfullname = fullname($notification);
            }
            $notification->unread = (bool)$notification->unread; // force to boolean for mustache.
        }
        return array_values($notifications);
    }

    /**
     * Retrieve user messages marked as notifications and not deleted.
     * Optionally retrieve only unread or both read/unread
     *
     * @param $userid
     * @param bool $includereadnotifications Exclude/Include read notifications.
     * @return array
     */
    public static function get_user_notifications($userid, $includereadnotifications = true) {
        global $DB;
        $userfields = user_picture::fields('u', null, 'realuseridfrom');
        $notificationsql = "SELECT m.id, m.id as notificationid, useridfrom, useridto, subject, fullmessage,
                                                      fullmessageformat, fullmessagehtml, smallmessage, notification, m.timecreated, null as timeread, 1 as unread, $userfields
                                                  FROM {message} m
                                             LEFT JOIN {user} u on u.id = m.useridfrom
                                                 WHERE m.notification = 1
                                                   AND m.useridto = ?
                                                   AND m.timeusertodeleted = 0";
        $params = array($userid);
        if ($includereadnotifications === true) {
            $notificationsql .= "
                                             UNION ALL
                                              SELECT mr.id, mr.id as notificationid, useridfrom, useridto, subject, fullmessage,
                                                      fullmessageformat, fullmessagehtml, smallmessage, notification, mr.timecreated, timeread, 0 as unread, $userfields
                                                  FROM {message_read} mr
                                             LEFT JOIN {user} u on u.id = mr.useridfrom
                                                 WHERE mr.notification = 1
                                                   AND mr.useridto = ?
                                                   AND mr.timeusertodeleted = 0";
            $params[] = $userid;
        }
        $notifications = $DB->get_records_sql("$notificationsql
                                              ORDER BY timecreated DESC, notificationid DESC", $params);
        return $notifications;
    }
}
