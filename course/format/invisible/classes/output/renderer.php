<?php

namespace format_invisible\output;

use format_topics_renderer;

global $CFG;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/topics/renderer.php');

/**
 * Class renderer to render footer content.
 * @package format_invisible\output
 */
class renderer extends format_topics_renderer {
    /**
     * @param contentfooter $content Course footer library.
     * @return bool|string The rendered template output.
     */
    public function render_contentfooter(contentfooter $content) {
        $data = $content->export_for_template($this);
        return $this->render_from_template('format_invisible/course_footer', $data);
    }

    /**
     * @param contentfooter $content Course footer library.
     * @return bool|string The rendered template output.
     */
    public function render_contentheader(contentheader $content) {
        $data = $content->export_for_template($this);
        return $this->render_from_template('format_invisible/content_header', $data);
    }
}