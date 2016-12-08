<?php
namespace local_conversations\output;
defined('MOODLE_INTERNAL') || die;
use stdClass;
use local_conversations\api;
class my_messages implements \renderable, \templatable {

    public $mycontactswithmessages = array();

    public function __construct() {
        global $USER;
        $allmessage = api::get_user_conversations($USER->id);
        $this->mycontactswithmessages = api::group_user_conversations($allmessage, $USER);

        return;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $data->mycontactswithmessages = array_values($this->mycontactswithmessages);
        $data->hascontacts = count($this->mycontactswithmessages) > 0;
        return $data;
    }
}
