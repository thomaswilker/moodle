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
 * Make hooks dispatchable by providing a get_key method which returns the hook name.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\hook;

use core\callback\dispatchable;

defined('MOODLE_INTERNAL') || die();

/**
 * Make hooks dispatchable by providing a get_key method which returns the hook name.
 * Don't call this directly - use \core\callback\hook::fire();
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook implements dispatchable {

    /**
     * @var string The name of the hook
     */
    protected $hookname = '';

    /**
     * @var stdClass|null The arguments passed as an stdClass or null
     */
    protected $arguments = null;

    /**
     * Convenience to create a hook and dispatch it in a single line.
     *
     * \core\hook\dispatcher::fire('noseitchy', $args);
     *
     * @param string $hookname
     * @param stdClass $arguments List of modifyable arguments.
     */
    public static function fire(string $hookname, \stdClass $arguments = null) {
        $hook = new static($hookname, $arguments);

        hook_dispatcher::instance()->dispatch($hook);
    }

    /**
     * Construct a hook compatible with the dispatcher.
     * Can only be called from fire().
     *
     * @param string $hookname
     * @param stdClass $arguments
     */
    protected function __construct($hookname, \stdClass $arguments) {
        $this->hookname = $hookname;
        $this->arguments = $arguments;
    }

    /**
     * For hooks the key is the hookname.
     */
    public function get_key() {
        return $this->hookname;
    }

    /**
     * Hooks get the arguments property passed as an argument.
     */
    public function get_arguments() {
        return $this->arguments;
    }
}
