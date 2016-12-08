<?php
/**
 *
 * The tours available are the javascript files in the amd/src directory
 * They must be named help_tour_xxxxx.js
 */
 function get_tour_file_list() {
    global $CFG;
    $javascriptmodules = get_directory_list($CFG->dirroot . '/blocks/oua_help_tour/amd/src/', '', false);
    $tourfiles = array();
    foreach ($javascriptmodules as $module) {
        if (strpos($module, 'help_tour_') !== false) {
            $tourname = str_replace('.js', '', $module);
            $tourfiles[$tourname] = $tourname;
        }
    }
    return $tourfiles;
}
