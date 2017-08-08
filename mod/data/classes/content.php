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
 * Class for loading/storing a database record from the DB.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data;
defined('MOODLE_INTERNAL') || die();

use \core\persistent;

/**
 * Class for loading/storing a database content from the DB.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends persistent {

    const TABLE = 'data_content';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'fieldid' => array(
                'type' => PARAM_INT,
            ),
            'recordid' => array(
                'type' => PARAM_INT,
            ),
            'content' => array(
                'type' => PARAM_RAW,
            ),
            'content1' => array(
                'type' => PARAM_RAW,
            ),
            'content2' => array(
                'type' => PARAM_RAW,
            ),
            'content3' => array(
                'type' => PARAM_RAW,
            ),
            'content4' => array(
                'type' => PARAM_RAW,
            )
        );
    }

    /**
     * Validate the database instance id.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_recordid($value) {
        $database = $this->get_related_record()->get_database();

        $database2 = $this->get_field()->get_database();

        if ($database->get('id') != $database2->get('id')) {
            return new lang_string('invalidmoduleid', 'error', $value);
        }

        return true;
    }

    /**
     * Helper to get the related persistent.
     *
     * @return record
     */
    public function get_related_record() {
        return new record($this->get('recordid'));
    }

    /**
     * Helper to get the related persistent.
     *
     * @return field
     */
    public function get_field() {
        return new field($this->get('fieldid'));
    }

    /**
     * Clean up files on delete.
     */
    protected function before_delete() {
        $context = $this->get_field()->get_database()->get_context();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_data', 'content', $this->get('id'));
    }

}
