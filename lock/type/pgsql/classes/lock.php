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

namespace locktype_pgsql;

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

    private static $cache = array();

    private $index = 0;

    /**
     * This function generates the unique index for a specific lock key.
     * Once an index is assigned to a key, it never changes - so this is
     * statically cached.
     */
    protected function get_index_from_key($key) {
        global $DB;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // 0 is defined as a lock for the index generation table.
        $index = $DB->get_record('locktype_pgsql', array('key'=>$key));

        if (!$index) {
            $record = new \stdClass();
            $record->key = $key;
            try {
                $index = $DB->insert_record('locktype_pgsql', $record);
            } catch (dml_exception $de) {
                // Race condition - never mind - now the value is guaranteed to exist.
                $index = $DB->get_record('locktype_pgsql', array('key'=>$key));
            }
        }

        if (!$index) {
            throw new moodle_exception('Could not generate unique index for key');
        }

        self::$cache[$key] = $index;
        return $index;
    }

    /**
     * Return information about the blocking behaviour of the lock type on this platform.
     * @return boolean - True if attempting to get a lock will block indefinitely.
     */
    public function is_blocking() {
        return false;
    }

    /**
     * Multiple locks for the same resource can be held by a single process.
     * @return boolean - True
     */
    public function is_stackable() {
        return true;
    }

    /**
     * Is available.
     * @return boolean - True if this lock type is available in this environment.
     */
    public function is_available() {
        global $CFG;
        return $CFG->dbtype === 'pgsql';
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - The identifier for the lock. Should use frankenstyle prefix.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @return boolean - true if a lock was obtained.
     */
    public function lock($resource, $timeout) {
        global $DB;

        $this->index = $this->get_index_from_key($resource);

        $result = $DB->get_record_sql('select pg_try_advisory_lock(:index) AS locked', array('index'=>$this->index));
        $locked = (bool)($result->locked);

        // Try until the giveup time.
        while (!$locked && time() < $giveuptime) {
            usleep(rand(10000, 250000)); // Sleep between 10 and 250 milliseconds.
            $result = $DB->get_record_sql('select pg_try_advisory_lock(:index) AS locked', array('index'=>$this->index));
            $locked = (bool)($result->locked);
        }

        return $locked;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function unlock() {
        global $DB;

        $result = $DB->get_record_sql('select pg_advisory_unlock(:index) AS unlocked', array('index'=>$this->index));
        return (bool)$result->unlocked;
    }
}
