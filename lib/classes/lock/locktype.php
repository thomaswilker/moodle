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
interface locktype {

    /**
     * Return information about the blocking behaviour of the lock type on this platform.
     * @return boolean - True if attempting to get a lock will block indefinitely.
     */
    public function supports_timeout();

    /**
     * Will this lock be automatically released when the process ends.
     * This should never be relied upon in code - but is useful in the case of
     * fatal errors. If a lock type does not support this auto release,
     * the max lock time parameter must be obeyed to eventually clean up a lock.
     * @return boolean - True if this lock type will be automatically released when the current process ends.
     */
    public function supports_auto_release();

    /**
     * supports_recursion
     * @return boolean - True if attempting to get 2 locks on the same resource will "stack"
     */
    public function supports_recursion();

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available();

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     *                       Not all lock types will support this.
     * @param int $maxlifetime - The number of seconds to wait before reclaiming a stale lock.
     *                       Not all lock types will use this - e.g. if they support auto releasing
     *                       a lock when a process ends.
     * @return boolean - True if a lock was obtained.
     */
    public function lock($resource, $timeout, $maxlifetime = 86400);

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - True if the lock is no longer held (including if it was never held).
     */
    public function unlock();
}
