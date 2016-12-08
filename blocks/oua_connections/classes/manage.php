<?php
namespace block_oua_connections;
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
use user_picture;
use stdClass;
use core_user;
use context_course;
use core\message\message as core_message;
use moodle_url;
use context_user;

class manage {
    protected $onlinecontacts = null;
    protected $offlinecontacts = null;
    protected $blockedcontacts = null;

    protected $cfg;
    /** @var \moodle_database */
    protected $db;
    protected $output;
    protected $page;
    protected $user;

    public function __construct($cfg, $db, $output, $page, $user) {
        $this->cfg = $cfg;
        $this->db = $db;
        $this->output = $output;
        $this->page = $page;
        $this->user = $user;
    }

    protected function collect_contacts() {
        // Strangers are a list of users who have sent me a message.  Let's put those first
        // This is not strictly supported by the original Higher Education example,
        // however it is an option if another customer allows users to message me that
        // aren't on my contact list.
        list($onlinecontacts, $offlinecontacts, $strangers) = message_get_contacts();

        // Ensure contacts are keyed by the userid.
        $onlinecontactskeyed = array();
        foreach ($onlinecontacts as $user) {
            $onlinecontactskeyed[$user->id] = $user;
        }
        $this->onlinecontacts = $onlinecontactskeyed;

        $offlinecontactskeyed = array();
        foreach ($offlinecontacts as $user) {
            $offlinecontactskeyed[$user->id] = $user;
        }
        $this->offlinecontacts = $offlinecontactskeyed;

        // Find blocked users and ensure they are formatted in an easy search mechanism.
        $blockedusersmaster = message_get_blocked_users();
        // Also block users who have blocked me.
        $userswhohaveblockedme = $this->get_users_who_have_blocked_me($this->user->id);
        $blockedusersmaster = array_merge($blockedusersmaster, $userswhohaveblockedme);
        $blockedcontacts = array();
        foreach ($blockedusersmaster as $blockeduser) {
            $blockedcontacts[$blockeduser->id] = $blockeduser;
        }
        $this->blockedcontacts = $blockedcontacts;
    }
    protected function get_users_who_have_blocked_me($userid) {
        $userfields = user_picture::fields('u', array('lastaccess'));

        // Get a list of users who have me blocked.
        $userswhohaveblockedme = $this->db->get_records_sql("SELECT $userfields
                                                               FROM {message_contacts} mc
                                                               JOIN {user} u ON mc.userid = u.id
                                                              WHERE mc.contactid = ?
                                                                AND mc.blocked = 1", array($userid));
        return $userswhohaveblockedme;

    }
    protected function add_common_enrolments_to_suggested(&$suggestedcontacts, $maximumusers) {
        $courselist = enrol_get_my_courses();
        unset($courselist[$this->page->course->id]);
        $courseids = array_keys($courselist);
        // Put the current course at the top, we removed it above to ensure we force it to the top.
        array_unshift($courseids, $this->page->course->id);

        unset($courselist);

        foreach ($courseids as $courseid) {
            $context = context_course::instance($courseid);

            // The fields selected are the same as used in message_get_contacts().
            // TODO: Write test to verify this is true.
            // TODO: Update capability for block:oua_connections/can_connect.
            $classmates = get_enrolled_users($context, '', 0, user_picture::fields('u', array('lastaccess')),
                'lastaccess DESC', 0, max($maximumusers * 4, 100), true);

            $this->add_to_suggested_contacts($suggestedcontacts, $classmates);

            if (count($suggestedcontacts) >= $maximumusers) {
                // The parent function handles what part of the array to send, we just stop if we know we have more.
                // that we were asked to have by the end.
                return;
            }
        }
    }

    protected function add_friends_of_friends_suggested(&$suggestedcontacts, $maximumusers) {
        $allfriendofflinecontacts = array();

        // Connections of my connections, attempt to find all the online people first, then add the offline
        // people as second options.
        foreach ($this->onlinecontacts as $contact) {
            list($friendonlinecontacts, $friendofflinecontacts, $strangers) = message_get_contacts($contact);

            $allfriendofflinecontacts += $friendofflinecontacts;

            // Filter online contacts to remove ones that I'm also a contact with.
            $this->add_to_suggested_contacts($suggestedcontacts, $allfriendofflinecontacts);

            if (count($suggestedcontacts) >= $maximumusers) {
                // The parent function handles what part of the array to send, we just stop if we know we have more
                // that we were asked to have by the end.
                return;
            }
        }

        foreach ($this->offlinecontacts as $contact) {
            list($friendonlinecontacts, $friendofflinecontacts, $strangers) = message_get_contacts($contact);

            $allfriendofflinecontacts += $friendofflinecontacts;

            // Filter online contacts to remove ones that I'm also a contact with.
            $this->add_to_suggested_contacts($suggestedcontacts, $allfriendofflinecontacts);

            if (count($suggestedcontacts) >= $maximumusers) {
                // The parent function handles what part of the array to send, we just stop if we know we have more
                // that we were asked to have by the end.
                return;
            }
        }

        // After exhausting the online friends of friends, add each of the offline contacts from those friends.
        $this->add_to_suggested_contacts($suggestedcontacts, $allfriendofflinecontacts);
        if (count($suggestedcontacts) >= $maximumusers) {
            // The parent function handles what part of the array to send, we just stop if we know we have more
            // that we were asked to have by the end.
            return;
        }
    }

    /**
     * @param $suggestedcontacts array The list of current connections.
     * @param $possiblecontacts array The list of possible new connections to add.
     */
    protected function add_to_suggested_contacts(&$suggestedcontacts, $possiblecontacts) {
        foreach ($possiblecontacts as $id => $user) {
            if (isset($this->onlinecontacts[$id]) || isset($this->offlinecontacts[$id]) || isset($suggestedcontacts[$id])
                    || isset($this->blockedcontacts[$id]) || $id == $this->user->id) {
                continue;
            }
            $suggestedcontacts[$id] = $user;
        }
    }

    /**
     * Return users connected to me
     * i.e. users in my contact list, that also have me in their contact list.
     *
     * @param $userid
     * @param int $displaycount
     * @param string $sortorder
     * @return mixed
     */
    public function connected_users($userid, $displaycount = 0, $sortorder = '') {
        $userfields = user_picture::fields('u', array('lastaccess'));
        switch ($sortorder) {
            case 'lastname':
                $sortorder = 'ORDER BY u.lastname';
                break;
            case 'firstname':
                $sortorder = 'ORDER BY u.firstname';
                break;
            case 'lastactive':
            default:
                $sortorder = 'ORDER BY u.lastaccess DESC, u.lastname';
                break;
        }
        // Join the contacts list to itself to ensure users are connected both ways.
        $connectedusers = $this->db->get_records_sql("SELECT $userfields
                                                        FROM {message_contacts} mc
                                                        JOIN {message_contacts} mcreturn ON (mcreturn.contactid = mc.userid AND mcreturn.userid = mc.contactid)
                                                        JOIN {user} u ON mcreturn.userid = u.id
                                                       WHERE mc.userid = ?
                                                         AND mc.blocked = 0
                                                         AND mcreturn.blocked = 0
                                                         $sortorder", array($userid), 0, $displaycount);
        return $connectedusers;
    }
    /**
     * Returns all users who are connected (both in each others contact lists.)
     *
     * @param $userid
     * @param int $displaycount
     * @param string $sortorder
     * @return mixed
     */
    public function get_all_connected_users() {
        // Get a list of users and their contacts, with the users context.
        $connectedusers = $this->db->get_recordset_sql("SELECT mc.userid, mc.contactid, c.id as usercontextid
                                                          FROM {message_contacts} mc
                                                          JOIN {context} c ON c.contextlevel = ? AND c.instanceid = mc.userid
                                                         WHERE mc.blocked = 0
                                                      ORDER BY mc.userid, mc.contactid", array(CONTEXT_USER));
        return $connectedusers;
    }
    public function suggested_users() {
        $maximumusers = 4;

        $this->collect_contacts();

        $suggestedcontacts = array();

        $this->add_common_enrolments_to_suggested($suggestedcontacts, $maximumusers);
        if (count($suggestedcontacts) >= $maximumusers) {
            return array_splice($suggestedcontacts, 0, $maximumusers);
        }

        $this->add_friends_of_friends_suggested($suggestedcontacts, $maximumusers);
        if (count($suggestedcontacts) >= $maximumusers) {
            return array_splice($suggestedcontacts, 0, $maximumusers);
        }

        // Find some random users on the platform.  We use those who have most recently accessed the platform to increase
        // the social interation.
        $userstoexclude = array_keys($suggestedcontacts) + array_keys($this->onlinecontacts) + array_keys($this->offlinecontacts);
        $recentusers = get_users(true, '', true, $userstoexclude, 'lastaccess DESC', '', '', 1, $maximumusers,
            user_picture::fields('', array('lastaccess')));
        $this->add_to_suggested_contacts($suggestedcontacts, $recentusers);

        return array_splice($suggestedcontacts, 0, $maximumusers);
    }

    public function send_connection_request($userid) {
        if (($userto = core_user::get_user($userid)) === false) { // Invalid userid.
            return false;
        }

        $messagebodydata = new stdClass;
        $messagebodydata->userfromid = $this->user->id;
        $profileurl = new moodle_url('/user/profile.php', array('id' => $this->user->id));
        $messagebodydata->userfromprofileurl = $profileurl->out(true);
        $messagebodydata->accepturl = '#';
        $messagebodydata->ignoreurl = '#';
        $messagebodydata->studentfrom = fullname($this->user);

        $subject = get_string('connect_request_subject', 'block_oua_connections');
        $plainbody = get_string('connect_request_body', 'block_oua_connections', $messagebodydata);
        $htmlbody = get_string('connect_request_body_html', 'block_oua_connections', $messagebodydata);
        $smallbody = get_string('connect_request_body_small', 'block_oua_connections', $messagebodydata);

        // Build data to create a message with notification tag.
        $connectionrequestmessage = new core_message();
        $connectionrequestmessage->component = 'block_oua_connections';
        $connectionrequestmessage->name = 'requestconnection';
        $connectionrequestmessage->userfrom = $this->user;
        $connectionrequestmessage->userto = $userto;
        $connectionrequestmessage->subject = $subject;
        $connectionrequestmessage->fullmessagehtml = $htmlbody;
        $connectionrequestmessage->fullmessageformat = FORMAT_HTML;
        $connectionrequestmessage->fullmessage = $plainbody;
        $connectionrequestmessage->smallmessage = $smallbody; // Only html.
        $connectionrequestmessage->notification = 1;

        $msg = message_send($connectionrequestmessage);

        return $msg !== false; // Return true if value.

    }
    public function delete_notification($userid, $notificationid) {
        if (is_callable('\local_conversations\api::delete_messages_by_id')) {
            \local_conversations\api::delete_messages_by_id($userid, array($notificationid));
        }
    }

    /**
     * Removes user from other users contact list as long as they are not already blocked.
     *
     * @param $meid my userid
     * @param $themid their userid
     */
    public function remove_me_from_users_contact_list($meid, $themid) {
        $params = array('meid' => $meid, 'themid' => $themid);
        if (($contact = $this->db->get_record('message_contacts', array('userid' => $themid, 'contactid' => $meid))) !== false) {
            $this->db->delete_records_select('message_contacts', 'userid = :themid AND contactid = :meid AND blocked = 0', $params);
            // Trigger event for removing a contact.
            $event = \core\event\message_contact_removed::create(array('objectid'      => $themid,
                                                                       'userid'        => $themid,
                                                                       'relateduserid' => $meid,
                                                                       'context'       => context_user::instance($themid)));
            $event->add_record_snapshot('message_contacts', $contact);
            $event->trigger();
        }
    }
    public function send_connected_notification($userid1, $userid2) {
        // Trigger event for connected users
        $event = \block_oua_connections\event\contact_connected::create(array(
                                                                            'objectid' => $userid1,
                                                                            'userid' => $userid1,
                                                                            'relateduserid' => $userid2,
                                                                            'context'  => \context_user::instance($userid1)
                                                                        ));
        $event->trigger();

        $user1 = core_user::get_user($userid1);
        $user2 = core_user::get_user($userid2);

        $this->send_connected_notification_message($user1, $user2);
        $this->send_connected_notification_message($user2, $user1);

    }
    protected function send_connected_notification_message($userfrom, $userto) {
        $messagebodydata = new stdClass;
        $messagebodydata->userfrom = fullname($userfrom);
        $messagebodydata->userto = fullname($userto);

        $subject = get_string('connect_accept_subject', 'block_oua_connections', $messagebodydata);
        $plainbody = get_string('connect_accept_body', 'block_oua_connections', $messagebodydata);
        $htmlbody = get_string('connect_accept_body_html', 'block_oua_connections', $messagebodydata);
        $smallbody = get_string('connect_accept_body_small', 'block_oua_connections', $messagebodydata);

        // Build data to create a message with notification tag.
        $connectionrequestmessage = new \core\message\message();
        $connectionrequestmessage->component = 'block_oua_connections';
        $connectionrequestmessage->name = 'acceptrequest';
        $connectionrequestmessage->userfrom = $userfrom;
        $connectionrequestmessage->userto = $userto;
        $connectionrequestmessage->subject = $subject;
        $connectionrequestmessage->fullmessagehtml = $htmlbody;
        $connectionrequestmessage->fullmessageformat = FORMAT_HTML;
        $connectionrequestmessage->fullmessage = $plainbody;
        $connectionrequestmessage->smallmessage = $smallbody;
        $connectionrequestmessage->notification = 1;

        $msgsent = message_send($connectionrequestmessage);
        return $msgsent;
    }
}
