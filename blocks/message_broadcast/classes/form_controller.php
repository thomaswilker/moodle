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
namespace block_message_broadcast;
class form_controller {
    public $admin = false;
    public $multicourse = array();
    public $courseid;
    public $pageheading = '';
    public $urlparams = array();
    private $edit = false;
    private $redirectpage = '';

    /**
     * @param bool $edit Is this an edit or new form
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     */
    public function __construct($edit = false) {
        global $PAGE, $DB;
        $this->edit = $edit;
        $PAGE->set_pagelayout('admin');
        if (has_capability('block/message_broadcast:send_broadcast_message', \context_system::instance())) {
            // admin or has system level broadcast capability
            $this->admin = true;
            $PAGE->set_context(\context_system::instance());
            $this->multicourse = $this->get_course_list();
            $this->courseid = optional_param('courseid', null, PARAM_INT);
            $this->pageheading = get_string('newmessage', 'block_message_broadcast');
        } else {
            $this->courseid = required_param('courseid', PARAM_INT);
            $coursecontext = \context_course::instance($this->courseid);
            $PAGE->set_context($coursecontext);
            require_capability('block/message_broadcast:send_broadcast_message', $coursecontext);
            $coursetomessage = $DB->get_record('course', array('id' => $this->courseid));
            $this->pageheading = get_string('newmessageforcourse', 'block_message_broadcast', $coursetomessage->shortname);
        }
        if ($this->courseid !== null) {
            $this->urlparams['courseid'] = $this->courseid;
        }

        if ($this->edit) {
            $this->redirectpage = 'editmessage.php';
        } else {
            $this->redirectpage = 'newmessage.php';
        }
    }

    public function get_course_list() {
        global $DB;
        // This is bad because we database hit for no reason when we are submitting the form.
        $allcourses = $DB->get_records_sql("SELECT  c.id AS courseid, cc.name AS category_name, c.shortname,c.fullname
                                              FROM {course} c
                                              JOIN {course_categories} cc on c.category = cc.id
                                             WHERE c.id <> 0
                                          ORDER BY cc.sortorder, c.sortorder");
        $multicourse = array('' => array(0 => 'System Wide'));

        foreach ($allcourses as $courseid => $course) {
            $multicourse[$course->category_name][$course->courseid] = $course->shortname;
        }

        return $multicourse;
    }

    public function get_redirecturl() {
        return new \moodle_url('/blocks/message_broadcast/' . $this->redirectpage, $this->urlparams);
    }

    public function get_managemessageurl() {
        return new \moodle_url('/blocks/message_broadcast/managemessages.php', $this->urlparams);
    }

    public function get_formcustomdata() {
        global $DB;
        $formcustomdata = array('courseid' => $this->courseid, 'multi_course' => $this->multicourse);
        $formcustomdata['courseids'] = array(0 => 0);
        $formcustomdata['headingtitle'] = '';
        $formcustomdata['messagebody'] = '';
        $formcustomdata['startdate'] = time();
        $formcustomdata['enddate'] = 0;

        if ($this->edit) {
            $messageid = required_param('id', PARAM_INT);
            $message = $DB->get_record('message_broadcast', array('id' => $messageid), '*', MUST_EXIST);

            $coursesql = "SELECT mbcx.id, c.id as courseid
                            FROM {message_broadcast_context} mbcx
                            JOIN {message_broadcast} m ON (m.id = mbcx.messagebroadcastid)
                            JOIN {context} cx ON (mbcx.contextid = cx.id AND cx.contextlevel = 50)
                            JOIN {course} c ON (cx.instanceid = c.id)
                           WHERE mbcx.messagebroadcastid = ?";
            $formcustomdata['courseids'] = $DB->get_records_sql_menu($coursesql, array($messageid));

            if (isset($message->startdate)) {
                $startdate = $message->startdate;
            } else {
                $startdate = $formcustomdata['startdate'];
            }

            if (isset($message->enddate)) {
                $enddate = $message->enddate;
            } else {
                $enddate = $formcustomdata['enddate'];
            }

            $formcustomdata = array_merge($formcustomdata, array(
                'id'    => $messageid,
                'width' => $message->width,
                'headingtitle' => $message->headingtitle,
                'messagebody'  => $message->messagebody,
                'startdate' => $startdate,
                'enddate' => $enddate
            ));
        }

        return $formcustomdata;
    }
}