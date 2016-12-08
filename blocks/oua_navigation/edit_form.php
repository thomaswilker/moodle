<?php
/**
 * Form for editing global oua_navigation instances.
 *
 * @package     blocks
 * @subpackage  oua_navigation
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oua_navigation_edit_form extends block_edit_form {
    /**
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $CFG;
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        $introprefix = array(
            'show' => get_string('showunitprefix', 'block_oua_navigation'),
            'hide' => get_string('hideunitprefix', 'block_oua_navigation'),
        );
        $mform->addElement('select', 'config_unit_intro_prefix', get_string('title_unit_intro_prefix', 'block_oua_navigation'), $introprefix);
        $mform->setDefault('config_config_unit_intro_prefix', 'show');
    }
}