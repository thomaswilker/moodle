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
 * Adhoc task abstract class.
 *
 * @package    core
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\task;

/**
 * Abstract class defining an adhoc task.
 */
abstract class adhoc_task {

    /** @var bool $blocking - Does this task block the entire cron process. */
    private $blocking = false;

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
     * Setter for $customdata.
     * @param string $customdata.
     */
    public function set_customdata($customdata) {
        $this->customdata = json_encode($customdata);
    }

    /**
     * Getter for $customdata.
     * @return string
     */
    public function get_customdata() {
        return json_decode($this->customdata);
    }

    /**
     * Setter for $nextruntime.
     * @param int $nextruntime.
     */
    public function set_nextruntime($nextruntime) {
        $this->nextruntime = $nextruntime;
    }

    /**
     * Getter for $nextruntime.
     * @return int
     */
    public function get_nextruntime() {
        return $this->nextruntime;
    }

    /**
     * Do the job.
     * Throw exceptions on errors. The job will be retried.
     */
    public abstract function execute();

}
