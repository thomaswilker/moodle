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

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin for DragMath equation editor (for use with TeX filter).
 *
 * @package   tinymce_dragmath
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tinymce_dragmath extends editor_tinymce_plugin {
    /** @var array list of buttons defined by this plugin */
    protected $buttons = array('dragmath');

    protected function update_init_params(array &$params, context $context,
            array $options = null) {

        $texexample = '$$\pi$$';

        // Make sure the correct context is set in the options for the filter.
        $options['context'] = $context;
        // Format a string with the active filter set.
        // If it is modified - we assume that some sort of text filter is working in this context.
        $result = format_text($texexample, true, $options);

        $texfilteractive = ($texexample !== $result);
        if (!$texfilteractive) {
            return;
        }

        if ($row = $this->find_button($params, 'nonbreaking')) {
            // Add button before 'nonbreaking'.
            $this->add_button_before($params, $row, 'dragmath', 'nonbreaking');
        } else {
            // If 'nonbreaking' is not found, add button in the end of the last row:
            $this->add_button_after($params, $this->count_button_rows($params), 'dragmath');
        }

        // Add JS file, which uses default name.
        $this->add_js_plugin($params);
    }
}
