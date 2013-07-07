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

class manager {
    /**
     * Get an instance of the currently configured locking subclass.
     * @return \core\lock\locktype
     */
    public static function get_current_lock_type() {
        global $CFG;

        if (!empty($CFG->locktype)) {
            $type = $CFG->locktype;
        } else {
            // Simple no configuration default is better than nothing.
            $type = 'file';
        }

        $classname = '\\core\\lock\\' . $type;
        return new $classname;
    }

    /**
     * Get a special lock type reserved for upgrades.
     * @return \core\lock\locktype
     */
    public static function get_upgrade_lock_type() {
        return new \core\lock\upgrade();
    }

    /**
     * Get a list of all the known lock types in this install.
     * return array($type=>\core\lock\locktype, ...) all the known lock types.
     */
    public static function get_all_lock_types() {
        $locktypes = array('db'=>new \core\lock\db(),
                           'file'=>new \core\lock\file(),
                           'memcache'=>new \core\lock\memcache());

        return $locktypes;
    }

    /**
     * Get a list of all the available lock types in this install.
     * return array($type=>\core\lock\locktype, ...) all the available lock types.
     */
    public static function get_available_lock_types() {
        $locktypes = self::get_all_lock_types();
        $availabletypes = array();

        foreach ($locktypes as $name => $locktype) {
            if ($locktype->is_available()) {
                $availabletypes[$name] = $locktype;
            }
        }
        return $availabletypes;
    }

}
