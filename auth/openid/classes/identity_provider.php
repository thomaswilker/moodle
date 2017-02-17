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
 * Class for loading/storing identity providers from the DB.
 *
 * @package    auth_openid
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_openid;

defined('MOODLE_INTERNAL') || die();

use core\persistent;

/**
 * Class for loading/storing identity providers from the DB
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class identity_provider extends persistent {

    const TABLE = 'auth_openid_idp';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'name' => array(
                'type' => PARAM_TEXT
            ),
            'image' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'discoveryendpoint' => array(
                'type' => PARAM_RAW
            ),
            'clientid' => array(
                'type' => PARAM_RAW
            ),
            'clientsecret' => array(
                'type' => PARAM_RAW
            ),
            'tokenendpoint' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'authorizationendpoint' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'userinfoendpoint' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'revocationendpoint' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'scopessupported' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            )
        );
    }
}
