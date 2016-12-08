<?php
namespace local_conversations\output;

defined('MOODLE_INTERNAL') || die;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    public function render_renderable($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('local_conversations/message_list', $data);
    }
    public function render_my_messages($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('local_conversations/my_messages_page', $data);
    }
    public function render_my_notifications($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('local_conversations/my_notifications_page', $data);
    }
}
