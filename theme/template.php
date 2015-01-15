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
 * This file is responsible for serving renderer template files to JS.
 *
 * @package   core
 * @copyright 2009 Petr Skoda (skodak)  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

require('../config.php');

$slashargument = get_file_argument();
if (substr_count($slashargument, '/') < 2) {
    template_not_found();
}

list($empty, $rev, $component, $templatename) = explode('/', $slashargument, 4);

$rev       = clean_param($rev, PARAM_INT);
$component = clean_param($component, PARAM_ALPHANUMEXT);
$templatename = clean_param($templatename, PARAM_ALPHANUMEXT);

// Check if this is a valid component.
$componentdir = core_component::get_component_directory($component);
if (empty($componentdir)) {
    template_not_found();
}

// Places to look.
$candidates = array();

// Theme dir.
$root = $CFG->dirroot;
$theme = $PAGE->theme->name;
$candidate = "${root}/theme/${theme}/templates/${component}/${templatename}.mustache";
$candidates[] = $candidate;

// Theme parents dir.
foreach ($PAGE->theme->parents as $theme) {
    $candidate = "${root}/theme/${theme}/templates/${component}/${templatename}.mustache";
    $candidates[] = $candidate;
}
// Component dir.
$candidate = "${componentdir}/templates/${templatename}.mustache";
$candidates[] = $candidate;


foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        send_cached_template($candidate, $etag);
    }
}
template_not_found();

function template_not_found() {
    header('HTTP/1.0 404 not found');
    die('Template was not found, sorry.');
}

function send_cached_template($templatepath) {
    global $CFG;
    require("$CFG->dirroot/lib/xsendfilelib.php");

    $lifetime = 60*60*24*60; // 60 days only - the revision may get incremented quite often
    $pathinfo = pathinfo($templatepath);
    $templatename = $pathinfo['filename'].'.'.$pathinfo['extension'];

    $mimetype = "text/mustache";

    header('Content-Disposition: inline; filename="'.$templatename.'"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($templatepath)) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime.', no-transform');
    header('Accept-Ranges: none');
    header('Content-Type: '.$mimetype);
    header('Content-Length: '.filesize($templatepath));

    if (xsendfile($templatepath)) {
        die;
    }

    readfile($templatepath);
    die;
}
