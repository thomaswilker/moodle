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
 * lock unit tests
 *
 * @package    lock
 * @category   phpunit
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Unit tests for our locking implementations.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lock_testcase extends advanced_testcase {

    /**
     * Some lock types will store data in the database.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Tests the static parse charset method
     * @return void
     */
    public function test_locks() {
        $locktypes = \core\lock\manager::get_all_lock_types();

        // Insert special upgrade lock type (not returned by default).
        $locktypes['upgrade'] = new \core\lock\upgrade();

        foreach ($locktypes as $lock1) {
            $lock2 = clone $lock1;

            if ($lock1->is_available()) {
                // This should work.
                $this->assertTrue($lock1->lock('abc', 2), 'Get a lock');
                if (!$lock1->is_blocking()) {
                    // This should timeout.
                    if ($lock1->is_stackable()) {
                        $this->assertTrue($lock2->lock('abc', 2), 'Get a stacked lock');
                        $this->assertTrue($lock2->unlock(), 'Release a stacked lock');
                    } else {
                        $this->assertFalse($lock2->lock('abc', 2), 'Cannot get a stacked lock');
                    }
                }
                // Release the lock.
                $this->assertTrue($lock1->unlock(), 'Release a lock');
                // Get it again.
                $this->assertTrue($lock1->lock('abc', 2), 'Get a lock again');
                // Release the lock again.
                $this->assertTrue($lock1->unlock(), 'Release a lock again');
                // Release the lock again (shouldn't hurt).
                $this->assertTrue($lock1->unlock(), 'Release a lock that is not held');
                if (!$lock1->is_auto_released()) {
                    // Test that a lock can be claimed after the timeout period.
                    $this->assertTrue($lock1->lock('abc', 2, 2), 'Get a lock');
                    sleep(3);
                    $this->assertTrue($lock2->lock('abc', 2, 2), 'Get another lock after a timeout');
                    $this->assertTrue($lock2->unlock(), 'Release the lock');
                }
            }
        }

    }
}

