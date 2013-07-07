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
class memcache_lock_factory implements \core\lock\lock_factory {

    /** @var Memcache $connection - The connection to the memcache server */
    protected $connection;

    /** @var string $type - The type of locking this factory is being used for */
    protected $type;

    /** @var array $options - The connection options (cached until a connection is opened). */
    protected $options;

    /** @var boolean $verbose - If true, debugging info about the owner of the lock will be written to the lock file. */
    protected $verbose;

    /**
     * Create an instance of this class.
     * The configuration for the memcache server is in options.
     * If the configuration is bad, or the Memcache extension is not loaded,
     * this factory will never return a lock.
     * @param $options[] - Array of options for this factory.
     *                     The array should have one key "memcacheserver" e.g. "localhost:389".
     */
    public function __construct($type, $options) {
        $this->type = $type;
        $this->connection = null;
        $this->options = $options;
        $this->verbose = false;
        if (isset($options['verbose'])) {
            $this->verbose = !empty($options['verbose']);
        }
    }

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        if ($this->connection) {
            return true;
        }
        return $this->open_connection($this->options);
    }

     /**
      * Return information about the blocking behaviour of the lock type on this platform.
      * @return boolean - true - will timeout if it can't get a lock.
      */
    public function supports_timeout() {
        return true;
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

        return sha1($CFG->siteidentifier . '_' . $this->type . '_' . $resource);
    }

    /**
     * Open a connection to the memcache servers.
     * @return bool - No error reported for any servers.
     */
    protected function open_connection($options) {

        if (!class_exists('\Memcache')) {
            return false;
        }

        $connection = new \Memcache();

        $server = trim($options['memcacheserver']);
        if (empty($server)) {
            return false;
        }

        $hostport = explode(':', $server);
        $host = $hostport[0];
        if (count($hostport) > 1) {
            $port = $hostport[1];
        } else {
            $port = 11211;
        }

        if (!$connection->addServer($host, $port, true, 100)) {
            return false;
        }
        $this->connection = $connection;
        return true;
    }

    /**
     * Get some info that might be useful for debugging.
     * @return boolean - string
     */
    protected function get_debug_info() {
        return 'host:' . php_uname('n') . ', pid:' . getmypid() . ', time:' . time();
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - Unused by this lock type.
     * @return lock|false - An open lock, or false if a lock could not be obtained.
     */
    public function get_lock($resource, $timeout, $maxlifetime = 86400) {
        $giveuptime = time() + $timeout;

        if (!$this->is_available()) {
            return false;
        }
        $value = 1;
        if ($this->verbose) {
            $value = $this->get_debug_info();
        }

        $key = $this->generate_key($resource);
        $locked = false;

        do {
            $locked = $this->connection->add($key, $value, 0, $maxlifetime);
            if (!$locked) {
                usleep(rand(10000, 250000)); // Sleep between 10 and 250 milliseconds.
            }
            // Try until the giveuptime.
        } while (!$locked && time() < $giveuptime);

        if (!$locked) {
            return false;
        }
        return new lock($key, $this);
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @param lock $lock - A lock obtained from this factory.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function release_lock(lock $lock) {
        return $this->connection->delete($lock->get_key());
    }

    /**
     * Extend a lock that was previously obtained with @lock.
     * @param lock $lock - a lock obtained from this factory.
     * @return boolean - true if the lock was extended.
     */
    public function extend_lock(lock $lock, $maxlifetime = 86400) {
        // Not supported by this factory.
        return false;
    }

    /**
     * Release resources.
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
