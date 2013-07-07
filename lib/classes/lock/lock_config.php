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
 * Defines locking apis.
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
class lock_config {

    /**
     * Get an instance of the currently configured locking subclass.
     * @return \core\lock\locktype
     */
    public static function get_lock_factory($type) {
        global $CFG;

        // Upgrade locks are special - non configurable.
        if ($type == 'upgrade') {
            return new \core\lock\upgrade_lock_factory('upgrade', array());
        }
        $lockconfig = array();
        if (isset($CFG->lock_config)) {
            $lockconfig = $CFG->lock_config;
        }

        // Simple no configuration default is better than nothing.
        $lockfactoryclass = '\core\lock\db_lock_factory';
        $lockfactoryoptions = array();

        if (isset($lockconfig[$type]['factory']) &&
                class_exists($lockconfig[$type]['factory'])) {
            $lockfactoryclass = $lockconfig[$type]['factory'];
            if (isset($lockconfig[$type]['options'])) {
                $lockfactoryoptions = $lockconfig[$type]['options'];
            }
        } else if (isset($lockconfig['default']['factory']) &&
                class_exists($lockconfig['default']['factory'])) {
            $lockfactoryclass = $lockconfig['default']['factory'];
            if (isset($lockconfig['default']['options'])) {
                $lockfactoryoptions = $lockconfig['default']['options'];
            }
        }

        $lockfactory = new $lockfactoryclass($type, $lockfactoryoptions);
        return $lockfactory;
    }

}
