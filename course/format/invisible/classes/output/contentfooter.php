<?php
namespace format_invisible\output;

use renderable;
use templatable;
use stdClass;

/**
 * Class contentfooter
 * @package format_invisible\output
 */
class contentfooter implements renderable, templatable {

    protected $previouscm;
    protected $nextcm;

    /**
     * @param cm_info|null $previous Previous course module if there is one.
     * @param cm_info|null $next Next course module if there is one.
     */
    public function __construct($previous, $next) {
        $this->nextcm = $next;
        $this->previouscm = $previous;
    }

    /**
     * Export the data from this widget for template rendering.
     *
     * @param \renderer_base $output The item we are exporting into.
     * @return \stdClass The class data for rendering.
     */
    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $data->nexturl = !is_null($this->nextcm) ? $this->nextcm->url : null;
        $data->previousurl = !is_null($this->previouscm) ? $this->previouscm->url : null;
        $data->displaydiv = !is_null($this->nextcm) || !is_null($this->previouscm);

        return $data;
    }
}