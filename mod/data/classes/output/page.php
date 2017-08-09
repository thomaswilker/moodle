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
 * Renderable for entire database module page.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use stdClass;
use templatable;
use mod_data\database;
use mod_data\external\database_exporter;
use mod_data\external\capability_exporter;

/**
 * Renderable for the entire page of a database module UI.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page implements renderable, templatable {

    /**
     * @var database $database
     */
    private $database = null;

    /**
     * @var context $context
     */
    private $context = null;

    /**
     * @param database $database The database we are viewing.
     * @param context $context The page context.
     * @param stdClass $course The course record.
     * @param cm_info $cm The page module.
     * @param string $current The current tab id.
     */
    public function __construct($database, $context, $course, $cm, $current) {
        $this->database = $database;
        $this->context = $context;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        global $OUTPUT;

        $context = new stdClass();
        $exporter = new database_exporter($this->database, ['context' => $this->context]);
        $context->database = $exporter->export($output);

        $exporter = new capability_exporter(null, ['context' => $this->context]);
        $context->capabilities = $exporter->export($output);
        return $context;
    }
}
