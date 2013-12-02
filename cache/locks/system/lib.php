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
 * File locking for the Cache API
 *
 * @package    cachelock_system
 * @category   cache
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * File locking plugin
 *
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachelock_system implements cache_lock_interface {

    /**
     * The name of the cache lock instance
     * @var string
     */
    protected $name;

    /**
     * The lock factory that gives us juicy locks.
     * @var string
     */
    protected $lockfactory;

    /**
     * The maximum life in seconds for a lock file. By default null for none.
     * @var int|null
     */
    protected $maxlife = null;

    /**
     * The max time to wait for a lock (seconds).
     * @var int
     */
    protected $waittimeout = 1;

    /**
     * An array containing the locks that have been acquired but not released so far.
     * @var array Array of key => lock file path
     */
    protected $locks = array();

    /**
     * Initialises the cache lock instance.
     *
     * @param string $name The name of the cache lock
     * @param array $configuration
     */
    public function __construct($name, array $configuration = array()) {
        $this->name = $name;

        if (array_key_exists('maxlife', $configuration) && is_number($configuration['maxlife'])) {
            $maxlife = (int)$configuration['maxlife'];
            // Minimum lock time is 60 seconds.
            $this->maxlife = max($maxlife, 60);
        }
        if (array_key_exists('waittimeout', $configuration) && is_number($configuration['waittimeout'])) {
            $this->waittimeout = (int)$configuration['waittimeout'];
        }
        $this->lockfactory = \core\lock\lock_config::get_lock_factory('cache');
    }

    /**
     * Acquire a lock.
     *
     * If the lock can be acquired:
     *      This function will return true.
     *
     * If the lock cannot be acquired the result of this method is determined by the block param:
     *      $block = true (default)
     *          The function will block any further execution unti the lock can be acquired.
     *          This involves the function attempting to acquire the lock and the sleeping for a period of time. This process
     *          will be repeated until the lock is required or until a limit is hit (100 by default) in which case a cache
     *          exception will be thrown.
     *      $block = false
     *          The function will return false immediately.
     *
     * If a max life has been specified and the lock can not be acquired then the lock file will be checked against this time.
     * In the case that the file exceeds that max time it will be forcefully deleted.
     * Because this can obviously be a dangerous thing it is not used by default. If it is used it should be set high enough that
     * we can be as sure as possible that the executing code has completed.
     *
     * @param string $key The key that we want to lock
     * @param string $ownerid A unique identifier for the owner of this lock. Not used by default.
     * @param bool $block True if we want the program block further execution until the lock has been acquired.
     * @return bool
     * @throws cache_exception If block is set to true and more than 100 attempts have been made to acquire a lock.
     */
    public function lock($key, $ownerid, $block = false) {
        // Get the name of the lock file we want to use.
        // This is just an estimate based on the assumption that a single lock attempt would take 0.01 seconds.
        // It is definitely not accurate - but will do for this use case.
        $lock = $this->lockfactory->get_lock($key, $this->waittimeout, $this->maxlife);

        if (!$lock) {
            return false;
        }
        $this->locks[$key] = $lock;
        return true;
    }

    /**
     * Releases an acquired lock.
     *
     * For more details see {@link cache_lock::unlock()}
     *
     * @param string $key
     * @param string $ownerid A unique identifier for the owner of this lock. Not used by default.
     * @param bool $forceunlock If set to true the lock will be removed if it exists regardless of whether or not we own it.
     * @return bool
     */
    public function unlock($key, $ownerid, $forceunlock = false) {
        if (array_key_exists($key, $this->locks)) {
            $this->locks[$key]->release();
            unset($this->locks[$key]);
            return true;
        }
        // You cannot unlock a file you didn't lock.
        return false;
    }

    /**
     * Checks if the given key is locked.
     *
     * @param string $key
     * @param string $ownerid
     */
    public function check_state($key, $ownerid) {
        if (array_key_exists($key, $this->locks)) {
            // The key is locked and we own it.
            return true;
        }
        if ($lock = $this->lock_factory->get_lock($key, 0)) {
            $lock->release();
            return false;
        }
        // Someone else has this lock.
        return true;
    }

    /**
     * Cleans up the instance what it is no longer needed.
     */
    public function __destruct() {
        foreach ($this->locks as $lock) {
            // Naught, naughty developers.
            $lock->release();
        }
    }
}
