<?php
/**
 * Local output prototype test file.
 *
 * This file renders an array of course links in three different ways.
 *  1. A collection of links (each within a p tag)
 *  2. A list of links
 *  3. A dropdown menu containing a list of links.
 *
 * The purpose of these three methods is to show how a UI element (core_ui_link) can be rendered
 * in three ways depending upon how and where it has being used.
 *
 * The collection of links is straigh rendering of core_ui_link instances.
 * The list is core_ui_link instances rendered as part of a large component, in this case core_ui_menu.
 * The menu is core_ui_link instances rendered as part of a core_ui_menu component that is within a core_ui_menu_dropdown component.
 *
 * The list of courses is taken from the site, so for the purposes of testing having 4 or 5 courses is going to be ideal.
 * Simply browse to this script to see the outcome.
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/output/locallib.php');

$PAGE->set_url('/local/output/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('title', 'local_output'));
$PAGE->set_heading(get_string('heading', 'local_output'));
$PAGE->navbar->add(get_string('pluginname', 'local_output'), $PAGE->url);

/** @var local_output_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('local_output');
$courses = new course_list();

echo $renderer->header();
echo $renderer->course_links($courses);
echo $renderer->course_menu($courses);
echo $renderer->course_menu_dropdown($courses);
echo '<br />';
echo '<br />';
echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut et faucibus risus, nec pretium justo. Pellentesque adipiscing velit eu ligula tristique interdum. Sed hendrerit ultrices neque, eu luctus eros sollicitudin sit amet. Mauris quis dui ut lectus sagittis dictum sed porttitor purus. Vestibulum adipiscing odio a lorem rutrum, nec lobortis eros luctus. Vivamus fermentum mi lorem, eget tempus elit aliquam non. Mauris nec pharetra ante, vel suscipit sem. Vivamus vitae mi sapien. Vestibulum a vestibulum leo, non adipiscing ipsum. Ut scelerisque massa vel tempus eleifend. Sed scelerisque sapien at metus ultricies, quis ullamcorper turpis pretium.</p>';
echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut et faucibus risus, nec pretium justo. Pellentesque adipiscing velit eu ligula tristique interdum. Sed hendrerit ultrices neque, eu luctus eros sollicitudin sit amet. Mauris quis dui ut lectus sagittis dictum sed porttitor purus. Vestibulum adipiscing odio a lorem rutrum, nec lobortis eros luctus. Vivamus fermentum mi lorem, eget tempus elit aliquam non. Mauris nec pharetra ante, vel suscipit sem. Vivamus vitae mi sapien. Vestibulum a vestibulum leo, non adipiscing ipsum. Ut scelerisque massa vel tempus eleifend. Sed scelerisque sapien at metus ultricies, quis ullamcorper turpis pretium.</p>';
echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut et faucibus risus, nec pretium justo. Pellentesque adipiscing velit eu ligula tristique interdum. Sed hendrerit ultrices neque, eu luctus eros sollicitudin sit amet. Mauris quis dui ut lectus sagittis dictum sed porttitor purus. Vestibulum adipiscing odio a lorem rutrum, nec lobortis eros luctus. Vivamus fermentum mi lorem, eget tempus elit aliquam non. Mauris nec pharetra ante, vel suscipit sem. Vivamus vitae mi sapien. Vestibulum a vestibulum leo, non adipiscing ipsum. Ut scelerisque massa vel tempus eleifend. Sed scelerisque sapien at metus ultricies, quis ullamcorper turpis pretium.</p>';
echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut et faucibus risus, nec pretium justo. Pellentesque adipiscing velit eu ligula tristique interdum. Sed hendrerit ultrices neque, eu luctus eros sollicitudin sit amet. Mauris quis dui ut lectus sagittis dictum sed porttitor purus. Vestibulum adipiscing odio a lorem rutrum, nec lobortis eros luctus. Vivamus fermentum mi lorem, eget tempus elit aliquam non. Mauris nec pharetra ante, vel suscipit sem. Vivamus vitae mi sapien. Vestibulum a vestibulum leo, non adipiscing ipsum. Ut scelerisque massa vel tempus eleifend. Sed scelerisque sapien at metus ultricies, quis ullamcorper turpis pretium.</p>';
echo $renderer->footer();
