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
 * RequireJS helper functions.
 *
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Collection of requirejs related methods.
 */
class core_requirejs {

    /**
     * Scan the source for AMD modules and return them all.
     *
     * The expected location for amd modules is:
     *  <componentdir>/amd/modulename.js
     *
     * @return array $files
     */
    public static function find_all_amd_modules() {
        global $CFG;

        $jsdirs = array('core' => $CFG->libdir . '/amd/');
        $jsfiles = array();
        $subsystems = core_component::get_core_subsystems();
        foreach ($subsystems as $subsystem => $dir) {
            if (!empty($dir) && file_exists($dir . '/amd')) {
                $jsdirs[$subsystem] = $dir . '/amd';
            }
        }
        $plugintypes = core_component::get_plugin_types();
        foreach ($plugintypes as $type => $dir) {
            $plugins = core_component::get_plugin_list_with_file($type, 'amd', false);
            foreach ($plugins as $plugin => $dir) {
                $jsdirs[$plugin] = $dir;
            }
        }

        foreach ($jsdirs as $component => $dir) {
            $items = new RecursiveDirectoryIterator($dir);
            foreach ($items as $item) {
                $extension = $item->getExtension();
                if ($extension === 'js') {
                    $jsfiles[$component . '/' . $item->getBaseName('.js')] = $item->getRealPath();
                }
                unset($item);
            }
            unset($items);
        }

        return $jsfiles;
    }

}
