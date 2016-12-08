<?php

class theme_ouaclean_submit_confirm_form extends mod_assign_confirm_submission_form {
    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;

        list($requiresubmissionstatement,
            $submissionstatement,
            $coursemoduleid,
            $data) = $this->_customdata;




        if ($requiresubmissionstatement) {
            $mform->addElement('checkbox', 'submissionstatement', '', $submissionstatement);
            $mform->addRule('submissionstatement', get_string('required'), 'required', null, 'client', false, true);
        }
        $mform->addElement('hidden', 'id', $coursemoduleid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'confirmsubmit');
        $mform->setType('action', PARAM_ALPHA);
        $this->add_action_buttons(true, get_string('goahead', 'theme_ouaclean'), "primary_btn confirm-submit");
        // Need to fake the quick form name because the confirmsubmission page expects a quickform named mod_assign_confirm_submission.
        $mform->addElement('hidden', '_qf__mod_assign_confirm_submission_form', '1');
        $mform->setType('_qf__mod_assign_confirm_submission_form', PARAM_BOOL);
        if ($data) {
            $this->set_data($data);
        }
    }
    function add_action_buttons($cancel = true, $submitlabel = null, $cssclasses = '') {
        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechanges');
        }
        $mform =& $this->_form;

        //no group needed
        $submit = $mform->addElement('xbutton', 'submitbutton', $submitlabel);
        $mform->updateElementAttr(array($submit), array("class" => $cssclasses, "type" => "submit"));
        $mform->closeHeaderBefore('submitbutton');
        if ($cancel) {

        }
    }

}