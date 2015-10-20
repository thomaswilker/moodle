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
namespace core\form;

use templatable;
use renderer_base;

/**
 * Abstract base class for all types of form elements.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class element implements templatable {

    /** @var string $name All form elements should have a unique name. */
    protected $name = '';

    /** @var mixed $value All form elements should have a way to get and set the value. */
    protected $value = null;

    /** @var string $label All elements should have a label. */
    protected $label = '';

    /** @var string $help Elements can have help. */
    protected $help = '';

    /** @var string $disabled All elements are enabled by default, but can be disabled. */
    protected $disabled = false;

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = $name;
    }

    public function get_help() {
        return $this->help;
    }

    public function set_help($help) {
        $this->help = $help;
    }

    public function set_disabled($disabled) {
        return $this->disabled;
    }

    public function is_disabled() {
        return $this->disabled;
    }

    public function get_value() {
        return $this->value;
    }

    public function set_value($value) {
        $this->value = $value;
    }

    public function get_label() {
        return $this->label;
    }

    public function set_label($label) {
        $this->label = $label;
    }

    public abstract function get_type();

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
            'name' => $this->get_name(),
            'value' => $this->get_value(),
            'label' => $this->get_label(),
            'help' => $this->get_help(),
            'disabled' => $this->is_disabled(),
            'type' => $this->get_type(),
        );
    }

}
