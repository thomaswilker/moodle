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
 * Scheduled task abstract class.
 *
 * @package    core
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

/**
 * Abstract class defining a scheduled task.
 */
abstract class scheduled_task {

    /** @var \core\lock\locktype $lock - The lock controlling this task. */
    private $lock = null;

    /** @var \core\lock\locktype $cronlock - The lock controlling the entire cron process. */
    private $cronlock = null;

    /** @var bool $blocking - Does this task block the entire cron process. */
    private $blocking = false;

    /** @var string $hour - Pattern to work out the valid hours */
    private $hour = '*';

    /** @var string $minute - Pattern to work out the valid minutes */
    private $minute = '*';

    /** @var string $day - Pattern to work out the valid days */
    private $day = '*';

    /** @var string $month - Pattern to work out the valid months */
    private $month = '*';

    /** @var string $dayofweek - Pattern to work out the valid dayofweek */
    private $dayofweek = '*';

    /** @var int $faildelay - Exponentially increasing fail delay */
    private $faildelay = 0;

    /**
     * Set the current lock for this scheduled task.
     * @param \core\lock\locktype $lock
     */
    public function set_lock(\core\lock\locktype $lock) {
        $this->lock = $lock;
    }

    /**
     * Set the current lock for the entire cron process.
     * @param \core\lock\locktype $lock
     */
    public function set_cron_lock(\core\lock\locktype $lock) {
        $this->cronlock = $lock;
    }

    /**
     * Get the current lock for this scheduled task.
     * @return \core\lock\locktype
     */
    public function get_lock() {
        return $this->lock;
    }

    /**
     * Get the current lock for the entire cron.
     * @return \core\lock\locktype
     */
    public function get_cron_lock() {
        return $this->cronlock;
    }

    /**
     * Setter for $blocking.
     * @param bool $blocking.
     */
    public function set_blocking($blocking) {
        $this->blocking = $blocking;
    }

    /**
     * Getter for $blocking.
     * @return bool
     */
    public function is_blocking() {
        return $this->blocking;
    }

    /**
     * Setter for $component.
     * @param string $component.
     */
    public function set_component($component) {
        $this->component = $component;
    }

    /**
     * Getter for $component.
     * @return string
     */
    public function get_component() {
        return $this->component;
    }

    /**
     * Setter for $minute.
     * @param string $minute.
     */
    public function set_minute($minute) {
        $this->minute = $minute;
    }

    /**
     * Getter for $minute.
     * @return string
     */
    public function get_minute() {
        return $this->minute;
    }

    /**
     * Setter for $hour.
     * @param string $hour.
     */
    public function set_hour($hour) {
        $this->hour = $hour;
    }

    /**
     * Getter for $hour.
     * @return string
     */
    public function get_hour() {
        return $this->hour;
    }

    /**
     * Setter for $month.
     * @param string $month.
     */
    public function set_month($month) {
        $this->month = $month;
    }

    /**
     * Getter for $month.
     * @return string
     */
    public function get_month() {
        return $this->month;
    }

    /**
     * Setter for $day.
     * @param string $day.
     */
    public function set_day($day) {
        $this->day = $day;
    }

    /**
     * Getter for $day.
     * @return string
     */
    public function get_day() {
        return $this->day;
    }

    /**
     * Setter for $faildelay.
     * @param int $faildelay.
     */
    public function set_fail_delay($faildelay) {
        $this->faildelay = $faildelay;
    }

    /**
     * Getter for $faildelay.
     * @return int
     */
    public function get_fail_delay() {
        return $this->faildelay;
    }

    /**
     * Setter for $dayofweek.
     * @param string $dayofweek.
     */
    public function set_dayofweek($dayofweek) {
        $this->dayofweek = $dayofweek;
    }

    /**
     * Getter for $dayofweek.
     * @return string
     */
    public function get_dayofweek() {
        return $this->dayofweek;
    }

    /**
     * Take a cron field definition and return an array of valid numbers with the range min-max.
     *
     * @param string $field - The field definition.
     * @param int $min - The minimum allowable value.
     * @param int $max - The maximum allowable value.
     * @return array(int)
     */
    public function eval_cron_field($field, $min, $max) {
        // Cleanse the input.
        $field = trim($field);

        // Format for a field is:
        // <fieldlist> := <range>(/<step>)(,<fieldlist>)
        // <step>  := int
        // <range> := <any>|<int>|<min-max>
        // <any>   := *
        // <min-max> := int-int
        // End of format BNF.

        // This function is complicated but is covered by unit tests.
        $range = array();

        $matches = array();
        preg_match_all('@[0-9]+|\*|,|/|-@', $field, $matches);

        $last = 0;
        $inrange = false;
        $instep = false;

        foreach ($matches[0] as $match) {
            if ($match == '*') {
                array_push($range, range($min, $max));
            } else if ($match == '/') {
                $instep = true;
            } else if ($match == '-') {
                $inrange = true;
            } else if (is_numeric($match)) {
                if ($instep) {
                    $i = 0;
                    for ($i = 0; $i < count($range[count($range)-1]); $i++) {
                        if (($i) % $match != 0) {
                            $range[count($range)-1][$i] = -1;
                        }
                    }
                    $inrange = false;
                } else if ($inrange) {
                    if (count($range)) {
                        $range[count($range)-1] = range($last, $match);
                    }
                    $inrange = false;
                } else {
                    if ($match >= $min && $match <= $max) {
                        array_push($range, $match);
                    }
                    $last = $match;
                }
            }
        }

        // Flatten the result.
        $result = array();
        foreach ($range as $r) {
            if (is_array($r)) {
                foreach ($r as $rr) {
                    if ($rr >= $min && $rr <= $max) {
                        $result[$rr] = 1;
                    }
                }
            } else if (is_numeric($r)) {
                if ($r >= $min && $r <= $max) {
                    $result[$r] = 1;
                }
            }
        }
        $result = array_keys($result);
        sort($result, SORT_NUMERIC);
        return $result;
    }

    /**
     * Assuming $list is an ordered list of items, this function returns the item
     * in the list that is greater than or equal to the current value (or 0). If
     * no value is greater than or equal, this will return the first valid item in the list.
     * If list is empty, this function will return 0.
     *
     * @param int $current The current value
     * @param array(int) $list The list of valid items.
     * @return int $next.
     */
    private function next_in_list($current, $list) {
        foreach ($list as $l) {
            if ($l >= $current) {
                return $l;
            }
        }
        if (count($list)) {
            return $list[0];
        }

        return 0;
    }

    /**
     * Calculate when this task should next be run based on the schedule.
     * @return int $nextruntime.
     */
    public function get_next_scheduled_time() {
        $validminutes = $this->eval_cron_field($this->minute, 0, 59);
        $validhours = $this->eval_cron_field($this->hour, 0, 23);
        $validdays = $this->eval_cron_field($this->day, 0, 31);
        $validdaysofweek = $this->eval_cron_field($this->dayofweek, 0, 7);
        $validmonths = $this->eval_cron_field($this->month, 0, 11);

        $currentminute = date("i");
        $currenthour = date("H");
        $currentday = date("j");
        $currentmonth = date("n");
        $currentdayofweek = date("w");

        $nextvalidminute = $this->next_in_list($currentminute, $validminutes);
        $nextvalidhour = $this->next_in_list($currenthour, $validhours);
        $nextvaliddayofmonth = $this->next_in_list($currentday, $validdays);
        $nextvalidmonth = $this->next_in_list($currentmonth, $validmonths);
        $nextvaliddayofweek = $this->next_in_list($currentdayofweek, $validdaysofweek);

        // Special handling for dayofmonth vs dayofweek:
        // if either field is * - use the other field
        // otherwise - choose the soonest (see man 5 cron).

        // Work out the next valid time based on the days of the month.
        $nexttimebasedondayofmonth = mktime($nextvalidhour,
                                           $nextvalidminute,
                                           0,
                                           $nextvalidmonth,
                                           $nextvaliddayofmonth,
                                           date("Y"));

        // This function is complicated but is covered by unit tests.
        // Work out the next valid time based on the days of the week.
        if ($nextvaliddayofweek < $currentdayofweek) {
            $nextvaliddayofweek += 7;
        }
        $nextday = $currentday + ($nextvaliddayofweek - $currentdayofweek);

        $nexttimebasedondayofweek = mktime($nextvalidhour,
                                           $nextvalidminute,
                                           0,
                                           $nextvalidmonth,
                                           $nextday,
                                           date("Y"));

        // Work out which next valid time to use.
        if ($this->dayofweek == '*') {
            $nexttime = $nexttimebasedondayofmonth;
        } else if ($this->dayofmonth == '*') {
            $nexttime = $nexttimebasedondayofweek;
        } else {
            $nexttime = $nexttimebasedondayofmonth;
            if ($nexttimebasedondayofweek < $nexttime) {
                $nexttime = $nexttimebasedondayofweek;
            }
        }

        return $nexttime;
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public abstract function execute();

}
