<?php

class block_oua_connections extends block_base {

    public function init() {
        $this->title = get_string('oua_connections', 'block_oua_connections');
    }

    public function get_content() {
        global $CFG;
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
        $renderer = $this->page->get_renderer('block_oua_connections');
        $tabsdata = new stdClass();
        $myconnections = new \block_oua_connections\output\my_connections(4);
        $suggestedconnections = new \block_oua_connections\output\suggested_connections();
        $tabsdata->myconnections = $renderer->render($myconnections);
        if (empty($myconnections->myconnections)) {
            $tabsdata->myconnectionsactive = false; // Class to make sugggested connections active
        } else {
            $tabsdata->myconnectionsactive = true; // Class to make sugggested connections active
        }
        $tabsdata->suggestedconnections = $renderer->render($suggestedconnections);

        $this->content->text = $renderer->display_connections_tabs($tabsdata);
        if (isloggedin() && has_capability('moodle/site:sendmessage', $this->page->context)
            && !empty($CFG->messaging) && !isguestuser()) {
            require_once($CFG->dirroot . '/message/lib.php');
            //message_messenger_requirejs();
        }
        return $this->content;
    }

    public function hide_header() {
        return true;
    }
    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

}
