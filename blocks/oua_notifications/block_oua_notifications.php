<?php
/**
 * Student notifications block
 *
 * @package    block_oua_notifications
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */

use block_oua_notifications\output\renderable as notification_list;
class block_oua_notifications extends block_base {


    public function init() {
      $this->title = get_string('oua_notifications', 'block_oua_notifications');
    }

    public function get_content() {
        global $USER;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->header = '';
        $this->content->text = '';
        $this->content->footer = '';

        if (!$this->page->user_is_editing()) {
            $this->title = '';
        }

        $notifications = new notification_list($USER->id);
        $renderer = $this->page->get_renderer('block_oua_notifications');
        $this->content->text = $renderer->render($notifications);

        return $this->content;
    }

    function hide_header() {
        return true;
    }


}