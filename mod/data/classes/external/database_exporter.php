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
 * Class for exporting database data.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data\external;

defined('MOODLE_INTERNAL') || die();

use mod_data\database;
use core\external\persistent_exporter;
use external_files;
use external_util;
use renderer_base;

/**
 * Class for exporting database data.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database_exporter extends persistent_exporter {

    protected static function define_class() {
        return database::class;
    }

    protected static function define_related() {
        return array(
            'context' => 'context',
        );
    }

    protected static function define_other_properties() {
        return array(
            'coursemodule' => array(
                'type' => PARAM_INT
            ),
            'introfiles' => array(
                'type' => external_files::get_properties_for_exporter(),
                'multiple' => true,
                'optional' => true,
            ),
        );
    }

    protected function get_other_values(renderer_base $output) {
        $context = $this->related['context'];

        $values = array(
            'coursemodule' => $context->instanceid,
            'introfiles' => external_util::get_area_files($context->id, 'mod_data', 'intro', false, false),
        );

        return $values;
    }

    /**
     * Get the formatting parameters for the intro.
     *
     * @return array
     */
    protected function get_format_parameters_for_intro() {
        return [
            'component' => 'mod_data',
            'filearea' => 'intro',
        ];
    }
}
