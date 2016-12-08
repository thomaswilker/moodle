<?php
namespace block_message_broadcast\output;
defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;
use stdClass;
use ArrayIterator;

class renderer extends plugin_renderer_base {
    public function render_manage_messages_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_message_broadcast/manage_messages_table', $data);
    }
    public function display_unread_messages($unreadmessages) {
        global $CFG;
        $data = new ArrayIterator($unreadmessages);
        $nameddata = new StdClass();
        $nameddata->messagelist = $data;
        $nameddata->urlbase = $CFG->wwwroot;
        $html = $this->render_from_template('block_message_broadcast/unread_messages', $nameddata);

        return $html;
    }
}

