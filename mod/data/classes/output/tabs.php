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
 * Renderable for database module tabs.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data\output;
defined('MOODLE_INTERNAL') || die();

use tabtree;
use tabobject;
use moodle_url;

/**
 * Renderable for the tabs display at the top of the database module.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tabs {

    /**
     * Generate list of tabs.
     *
     * @param database $database The database we are viewing
     * @param context $context The database context
     * @param string $current The current tab id
     * @param stdClass $course The course record
     * @return tabobject[]
     */
    public static function generate_tabs($database, $context, $current, $course) {
        // Build the list of tabs.

        $row = [];
        $url = new moodle_url('/mod/data/newview.php', array('d' => $database->get('id')));
        $row[] = new tabobject('list', $url, get_string('list', 'data'));
        $url = new moodle_url('/mod/data/newview.php', ['d' => $database->get('id'), 'mode' => 'single']);
        $row[] = new tabobject('single', $url, get_string('single', 'data'));
        $url = new moodle_url('/mod/data/newview.php', ['d' => $database->get('id'), 'mode' => 'asearch']);
        $row[] = new tabobject('asearch', $url, get_string('search', 'data'));

        /*
        if (data_user_can_add_entry($data, $currentgroup, $groupmode, $context)) {
            $addstring = empty($editentry) ? get_string('add', 'data') : get_string('editentry', 'data');
            $row[] = new tabobject('add', new moodle_url('/mod/data/edit.php', array('d' => $data->id)), $addstring);
        }
        if (has_capability(DATA_CAP_EXPORT, $context)) {
            // The capability required to Export database records is centrally defined in 'lib.php'
            // and should be weaker than those required to edit Templates, Fields and Presets.
            $row[] = new tabobject('export', new moodle_url('/mod/data/export.php', array('d' => $data->id)),
                         get_string('export', 'data'));
        }
        if (has_capability('mod/data:managetemplates', $context)) {
            if ($currenttab == 'list') {
                $defaultemplate = 'listtemplate';
            } else if ($currenttab == 'add') {
                $defaultemplate = 'addtemplate';
            } else if ($currenttab == 'asearch') {
                $defaultemplate = 'asearchtemplate';
            } else {
                $defaultemplate = 'singletemplate';
            }

            if ($data->usetemplates) {
                $templatestab = new tabobject('templates', new moodle_url('/mod/data/templates.php', array('d' => $data->id, 'mode' => $defaultemplate)),
                             get_string('templates','data'));
                $row[] = $templatestab;
            }
            $row[] = new tabobject('fields', new moodle_url('/mod/data/field.php', array('d' => $data->id)),
                         get_string('fields','data'));
            $row[] = new tabobject('presets', new moodle_url('/mod/data/preset.php', array('d' => $data->id)),
                         get_string('presets', 'data'));
        }

        if ($currenttab == 'templates' and isset($mode) && isset($templatestab)) {
        */
        return new tabtree($row, $current);
    }
}
