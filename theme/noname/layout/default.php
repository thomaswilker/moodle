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
 * A three column layout for the noname theme.
 *
 * @package   theme_noname
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Is there a settings nav ? use that...

$settings = $PAGE->settingsnav;

$flatnodes = [];

function add_visible_children($flatnodes, $node) {
    foreach ($node->children as $child) {
        if ($child->forceopen && $child->has_children()) {
            $flatnodes = add_visible_children($flatnodes, $child);
        } else if ($child->display) {
            $flatnodes[] = $child;
        }
    }
    return $flatnodes;
}

function find_child_action($node) {
    foreach ($node->children as $child) {
        if ($child->display && $child->action) {
            return $child->action;
        } else if ($child->has_children()) {
            $test = find_child_action($child);
            if ($test) {
                return $test;
            }
        }
    }
}

function ensure_actions($node) {
    global $PAGE;
    if (empty($node->action)) {
        $action = find_child_action($node);
        $node->action = $action;
    }
    foreach ($node->children as $child) {
        ensure_actions($child);
    }
}

if ($settings) {
    ensure_actions($settings);
 //   $flatnodes = add_visible_children($flatnodes, $settings);
    $current = $settings->find_active_node();
    if ($current && $current->has_children()) {
        foreach ($current->children as $child) {
            if ($child->display) {
                $flatnodes[] = $child;
            }
        }
    } else if ($current) {
        foreach ($current->parent->children as $child) {
            if ($child->display) {
                $flatnodes[] = $child;
            }
        }
    } else {
        // Get all the children of the first expanded node?
        foreach ($settings->children as $branch) {
            if ($branch->display) {
                foreach ($branch->children as $child) {
                    if ($child->display) {
                        $flatnodes[] = $child;
                    }
                }
                break;
            }
        }
        //$flatnodes = add_visible_children($flatnodes, $settings);
    }
}

$flatnav = [];
foreach ($flatnodes as $node) {
    $flatnav[] = [
        'text' => $node->text,
        'action' => ($node->action . ''),
        'active' => $node->isactive,
        'icon' => ''
    ];
}

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, array('context' => context_course::instance(SITEID))),
    'output' => $OUTPUT,
    'flatnav' => $flatnav,
    'blocks' => $OUTPUT->blocks('default'),
];

echo $OUTPUT->render_from_template('theme_noname/default-layout', $templatecontext);

