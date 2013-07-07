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

namespace core_lock;

defined('MOODLE_INTERNAL') || die();

class manager {
    /**
     * Get an instance of the currently configured locking subclass.
     */
    public static function get_current_lock_type() {
        global $CFG;

        $type = $CFG->locktype;

        if (!$type) {
            // Simple no configuration default is better than nothing.
            $type = 'flock';
        }

        $classname = '\\locktype_' . $type . '\\lock';
        return new $classname;
    }

    /**
     * Get a list of all the known lock types in this install.
     * return array($type=>$class, ...) all the known lock types.
     */
    public static function get_all_lock_types() {
        $plugins = \core_component::get_plugin_list('locktype');
        $locktypes = array();

        foreach ($plugins as $type => $path) {
            $classname = '\\locktype_' . $type . '\\lock';
            $locktypes[$type] = new $classname();
        }
        return $locktypes;
    }

    /**
     * Get a list of all the available lock types in this install.
     * return array($type=>$class, ...) all the available lock types.
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
