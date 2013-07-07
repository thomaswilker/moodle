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
 * Defines locking apis
 *
 * @package    core
 * @category   lock
 * @copyright  Damyon Wiese 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\lock;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines api for locking (including separate cluster nodes)
 *
 * @package   core
 * @category  lock
 * @copyright Damyon Wiese 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class db implements \core\lock\locktype {

    protected $token = '';

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        return true;
    }

     /**
     * Return information about the blocking behaviour of the lock type on this platform.
     * @return boolean - Defer to the DB driver.
     */
    public function is_blocking() {
        global $DB;
        return $DB->is_lock_blocking();
    }

    /**
     * This lock type will NOT be automatically released when a process ends.
     * @return boolean - False
     */
    public function is_auto_released() {
        global $DB;
        return $DB->is_lock_auto_released();
    }

    /**
     * Multiple locks for the same resource can be held by a single process.
     * @return boolean - True
     */
    public function is_stackable() {
        global $DB;
        return $DB->is_lock_stackable();
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - Unused by this lock type.
     * @return boolean - true if a lock was obtained.
     */
    public function lock($resource, $timeout, $maxlifetime = 86400) {
        global $DB;

        $this->token = $DB->lock($resource, $timeout, $maxlifetime);

        return $this->token !== false;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function unlock() {
        global $DB;

        return $DB->unlock($this->token);
    }
}
