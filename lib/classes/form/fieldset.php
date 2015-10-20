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
 * Class for common properties of scheduled_task and adhoc_task.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fieldset implements templatable {

    /** @var string $id All fieldsets should have a unique id. */
    protected $id = null;

    /** @var string $name All fieldsets should have a unique name. */
    protected $name = null;

    /** @var array<element_row> $elementrows Fieldsets contain a list of element rows that group elements on a line. */
    protected $elementrows = array();

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = $name;
    }

    public function add_row($id = '') {
        if (empty($id)) {
            $id = uniqid(true);
        }
        $row = new element_row($id);
        array_push($this->elementrows, $row);
        return $row;
    }

    public function get_row($id) {
        foreach ($this->elementrows as $index => $elementrow) {
            if ($elementrow->get_id() == $id) {
                return $this->elementrows[$index];
            }
        }
        throw new \coding_exception('Row with id ' . $id . ' not found');
    }

    public function remove_row($id) {
        $row = false;
        foreach ($this->elementrows as $index => $elementrow) {
            if ($elementrow->get_id() == $id) {
                $row = $this->elementrows[$index];
                unset($this->elementrows[$index]);
            }
        }
        if (!$row) {
            throw new \coding_exception('Row with id ' . $id . ' not found');
        }
        return $row;
    }


    public function __construct($id = '', $name = '') {
        if (empty($id)) {
            $id = uniqid(true);
        }
        $this->set_id($id);
        $this->set_name($name);
    }

    public function export_for_template(renderer_base $output) {
        $exportedrows = array();
        foreach ($this->elementrows as $row) {
            array_push($exportedrows, $row->export_for_template($output));
        }
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'elementRows' => $exportedrows
        );
    }
}
