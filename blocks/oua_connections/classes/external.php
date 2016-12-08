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
 * This is the external API for suggested connections
 *
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_connections;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class external extends external_api {
    /**
     * Requests a connection to the given userid.
     *
     * @param string $userid userid to request connection to
     *
     * @return array[string]
     */
    public static function request_connection($userid) {
        $params = self::validate_parameters(self::request_connection_parameters(), array('userid' => $userid));

        return api::request_connection($userid);
    }

    /**
     * Returns description of request_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function request_connection_parameters() {
        return new external_function_parameters(array('userid' => new external_value(PARAM_INT,
                                                                                     'user id of requested connection')));
    }

    /**
     * Returns description of request_connection() result value.
     *
     * @return external_value
     */
    public static function request_connection_returns() {
        return new external_value(PARAM_BOOL, 'Did the request send successfully?');
    }

    /**
     * Returns description of request_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function accept_request_connection_parameters() {
        return new external_function_parameters(array('messageid' => new external_value(PARAM_INT,
                                                                                        'message id of request to accept and dismiss'),
                                                      'userid'    => new external_value(PARAM_INT,
                                                                                        'user id of connection to accept'),));
    }

    /**
     * Requests a connection to the given userid.
     *
     * @param string $messageid message id to dismiss
     * @param string $userid userid to request connection to
     *
     * @return array[string]
     */
    public static function accept_request_connection($messageid, $userid) {
        $params = self::validate_parameters(self::accept_request_connection_parameters(),
                                            array('messageid' => $messageid, 'userid' => $userid));

        return api::accept_request_connection($messageid, $userid);
    }

    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function accept_request_connection_returns() {
        return new external_value(PARAM_BOOL, 'Was the connection successful');
    }


    /**
     * Requests a connection to the given userid.
     *
     * @param string $userid userid to request connection to
     *
     * @return array[string]
     */
    public static function ignore_request_connection($messageid, $userid) {
        $params = self::validate_parameters(self::ignore_request_connection_parameters(),
                                            array('messageid' => $messageid, 'userid' => $userid));

        return api::ignore_request_connection($messageid, $userid);
    }

    /**
     * Returns description of request_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function ignore_request_connection_parameters() {
        return new external_function_parameters(array('messageid' => new external_value(PARAM_INT,
                                                                                        'message id of request to dismiss'),
                                                      'userid'    => new external_value(PARAM_INT,
                                                                                        'user id of connection to accept'),));
    }

    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function ignore_request_connection_returns() {
        return new external_value(PARAM_BOOL, 'Was the request successfully ignored');
    }


    /**
     * Deletes the given connection
     *
     * @param $connectionid Id of notification to delete
     *
     * @return array
     */
    public static function delete_connection($userid) {
        $params = self::validate_parameters(self::delete_connection_parameters(), array('userid' => $userid));

        return api::delete_connection($userid);
    }

    /**
     * Returns description of delete_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_connection_parameters() {
        return new external_function_parameters(array('userid' => new external_value(PARAM_INT,
                                                                                     'User id of connection to to delete')));
    }

    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function delete_connection_returns() {
        return new external_single_structure(array('allmyconnections' => new external_value(PARAM_RAW,
                                                                  'JSON object containing all current connections')));
    }

}
