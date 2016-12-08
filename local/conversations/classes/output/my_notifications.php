<?php
namespace local_conversations\output;
defined('MOODLE_INTERNAL') || die;
use stdClass;
use local_conversations\api;
class my_notifications implements \renderable, \templatable {

    public $mynotifications = array();

    public function __construct() {
        global $USER;
        $this->mynotifications = api::get_user_notifications($USER->id);
        return;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $data->mynotifications = array_values(api::format_notifications_for_template($this->mynotifications));
        $data->hasnotifications = count($this->mynotifications) > 0;
        return $data;
    }
}
