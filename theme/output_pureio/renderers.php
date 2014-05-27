<?php

require_once($CFG->dirroot.'/local/output/renderer.php');

class theme_output_pureio_local_output_renderer extends local_output_renderer {

    public function render_core_menu_dropdown(core_menu_dropdown $menu) {
        $html = '';

        $html .= '<div id="demo-horizontal-menu">';
        $html .= '<ul id="std-menu-items">';
        $html .= '<li>';
        if ($menu->button instanceof renderable) {
            $html .= $this->render($menu->button);
        } else {
            $html .= (string)$menu->button;
        }
        $html .= '<ul>' . "\n";
        foreach ($menu->items as $index => $item) {
            $classes = array();
            if ($menu->flags[$index] & core_menu::MENU_ITEM_DISABLED) {
                $classes[] = 'pure-menu-disabled';
            }
            if ($menu->flags[$index] & core_menu::MENU_ITEM_ACTIVE) {
                $classes[] = 'pure-menu-active';
            }
            $html .= '<li class="' . implode($classes, ' ') . '">';
            $html .= $this->render($item);
            $html .= '</li>' . "\n";
        }
        $html .= '</ul>' . "\n";
        $html .= '</li>' . "\n";
        $html .= '</ul>' . "\n";
        $html .= '</div>' . "\n";

        return $html;
    }

    public function render_core_menu(core_menu $menu) {
        $html = '';

        $html .= '<div class="pure-menu pure-menu-open">' . "\n";
        $html .= '<ul>' . "\n";
        foreach ($menu->items as $index => $item) {
            $classes = array();
            if ($menu->flags[$index] & core_menu::MENU_ITEM_DISABLED) {
                $classes[] = 'pure-menu-disabled';
            }
            if ($menu->flags[$index] & core_menu::MENU_ITEM_ACTIVE) {
                $classes[] = 'pure-menu-active';
            }
            $html .= '<li class="' . implode($classes, ' ') . '">';
            $html .= $this->render($item);
            $html .= '</li>' . "\n";
        }
        $html .= '</ul>' . "\n";

        return $html;
    }
}
