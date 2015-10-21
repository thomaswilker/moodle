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
namespace core\form\element;

use core\form\element;
use templatable;
use renderer_base;

/**
 * Abstract base class for all types of form elements.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text extends element {

    /** @var boolean $autocomplete By default we disable autocomplete. */
    protected $autocomplete = false;

    /** @var boolean $autofocus Do we automatically set focus on this field? */
    protected $autofocus = false;

    /** @var int $maxlength What is the maximum length for this input field */
    protected $maxlength = 255;

    /** @var int $size What is the size for this input field */
    protected $size = 48;

    /** @var boolean $placeholder Default text to show when there is no value. */
    protected $placeholder = '';

    public function get_type() {
        return 'text';
    }

    public function get_template() {
        return 'core/form-field-text';
    }

    public function set_autocomplete($autocomplete) {
        $this->autocomplete = $autocomplete;
    }

    public function is_autocomplete() {
        return $this->autocomplete;
    }

    public function set_autofocus($autofocus) {
        $this->autofocus = $autofocus;
    }

    public function is_autofocus() {
        return $this->autofocus;
    }

    public function set_maxlength($maxlength) {
        $this->maxlength = $maxlength;
    }

    public function get_maxlength() {
        return $this->maxlength;
    }

    public function set_placeholder($placeholder) {
        $this->placeholder = $placeholder;
    }

    public function get_placeholder() {
        return $this->placeholder;
    }

    public function set_size($size) {
        $this->size = $size;
    }

    public function get_size() {
        return $this->size;
    }

    public function __construct($id = '') {
        parent::__construct($id);
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
        $result['size'] = $this->get_size();
        $result['placeholder'] = $this->get_placeholder();
        $result['maxlength'] = $this->get_maxlength();
        $result['autofocus'] = $this->is_autofocus();
        $result['autocomplete'] = $this->is_autocomplete();
        return $result;
    }

}
