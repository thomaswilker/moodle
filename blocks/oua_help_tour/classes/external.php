<?php

/**
 *  *
 * @package    block_oua_help_tour
 * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_help_tour;

global $CFG;
require_once($CFG->libdir . "/externallib.php");
use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use invalid_parameter_exception;

class external extends external_api {
    public static function help_tour_exists($helptourname) {
        global $CFG;
        return file_exists($CFG->dirroot . '/blocks/oua_help_tour/amd/src/' . $helptourname . '.js');
    }

    /**
     * Set user preference to disable the help tour for the given tour name.
     *
     * @param $helptourname Name of tour to disable.
     * @return array
     */
    public static function disable_help_tour($helptourname) {
        $params = self::validate_parameters(self::disable_help_tour_parameters(), array('helptourname' => $helptourname));
        if (!static::help_tour_exists($params['helptourname'])) {
            throw new invalid_parameter_exception('Cannot find specified help tour.');
        }
        set_user_preference('block_oua_' . $params['helptourname'] . '_disabled', true);

        return true;
    }

    /**
     * Returns description of disable_help_tour() parameters.
     *
     * @return external_function_parameters
     */
    public static function disable_help_tour_parameters() {
        $helptourname = new external_value(PARAM_TEXT, 'String of your to disable');
        $params = array('helptourname' => $helptourname);
        return new external_function_parameters($params);
    }

    /**
     * Returns description of disable_help_tour() result value.
     *
     * @return external_description
     */
    public static function disable_help_tour_returns() {
        return new external_value(PARAM_BOOL, 'Help tour disabled successfully');
    }

    /**
     * Set user preference to disable the help tour for the given tour name for this session
     *
     * @param $helptourname Name of tour to disable.
     * @return array
     */
    public static function disable_help_tour_for_session($helptourname) {
        global $SESSION;
        $params = self::validate_parameters(self::disable_help_tour_parameters(), array('helptourname' => $helptourname));
        if (!static::help_tour_exists($params['helptourname'])) {
            throw new invalid_parameter_exception('Cannot find specified help tour.');
        }
        $SESSION->{'block_oua_' . $params['helptourname'] . '_disabled'} = true;

        return true;
    }

    /**
     * Returns description of disable_help_tour() parameters.
     *
     * @return external_function_parameters
     */
    public static function disable_help_tour_for_session_parameters() {
        $helptourname = new external_value(PARAM_TEXT, 'String of your to disable');
        $params = array('helptourname' => $helptourname);
        return new external_function_parameters($params);
    }

    /**
     * Returns description of disable_help_tour() result value.
     *
     * @return external_description
     */
    public static function disable_help_tour_for_session_returns() {
        return new external_value(PARAM_BOOL, 'Help tour disabled successfully');
    }
}
