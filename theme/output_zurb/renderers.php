<?php

require_once($CFG->dirroot.'/local/output/renderer.php');

class theme_output_zurb_local_output_renderer extends local_output_renderer {

    public function render_core_menu_dropdown(core_menu_dropdown $menu) {
        $html = '';

        $html .= '<div">';
        $uniqid = html_writer::random_id('core_menu_dropdown');
        $html .= '<a data-dropdown="' . s($uniqid) . '" href="#" class="button">';
        if ($menu->button instanceof renderable) {
            $html .= $this->render($menu->button);
        } else {
            $html .= (string)$menu->button;
        }
        $html .= ' »';
        $html .= '</a>' . "\n";
        $html .= '<ul id="' . s($uniqid) . '" data-drop-down-content class="f-dropdown">' . "\n";
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

    public function render_core_menu(core_menu $menu) {
        $html = '';

        $html .= '<ul class="side-nav">' . "\n";
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
}
