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
use renderable;
use renderer_base;

/**
 * Abstract base class for all types of form elements.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class element implements templatable, renderable {

    /** @var string $id All elements should have a unique id. */
    protected $id = null;

    /** @var string $name All form elements should have a unique name. */
    protected $name = '';

    /** @var mixed $value All form elements should have a way to get and set the value. */
    protected $value = null;

    /** @var string $label All elements should have a label. */
    protected $label = '';

    /** @var string $labelvisible Sometimes it is better to only show the label to screen readers */
    protected $labelvisible = true;

    /** @var string $fieldvisible Some fields are not visible at all (hidden fields) */
    protected $visible = true;

    /** @var string $help Elements can have help. */
    protected $help = '';

    /** @var string $error Elements can have errors. */
    protected $error = '';

    /** @var string $disabled All elements are enabled by default, but can be disabled. */
    protected $disabled = false;

    /** @var array $rules List of validation rules to apply to this field. */
    protected $rules = array();

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = $name;
        return $this;
    }

    public function get_error() {
        return $this->error;
    }

    public function set_error($error) {
        $this->error = $error;
        return $this;
    }

    public function get_help() {
        return $this->help;
    }

    public function set_help($help) {
        $this->help = $help;
        return $this;
    }

    public function set_visible($visible) {
        $this->visible = $visible;
        return $this;
    }

    public function is_visible() {
        return $this->visible;
    }

    public function set_disabled($disabled) {
        $this->disabled = $disabled;
        return $this;
    }

    public function is_disabled() {
        return $this->disabled;
    }

    public function get_value() {
        return $this->value;
    }

    public function set_value($value) {
        $this->value = $value;
        return $this;
    }

    public function get_label() {
        return $this->label;
    }

    public function set_label($label) {
        $this->label = $label;
        return $this;
    }

    public function set_label_visible($visible) {
        $this->labelvisible = $visible;
        return $this;
    }

    public function is_label_visible() {
        return $this->labelvisible;
    }

    public function add_rule($rule) {
        array_push($this->rules, $rule);
        return $rule;
    }

    public function get_rule($id) {
        foreach ($this->rules as $index => $rule) {
            if ($rule->get_id() == $id) {
                return $this->rules[$index];
            }
        }
        throw new \coding_exception('Rule with id ' . $id . ' not found');
    }

    public function remove_rule($id) {
        $rule = false;
        foreach ($this->rules as $index => $rule) {
            if ($rule->get_id() == $id) {
                $rule = $this->rules[$index];
                unset($this->rules[$index]);
                return $rule;
            }
        }
        if (!$rule) {
            throw new \coding_exception('Rule with id ' . $id . ' not found');
        }
    }

    public function __construct($id = '') {
        if (empty($id)) {
            $id = uniqid(true);
        }
        $this->set_id($id);
    }

    public abstract function get_type();

    public abstract function get_template();

    public function render(renderer_base $output) {
        $context = $this->export_for_template($output);
        return $output->render_from_template($this->get_template(), $context);
    }

    public function evaluate_rule($rule) {
        if ($rule->evaluate()) {
            $rule->get_trigger()->pass($this);
        } else {
            $rule->get_trigger()->fail($this);
        }
    }

    public function evaluate_rules() {
        foreach ($this->rules as $rule) {
            $this->evaluate_rule($rule);
        }
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
        $exportedrules = array();
        foreach ($this->rules as $rule) {
            array_push($exportedrules, $rule->export_for_template($output));
        }
        return array(
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'value' => $this->get_value(),
            'error' => $this->get_error(),
            'visible' => $this->is_visible(),
            'label' => $this->get_label(),
            'labelVisible' => $this->is_label_visible(),
            'help' => $this->get_help(),
            'disabled' => $this->is_disabled(),
            'type' => $this->get_type(),
            'template' => $this->get_template(),
            'rules' => $exportedrules
        );
    }

}
