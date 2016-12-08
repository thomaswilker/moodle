<?php
namespace block_oua_social_activity\output;
use stdClass;
use user_picture;
use moodle_url;
defined('MOODLE_INTERNAL') || die;

class social_events_list implements \renderable, \templatable {
    private $connection_events;
    private $mycontactlist;
    public function __construct($userid, $daysback, $numberofevents, $lasteventid = null) {
        $this->connection_events = $this->get_connection_events_for_user($userid, $daysback, $numberofevents);
        $this->mycontactlist = $this->users_connected_to($userid);
    }
    protected function get_connection_events_for_user($userid, $daysback = 20, $limit = 20) {
        global $DB;
        $events = $DB->get_records_sql("SELECT DISTINCT event.id, event.eventname, event.component, event.action, event.target, event.objecttable, event.objectid,
                                                       event.crud, event.edulevel, event.contextid, event.contextlevel, event.contextinstanceid, event.userid,
                                                       event.courseid, event.relateduserid, event.timecreated
                                               FROM {message_contacts} mc
                                               JOIN {message_contacts} mcreturn ON (mcreturn.contactid = mc.userid AND mcreturn.userid = mc.contactid) -- only events of people who are connected both ways (DISTINCT filters out the second event)
                                               JOIN {oua_social_activity_events} connecttime ON (mc.userid = connecttime.userid AND mc.contactid = connecttime.relateduserid) OR (mc.userid = connecttime.relateduserid AND mc.contactid = connecttime.userid ) -- Gets the connected time of my contact
                                               JOIN {oua_social_activity_events} event ON event.eventname = '\\block_oua_connections\\event\\contact_connected' AND (event.userid = mc.contactid  OR event.relateduserid = mc.contactid ) AND event.timecreated > connecttime.timecreated -- events from people in contact list, only get events for people after i've connected with them
                                              WHERE mc.userid = :userid1
                                                AND event.relateduserid <> :userid2 AND event.userid <> :userid3 -- exclude events that are about myself
                                                AND mc.blocked = 0 AND mcreturn.blocked = 0 -- exclude blocked users
                                                AND event.timecreated >= extract(EPOCH FROM (now() - (interval '1 days') * :daysback) )
                                              ORDER BY timecreated DESC", array('userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid, 'daysback' => $daysback), 0, $limit);

        return $events;
    }

    /**
     * Get the users connected (i.e. users that are in my contact list, and I am in their contact list).
     * @param $userid userid to retrieve users connected to.
     *
     * @return array list of users with user profile details of users connected to userid
     */
    protected function users_connected_to($userid) {
        global $DB;
        $userfields = user_picture::fields('u', array('lastaccess'));

        // Join the contacts list to itself to ensure users are connected both ways.
        $connectedusers = $DB->get_records_sql("SELECT $userfields
                                                        FROM {message_contacts} mc
                                                        JOIN {message_contacts} mcreturn ON (mcreturn.contactid = mc.userid AND mcreturn.userid = mc.contactid)
                                                        JOIN {user} u ON mcreturn.userid = u.id
                                                       WHERE mc.userid = ?
                                                         AND mc.blocked = 0
                                                         AND mcreturn.blocked = 0
                                                         AND u.deleted = 0", array($userid));
        return $connectedusers;
    }

    /**
     * Get all user detail fields (used for fullname and profile pictures)
     * @param array $userids
     *
     * @return array|void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_user_details($userids = array()) {
        global $DB;
        if(empty($userids)) {
            return;
        }
        $userfields = user_picture::fields('u', array('lastaccess'));
        list($insql, $inparams) = $DB->get_in_or_equal($userids);
        $userdetails = $DB->get_records_sql("SELECT $userfields
                                                  FROM {user} u
                                                 WHERE u.id $insql", $inparams);
        return $userdetails;
    }
    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $fulluserlist = array();
        // This should only be 20 at a time, so looping twice should be ok.
        foreach ($this->connection_events as &$event) { // Create a list of userids
            $fulluserlist[$event->userid] = $event->userid;
            $fulluserlist[$event->relateduserid] = $event->relateduserid;
        }
        $fulluserlistdetails = $this->get_user_details($fulluserlist);

        foreach ($this->connection_events as &$event) {
            $connectiondetails = array();

            $u1iscontact = array_key_exists($event->userid, $this->mycontactlist);
            if ($u1iscontact) {
                $name1 = 'u1';
                $name2 = 'u2';
            } else {
                $name1 = 'u2';
                $name2 = 'u1';
            }
            $connectiondetails[$name1 . 'id'] = $event->userid;
            $connectiondetails[$name1 . 'fullname'] = fullname($fulluserlistdetails[$event->userid]);
            $profileurl = new moodle_url('/user/profile.php', array('id' =>  $event->userid));
            $connectiondetails[$name1 . 'profileurl'] = $profileurl->out(true);

            $u2iscontact = array_key_exists($event->relateduserid, $this->mycontactlist);
            $connectiondetails[$name2 . 'id'] = $event->relateduserid;
            $connectiondetails[$name2 . 'fullname'] = fullname($fulluserlistdetails[$event->relateduserid]);
            $profileurl = new moodle_url('/user/profile.php', array('id' =>  $event->relateduserid));
            $connectiondetails[$name2 . 'profileurl'] = $profileurl->out(true);
            if ($u1iscontact && $u2iscontact) {
                $event->message = get_string('bothcontactsconnected', 'block_oua_social_activity', $connectiondetails);
            } else {
                $event->message = get_string('contactconnected', 'block_oua_social_activity', $connectiondetails);
            }
            $event->event_date_formatted = userdate($event->timecreated, get_string('eventdateformat', 'block_oua_social_activity'));
        }
        $data->social_events = array_values($this->connection_events);
        return $data;
    }
}