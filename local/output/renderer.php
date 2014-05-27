<?php
/**
 * This file contains the local output plugin renderer.
 *
 * The methods here are really only here so that I can more cleanly show what is going on.
 * If we choose to adopt this method of element handling then these methods would exist on the core_renderer.
 * Not within a plugin.
 *
 * But you're smart - you already knew that.
 */

/**
 * Local output renderer.
 */
class local_output_renderer extends plugin_renderer_base {

    /**
     * This is the bootstrap 2 drop down menu HTML
     */
    public function render_core_menu_dropdown(core_menu_dropdown $menu) {
        $html = '';

        $html .= '<div class="btn-group">';
        $html .= '<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">';
        if ($menu->button instanceof renderable) {
            $html .= $this->render($menu->button);
        } else {
            $html .= (string)$menu->button;
        }
        $html .= ' <span class="caret"></span>';
        $html .= '</a>' . "\n";
        $html .= '<ul class="dropdown-menu">' . "\n";
        foreach ($menu->items as $index => $item) {
            $classes = array();
            if ($menu->flags[$index] & core_menu::MENU_ITEM_DISABLED) {
                $classes[] = 'disabled';
            }
            if ($menu->flags[$index] & core_menu::MENU_ITEM_ACTIVE) {
                $classes[] = 'active';
            }
            $html .= '<li class="' . implode($classes, ' ') . '">';
            $html .= $this->render($item);
            $html .= '</li>' . "\n";
        }
        $html .= '</ul>' . "\n";
        $html .= '</div>' . "\n";

        return $html;
    }

    /**
     * This is the bootstrap 2 fancy list HTML
     */
    public function render_core_menu(core_menu $menu) {
        $html = '';

        $html .= '<ul class="nav nav-list nav-stacked">' . "\n";
        foreach ($menu->items as $index => $item) {
            $classes = array();
            if ($menu->flags[$index] & core_menu::MENU_ITEM_DISABLED) {
                $classes[] = 'disabled';
            }
            if ($menu->flags[$index] & core_menu::MENU_ITEM_ACTIVE) {
                $classes[] = 'active';
            }
            $html .= '<li class="' . implode($classes, ' ') . '">';
            $html .= $this->render($item);
            $html .= '</li>' . "\n";
        }
        $html .= '</ul>' . "\n";

        return $html;
    }

    /**
     * Renders a courses menu.
     *
     * @param course_list $courses
     * @return string
     */
    public function course_menu_dropdown(course_list $courses) {
        // Logic.
        $links = $courses->get_course_links();
        $dropdown = new core_menu_dropdown(
            get_string('courses', 'local_output'),
            $links
        );
        // Make the second item disabled.
        $dropdown->set_menu_item_flags(1, core_menu_dropdown::MENU_ITEM_DISABLED);
        // Make the third item active.
        $dropdown->set_menu_item_flags(2, core_menu_dropdown::MENU_ITEM_ACTIVE);
        // Display.
        $html = '<div class="course_menu">';
        $html .= $this->output->heading(get_string('coursemenu', 'local_output'));
        $html .= '<p>'.get_string('coursemenudesc', 'local_output').'</p>';
        $html .= $this->render($dropdown);
        $html .= '</div>';
        return $html;
    }

    /**
     * Renders a courses menu.
     *
     * @param course_list $courses
     * @return string
     */
    public function course_menu(course_list $courses) {
        // Logic.
        $links = $courses->get_course_links();
        $menu = new core_menu($links);
        // Make the second item disabled.
        $menu->set_menu_item_flags(1, core_menu::MENU_ITEM_DISABLED);
        // Make the third item active.
        $menu->set_menu_item_flags(2, core_menu::MENU_ITEM_ACTIVE);
        // Display.
        $html = '<div class="course_menu">';
        $html .= $this->output->heading(get_string('coursemenu', 'local_output'));
        $html .= '<p>'.get_string('coursemenudesc', 'local_output').'</p>';
        $html .= $this->render($menu);
        $html .= '</div>';
        return $html;
    }

    /**
     * Renders a collection of course links.
     *
     * @param course_list $courses
     * @return string
     */
    public function course_links(course_list $courses) {
        // Logic.
        $links = $courses->get_course_links();
        // Display.
        $html = '<div class="course_links">';
        $html .= $this->output->heading(get_string('courselinks', 'local_output'));

        // I dont get the point of this example - the HTML is not semantic - it should really use a list
        // with the styles removed.
        $html .= '<p>'.get_string('courselinksdesc', 'local_output').'</p>';
        foreach ($links as $link) {
            $html .= '<p>'.$this->render($link).'</p>';
        }
        $html .= '</div>';
        return $html;
    }
}
