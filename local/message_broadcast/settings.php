<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');
/**
 * Adds "Manage Broadcast Messages" link to site admin navigation menu
 *
*/
if (!during_initial_install() && has_capability('moodle/site:config', context_system::instance())) {

    $ADMIN->add(
        'messageoutputs',
        new admin_externalpage(
            'message_broadcast',
            get_string('managemessages', 'block_message_broadcast'),
            "$CFG->wwwroot/blocks/message_broadcast/managemessages.php",
            "moodle/site:config"
        )
    );
}
