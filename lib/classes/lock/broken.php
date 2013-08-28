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
 * Defines a broken lock type that can never lock a resource.
 * Used when there is a configuration error.
 *
 * @package   core
 * @category  lock
 * @copyright Damyon Wiese 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class broken implements \core\lock\locktype {

    /**
     * Not important because this will never return valid lock.
     * @return boolean - always true
     */
    public function supports_timeout() {
        return true;
    }

    /**
     * Not important because this will never return valid lock.
     * @return boolean - always true
     */
    public function supports_auto_release() {
        return true;
    }

    /**
     * Not important because this will never return valid lock.
     * @return boolean - always false
     */
    public function is_available() {
        return false;
    }

    /**
     * Not important because this will never return valid lock.
     * @return boolean - always false
     */
    public function supports_recursion() {
        return false;
    }

    /**
     * Get a lock within the specified timeout or return false.
     * @param string $resource - Unused
     * @param int $timeout - Unused
     * @param int $maxlifetime - Unused
     * @return boolean - always false.
     */
    public function lock($resource, $timeout, $maxlifetime = 86400) {
        return false;
    }

    /**
     * Release a lock that was previously obtained with @lock.
     * @return boolean - always false.
     */
    public function unlock() {
        return false;
    }
}
