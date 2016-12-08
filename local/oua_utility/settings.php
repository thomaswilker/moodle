<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
if ($hassiteconfig) {
    $settings = new \admin_settingpage('local_oua_utility', get_string('pluginname', 'local_oua_utility'));
    $ADMIN->add('localplugins', $settings);

    require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');

    $options = array('' => get_string('none'));
    $roles = get_assignable_roles(context_system::instance());

    foreach ($roles as $roleid => $rolename) {
        $options[$roleid] = $rolename;
    }
    $label = get_string('config_globalteacherroleid', 'local_oua_utility');
    $desc = get_string('config_globalteacherroleid_details', 'local_oua_utility');
    $default = null;

    $settings->add(new \admin_setting_configselect('local_oua_utility/globalteacherroleid', $label, $desc, $default, $options));

    $label = get_string('config_autoremoverole', 'local_oua_utility');
    $desc = get_string('config_autoremoverole_details', 'local_oua_utility');
    $default = '1';
    $settings->add(new \admin_setting_configcheckbox('local_oua_utility/autoremoverole', $label, $desc, $default));
}
