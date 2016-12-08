<?php
namespace block_oua_course_progress_bar\output;
defined('MOODLE_INTERNAL') || die;

class renderable implements \renderable, \templatable {
    private $progress;
    private $startdate;
    private $enddate;

    public function __construct($courseid, $progress = null, $startdate = null, $enddate = null) {
        global $USER;

        $course = get_course($courseid);

        $completion = new \local_oua_completion\oua_completion_info($course);
        $percent = round($completion->get_user_progress($USER->id));
        $courseformatoptions = course_get_format($courseid)->get_format_options();


        if (isset($course->startdate)) {
            $this->startdate = userdate($course->startdate, '%b %e');
        }

        if (isset($courseformatoptions['courseenddate'])) {
            $this->enddate = userdate($courseformatoptions['courseenddate'], '%b %e');
        }

        $this->progress = $percent;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new \StdClass();
        $data->progress = $this->progress;
        $data->startdate = $this->startdate;
        $data->enddate = $this->enddate;

        return $data;
    }
}