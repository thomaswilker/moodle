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
 * Base class for all types of form elements.
 *
 * @package    core
 * @category   form
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\form\trigger;

use core\form\trigger;
use core\form\element;
use renderer_base;

/**
 * Abstract base class for all types of form elements.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class invalidate extends trigger {

    /** @var string $message The message to show when this validation rule fails */
    protected $message = '';

    public function get_type() {
        return 'validate';
    }

    public function set_message($message) {
        $this->message = $message;
        return $this;
    }

    public function get_message() {
        return $this->message;
    }

    public function pass(element $element) {
        $element->set_error($this->message);
    }

    public function fail(element $element) {
        $element->set_error('');
    }

    public function __construct($message) {
        $this->set_message($message);
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $result = parent::export_for_template($output);
        $result['message'] = $this->get_message();
        return $result;
    }

}
