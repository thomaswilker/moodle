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
 * Class for loading/storing database entities from the DB.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data;
defined('MOODLE_INTERNAL') || die();

use stdClass;
use completion_info;

/**
 * Class for doing things with database.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Create a database field from a record containing all the data for the class.
     *
     * Requires moodle/competency:competencymanage capability at the system context.
     *
     * @param stdClass $record Record containing all the data for an instance of the class.
     * @return field
     */
    public static function create_field(stdClass $record) {
        $field = new field(0, $record);

        $database = $field->get_database();

        // First we do a permissions check.
        require_capability('mod/data:managetemplates', $database->get_context());

        $errors = $field->validate();

        $field->create();

        // Trigger an event for creating this field.
        $event = \mod_data\event\field_created::create(array(
            'objectid' => $field->get('id'),
            'context' => $database->get_context(),
            'other' => array(
                'fieldname' => $field->get('name'),
                'dataid' => $field->get('dataid')
            )
        ));
        $event->trigger();

        return $field;
    }

    /**
     * Load a field record.
     *
     * @param int $fieldid The field id we are retrieving.
     * @return field
     */
    public static function get_field($fieldid) {
        $field = new field($fieldid);
        $context = $field->get_database()->get_context();

        require_capability('mod/data:view', $context);

        return $field;
    }

    /**
     * Load all field records.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the fields.
     * @return field[]
     */
    public static function get_fields($databaseorid = 0) {
        $database = $databaseorid;
        if (!is_object($databaseorid)) {
            $database = new database($databaseorid);
        }
        $context = $database->get_context();

        require_capability('mod/data:view', $context);

        return $database->get_fields();
    }

    /**
     * Update a database field from a record containing all the data for the class.
     *
     * Requires moodle/competency:competencymanage capability at the system context.
     *
     * @param stdClass $record Record containing all the data for an instance of the class.
     * @return boolean
     */
    public static function update_field(stdClass $record) {
        $field = new field(0, $record);

        $database = $field->get_database();
        $context = $database->get_context();
        // First we do a permissions check.
        require_capability('mod/data:managetemplates', $context);

        $result = $field->update();

        if ($result) {
            // Trigger an event for updating this field.
            $event = \mod_data\event\field_updated::create(array(
                'objectid' => $field->get('id'),
                'context' => $context,
                'other' => array(
                    'fieldname' => $field->get('name'),
                    'dataid' => $database->get('id')
                )
            ));
            $event->trigger();
        }

        return $result;
    }

    /**
     * Delete a field completely
     *
     * @param int $fieldid The id of the field to delete.
     * @return bool
     */
    public static function delete_field($fieldid) {
        $field = new field($fieldid);

        $database = $field->get_database();
        $context = $database->get_context();
        // First we do a permissions check.
        require_capability('mod/data:managetemplates', $context);

        // Field persistent will also delete all the content records.
        $result = $field->delete();

        // Trigger an event for deleting this field.
        if ($result) {
            $event = \mod_data\event\field_deleted::create(array(
                'objectid' => $field->get('id'),
                'context' => $context,
                'other' => array(
                    'fieldname' => $field->get('name'),
                    'dataid' => $database->get('id')
                 )
            ));
            $event->add_record_snapshot('data_fields', $field->to_record());
            $event->trigger();
        }

        return $result;
    }

    /**
     * Create a database record and return it.
     *
     * @param stdClass $database The database record.
     * @return database
     */
    public static function create_database(stdClass $database) {
    }

    /**
     * Load a database record.
     *
     * @param int $databaseid The database id.
     * @return database
     */
    public static function get_database($databaseid) {
        $database = new database($databaseid);
        $context = $database->get_context();

        require_capability('mod/data:view', $context);

        return $database;
    }

    /**
     * Update a database record.
     *
     * @param stdClass $database The database record.
     * @return database
     */
    public static function update_database(stdClass $database) {
    }

    /**
     * Delete a database record.
     *
     * @param int $databaseid The database recordid.
     * @return boolean
     */
    public static function delete_database($databaseorid) {
    }

    /**
     * Record a log action that the database module was viewed.
     *
     * @param database $database The database viewed.
     * @param stdClass $course The course record
     * @param stdClass $cm The course module record
     * @param context $context The context
     * @return bool
     */
    public static function database_viewed($database, $course, $cm, $context) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        // Trigger course_module_viewed event.
        $params = array(
            'context' => $context,
            'objectid' => $database->get('id')
        );

        $event = \mod_data\event\course_module_viewed::create($params);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('data', $database->to_record());
        $event->trigger();

        // Completion.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);
    }

    /**
     * Create a record.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param stdClass[] $contents List of contents to attach to the record.
     * @return record
     */
    public static function create_record($databaseorid = 0, $records) {
    }

    /**
     * Load all record records.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param int $page
     * @param int $perpage
     * @return record[]
     */
    public static function get_records($databaseorid = 0, $page = 0, $perpage = 50) {
    }

    /**
     * Count records viewable in a database.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the records.
     * @return int
     */
    public static function count_records($databaseorid = 0) {
    }

    /**
     * Load all content records.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param mixed $recordorid The record, or the id of the record containing the content.
     * @return content[]
     */
    public static function get_contents($databaseorid = 0, $recordorid = 0) {
    }

    /**
     * Load all records and content in one go.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param int $page
     * @param int $perpage
     * @return stdClass[] - containing record and contents fields, contents in an array of content.
     */
    public static function get_records_and_contents($databaseorid = 0, $page = 0, $perpage = 50) {
    }

    /**
     * Search for records and content (simple search).
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param string $query The search query.
     * @param int $page
     * @param int $perpage
     * @return stdClass[] - containing record and contents fields, contents in an array of content.
     */
    public static function search_records_and_contents($databaseorid = 0, $query = '', $page = 0, $perpage = 50) {
    }

    /**
     * Search for records and content (simple search).
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param string $query The search query.
     * @return int
     */
    public static function count_search_records_and_contents($databaseorid = 0, $query = '') {
    }

    /**
     * Update a record.
     *
     * @param mixed $databaseorid The database, or the id of the database containing the record.
     * @param mixed $recordorid The record, or the id of the record containing the contents.
     * @param stdClass[] $contents List of contents to attach to the record.
     * @return record
     */
    public static function update_record($databaseorid = 0, $records) {
    }

    /**
     * Delete a record.
     *
     * @param mixed $recordorid The record, or the id of the record containing the contents.
     * @return boolean
     */
    public static function delete_record($recordorid = 0) {
    }

    /**
     * Get the full list of capabilities of a user in this module instance.
     *
     * @param context $context The context
     * @param int $userid The user to check. Optional.
     * @return array of capabilities
     */
    public static function get_capabilities($context, $userid = 0) {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        return [
            'canview' => has_capability('mod/data:view', $context, $userid),
            'canexportuserinfo' => has_capability('mod/data:exportuserinfo', $context, $userid),
            'canexportallentries' => has_capability('mod/data:exportallentries', $context, $userid),
            'canexportownentry' => has_capability('mod/data:exportownentry', $context, $userid),
            'canmanageuserpresets' => has_capability('mod/data:manageuserpresets', $context, $userid),
            'canviewalluserpresets' => has_capability('mod/data:viewalluserpresets', $context, $userid),
            'canmanagetemplates' => has_capability('mod/data:managetemplates', $context, $userid),
            'canmanagecomments' => has_capability('mod/data:managecomments', $context, $userid),
            'canmanageentries' => has_capability('mod/data:manageentries', $context, $userid),
            'canapprove' => has_capability('mod/data:approve', $context, $userid),
            'canviewallratings' => has_capability('mod/data:viewallratings', $context, $userid),
            'canviewanyrating' => has_capability('mod/data:viewanyrating', $context, $userid),
            'canviewrating' => has_capability('mod/data:viewrating', $context, $userid),
            'canrate' => has_capability('mod/data:rate', $context, $userid),
            'cancomment' => has_capability('mod/data:comment', $context, $userid),
            'canwriteentry' => has_capability('mod/data:writeentry', $context, $userid),
            'canviewentry' => has_capability('mod/data:viewentry', $context, $userid)
        ];
    }

}
