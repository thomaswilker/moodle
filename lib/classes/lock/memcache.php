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
 * Memcache lock.
 *
 * @package    core
 * @category   lock
 * @copyright  Damyon Wiese 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\lock;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines api for locking using memcache (including separate cluster nodes)
 *
 * @package   core
 * @category  lock
 * @copyright Damyon Wiese 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class memcache implements \core\lock\locktype {

    /** @var string $key - The key for the lock. */
    protected $key;

    /** @var Memcache $connection - The connection to the memcache server */
    protected $memcache;

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        global $CFG;

        return !empty($CFG->memcachelockserver) && class_exists('Memcache');
    }

     /**
      * Return information about the blocking behaviour of the lock type on this platform.
      * @return boolean - Defer to the DB driver.
      */
    public function supports_timeout() {
        return false;
    }

    /**
     * This lock type will NOT be automatically released when a process ends.
     * @return boolean - False
     */
    public function supports_auto_release() {
        return false;
    }

    /**
     * Multiple locks for the same resource can be held by a single process.
     * @return boolean - True
     */
    public function supports_recursion() {
        return false;
    }

    /**
     * Given a resource, generate a unique key (unique across sites).
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @return string - A unique key for the lock.
     */
    protected function generate_key($resource) {
        global $CFG;

        return sha1($CFG->siteidentifier . '_' . $resource);
    }

    /**
     * Open a connection to the memcache servers.
     * @return bool - No error reported for any servers.
     */
    protected function open_connection() {
        global $CFG;

        $this->connection = new \Memcache();

        $server = trim($CFG->memcachelockserver);
        if (empty($server)) {
            return false;
        }

        $hostportweight = explode(':', $server);
        $host = $hostportweight[0];
        if (count($hostportweight) > 1) {
            $port = $hostportweight[1];
        } else {
            $port = 11211;
        }
        if (count($hostportweight) > 1) {
            $weight = $hostportweight[2];
        } else {
            $weight = 100;
        }

        if (!$this->connection->addServer($host, $port, true, $weight)) {
            return false;
        }
        return true;
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - Unused by this lock type.
     * @return boolean - true if a lock was obtained.
     */
    public function lock($resource, $timeout, $maxlifetime = 86400) {
        $giveuptime = time() + $timeout;

        if (!$this->is_available()) {
            return false;
        }

        if (!$this->open_connection()) {
            return false;
        }

        $this->key = $this->generate_key($resource);
        $locked = $this->connection->add($this->key, 1, 0, $maxlifetime);

        do {
            $locked = $this->connection->add($this->key, 1, 0, $maxlifetime);
            if (!$locked) {
                usleep(rand(10000, 250000)); // Sleep between 10 and 250 milliseconds.
            }
            // Try until the giveuptime.
        } while (!$locked && time() < $giveuptime);

        return $locked;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function unlock() {
        $result = $this->connection->delete($this->key);
        $this->connection->close();
        return $result;
    }
}
