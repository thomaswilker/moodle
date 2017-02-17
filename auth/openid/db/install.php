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
 * auth_openid installer script.
 *
 * @package    auth_openid
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_auth_openid_install() {
    global $CFG, $DB;

    // Setup default list of identity providers.
    $record = (object) [
        'name' => 'Google',
        'image' => 'data:image/png;base64,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',
        'discoveryendpoint' => 'https://accounts.google.com/.well-known/openid-configuration',
        'clientid' => '415917367313-haalbd9hb7t515le9fehl1ogs5fre9i0.apps.googleusercontent.com',
        'clientsecret' => 'ZFFecu040yGkJOEAmr-a9bQi'
    ];

    $idp = new \auth_openid\identity_provider(0, $record);

    $idp->create();

}
