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

/**
 * Public class defining a form.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class formlibrary {


    public static function load_form($component, $name) {
        // We call the component so it can register all the forms it knows of.
        $forms = component_callback($component, 'register_forms');

        if (!isset($forms[$name])) {
            throw new coding_exception('The form does not exist');
        }
        if (!class_exists($forms[$name])) {
            throw new coding_exception('The form class does not exist');
        }

        $formclass = $forms[$name];
        return new $formclass();
    }
}
