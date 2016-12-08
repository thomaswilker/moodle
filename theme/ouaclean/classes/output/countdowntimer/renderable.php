<?php
namespace theme_ouaclean\output\countdowntimer;

class renderable implements \renderable, \templatable {
    private $countdowntodate;

    public function __construct($countdowntodate, $end = false) {
        $countdowntodate = userdate($countdowntodate, '%Y/%m/%d %T');
        $this->countdowntodate = $countdowntodate;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new \StdClass();
        $data->countdowntodate = $this->countdowntodate;
        return $data;
    }
}