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
 * This is the external API for messages
 *
 * @package    block_oua_messages
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_messages;

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;

class external extends external_api {
    /**
     * Returns description of request_connection() parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_message_parameters() {
        return new external_function_parameters(
            array('messageid' => new external_value(PARAM_INT, 'message id of message to delete'))
        );

    }

    /**
     * Requests a connection to the given userid.
     * @param string $userid userid to request connection to
     * @return array[string]
     */
    public static function delete_message($messageid) {
        $params = self::validate_parameters(self::delete_message_parameters(),
            array('messageid' => $messageid));

        return api::delete_message($messageid);
    }

    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function delete_message_returns() {
        return new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'JSON object containing rendered message list'));
    }


    /**
     * Description of result value.
     * @return external_multiple_structure
     */
    public static function mark_message_read_returns()
    {
        return new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'JSON object containing read message id'));
    }

    /**
     * @return external_function_parameters
     */
    public static function mark_message_read_parameters()
    {
        return new external_function_parameters(
            array('messageid' => new external_value(PARAM_INT, 'Message id of marked read message'))
        );
    }

    /**
     * @param $messageid
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function mark_message_read($messageid)
    {
        $params = self::validate_parameters(self::mark_message_read_parameters(),
            array('messageid' => $messageid));

        return api::mark_message_read($messageid);
    }
}
