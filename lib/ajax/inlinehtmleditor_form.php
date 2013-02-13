<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class core_inlinehtmleditor_form extends moodleform implements renderable {
    protected $course;
    protected $context;

    function definition() {
        global $USER, $CFG, $DB, $PAGE;

        $mform    = $this->_form;
/*
        $course        = $this->_customdata['course']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];
*/

        $mform->setDisableShortforms();
        $mform->addElement('header', 'general', get_string('updatetext', 'core'));

        $mform->addElement('editor', 'textupdate', '', null);
 //       $mform->addElement('static', 'applychanges', '<button>Apply changes</button>');
        $this->add_action_buttons(false, get_string('applychanges', 'core'));
    }

}

