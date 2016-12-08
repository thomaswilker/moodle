<?php
/**
 * @package     oua_utility
 * @category    local
 * @copyright   2015 Russell Smith
 * @author      Russell Smith <russell.smith@open.edu.au>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'OUA Utility Library';
$string['config_globalteacherroleid'] = 'Role to assign for site-wide teachers';
$string['config_globalteacherroleid_details'] = 'Must be assignable at the SYSTEM CONTEXT. When set anyone who is assigned a role with grade export capability will also receive this role. To disable auto assigning, set role to None.';
$string['config_autoremoverole'] = 'Auto remove role, when user is no longer assigned as a teacher.';
$string['config_autoremoverole_details'] = 'This may remove anyone added manually to the global teacher role.';
$string['task_globalteachersync'] = 'Sync users who have is_global_teacher into Global teacher role specified in config.';
