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

namespace theme_ouaclean\output;

defined('MOODLE_INTERNAL') || die;

use theme_bootstrap_core_renderer;
use single_button;
use html_writer;

/**
 * Class containing data for mustache layouts
 *
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */
class core_renderer extends theme_bootstrap_core_renderer {
    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_embedded_layout(layout\embedded_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_maintenance_layout(layout\maintenance_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_columns1_layout(layout\columns1_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_columns2_layout(layout\columns2_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }


    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_secure_layout(layout\secure_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_dashboard_layout(layout\dashboard_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_profile_layout(layout\profile_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }

    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_course_layout(layout\course_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }
    /**
     * Defer to template.
     *
     * @param $page
     *
     * @return string html for the page
     */
    public function render_inlineforum_course_layout(layout\inlineforum_course_layout $page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('theme_ouaclean/layout_wrapper', $data);
    }
    /**
     * Custom blocks renderer to add text to block regions
     *
     * @param string $region
     * @param array  $classes
     * @param string $tag
     *
     * @return string
     * @throws coding_exception
     */

    public function blocks($region, $classes = array(), $tag = 'aside') {

        $displayregion = $this->page->apply_theme_region_manipulations($region);
        $classes = (array)$classes;
        $classes[] = 'block-region';
        $data = new \StdClass();
        $data->tag = $tag;
        $data->id = 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion);
        $data->classes = join(' ', $classes);
        $data->datablockregion = $displayregion;
        $data->datadroptarget = '1';
        $data->blockcontent = '';

        if (strpos($region, 'side') === 0) {
            $data->regionname = get_string('block-region-text', 'theme_ouaclean') . get_string('region-' . $region, 'theme_ouaclean');
        }
        if ($this->page->blocks->region_has_content($displayregion, $this)) {
            $data->blockcontent .= $this->blocks_for_region($displayregion);
        }

        return parent::render_from_template('theme_ouaclean/blocks_oua', $data);
    }
    /**
     * Customise core renderer to allow button to have a css class
     *
     * This will return HTML to display a form containing a single button.
     *
     * This class is the same as the core button renderer, except we add a buttonclass.
     * buttonclass allows a custom class on the button div.  The default class is on the container
     * div as does not always serve our purpose.
     *
     * @param single_button $button
     *
     * @return string HTML fragment
     */
    protected function render_single_button(single_button $button) {
        $attributes = array('type'     => 'submit',
            'value'    => $button->label,
            'class'    => isset($button->buttonclass) ? $button->buttonclass : '',
            'disabled' => $button->disabled ? 'disabled' : null,
            'title'    => $button->tooltip);

        if ($button->actions) {
            $id = html_writer::random_id('single_button');
            $attributes['id'] = $id;
            foreach ($button->actions as $action) {
                $this->add_action_handler($action, $id);
            }
        }

        // first the input element
        $output = html_writer::empty_tag('input', $attributes);

        // then hidden fields
        $params = $button->url->params();
        if ($button->method === 'post') {
            $params['sesskey'] = sesskey();
        }
        foreach ($params as $var => $val) {
            $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output);

        // now the form itself around it
        if ($button->method === 'get') {
            $url = $button->url->out_omit_querystring(true); // url without params, the anchor part allowed
        } else {
            $url = $button->url->out_omit_querystring();     // url without params, the anchor part not allowed
        }
        if ($url === '') {
            $url = '#'; // there has to be always some action
        }
        $attributes = array('method' => $button->method,
            'action' => $url,
            'id'     => $button->formid);
        $output = html_writer::tag('form', $output, $attributes);

        // and finally one more wrapper with class
        return html_writer::tag('div', $output, array('class' => $button->class));
    }
}
