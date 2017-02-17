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
 * Open ID authentication.
 *
 * @package auth_openid
 * @copyright 2017 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('../../config.php');

$idpid = required_param('id', PARAM_INT);
$wantsurl = new moodle_url(optional_param('wantsurl', '/', PARAM_URL));

require_sesskey();

$idp = new \auth_openid\identity_provider($idpid);

$returnparams = ['wantsurl' => $wantsurl, 'sesskey' => sesskey(), 'id' => $idpid];
$returnurl = new moodle_url('/auth/openid/login.php', $returnparams);

$client = new \auth_openid\oauth2_client($idp, $returnurl);

if (!$client->is_logged_in()) {
    redirect($client->get_login_url());
} else {
    
    die($wantsurl);
    redirect($wantsurl);
}
