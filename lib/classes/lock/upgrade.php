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
 * Special lock type that is used for locking the upgrade code.
 * It uses the config_plugins table to store the lock which consists
 * of a unique token and a timeout. It is not returned by the default
 * {@link \core\lock\manager::get_available_lock_types()} because
 * it should not be used for anything but upgrade code.
 *
 * @package    core
 * @category   lock
 * @copyright  Damyon Wiese 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\lock;

defined('MOODLE_INTERNAL') || die();

/**
 * Lock the upgrade - even for clustered systems.
 * Must not rely on any DB tables that have not existed in core since 2.2.
 *
 * @package   core
 * @category  lock
 * @copyright Damyon Wiese 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgrade implements \core\lock\locktype {

    private $token = '';

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
        return false;
    }

    /**
     * This lock type will NOT be automatically released when a process ends.
     * @return boolean - False
     */
    public function is_auto_released() {
        return false;
    }

    /**
     * Multiple locks for the same resource can be held by a single process.
     * @return boolean - True
     */
    public function is_stackable() {
        return false;
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - Ignored for this lock type - there is only one upgrade lock.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - Set or extend the lifetime of this lock.
     * @return boolean - true if a lock was obtained.
     */
    public function lock($resource, $timeout, $maxlifetime = 86400) {
        global $DB;

        $now = time();
        $giveuptime = $now + $timeout;
        $expires = $now + $maxlifetime;

        // See if we already have a lock.
        if (!$this->token) {
            $this->token = $DB->generate_unique_token();
        } else {
            // Extending a held lock.
            $params = array('expires' => $expires,
                            'token' => $this->token,
                            'plugin' => 'core_upgradelock',
                            'now' => $now);
            $cast = $DB->sql_cast_char2int('value');
            $sql = 'UPDATE {config_plugins}
                        SET
                            value = :expires
                        WHERE
                        (name = :token AND plugin = :plugin AND ' . $cast . ' < :now)';
            $result = $DB->execute($sql, $params);
            return $result;
        }
        if (!$DB->record_exists('config_plugins', array('plugin'=>'core_upgradelock'))) {
            $record = new \stdClass();
            $record->plugin = 'core_upgradelock';
            $record->name = '';
            // Use 0 for no timeout so char2int does not throw an error.
            $record->value = '0';
            $result = $DB->insert_record('config_plugins', $record);
        }

        $params = array('expires' => $expires,
                        'token' => $this->token,
                        'noowner'=> '',
                        'plugin' => 'core_upgradelock',
                        'now' => $now);

        // Value column is char - not int.
        $cast = $DB->sql_cast_char2int('value');
        $sql = 'UPDATE {config_plugins}
                    SET
                        value = :expires,
                        name = :token
                    WHERE
                        plugin = :plugin AND
                        (name = :noowner OR ' . $cast . ' < :now)';

        $DB->execute($sql, $params);

        $countparams = array('plugin'=>'core_upgradelock', 'name'=>$this->token);
        $result = $DB->count_records('config_plugins', $countparams);
        $locked = $result === 1;

        // Try until the giveup time.
        while (!$locked && $now < $giveuptime) {
            usleep(rand(10000, 250000)); // Sleep between 10 and 250 milliseconds.
            $now = time();
            $params['now'] = $now;
            $DB->execute($sql, $params);
            $result = $DB->count_records('config_plugins', $countparams);
            $locked = $result === 1;
        }

        return $locked;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - true if the lock is no longer held (including if it was never held).
     */
    public function unlock() {
        global $DB;

        $params = array('upgradelock'=>'core_upgradelock',
                        'noexpires' => '0',
                        'token' => $this->token,
                        'noowner' => '');

        $sql = 'UPDATE {config_plugins}
                    SET
                        value = :noexpires,
                        name = :noowner
                    WHERE
                        name = :token AND
                        plugin = :upgradelock';
        $DB->execute($sql, $params);

        // Count the records to see if we released the lock.
        $countparams = array('plugin'=>'core_upgradelock','name'=>$this->token);
        $result = $DB->count_records('config_plugins', $countparams);
        $unlocked = $result === 0;

        return $unlocked;
    }
}
