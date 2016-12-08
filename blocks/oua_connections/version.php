<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_oua_connections'; // Full name of the plugin (used for diagnostics).
$plugin->version = 2015092900;  // YYYYMMDDHH (year, month, day, 24-hr time.
$plugin->requires = 2015032600;
$plugin->dependencies = array('theme_ouaclean' => 2015050500); // Requires blockui.
