<?php
/**
 * Student notifications block
 *
 * @package    block_oua_social_activity
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */

use block_oua_social_activity\output\social_events_list;
class block_oua_social_activity extends block_base {


    public function init() {
      $this->title = get_string('oua_social_activity', 'block_oua_social_activity');
    }

    public function get_content() {
        global $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        if (empty($this->config)) {
            $this->config = new stdClass();
        }
        if (empty($this->config->numberofsocialevents)) {
            $this->config->numberofsocialevents = 20;
        }
        if (empty($this->config->numberofdaysback)) {
            $this->config->numberofdaysback = 20;
        }
        $this->content = new stdClass;
        $this->content->header = '';
        $this->content->text = '';
        $this->content->footer = '';

        if (!$this->page->user_is_editing()) {
            $this->title = '';
        }

        $notifications = new social_events_list($USER->id, $this->config->numberofdaysback, $this->config->numberofsocialevents);
        $renderer = $this->page->get_renderer('block_oua_social_activity');
        $this->content->text = $renderer->render($notifications);

        return $this->content;
    }

    function hide_header() {
        return true;
    }


}