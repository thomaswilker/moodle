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
 * Configurable oauth2client class.
 *
 * @package    auth_openid
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_openid;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/oauthlib.php');
require_once($CFG->libdir . '/filelib.php');

use moodle_url;
use curl;

/**
 * Configurable oauth2client class where the urls come from DB.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth2_client extends \oauth2_client {

    /** @var auth_openid\identity_provider $identityprovider */
    private $identityprovider;

    protected function discover_endpoints() {

        if ($this->identityprovider->get('tokenendpoint') == '' ||
                $this->identityprovider->get('userinfoendpoint') == '' ||
                $this->identityprovider->get('authorizationendpoint') == '' ||
                $this->identityprovider->get('revocationendpoint') == '' ||
                $this->identityprovider->get('scopessupported') == '') {

            $curl = new curl();
            if (!$json = $curl->get($this->identityprovider->get('discoveryendpoint'))) {
                $msg = 'Could not discover end points for identity provider' . $this->identityprovider->get('name');
                throw new moodle_exception($msg);
            }

            $info = json_decode($json);

            $baseurl = parse_url($this->identityprovider->get('discoveryendpoint'));
            $imageurl = $baseurl['scheme'] . '://' . $baseurl['host'] . '/favicon.ico';
            $this->identityprovider->set('image', $imageurl);

            if (!empty($info->authorization_endpoint)) {
                $this->identityprovider->set('authorizationendpoint', $info->authorization_endpoint);
            }

            if (!empty($info->token_endpoint)) {
                $this->identityprovider->set('tokenendpoint', $info->token_endpoint);
            }
            if (!empty($info->userinfo_endpoint)) {
                $this->identityprovider->set('userinfoendpoint', $info->userinfo_endpoint);
            }
            if (!empty($info->revocation_endpoint)) {
                $this->identityprovider->set('revocationendpoint', $info->revocation_endpoint);
            }
            if (!empty($info->scopes_supported)) {
                $this->identityprovider->set('scopessupported', implode(' ', $info->scopes_supported));
            }

            $this->identityprovider->update();
        }
    }

    /**
     * Constructor.
     *
     * @param identity_provider $identityprovider
     * @param moodle_url $returnurl
     */
    public function __construct(identity_provider $idp, moodle_url $returnurl) {
        $this->identityprovider = $idp;
        $this->discover_endpoints();
        parent::__construct($idp->get('clientid'), $idp->get('clientsecret'), $returnurl, 'openid email profile');
    }

    /**
     * Returns the auth url for OAuth 2.0 request
     * @return string the auth url
     */
    protected function auth_url() {
        return $this->identityprovider->get('authorizationendpoint');
    }

    /**
     * Returns the token url for OAuth 2.0 request
     * @return string the auth url
     */
    protected function token_url() {
        return $this->identityprovider->get('tokenendpoint');
    }
}
