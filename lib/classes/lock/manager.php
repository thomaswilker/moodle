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
 * Lock Manager class.
 *
 * Used to get and configure locks.
 *
 * @package   core
 * @category  lock
 * @copyright Damyon Wiese 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Get an instance of the currently configured locking subclass.
     * @return \core\lock\locktype
     */
    public static function get_current_lock_type() {
        global $CFG;
        // Simple no configuration default is better than nothing.
        $type = '\\core\\lock\\file';

        if (!empty($CFG->locktype)) {
            if (class_exists($CFG->locktype) && ) {
                $type = $CFG->locktype;
            }
        }

        $lock = new $type();
        if ($lock instanceof \core\lock\locktype) {
            return $lock;
        }

        // This broken lock type will never return a valid lock.
        return new \core\lock\broken();
    }

    /**
     * Get a special lock type reserved for upgrades.
     * @return \core\lock\locktype
     */
    public static function get_upgrade_lock_type() {
        return new \core\lock\upgrade();
    }

}
