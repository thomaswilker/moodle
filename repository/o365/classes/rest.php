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
 * Office 365 Rest API.
 *
 * @package    repository_o365
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace repository_o365;

defined('MOODLE_INTERNAL') || die();

/**
 * Office 365 Rest API
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rest extends \core\oauth2\rest {

    /**
     * Define the functions of the rest API.
     *
     * @return array Example:
     *  [ 'listFiles' => [ 'method' => 'get', 'endpoint' => 'http://...', 'args' => [ 'folder' => PARAM_STRING ] ] ]
     */
    public function get_api_functions() {
        return [
            'list' => [
                'endpoint' => 'https://graph.microsoft.com/v1.0/me/drive/{parent}/children',
                'method' => 'get',
                'args' => [
                    '$select' => PARAM_RAW,
                    '$expand' => PARAM_RAW,
                    'parent' => PARAM_RAW,
                    '$skip' => PARAM_INT,
                    '$skipToken' => PARAM_RAW,
                    '$count' => PARAM_INT
                ],
                'response' => 'json'
            ],
            'search' => [
                'endpoint' => 'https://graph.microsoft.com/v1.0/me/drive/{parent}/search(q=\'{search}\')',
                'method' => 'get',
                'args' => [
                    'search' => PARAM_NOTAGS,
                    '$select' => PARAM_RAW,
                    'parent' => PARAM_RAW,
                    '$skip' => PARAM_INT,
                    '$skipToken' => PARAM_RAW,
                    '$count' => PARAM_INT
                ],
                'response' => 'json'
            ],
            'get' => [
                'endpoint' => 'https://graph.microsoft.com/v1.0/me/drive/items/{id}',
                'method' => 'get',
                'args' => [
                    'id' => PARAM_RAW,
                    '$select' => PARAM_RAW,
                    '$expand' => PARAM_RAW
                ],
                'response' => 'json'
            ]
        ];
    }
}
