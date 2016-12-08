<?php
/**
 * Student messages block
 *
 * @package    block_oua_messages
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
use block_oua_messages\output\renderable as message_list;

class block_oua_messages extends block_base {

    public function init() {
        $this->title = get_string('oua_messages', 'block_oua_messages');
    }

    public function get_content() {
        global $CFG, $USER;
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

        $messages = new message_list($USER->id);
        $renderer = $this->page->get_renderer('block_oua_messages');
        $this->content->text = $renderer->render($messages);
        if (isloggedin() && has_capability('moodle/site:sendmessage', $this->page->context)
                && !empty($CFG->messaging) && !isguestuser()) {
            require_once($CFG->dirroot . '/message/lib.php');
            message_messenger_requirejs();
        }
        return $this->content;
    }

    function hide_header() {
        return true;
    }

}
