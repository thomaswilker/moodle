<?php
namespace format_invisible\output;

use renderable;
use templatable;
use stdClass;

/**
 * Class courseheader
 * @package format_invisible\output
 */
class contentheader implements renderable, templatable {

    protected $data;

    /**
     * @param cm_info|null $previous Previous course module if there is one.
     * @param cm_info|null $next Next course module if there is one.
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Export the data from this widget for template rendering.
     *
     * @param \renderer_base $output The item we are exporting into.
     * @return \stdClass The class data for rendering.
     */
    public function export_for_template(\renderer_base $output) {
        $this->data->nexturl = !is_null($this->data->nextcm) ? $this->data->nextcm->url : null;
        $this->data->nextname = !is_null($this->data->nextcm) ? $this->data->nextcm->name : null;
        $this->data->previousurl = !is_null($this->data->previouscm) ? $this->data->previouscm->url : null;
        $this->data->previousname = !is_null($this->data->previouscm) ? $this->data->previouscm->name : null;
        $this->data->displaydiv = !is_null($this->data->nextcm) || !is_null($this->data->previouscm);

        return $this->data;
    }
}