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
 * Class for loading/storing a database field from the DB.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data;
defined('MOODLE_INTERNAL') || die();

use \core\persistent;

/**
 * Class for loading/storing a database field from the DB.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field extends persistent {

    const TABLE = 'data_fields';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'dataid' => array(
                'type' => PARAM_INT
            ),
            'type' => array(
                'type' => PARAM_ALPHANUMEXT,
            ),
            'name' => array(
                'type' => PARAM_TEXT,
            ),
            'description' => array(
                'default' => '',
                'type' => PARAM_TEXT,
            ),
            'required' => array(
                'type' => PARAM_BOOL,
                'default' => false
            ),
            'param1' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param2' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param3' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param4' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param5' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param6' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param7' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param8' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param9' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'param10' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
        );
    }

    /**
     * Validate the database instance id.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_dataid($value) {
        global $DB;

        if ($value !== null && !$DB->record_exists('data', array('id' => $value))) {
            return new lang_string('invalidmoduleid', 'error', $value);
        }

        return true;
    }

    /**
     * Ensure no orphan records.
     */
    protected function before_delete() {
        $contents = $this->get_contents();
        foreach ($contents as $content) {
            $content->delete();
        }
    }

    /**
     * Helper function to get all the content entries for this field.
     *
     * @return database
     */
    public function get_contents() {
        global $DB;

        $params = ['fieldid' => $this->get('id')];
        $records = $DB->get_records(content::TABLE, $params);
        $relations = [];
        foreach ($records as $record) {
            $relations[] = new content(0, $record);
        }

        return $relations;
    }

    /**
     * Helper to get the related persistent.
     *
     * @return database
     */
    public function get_database() {
        return new database($this->get('dataid'));
    }

}
