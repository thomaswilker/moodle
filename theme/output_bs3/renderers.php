<?php

require_once($CFG->dirroot.'/local/output/renderer.php');

class theme_output_bs3_local_output_renderer extends local_output_renderer {
    public function render_core_menu_dropdown(core_menu_dropdown $menu) {
        $html = '';

        $html .= '<div class="btn-group">';
        $uniqid = html_writer::random_id('core_menu_dropdown');
        $html .= '<button type="button" id="' . s($uniqid) . '" class="btn btn-info dropdown-toggle" data-toggle="dropdown">';
        if ($menu->button instanceof renderable) {
            $html .= $this->render($menu->button);
        } else {
            $html .= (string)$menu->button;
        }
        $html .= ' <span class="caret"></span>';
        $html .= '</button>' . "\n";
        $html .= '<ul role="menu" labelledby="' . $uniqid . '" class="dropdown-menu">' . "\n";
        foreach ($menu->items as $index => $item) {
            $classes = array();
            if ($menu->flags[$index] & core_menu::MENU_ITEM_DISABLED) {
                $classes[] = 'disabled';
            }
            if ($menu->flags[$index] & core_menu::MENU_ITEM_ACTIVE) {
                $classes[] = 'active';
            }
            $html .= '<li role="presentation" class="' . implode($classes, ' ') . '">';
            if ($item instanceof renderable) {
                $itemhtml = $this->render($item);
            } else {
                $itemhtml = (string) $item;
            }
            // Add attributes to the links in the list.
            $attributes = array('role'=>'menuitem', 'tabindex'=>'-1');
            $newitemhtml = dom_utils::add_attributes_to_first_node_of_type('a', $attributes, $itemhtml);
            if ($newitemhtml === false) {
                debugging('core_menu_dropdown renderable expected a link, found ' . $itemhtml . ' instead.', DEBUG_DEVELOPER); 
                $newitemhtml = $itemhtml;
            }
            $html .= $newitemhtml;
            $html .= '</li>' . "\n";
        }
        $html .= '</ul>' . "\n";
        $html .= '</div>' . "\n";

        return $html;
    }

    public function render_core_menu(core_menu $menu) {
        $html = '';

        $html .= '<div class="list-group">' . "\n";
        foreach ($menu->items as $index => $item) {
            $classes = array('list-group-item');
            if ($menu->flags[$index] & core_menu::MENU_ITEM_DISABLED) {
                $classes[] = 'disabled';
            }
            if ($menu->flags[$index] & core_menu::MENU_ITEM_ACTIVE) {
                $classes[] = 'active';
            }
            if ($item instanceof renderable) {
                $itemhtml = $this->render($item);
            } else {
                $itemhtml = (string) $item;
            }
            $classes = implode(' ', $classes);
            $newitemhtml = dom_utils::add_attributes_to_first_node_of_type('a', array('class'=>$classes), $itemhtml);
            if ($newitemhtml === false) {
                debugging('core_menu renderable expected a link, found ' . $itemhtml . ' instead.', DEBUG_DEVELOPER); 
                $newitemhtml = $itemhtml;
            }
            $html .= $newitemhtml;
        }
        $html .= '</ul>' . "\n";

        return $html;
    }
}
