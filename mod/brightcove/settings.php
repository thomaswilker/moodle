<?php
/**
 * Page module admin settings and defaults
 *
 * @package    mod
 * @subpackage brightcove
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('brightcove/accountid',
        get_string('accountid', 'brightcove'),
        get_string('accountid_help', 'brightcove'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('brightcove/defaultplayerid',
        get_string('defaultplayerid', 'brightcove'),
        get_string('defaultplayerid_help', 'brightcove'),
        '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('brightcove/threeplayapikey',
        get_string('threeplayapikey', 'brightcove'),
        get_string('threeplayapikey_help', 'brightcove'),
        '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('brightcove/threeplayprojectid',
        get_string('threeplayprojectid', 'brightcove'),
        get_string('threeplayprojectid_help', 'brightcove'),
        '', PARAM_TEXT));
}
