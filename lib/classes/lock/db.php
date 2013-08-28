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

    /** @var string $token A uniq token representing a held lock */
    protected $token = '';

    /** @var moodle_database $db Hold a reference to the global $DB */
    protected $db;

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        return true;
    }

    /**
     * Almighty constructor.
     */
    public function __construct() {
        global $DB;

        // Save a reference to the global $DB so it will not be released while we still have open locks.
        $this->db = $DB;
    }

    /**
     * Return information about the blocking behaviour of the lock type on this platform.
     * @return boolean - Defer to the DB driver.
     */
    public function supports_timeout() {
        return $this->db->is_lock_blocking();
    }

    /**
     * Will this lock type will be automatically released when a process ends.
     *
     * @return boolean - Defer to the DB driver.
     */
    public function supports_auto_release() {
        return $this->db->is_lock_auto_released();
    }

    /**
     * Multiple locks for the same resource can be held by a single process.
     * @return boolean - Defer to the DB driver.
     */
    public function supports_recursion() {
        return $this->db->is_lock_stackable();
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - Unused by this lock type.
     * @return boolean - true if a lock was obtained.
     */
    public function lock($resource, $timeout, $maxlifetime = 86400) {

        $this->token = $this->db->lock($resource, $timeout, $maxlifetime);

        return $this->token !== false;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function unlock() {

        return $this->db->unlock($this->token);
    }
}
