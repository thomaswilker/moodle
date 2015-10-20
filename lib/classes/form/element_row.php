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
 * A row for form elements. All elements must be in a row, even if there is only one
 * element in the row.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element_row implements templatable {

    /** @var string $id All element_rows should have a unique id. */
    protected $id = null;

    /** @var array<element> $elements Element rows group elements on a line. */
    protected $elements = array();

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function add_element($element) {
        array_push($this->elements, $element);
        return $element;
    }

    public function get_element($id) {
        foreach ($this->elements as $index => $element) {
            if ($element->get_id() == $id) {
                return $this->elements[$index];
            }
        }
        throw new \coding_exception('Element with id ' . $id . ' not found');
    }

    public function remove_element($id) {
        $element = false;
        foreach ($this->elements as $index => $element) {
            if ($element->get_id() == $id) {
                $element = $this->elements[$index];
                unset($this->elements[$index]);
            }
        }
        if (!$element) {
            throw new \coding_exception('Element with id ' . $id . ' not found');
        }
        return $element;
    }


    public function __construct($id = '') {
        if (empty($id)) {
            $id = uniqid(true);
        }
        $this->set_id($id);
    }

    public function export_for_template(renderer_base $output) {
        $exportedelements = array();
        foreach ($this->elements as $element) {
            array_push($exportedelements, $element->export_for_template($output));
        }
        return array(
            'id' => $this->id,
            'elements' => $exportedelements
        );
    }
}
