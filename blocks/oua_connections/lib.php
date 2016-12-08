<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Callback for when Contact Role is updated, this function re-assigns role that allows a user1 to view another users2 profile
 * when that user1 is added to user 2's profile.
 *
 * @param string $name The name of the plugin that initiated the callback request is passed in from the hook.
 * @throws coding_exception
 * @throws dml_exception
 */
function block_oua_connections_updatedcallback($name = '') {
    global $CFG, $DB;
    
    $config = get_config('block_oua_connections');
    if (!isset($config->viewprofilecontactrole) || $config->viewprofilecontactrole == '') {
        return;
    }

    // On setting save, update existing connected users to have the new role assigned.
    $connectionsmanager = new \block_oua_connections\manage($CFG, $DB, null, null, null);
    $users = $connectionsmanager->get_all_connected_users();

    foreach ($users as $userconnection) {
        role_assign($config->viewprofilecontactrole, $userconnection->contactid, $userconnection->usercontextid);
    }
}
