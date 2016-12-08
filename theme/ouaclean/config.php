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
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */

$THEME->name = 'ouaclean';

/////////////////////////////////
// The only thing you need to change in this file when copying it to
// create a new theme is the name above. You also need to change the name
// in version.php and lang/en/theme_ouaclean.php as well.
//////////////////////////////////
//
$THEME->doctype = 'html5';
$THEME->parents = array('bootstrap');
$THEME->sheets = array('additionalcustom');
$THEME->lessfile = 'moodle';
$THEME->lessvariablescallback = 'theme_oua_clean_less_variables';
$THEME->extralesscallback = 'theme_oua_clean_extra_less';

$THEME->supportscssoptimisation = true;
$THEME->yuicssmodules = array('tabview');
$THEME->enable_dock = false;
$THEME->editor_sheets = array();

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_ouaclean_process_css';
$THEME->javascripts_footer = array(

);
$THEME->blockrendermethod = 'blocks';

$THEME->layouts = array(
    // Most backwards compatible layout without the blocks - this is the layout used by default.
    'base' => array(
        'file' => 'layout.php',
        'renderable' => 'columns1_layout',
        'regions' => array(),
    ),
    // Standard layout with blocks, this is recommended for most pages with general information.
    'standard' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
    ),
    // Standard layout with blocks, this is recommended for most pages with general information.
    'sitecourse' => array(
        'file' => 'layout.php',
        'renderable' => 'columns1_layout',
        'regions' => array('side-d'),
        'defaultregion' => 'side-d',
    ),
    // Main course page.
    'course' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
        'options' => array('langmenu' => true),
    ),
    'coursecategory' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
    ),
    // Part of course, typical for modules - default page layout if $cm specified in require_login().
    'incourse' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
    ),
    // Part of course, typical for modules - default page layout if $cm specified in require_login().
    'inlineforum' => array(
        'file' => 'layout.php',
        'renderable' => 'inlineforum_course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
    ),
    // The site home page.
    'frontpage' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
        'options' => array('nonavbar' => true),
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
    ),
    // My dashboard page.
    'mydashboard' => array(
        'file' => 'layout.php',
        'renderable' => 'dashboard_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-tabfoot',
            'side-tabx',
            'side-taba',
            'side-tabb',
            'side-tabc',
        ),
        'defaultregion' => 'side-tabfoot',
        'options' => array('langmenu' => true),
    ),
    // My public page.
    'mypublic' => array(
        'file' => 'layout.php',
        'renderable' => 'profile_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
        ),
        'defaultregion' => 'side-d',
    ),
    // My public page.
    'ouaprofile' => array(
        'file' => 'layout.php',
        'renderable' => 'profile_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
        ),
        'defaultregion' => 'side-d',
    ),
    'login' => array(
        'file' => 'layout.php',
        'renderable' => 'columns1_layout',
        'regions' => array(),
        'options' => array('langmenu' => true),
    ),

    // Pages that appear in pop-up windows - no navigation, no blocks, no header.
    'popup' => array(
        'file' => 'popup.php',
        'renderable' => 'popup_layout',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => 'layout.php',
        'renderable' => 'columns1_layout',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nocoursefooter' => true),
    ),
    // Embeded pages, like iframe/object embeded in moodleform - it needs as much space as possible.
    'embedded' => array(
        'file' => 'layout.php',
        'renderable' => 'embedded_layout',
        'regions' => array()
    ),
    // Used during upgrade and install, and for the 'This site is undergoing maintenance' message.
    // This must not have any blocks, links, or API calls that would lead to database or cache interaction.
    // Please be extremely careful if you are modifying this layout.
    'maintenance' => array(
        'file' => 'layout.php',
        'renderable' => 'maintenance_layout',
        'regions' => array(),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'layout.php',
        'renderable' => 'columns1_layout',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => false),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => 'layout.php',
        'renderable' => 'embedded_layout',
        'regions' => array(),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => 'layout.php',
        'renderable' => 'course_layout',
        'regions' => array(
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d',
            'side-tabhead',
            'side-taba',
            'side-tabb',
            'side-tabc',
            'side-tabfoot'),
        'defaultregion' => 'side-tabfoot',
    ),
    // The pagelayout used for safebrowser and securewindow.
    'secure' => array(
        'file' => 'layout.php',
        'renderable' => 'secure_layout',
        'regions' => array('side-post',
            'side-a', /* Names are limited to 16 Chars. */
            'side-b',
            'side-c',
            'side-d'),
        'defaultregion' => 'side-post'
    ),

    // Page layout for help prototype container only to allow flexibility in design
    'help' => array(
        'file' => 'layout.php',
        'renderable' => 'columns1_layout',
        'regions' => array(),
        'options' => array('langmenu' => true),
    ),
);

$THEME->requiredblocks = 'settings,navigation';
