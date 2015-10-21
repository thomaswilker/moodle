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
 * Class for a validation rule.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule implements templatable {

    /** @var string ALL Class constant for a logic "and" */
    const ALL = 'and';

    /** @var string ANY Class constant for a logic "or" */
    const ANY = 'any';

    /** @var trigger $trigger - The type of action to trigger when the conditions of this rule are met. */
    var $trigger = null;

    /** @var array<condition> $conditions - The conditions of this rule. */
    var $conditions = array();

    /** @var string $logictype Either ANY or ALL. */
    protected $logictype = self::ALL;

    public function get_trigger() {
        return $this->trigger;
    }

    public function set_trigger($trigger) {
        $this->trigger = $trigger;
        return $this;
    }

    public function set_logic_type($logictype) {
        if ($logictype != self::ANY && $logictype != self::ALL) {
            throw new \coding_exception('Logic type must be ANY or ALL');
        }
        $this->logictype = $logictype;
        return $this;
    }

    public function get_logic_type() {
        return $this->logictype;
    }

    public function add_condition($condition) {
        array_push($this->conditions, $condition);
        return $condition;
    }

    public function get_conditions() {
        return $this->conditions;
    }

    public function remove_condition($index) {
        if (!isset($this->conditions[$index])) {
            throw new \coding_exception('Condition not found');
        }
        $condition = $this->conditions[$index];
        unset($this->conditions[$index]);
        return $condition;
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
        $exportedconditions = array();
        foreach ($this->conditions as $condition) {
            array_push($exportedconditions, $condition->export_for_template($output));
        }
        return array(
            'logictype' => $this->logictype,
            'conditions' => $exportedconditions,
            'trigger' => $this->trigger->export_for_template($output)
        );
    }

}
