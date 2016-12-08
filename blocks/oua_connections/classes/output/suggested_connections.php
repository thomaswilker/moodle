<?php
namespace block_oua_connections\output;
defined('MOODLE_INTERNAL') || die;
use stdClass;
use moodle_url;

class suggested_connections implements \renderable, \templatable {
    private $connectionsmanager;
    public $suggestedconnections;

    public function __construct() {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $connectionsmanager = new \block_oua_connections\manage($CFG, $DB, $OUTPUT, $PAGE, $USER);

        $this->connectionsmanager = $connectionsmanager;
        $users = $this->connectionsmanager->suggested_users();
        $data = array();

        foreach ($users as $user) {
            $userdata = new stdClass();

            if (isguestuser($user)) {
                continue;
            }

            $userdata->userpicturehtml = $OUTPUT->user_picture($user, array('link' => false, 'size' => 60, 'alttext' => false));
            $userdata->profilelink = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $PAGE->course->id));
            $userdata->fullname = fullname($user);
            $userdata->userid = $user->id;
            $data[] = $userdata;
        }
        $this->suggestedconnections = $data;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $data->suggestedconnections = $this->suggestedconnections;

        return $data;
    }
}