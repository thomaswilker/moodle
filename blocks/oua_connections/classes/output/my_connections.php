<?php
namespace block_oua_connections\output;
defined('MOODLE_INTERNAL') || die;
use stdClass;
use moodle_url;

class my_connections implements \renderable, \templatable {
    private $connectionsmanager;
    public $myconnections;

    public function __construct($displaycount = 0, $sortorder = '') {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $connectionsmanager = new \block_oua_connections\manage($CFG, $DB, $OUTPUT, $PAGE, $USER);

        $this->connectionsmanager = $connectionsmanager;
        $users = $this->connectionsmanager->connected_users($USER->id, $displaycount, $sortorder);
        $data = array();

        foreach ($users as $user) {
            $userdata = new stdClass();

            if (isguestuser($user)) {
                continue;
            }

            $userdata->userpicturehtml = $OUTPUT->user_picture($user, array('link' => true, 'size' => 60, 'alttext' => false));
            $userdata->profilelink = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $PAGE->course->id));
            $userdata->fullname = fullname($user);
            $userdata->userid = $user->id;
            $userdata->messagelink = new moodle_url('/message/index.php#id_message', array('user2' => $user->id));
            $data[] = $userdata;
        }
        $this->myconnections = $data;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $data->myconnections = $this->myconnections;
        $data->hasconnections = count($this->myconnections);
        $data->viewconnectionslink = new moodle_url('/blocks/oua_connections/all_connections.php');
        $data->dashboardlink = new moodle_url('/my/');
        return $data;
    }
}