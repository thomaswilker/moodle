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

/**
 * Abstract class defining a form.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class form {

    /** @var string $id All forms should have a unique id - will be generated if not supplied. */
    protected $id = null;

    /** @var array<fieldset> $fieldsets Forms contain a list of fieldsets that can be expanded and collapsed. */
    protected $fieldsets = array();

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function add_fieldset($id, $name) {
        array_push($this->fieldsets, new fieldset($id, $name));
    }

    public function remove_fieldset($id) {
        foreach ($this->fieldsets as $index => $fieldset) {
            if ($fieldset->get_id() == $id) {
                unset($this->fieldsets[$index]);
            }
        }
    }

    public function __construct($id = '') {
        if (empty($id)) {
            $id = uniqid(true);
        }
        $this->set_id($id);
    }

}
