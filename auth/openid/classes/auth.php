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
 * Anobody can login with any password.
 *
 * @package auth_openid
 * @copyright 2017 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

namespace auth_openid;

defined('MOODLE_INTERNAL') || die();

use pix_icon;
use moodle_url;

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for openid authentication.
 *
 * @package auth_openid
 * @copyright 2017 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class auth extends \auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'openid';
        $this->config = get_config('auth/openid');
    }

    public function get_idp_list() {
        $first = (object)['id' => 1, 'name' => 'Google', 'icon' => 't/edit'];

        return [
            $first
        ];
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return true;
    }

    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    public function config_form($config, $err, $userfields) {
        include("../config.php");
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    public function process_config($config) {
        return true;
    }

    public function loginpage_idp_list($wantsurl) {
        $providers = identity_provider::get_records();
        $result = [];
        if (empty($wantsurl)) {
            $wantsurl = '/';
        }
        foreach ($providers as $idp) {
            $params = ['id' => $idp->get('id'), 'wantsurl' => $wantsurl, 'sesskey' => sesskey()];
            $url = new moodle_url('/auth/openid/login.php', $params);
            $icon = $idp->get('image');
            $result[] = ['url' => $url, 'iconurl' => $icon, 'name' => $idp->get('name')];
        }
        return $result;
    }
}


