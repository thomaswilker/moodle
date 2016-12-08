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
 * myprofile renderer.
 *
 * @package    core_user
 * @copyright  2015 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_user\output\myprofile\tree;
use core_user\output\myprofile\category;
defined('MOODLE_INTERNAL') || die;

class theme_ouaclean_core_user_myprofile_renderer extends \core_user\output\myprofile\renderer {
    /**
     * Render the whole tree.
     *
     * @param tree $tree
     *
     * @return string
     */
    public function render_tree(tree $tree) {
        $return = \html_writer::start_tag('div', array('class' => 'profile_tree'));
        $categories = $tree->categories;

        $return .= \html_writer::start_tag('ul', array('class' => 'profile-row'));
            $return .= $this->render($categories['contact']);
            $return .=  $this->render($categories['coursedetails']);
        $return .= \html_writer::end_tag('ul');

        $return .= \html_writer::start_tag('ul', array('class' => 'profile-row'));
            $return .= $this->render($categories['miscellaneous']);
        $return .= \html_writer::end_tag('ul');

        $return .= \html_writer::start_tag('ul', array('class' => 'profile-row'));
        $return .=  $this->render($categories['administration']);
        $return .= \html_writer::end_tag('ul');


        $return .= \html_writer::end_tag('div');
        return $return;

    }
    /**
     * Render a category.
     *
     * @param category $category
     *
     * @return string
     */
    public function render_category(category $category) {
        $classes = $category->classes;
        if (empty($classes)) {
            $return = \html_writer::start_tag('section', array('class' => 'node_category'));
        } else {
            $return = \html_writer::start_tag('section', array('class' => 'node_category ' . $classes));
        }
        $return .= \html_writer::tag('h2', $category->title);
        $nodes = $category->nodes;
        if (empty($nodes)) {
            // No nodes, nothing to render.
            return '';
        }
        $return .= \html_writer::start_tag('ul');
        foreach ($nodes as $node) {
            $return .= $this->render($node);
        }
        $return .= \html_writer::end_tag('ul');
        $return .= \html_writer::end_tag('section');
        return $return;
    }


}
