<?php
/**
 * Form for editing settings for individual help tour instances.
 *
 * @package   block_oua_help_tour
 * @copyright 2016 Ben Kelada (ben.kelada@open.edu.au)
  */
require_once($CFG->dirroot.'/blocks/oua_help_tour/lib.php');
/**
 * From for editing settings for help tour
 *
 * @copyright 2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class block_oua_help_tour_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        /*
         * The tours available are the javascript files in the amd/src directory
         * They must be named help_tour_xxxxx.js
         */

        $availabletours = array();
        $availabletours[] = '';
        $availabletours = $availabletours + get_tour_file_list();
        $mform->addElement('select', 'config_tourinstance', get_string('config_tourinstance', 'block_oua_help_tour'), $availabletours);
        $mform->setDefault('config_tourinstance', '');

        /* This checkbox is never saved to database (always false)
           But when it is checked and submitted, all users preferences for this instance tour disable will be reset to 0
        */
        $mform->addElement('advcheckbox', 'config_resetthistour', get_string('config_resetthistour', 'block_oua_help_tour'));
        $mform->setType('config_resetthistour', PARAM_BOOL);
        $mform->setDefault('config_resetthistour', false);


        return $mform;
    }

}
