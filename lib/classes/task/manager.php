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
 * Scheduled and adhoc task management.
 *
 * @package    core
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

define('CORE_TASK_TASKS_FILENAME', 'db/tasks.php');
/**
 * Collection of task related methods.
 *
 * Some locking rules for this class:
 * All changes to scheduled tasks must be protected with both - the global cron lock and the lock
 * for the specific scheduled task (in that order). Locks must be released in the reverse order.
 */
class manager {

    /**
     * Given a component name, will load the list of tasks in the db/tasks.php file for that component.
     *
     * @return array(core\task\scheduled_task) - List of scheduled tasks for this component.
     */
    public static function load_default_scheduled_tasks_for_component($componentname) {
        $dir = \core_component::get_component_directory($componentname);

        if (!$dir) {
            return array();
        }

        $file = $dir . '/' . CORE_TASK_TASKS_FILENAME;
        if (!file_exists($file)) {
            return array();
        }

        $tasks = null;
        require_once($file);

        if (!isset($tasks)) {
            return array();
        }

        $scheduledtasks = array();

        foreach ($tasks as $task) {
            // Assume the root namespace.
            $classname = '\\' . $task['classname'];

            $scheduledtask = new $classname();
            $scheduledtask->set_component($componentname);
            $scheduledtask->set_blocking(!empty($task['blocking']));
            $scheduledtask->set_minute($task['minute']);
            $scheduledtask->set_hour($task['hour']);
            $scheduledtask->set_day($task['day']);
            $scheduledtask->set_month($task['month']);
            $scheduledtask->set_dayofweek($task['dayofweek']);
            $scheduledtask->set_fail_delay(0);
            $scheduledtasks[] = $scheduledtask;
        }

        return $scheduledtasks;
    }

    /**
     * Update the database to contain a list of scheduled task for a component.
     * The list of scheduled tasks is taken from @load_scheduled_tasks_for_component.
     * Will throw exceptions for any errors.
     *
     * @param string $componentname - The frankenstyle component name.
     */
    public static function reset_scheduled_tasks_for_component($componentname) {
        global $DB;
        $cronlock = \core\lock\manager::get_current_lock_type();

        if (!$cronlock->lock('core_cron', 10, 60)) {
            throw new moodle_exception('locktimeout');
        }
        $tasks = self::load_default_scheduled_tasks_for_component($componentname);

        $tasklocks = array();
        foreach ($tasks as $task) {
            $lock = \core\lock\manager::get_current_lock_type();

            if (!$lock->lock(get_class($task), 10, 60)) {
                // Could not get all the locks required - release all locks and fail.
                foreach ($tasklocks as $tasklock) {
                    $tasklock->unlock();
                }
                $cronlock->unlock();
                throw new moodle_exception('locktimeout');
            }
            $tasklocks[] = $lock;
        }

        // Got a lock on cron and all the tasks for this component, time to reset the config.
        $DB->delete_records('scheduled_task', array('component'=>$componentname));
        foreach ($tasks as $task) {
            $record = new \stdClass();
            $record->component = $componentname;
            $record->classname = get_class($task);
            $record->blocking = $task->is_blocking();
            $record->hour = $task->get_hour();
            $record->minute = $task->get_minute();
            $record->day = $task->get_day();
            $record->dayofweek = $task->get_dayofweek();
            $record->month = $task->get_month();
            $DB->insert_record('scheduled_task', $record);
        }

        // Release the locks.
        foreach ($tasklocks as $tasklock) {
            $tasklock->unlock();
        }

        $cronlock->unlock();
    }

    /**
     * Change the default configuration for a scheduled task.
     * The list of scheduled tasks is taken from {@link load_scheduled_tasks_for_component}.
     *
     * @param \core\task\scheduled_task $task - The new scheduled task information to store.
     * @return boolean - True if the config was saved.
     */
    public static function configure_scheduled_task(scheduled_task $task) {
        global $DB;
        $cronlock = \core\lock\manager::get_current_lock_type();

        if (!$cronlock->lock('core_cron', 10, 60)) {
            throw new moodle_exception('locktimeout');
        }

        $lock = \core\lock\manager::get_current_lock_type();

        if (!$lock->lock(get_class($task), 10, 60)) {
            $cronlock->unlock();
            throw new moodle_exception('locktimeout');
        }

        $record = new \stdClass();
        $record->component = $componentname;
        $record->blocking = $task->is_blocking();
        $record->hour = $task->get_hour();
        $record->minute = $task->get_minute();
        $record->day = $task->get_day();
        $record->dayofweek = $task->get_dayofweek();
        $record->month = $task->get_month();
        $result = $DB->update_record('scheduled_task', $record, array('classname'=>get_class($task)));

        $lock->unlock();
        $cronlock->unlock();
        return $result;
    }

    /**
     * Given a component name, will load the list of tasks from the scheduled_tasks table for that component.
     * Do not execute tasks loaded from this function - they have not been locked.
     * @return array(core\task\scheduled_task)
     */
    public static function load_scheduled_tasks_for_component($componentname) {
        global $DB;

        $tasks = array();
        // We are just reading - so no locks required.
        $records = $DB->get_records('scheduled_task', array('componentname'=>$componentname), '*', IGNORE_MISSING);
        foreach ($records as $record) {
            $classname = '\\' . $record->classname;
            $task = new $classname;
            $task->set_component($record->component);
            $task->set_blocking(!empty($record->blocking));
            $task->set_minute($record->minute);
            $task->set_hour($record->hour);
            $task->set_day($record->day);
            $task->set_month($record->month);
            $task->set_dayofweek($record->dayofweek);
            $task->set_fail_delay($record->faildelay);
            $tasks[] = $task;
        }

        return $tasks;
    }

    /**
     * This function will dispatch the next scheduled task in the queue. The task will be handed out
     * with an open lock - possibly on the entire cron process. Make sure you call either
     * {@link scheduled_task_failed} or {@link scheduled_task_complete} to release the lock and reschedule the task.
     *
     * @param $timestart - The start of the cron process - do not repeat any tasks that have been run more recently than this.
     * @return core\task\scheduled_task or null
     */
    public static function get_next_scheduled_task($timestart) {
        global $DB;
        $cronlock = \core\lock\manager::get_current_lock_type();

        if (!$cronlock->lock('core_cron', 10)) {
            throw new moodle_exception('locktimeout');
        }

        $where = '(lastruntime IS NULL OR lastruntime < :timestart1) AND (nextruntime IS NULL OR nextruntime < :timestart2)';
        $params = array('timestart1'=>$timestart, 'timestart2'=>$timestart);
        $records = $DB->get_records_select('scheduled_task', $where, $params);

        foreach ($records as $record) {
            $lock = \core\lock\manager::get_current_lock_type();

            if ($lock->lock(($record->classname), 10)) {
                $classname = '\\' . $record->classname;
                $task = new $classname();
                $task->set_component($record->component);
                $task->set_blocking(!empty($record->blocking));
                $task->set_minute($record->minute);
                $task->set_hour($record->hour);
                $task->set_day($record->day);
                $task->set_month($record->month);
                $task->set_dayofweek($record->dayofweek);
                $task->set_fail_delay($record->faildelay);

                $task->set_lock($lock);
                if (!$task->is_blocking()) {
                    $cronlock->unlock();
                } else {
                    $task->set_cron_lock($cronlock);
                }
                return $task;
            }
        }

        // No tasks.
        $cronlock->unlock();
        return null;
    }

    /**
     * This function indicates that a scheduled task was not completed succesfully and should be retried.
     *
     * @param core\task\scheduled_task
     */
    public static function scheduled_task_failed(scheduled_task $task) {
        $delay = $task->get_fail_delay();

        // Reschedule task with exponential fall off for failing tasks.
        if (empty($delay)) {
            $delay = 60;
        } else {
            $delay *= 2;
        }

        // Max of 24 hour delay.
        if ($delay > 86400) {
            $delay = 86400;
        }


        $record = $DB->get_record('scheduled_task', array('classname'=>get_class($task)));
        $record->nextruntime = time() + $delay;
        $record->faildelay = $delay;
        $DB->update_record('scheduled_task', $record);

        if ($task->is_blocking()) {
            $task->get_cron_lock()->unlock();
        }
        $task->get_lock()->unlock();
    }

    /**
     * This function indicates that a scheduled task was completed succesfully and should be rescheduled.
     *
     * @param core\task\scheduled_task
     */
    public static function scheduled_task_complete(scheduled_task $task) {
        global $DB;

        $record = $DB->get_record('scheduled_task', array('classname'=>get_class($task)));
        $record->lastruntime = time();
        $record->faildelay = 0;
        $record->nextruntime = $task->get_next_scheduled_time();

        $DB->update_record('scheduled_task', $record);

        // Reschedule and then release the locks.
        if ($task->is_blocking()) {
            $task->get_cron_lock()->unlock();
        }
        $task->get_lock()->unlock();
    }
}
