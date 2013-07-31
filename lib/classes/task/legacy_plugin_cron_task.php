<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Scheduled task class.
 *
 * @package    core
 * @copyright  2013 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

/**
 * Simple task to run cron for all plugins.
 * Note - this is only for plugins using the legacy cron method,
 * plugins can also now just add their own scheduled tasks which is the preferred method.
 */
class legacy_plugin_cron_task extends scheduled_task {

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        // Run the auth cron, if any before enrolments
        // because it might add users that will be needed in enrol plugins
        $auths = get_enabled_auth_plugins();
        mtrace("Running auth crons if required...");
        cron_trace_time_and_memory();
        foreach ($auths as $auth) {
            $authplugin = get_auth_plugin($auth);
            if (method_exists($authplugin, 'cron')) {
                mtrace("Running cron for auth/$auth...");
                $authplugin->cron();
                if (!empty($authplugin->log)) {
                    mtrace($authplugin->log);
                }
            }
            unset($authplugin);
        }

        // It is very important to run enrol early
        // because other plugins depend on correct enrolment info.
        mtrace("Running enrol crons if required...");
        $enrols = enrol_get_plugins(true);
        foreach($enrols as $ename=>$enrol) {
            // do this for all plugins, disabled plugins might want to cleanup stuff such as roles
            if (!$enrol->is_cron_required()) {
                continue;
            }
            mtrace("Running cron for enrol_$ename...");
            cron_trace_time_and_memory();
            $enrol->cron();
            $enrol->set_config('lastcron', time());
        }

        // Run all cron jobs for each module
        mtrace("Starting activity modules");
        get_mailer('buffer');
        if ($mods = $DB->get_records_select("modules", "cron > 0 AND ((? - lastcron) > cron) AND visible = 1", array($timenow))) {
            foreach ($mods as $mod) {
                $libfile = "$CFG->dirroot/mod/$mod->name/lib.php";
                if (file_exists($libfile)) {
                    include_once($libfile);
                    $cron_function = $mod->name."_cron";
                    if (function_exists($cron_function)) {
                        mtrace("Processing module function $cron_function ...", '');
                        cron_trace_time_and_memory();
                        $pre_dbqueries = null;
                        $pre_dbqueries = $DB->perf_get_queries();
                        $pre_time      = microtime(1);
                        if ($cron_function()) {
                            $DB->set_field("modules", "lastcron", $timenow, array("id"=>$mod->id));
                        }
                        if (isset($pre_dbqueries)) {
                            mtrace("... used " . ($DB->perf_get_queries() - $pre_dbqueries) . " dbqueries");
                            mtrace("... used " . (microtime(1) - $pre_time) . " seconds");
                        }
                        // Reset possible changes by modules to time_limit. MDL-11597
                        @set_time_limit(0);
                        mtrace("done.");
                    }
                }
            }
        }
        get_mailer('close');
        mtrace("Finished activity modules");

        mtrace("Starting blocks");
        if ($blocks = $DB->get_records_select("block", "cron > 0 AND ((? - lastcron) > cron) AND visible = 1", array($timenow))) {
            // We will need the base class.
            require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
            foreach ($blocks as $block) {
                $blockfile = $CFG->dirroot.'/blocks/'.$block->name.'/block_'.$block->name.'.php';
                if (file_exists($blockfile)) {
                    require_once($blockfile);
                    $classname = 'block_'.$block->name;
                    $blockobj = new $classname;
                    if (method_exists($blockobj,'cron')) {
                        mtrace("Processing cron function for ".$block->name.'....','');
                        cron_trace_time_and_memory();
                        if ($blockobj->cron()) {
                            $DB->set_field('block', 'lastcron', $timenow, array('id'=>$block->id));
                        }
                        // Reset possible changes by blocks to time_limit. MDL-11597
                        @set_time_limit(0);
                        mtrace('done.');
                    }
                }

            }
        }
        mtrace('Finished blocks');

        mtrace('Starting admin reports');
        $this->execute_plugin_type('report');
        mtrace('Finished admin reports');

        mtrace('Starting course reports');
        $this->execute_plugin_type('coursereport');
        mtrace('Finished course reports');


        // run gradebook import/export/report cron
        mtrace('Starting gradebook plugins');
        $this->execute_plugin_type('gradeimport');
        $this->execute_plugin_type('gradeexport');
        $this->execute_plugin_type('gradereport');
        mtrace('Finished gradebook plugins');

        // all other plugins
        $this->execute_plugin_type('message', 'message plugins');
        $this->execute_plugin_type('filter', 'filters');
        $this->execute_plugin_type('editor', 'editors');
        $this->execute_plugin_type('format', 'course formats');
        $this->execute_plugin_type('profilefield', 'profile fields');
        $this->execute_plugin_type('webservice', 'webservices');
        $this->execute_plugin_type('repository', 'repository plugins');
        $this->execute_plugin_type('qbehaviour', 'question behaviours');
        $this->execute_plugin_type('qformat', 'question import/export formats');
        $this->execute_plugin_type('qtype', 'question types');
        $this->execute_plugin_type('plagiarism', 'plagiarism plugins');
        $this->execute_plugin_type('theme', 'themes');
        $this->execute_plugin_type('tool', 'admin tools');
        $this->execute_plugin_type('local', 'local plugins');
    }

    /**
     * Executes cron functions for a specific type of plugin.
     *
     * @param string $plugintype Plugin type (e.g. 'report')
     * @param string $description If specified, will display 'Starting (whatever)'
     *   and 'Finished (whatever)' lines, otherwise does not display
     */
    function execute_plugin_type($plugintype, $description = null) {
        global $DB;

        // Get list from plugin => function for all plugins
        $plugins = get_plugin_list_with_function($plugintype, 'cron');

        // Modify list for backward compatibility (different files/names)
        $plugins = $this->bc_hack_plugin_functions($plugintype, $plugins);

        // Return if no plugins with cron function to process
        if (!$plugins) {
            return;
        }

        if ($description) {
            mtrace('Starting '.$description);
        }

        foreach ($plugins as $component=>$cronfunction) {
            $dir = core_component::get_component_directory($component);

            // Get cron period if specified in version.php, otherwise assume every cron
            $cronperiod = 0;
            if (file_exists("$dir/version.php")) {
                $plugin = new stdClass();
                include("$dir/version.php");
                if (isset($plugin->cron)) {
                    $cronperiod = $plugin->cron;
                }
            }

            // Using last cron and cron period, don't run if it already ran recently
            $lastcron = get_config($component, 'lastcron');
            if ($cronperiod && $lastcron) {
                if ($lastcron + $cronperiod > time()) {
                    // do not execute cron yet
                    continue;
                }
            }

            mtrace('Processing cron function for ' . $component . '...');
            cron_trace_time_and_memory();
            $pre_dbqueries = $DB->perf_get_queries();
            $pre_time = microtime(true);

            $cronfunction();

            mtrace("done. (" . ($DB->perf_get_queries() - $pre_dbqueries) . " dbqueries, " .
                    round(microtime(true) - $pre_time, 2) . " seconds)");

            set_config('lastcron', time(), $component);
            @set_time_limit(0);
        }

        if ($description) {
            mtrace('Finished ' . $description);
        }
    }

    /**
     * Used to add in old-style cron functions within plugins that have not been converted to the
     * new standard API. (The standard API is frankenstyle_name_cron() in lib.php; some types used
     * cron.php and some used a different name.)
     *
     * @param string $plugintype Plugin type e.g. 'report'
     * @param array $plugins Array from plugin name (e.g. 'report_frog') to function name (e.g.
     *   'report_frog_cron') for plugin cron functions that were already found using the new API
     * @return array Revised version of $plugins that adds in any extra plugin functions found by
     *   looking in the older location
     */
    function bc_hack_plugin_functions($plugintype, $plugins) {
        global $CFG; // mandatory in case it is referenced by include()d PHP script

        if ($plugintype === 'report') {
            // Admin reports only - not course report because course report was
            // never implemented before, so doesn't need BC
            foreach (core_component::get_plugin_list($plugintype) as $pluginname=>$dir) {
                $component = $plugintype . '_' . $pluginname;
                if (isset($plugins[$component])) {
                    // We already have detected the function using the new API
                    continue;
                }
                if (!file_exists("$dir/cron.php")) {
                    // No old style cron file present
                    continue;
                }
                include_once("$dir/cron.php");
                $cronfunction = $component . '_cron';
                if (function_exists($cronfunction)) {
                    $plugins[$component] = $cronfunction;
                } else {
                    debugging("Invalid legacy cron.php detected in $component, " .
                            "please use lib.php instead");
                }
            }
        } else if (strpos($plugintype, 'grade') === 0) {
            // Detect old style cron function names
            // Plugin gradeexport_frog used to use grade_export_frog_cron() instead of
            // new standard API gradeexport_frog_cron(). Also applies to gradeimport, gradereport
            foreach(core_component::get_plugin_list($plugintype) as $pluginname=>$dir) {
                $component = $plugintype.'_'.$pluginname;
                if (isset($plugins[$component])) {
                    // We already have detected the function using the new API
                    continue;
                }
                if (!file_exists("$dir/lib.php")) {
                    continue;
                }
                include_once("$dir/lib.php");
                $cronfunction = str_replace('grade', 'grade_', $plugintype) . '_' .
                        $pluginname . '_cron';
                if (function_exists($cronfunction)) {
                    $plugins[$component] = $cronfunction;
                }
            }
        } else if (strpos($plugintype, 'local') === 0) {
            // Local plugins can have legacy cron.php files too.
            if ($locals = core_component::get_plugin_list('local')) {
                mtrace('Processing customized cron scripts ...', '');
                // legacy cron files are executed directly
                foreach ($locals as $local => $localdir) {
                    if (file_exists("$localdir/cron.php")) {
                        include("$localdir/cron.php");
                    }
                }
                mtrace('done.');
            }
        }

        return $plugins;
    }

}
