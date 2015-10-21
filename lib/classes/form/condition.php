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
 * Form.
 *
 * @package    core
 * @category   form
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\form;

use templatable;
use renderer_base;

/**
 * Abstract class.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class condition implements templatable {


    /** @var boolean $javascript Some conditions can only be evaluated on the server */
    protected $javascript = false;

    /** @var element $element The element to test for this condition */
    protected $element = null;

    public abstract function get_type();

    public function set_javascript($javascript) {
        $this->javascript = $javascript;
        return $this;
    }

    public function is_javascript() {
        return $this->javascript;
    }

    public function set_element($element) {
        $this->element = $element;
        return $this;
    }

    public function get_element() {
        return $this->element;
    }

    public function __construct($element = null) {
        $this->set_element($element);
    }

    /**
     * Evaluate this condition and return true or false.
     *
     * @param element $element Form element to evaluate.
     * @return stdClass|array
     */
    public abstract function evaluate();

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
        return array(
            'type' => $this->get_type(),
            'javascript' => $this->is_javascript(),
            'element' => $this->element->get_id()
        );
    }

}
