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
 * Class for loading/storing a database from the DB.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data;
defined('MOODLE_INTERNAL') || die();

use \core\persistent;
use context_module;

/**
 * Class for loading/storing a database from the DB.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database extends persistent {

    const TABLE = 'data';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'course' => array(
                'type' => PARAM_INT
            ),
            'name' => array(
                'type' => PARAM_TEXT,
            ),
            'intro' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'introformat' => array(
                'choices' => array(FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN),
                'type' => PARAM_INT,
                'default' => FORMAT_HTML
            ),
            'comments' => array(
                'default' => false,
                'type' => PARAM_BOOL,
            ),
            'timeavailablefrom' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'timeavailableto' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'timeviewfrom' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'timeviewto' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'requiredentries' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'requiredentriestoview' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'maxentries' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'rssarticles' => array(
                'default' => 0,
                'type' => PARAM_INT,
            ),
            'singletemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'listtemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'listtemplateheader' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'listtemplatefooter' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'addtemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'rsstemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'rsstitletemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'csstemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'jstemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'asearchtemplate' => array(
                'default' => '',
                'type' => PARAM_CLEANHTML,
            ),
            'approval' => array(
                'default' => false,
                'type' => PARAM_BOOL,
            ),
            'manageapproved' => array(
                'default' => true,
                'type' => PARAM_BOOL,
            ),
            'scale' => array(
                'type' => PARAM_INT
            ),
            'scale' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'assessed' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'assesstimestart' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'assesstimefinish' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'defaultsort' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'defaultsortdir' => array(
                'type' => PARAM_BOOL,
                'default' => false
            ),
            'editany' => array(
                'type' => PARAM_BOOL,
                'default' => false
            ),
            'completionentries' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'config' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'notification' => array(
                'default' => false,
                'type' => PARAM_BOOL,
            ),
        );
    }

    /**
     * Helper to get the context for a database instance
     * @return context
     */
    public function get_context() {
        $cm = get_coursemodule_from_instance('data', $this->get('id'));
        return context_module::instance($cm->id);
    }

    /**
     * Validate the database instance id.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_timeavailableto($value) {
        // Check open and close times are consistent.
        if ($this->get('timeavailablefrom') && $value &&
                $value < $this->get('timeavailablefrom')) {
            return new lang_string('availabletodatevalidation', 'data');
        }
    }

    /**
     * Validate the database instance id.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_timeviewto($value) {
        // Check open and close times are consistent.
        if ($this->get('timeviewfrom') && $value &&
                $value < $this->get('timeviewfrom')) {
            return new lang_string('viewtodatevalidation', 'data');
        }
    }
}
