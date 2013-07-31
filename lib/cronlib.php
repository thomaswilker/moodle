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
 * Cron functions.
 *
 * @package    core
 * @subpackage admin
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute cron tasks
 */
function cron_run() {
    global $DB, $CFG, $OUTPUT;

    if (CLI_MAINTENANCE) {
        echo "CLI maintenance mode active, cron execution suspended.\n";
        exit(1);
    }

    if (moodle_needs_upgrading()) {
        echo "Moodle upgrade pending, cron execution suspended.\n";
        exit(1);
    }

    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/gradelib.php');

    if (!empty($CFG->showcronsql)) {
        $DB->set_debug(true);
    }
    if (!empty($CFG->showcrondebugging)) {
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;
    }

    set_time_limit(0);
    $starttime = microtime();

    // Increase memory limit
    raise_memory_limit(MEMORY_EXTRA);

    // Emulate normal session - we use admin accoutn by default
    cron_setup_user();

    // Start output log
    $timenow  = time();
    mtrace("Server Time: ".date('r', $timenow)."\n\n");

    // Run all scheduled tasks.
    while ($task = \core_task::get_next_scheduled_task($timenow)) {
        mtrace("Execute scheduled task:  ".get_class($task));
        try {
            $task->execute();
            mtrace("Scheduled task complete: ".get_class($task));
            cron_trace_time_and_memory();
            \core\task\manager::scheduled_task_complete($task);
        } catch (Exception $e) {
            mtrace("Scheduled task failed:   ".get_class($task).",".$e->getMessage());
            cron_trace_time_and_memory();
            \core\task\manager::scheduled_task_failed($task);
        }
        unset($task);
    }


    // Run question bank clean-up.
    mtrace("Starting the question bank cron...", '');
    cron_trace_time_and_memory();
    require_once($CFG->libdir . '/questionlib.php');
    question_bank::cron();
    mtrace('done.');

    //Run registration updated cron
    mtrace(get_string('siteupdatesstart', 'hub'));
    cron_trace_time_and_memory();
    require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');
    $registrationmanager = new registration_manager();
    $registrationmanager->cron();
    mtrace(get_string('siteupdatesend', 'hub'));

    // If enabled, fetch information about available updates and eventually notify site admins
    if (empty($CFG->disableupdatenotifications)) {
        require_once($CFG->libdir.'/pluginlib.php');
        $updateschecker = available_update_checker::instance();
        $updateschecker->cron();
    }


    mtrace('Running cache cron routines');
    cache_helper::cron();
    mtrace('done.');

    // Run automated backups if required - these may take a long time to execute
    require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot.'/backup/util/helper/backup_cron_helper.class.php');
    backup_cron_automated_helper::run_automated_backup();


    // Run stats as at the end because they are known to take very long time on large sites
    if (!empty($CFG->enablestats) and empty($CFG->disablestatsprocessing)) {
        require_once($CFG->dirroot.'/lib/statslib.php');
        // check we're not before our runtime
        $timetocheck = stats_get_base_daily() + $CFG->statsruntimestarthour*60*60 + $CFG->statsruntimestartminute*60;

        if (time() > $timetocheck) {
            // process configured number of days as max (defaulting to 31)
            $maxdays = empty($CFG->statsruntimedays) ? 31 : abs($CFG->statsruntimedays);
            if (stats_cron_daily($maxdays)) {
                if (stats_cron_weekly()) {
                    if (stats_cron_monthly()) {
                        stats_clean_old();
                    }
                }
            }
            @set_time_limit(0);
        } else {
            mtrace('Next stats run after:'. userdate($timetocheck));
        }
    }

    // Run badges review cron.
    mtrace("Starting badges cron...");
    require_once($CFG->dirroot . '/badges/cron.php');
    badge_cron();
    mtrace('done.');

    // cleanup file trash - not very important
    $fs = get_file_storage();
    $fs->cron();

    mtrace("Cron script completed correctly");

    gc_collect_cycles();
    mtrace('Cron completed at ' . date('H:i:s') . '. Memory used ' . display_size(memory_get_usage()) . '.');
    $difftime = microtime_diff($starttime, microtime());
    mtrace("Execution took ".$difftime." seconds");
}


/**
 * Output some standard information during cron runs. Specifically current time
 * and memory usage. This method also does gc_collect_cycles() (before displaying
 * memory usage) to try to help PHP manage memory better.
 */
function cron_trace_time_and_memory() {
    gc_collect_cycles();
    mtrace('... started ' . date('H:i:s') . '. Current memory use ' . display_size(memory_get_usage()) . '.');
}
