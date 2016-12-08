<?php
/**
 * Add brightcove form
 *
 * @package    mod
 * @subpackage brightcove
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_brightcove_mod_form extends moodleform_mod {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('videoname', 'brightcove'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'playerid', get_string('playerid', 'brightcove'), array('size' => '64'));
        $mform->setType('playerid', PARAM_TEXT);
        $mform->addRule('playerid', null, 'required', null, 'client');
        $mform->addElement('static', 'playerid_help', '', get_string('playerid_help', 'brightcove'));

        $brightcoveconfig = get_config('brightcove');
        if (isset($brightcoveconfig->defaultplayerid)) {
            $mform->setDefault('playerid', $brightcoveconfig->defaultplayerid);
        }

        $mform->addElement('text', 'videoid', get_string('videoid', 'brightcove'), array('size' => '64'));
        $mform->addRule('videoid', null, 'required', null, 'client');
        $mform->setType('videoid', PARAM_TEXT);
        $mform->addElement('static', 'videoid_help', '', get_string('videoid_help', 'brightcove'));

        // Adding the optional "intro" and "introformat" pair of fields.
        $this->standard_intro_elements(get_string('brightcoveintro', 'brightcove'));
        $mform->setAdvanced('introeditor');

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
