<?php
namespace block_message_broadcast\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;
use ArrayIterator;
use moodle_url;
use context_course;

class manage_messages_page implements renderable, templatable {
    private $messages;
    private $urlparams;

    public function __construct($courseid = null) {
        $urlparams = array();
        $coursecontextid = null;
        if ($courseid != null) {
            $urlparams['courseid'] = $courseid;
            $coursecontext = context_course::instance($courseid);
            $coursecontextid = $coursecontext->id;
        }
        $this->newmessageurl = new moodle_url("/blocks/message_broadcast/newmessage.php", $urlparams);
        $this->urlparams = $urlparams;
        $mangemessages = new \block_message_broadcast\manage();
        $this->messages = $mangemessages->get_messages($coursecontextid);
    }

    public function export_for_template(renderer_base $output) {
        $data = new StdClass();
        $data->newmessageurl = $this->newmessageurl;

        $data->messagelist = new ArrayIterator($this->messages);
        foreach ($data->messagelist as $key => &$val) {
            $this->urlparams['id'] = $val->id;
            $val->messagestartdate = userdate($val->startdate, get_string('messagedateformat', 'block_message_broadcast'));
            if ($val->enddate == 0) {
                $val->messageenddate = get_string('messagedatewhennodate', 'block_message_broadcast');
            } else {
                $val->messageenddate = userdate($val->enddate, get_string('messagedateformat', 'block_message_broadcast'));
            }
            $val->editmessageurl = new moodle_url("/blocks/message_broadcast/editmessage.php", $this->urlparams);
            $val->deletemessageurl = new moodle_url("/blocks/message_broadcast/deletemessage.php", $this->urlparams);
        }

        return $data;
    }
}