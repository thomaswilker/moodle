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

namespace core\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for all of formslib.
 *
 * @package    core
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/outputrenderers.php');

use renderable;
use templatable;
use core_renderer;

class form_renderer extends core_renderer {
    /**
     * Returns rendered widget.
     *
     * The provided widget needs to be an object that extends the renderable
     * interface.
     * If will then be rendered by a method based upon the classname for the widget.
     * For instance a widget of class `crazywidget` will be rendered by a protected
     * render_crazywidget method of this renderer.
     *
     * @param renderable $widget instance with renderable interface
     * @return string
     */
    public function render(renderable $widget) {
        $classname = get_class($widget);
        if ($widget instanceof templatable && method_exists($widget, 'get_template_name')) {
            $context = $widget->export_for_template($this);
            if ($context->frozen) {
                return $this->render_from_template('core/form-element-static', $context);
            } else {
                return $this->render_from_template($widget->get_template_name(), $context);
            }
        } else {
            return parent::render($widget);
        }
    }

}
