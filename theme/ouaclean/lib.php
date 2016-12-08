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

/**
 * Parses CSS before it is cached.
 *
 * This function can make alterations and replace patterns within the CSS.
 *
 * @param string       $css The CSS
 * @param theme_config $theme The theme config object.
 *
 * @return string The parsed CSS The parsed CSS.
 */
function theme_ouaclean_process_css($css, $theme) {
    global $CFG;
    // Set the background image for the logo.
    $logo = $theme->setting_file_url('logo', 'logo');
    if (!$logo) {
        $logo = new moodle_url($CFG->wwwroot . '/theme/ouaclean/pix/openunilogo.png');
    }
    $css = theme_ouaclean_set_logo($css, $logo);

    // Set custom CSS.
    if (!empty($theme->settings->customcss)) {
        $customcss = $theme->settings->customcss;
    } else {
        $customcss = null;
    }
    $css = theme_ouaclean_set_customcss($css, $customcss);

    return $css;
}
function theme_oua_clean_extra_less($theme) {
    $content = '';
    if (!empty($theme->settings->less_extra)) {
        $content = $theme->settings->less_extra;
    }

    return $content;
}

/**
 * Returns variables for LESS.
 *
 * We will inject some LESS variables from the settings that the user has defined
 * for the theme. No need to write some custom LESS for this.
 *
 * @param theme_config $theme The theme config object.
 * @return array of LESS variables without the @.
 */
function theme_oua_clean_less_variables($theme) {
    $variables = array();

    if (!empty($theme->settings->less_btn_link_primary)) {
        $variables['LinkDark'] = $theme->settings->less_btn_link_primary;
    }
    if (!empty($theme->settings->less_btn_link_hover)) {
        $variables['LinkBright'] = $theme->settings->less_btn_link_hover;
    }
    if (!empty($theme->settings->less_link_light_default)) {
        $variables['LinkLightDefault'] = $theme->settings->less_link_light_default;
    }
    if (!empty($theme->settings->less_link_light_hover)) {
        $variables['LinkActive'] = $theme->settings->less_link_light_hover;
    }
    if (!empty($theme->settings->less_link_hover)) {
        $variables['LinkHover'] = $theme->settings->less_link_hover;
    }
    if (!empty($theme->settings->less_section_light_bg)) {
        $variables['StructureLight'] = $theme->settings->less_section_light_bg;
    }
    if (!empty($theme->settings->less_section_dark_bg)) {
        $variables['StructureDark'] = $theme->settings->less_section_dark_bg;
    }
    if (!empty($theme->settings->less_page_background)) {
        $variables['PageBackground'] = $theme->settings->less_page_background;
    }
    if (!empty($theme->settings->less_header)) {
        $variables['Header'] = $theme->settings->less_header;
    }
    if (!empty($theme->settings->less_header_border)) {
        $variables['HeaderBorder'] = $theme->settings->less_header_border;
    }
    if (!empty($theme->settings->less_keyline)) {
        $variables['Keyline'] = $theme->settings->less_keyline;
    }
    if (!empty($theme->settings->less_bodycopy)) {
        $variables['BodyCopy'] = $theme->settings->less_bodycopy;
    }
    if (!empty($theme->settings->less_progressbar)) {
        $variables['ProgressBar'] = $theme->settings->less_progressbar;
    }




    return $variables;
}
/**
 * Adds the logo to CSS.
 *
 * @param string $css The CSS.
 * @param string $logo The URL of the logo.
 *
 * @return string The parsed CSS
 */
function theme_ouaclean_set_logo($css, $logo) {
    $tag = '[[setting:logo]]';
    $replacement = $logo;
    if (is_null($replacement)) {
        $replacement = '';
    }

    $css = str_replace($tag, $replacement, $css);

    return $css;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course course object
 * @param stdClass $cm
 * @param stdClass $context context object
 * @param string   $filearea file area
 * @param array    $args extra arguments
 * @param bool     $forcedownload whether or not force download
 * @param array    $options additional options affecting the file serving
 *
 * @return mixed The file is sent along with it's headers
 *
 * @return bool
 */
function theme_ouaclean_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM and $filearea === 'logo') {
        $theme = theme_config::load('ouaclean');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }

        return $theme->setting_file_serve('logo', $args, $forcedownload, $options);
    } else if ($context->contextlevel == CONTEXT_SYSTEM and $filearea === 'appleicon') {
        $theme = theme_config::load('ouaclean');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }

        return $theme->setting_file_serve('appleicon', $args, $forcedownload, $options);
    } else if ($context->contextlevel == CONTEXT_SYSTEM and $filearea === 'favicon') {
        $theme = theme_config::load('ouaclean');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }

        return $theme->setting_file_serve('favicon', $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Adds any custom CSS to the CSS before it is cached.
 *
 * @param string $css The original CSS.
 * @param string $customcss The custom CSS to add.
 *
 * @return string The CSS which now contains our custom CSS.
 */
function theme_ouaclean_set_customcss($css, $customcss) {
    $tag = '[[setting:customcss]]';
    $replacement = $customcss;
    if (is_null($replacement)) {
        $replacement = '';
    }

    $css = str_replace($tag, $replacement, $css);

    return $css;
}

function theme_ouaclean_page_init($page) {
    $modifiedpagelayout = $page->pagelayout;

    if ($page->course->id == 1 &&
        (strpos($page->pagetype, 'mod-forum') !== false
         || strpos($page->pagetype, 'mod-page') !== false)) {
        // We dont want in course navigation and blocks on the sitewide forum or pages
        $modifiedpagelayout = 'sitecourse'; // Force single column layout for forum on site page.
        $page->set_pagelayout($modifiedpagelayout);
        $page->theme->setup_blocks($modifiedpagelayout, $page->blocks);

    }
    if ($page->activityname == 'elluminate' && $page->pagelayout == 'standard') {
        // Elluminate is Blackboard's collaboration software, it uses the wrong page type "standard" when it's in a course module.
        // We force it to "incourse" as it's always inside a course and we want our in course navigation and blocks displayed.
        // The standard layout is not necessarily the incourse layout.
        $modifiedpagelayout = 'incourse';
        $page->set_pagelayout($modifiedpagelayout);
        $page->theme->setup_blocks($modifiedpagelayout, $page->blocks);
    }
    if ($page->pagetype == 'user-edit' && $page->pagelayout == 'admin') {
        $modifiedpagelayout = 'ouaprofile';
        $page->set_pagelayout($modifiedpagelayout);
        $page->theme->setup_blocks($modifiedpagelayout, $page->blocks);
    }
    if ($page->pagetype == 'user-profile' && $page->pagelayout == 'mypublic') {
        $modifiedpagelayout = 'ouaprofile';
        $page->set_pagelayout($modifiedpagelayout);
        $page->theme->setup_blocks($modifiedpagelayout, $page->blocks);
    }

}
