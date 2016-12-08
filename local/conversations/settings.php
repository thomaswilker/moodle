<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
if ($hassiteconfig) {
    $settings = new \admin_settingpage('local_conversations', get_string('pluginname', 'local_conversations'));
    $ADMIN->add('localplugins', $settings);

    $label = get_string('config_conversationrefreshtime', 'local_conversations');
    $desc = get_string('config_conversationrefreshtime_details', 'local_conversations');
    $default = 60000;
    $settings->add(new \admin_setting_configtext('local_conversations/conversationrefreshtime', $label, $desc, $default));

    $label = get_string('config_headerrefreshtime', 'local_conversations');
    $desc = get_string('config_headerrefreshtime_details', 'local_conversations');
    $default = 120000;
    $settings->add(new \admin_setting_configtext('local_conversations/headerrefreshtime', $label, $desc, $default));
}
