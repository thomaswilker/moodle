<?php
namespace theme_ouaclean\output\awesomebar;

class renderable implements \renderable, \templatable {
    private $stateclasses;
    private $box1header;
    private $box1headershort;
    private $box1content;
    private $box2header;
    private $box2headershort;
    private $box2content;
    private $box3headershort;
    private $box3header;
    private $box3content;
    private $extracontent;

    public function __construct($stateclasses, $box1header, $box1headershort, $box1content, $box2header, $box2headershort, $box2content, $box3header, $box3headershort, $box3content, $extracontent = '') {
        $this->stateclasses = $stateclasses;
        $this->box1header = $box1header;
        $this->box1headershort = $box1headershort;
        $this->box1content = $box1content;
        $this->box2header = $box2header;
        $this->box2headershort = $box2headershort;
        $this->box2content = $box2content;
        $this->box3header = $box3header;
        $this->box3headershort = $box3headershort;
        $this->box3content = $box3content;
        $this->extracontent = $extracontent;


    }

    public function export_for_template(\renderer_base $output) {
        $data = new \StdClass();
        $data->stateclasses = $this->stateclasses;
        $data->box1header = $this->box1header;
        $data->box1headershort = $this->box1headershort;
        $data->box1content = $this->box1content;
        $data->box2header = $this->box2header;
        $data->box2headershort = $this->box2headershort;
        $data->box2content = $this->box2content;
        $data->box3header = $this->box3header;
        $data->box3headershort = $this->box3headershort;
        $data->box3content = $this->box3content;
        $data->extracontent = $this->extracontent;


        return $data;
    }
}