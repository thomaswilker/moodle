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
 * Template driven renderer (fall back to standard renderer).
 *
 * @package    core\output
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/outputrenderers.php');

/**
 * Template driven renderer (fall back to standard renderer).
 *
 * @package    core
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templatable_renderer extends \renderer_base {

    protected $page;
    protected $component;
    protected $subtype;
    protected $target;
    protected $themeconfig;
    protected $fallback;

    /**
     * Constructor.
     *
     * @param theme_config $theme the theme we belong to.
     */
    public function __construct(\moodle_page $page,
                                $component,
                                $subtype = null,
                                $target = null,
                                \theme_config $themeconfig,
                                \renderer_base $fallback) {
        $this->page = $page;
        $this->component = $component;
        $this->subtype = $subtype;
        $this->target = $target;
        $this->themeconfig = $themeconfig;
        $this->fallback = $fallback;
    }


    /**
     * Returns rendered widget.
     *
     * The provided widget needs to be an object that extends the renderable
     * interface.
     * If will then be rendered by a raintpl using a template stored in the theme (or any of it's parents).
     *
     * @param renderable $widget instance with renderable interface
     * @return string
     */
    public function render(\renderable $widget) {
        var_dump($this->themeconfig);
        //debugging("render: $component, $subtype, $target\n");
        return $this->fallback->render($widget);
    }

    public function __call($function, $args) {
        //debugging("fallback: $function\n");
        return call_user_func_array(array($this->fallback, $function), $args);
    }

}
