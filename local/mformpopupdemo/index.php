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
 * Show a link to open a popup mform
 *
 * @package    local_mformpopupdemo
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/mformpopupdemo/demo_form.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$pageparams = array();
admin_externalpage_setup('local_mformpopupdemo', '', $pageparams);

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'local_mformpopupdemo'));

$mform = new local_mformpopupdemo_form();
$mform->set_data((object) array());

$output = $PAGE->get_renderer('local_mformpopupdemo');

echo $OUTPUT->header();

$formurl = new moodle_url('/local/mformpopupdemo/viewdemoform.php');
echo $output->container($output->popup_form_link('First popup link', $formurl));
echo $output->container($output->popup_form_link('Second popup link', $formurl));

echo $OUTPUT->footer();
