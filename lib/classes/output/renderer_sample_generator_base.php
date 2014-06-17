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
 * Abstract class that generates a list of renderer_sample_base classes.
 *
 * @package    core
 * @category   output
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\output;

/**
 * Abstract class for common properties of scheduled_task and adhoc_task
 * for display in the element library. Every plugin type is able to create their
 * own instance of a renderer_sample_generator_base to display the renderables defined
 * in that plugin.
 *
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class renderer_sample_generator_base {

    /**
     * Generate a list of renderer_sample_base instances.
     *
     * @return renderer_sample_base[] Array of renderer_sample classes.
     */
    public abstract function create_samples();
}
