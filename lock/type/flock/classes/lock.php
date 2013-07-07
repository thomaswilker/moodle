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

namespace locktype_flock;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines api for locking (including separate cluster nodes)
 *
 * @package   core
 * @category  lock
 * @copyright Damyon Wiese 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lock implements \core_lock\locktype {

    private $lockfile;

    /**
     * Return information about the blocking behaviour of the lock type on this platform.
     * @return boolean - True if attempting to get a lock will block indefinitely.
     */
    public function is_blocking() {
        global $CFG;

        return $CFG->ostype === 'WINDOWS';
    }

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        return true;
    }

    /**
     * Multiple locks for the same resource cannot be held from a single process.
     * @return boolean - False
     */
    public function is_stackable() {
        return false;
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @return boolean - true if a lock was obtained.
     */
    public function lock($resource, $timeout) {
        global $CFG;
        $giveuptime = time() + $timeout;

        $hash = md5($resource);
        $lockdir = $CFG->dataroot . '/locks/' . substr($hash, 0, 2);

        @mkdir($lockdir, 0755, true);

        $lockfilename = $lockdir . '/' . $hash;

        $this->lockfile = fopen($lockfilename, "wb");

        // Could not open the lock file.
        if (!$this->lockfile) {
            return false;
        }

        // Will block on windows. So sad.
        $wouldblock = false;
        $locked = flock($this->lockfile, LOCK_EX|LOCK_NB, $wouldblock);

        // Try until the giveup time.
        while (!$locked && $wouldblock && time() < $giveuptime) {
            usleep(rand(10000, 250000)); // Sleep between 10 and 250 milliseconds.
            $locked = flock($this->lockfile, LOCK_EX|LOCK_NB, $wouldblock);
        }

        if (!$locked) {
            fclose($this->lockfile);
            $this->lockfile = 0;
        }
        return $locked;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function unlock() {
        if (!$this->lockfile) {
            // We didn't have a lock - but it is defintely not locked.
            return true;
        }

        $result = flock($this->lockfile, LOCK_UN);
        fclose($this->lockfile);
        $this->lockfile = 0;
        return $result;
    }
}
