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
 * This plugin is used to access Office 365
 *
 * @package    repository_o365
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->libdir . '/google/lib.php');

/**
 * Office 365 plugin
 *
 * @package    repository_o365
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_o365 extends repository {

    /**
     * OAuth 2 client
     * @var \core\oauth2\client
     */
    private $client = null;

    /**
     * OAuth 2 Issuer
     * @var \core\oauth2\issuer
     */
    private $issuer = null;

    /**
     * Additional scopes required for drive.
     */
    const SCOPES = 'files.readwrite';

    /**
     * Constructor.
     *
     * @param int $repositoryid repository instance id.
     * @param int|stdClass $context a context id or context object.
     * @param array $options repository options.
     * @param int $readonly indicate this repo is readonly or not.
     * @return void
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = [], $readonly = 0) {
        parent::__construct($repositoryid, $context, $options, $readonly = 0);

        $this->issuer = \core\oauth2\api::get_issuer(get_config('o365', 'issuerid'));
    }

    /**
     * Get a cached user authenticated oauth client.
     *
     * @return \core\oauth2\client
     */
    protected function get_user_oauth_client() {
        if ($this->client) {
            return $this->client;
        }
        $returnurl = new moodle_url('/repository/repository_callback.php');
        $returnurl->param('callback', 'yes');
        $returnurl->param('repo_id', $this->id);
        $returnurl->param('sesskey', sesskey());

        $this->client = \core\oauth2\api::get_user_oauth_client($this->issuer, $returnurl, self::SCOPES);

        return $this->client;
    }

    /**
     * Checks whether the user is authenticate or not.
     *
     * @return bool true when logged in.
     */
    public function check_login() {
        $client = $this->get_user_oauth_client();
        return $client->is_logged_in();
    }

    /**
     * Print or return the login form.
     *
     * @return void|array for ajax.
     */
    public function print_login() {
        $client = $this->get_user_oauth_client();
        $url = $client->get_login_url();

        if ($this->options['ajax']) {
            $popup = new stdClass();
            $popup->type = 'popup';
            $popup->url = $url->out(false);
            return ['login' => [$popup]];
        } else {
            echo '<a target="_blank" href="'.$url->out(false).'">'.get_string('login', 'repository').'</a>';
        }
    }

    /**
     * Build the breadcrumb from a path.
     *
     * @param string $path to create a breadcrumb from.
     * @return array containing name and path of each crumb.
     */
    protected function build_breadcrumb($path) {
        $bread = explode('/', $path);
        $crumbtrail = '';
        foreach ($bread as $crumb) {
            list($id, $name) = $this->explode_node_path($crumb);
            $name = empty($name) ? $id : $name;
            $breadcrumb[] = [
                'name' => $name,
                'path' => $this->build_node_path($id, $name, $crumbtrail)
            ];
            $tmp = end($breadcrumb);
            $crumbtrail = $tmp['path'];
        }
        return $breadcrumb;
    }

    /**
     * Generates a safe path to a node.
     *
     * Typically, a node will be id|Name of the node.
     *
     * @param string $id of the node.
     * @param string $name of the node, will be URL encoded.
     * @param string $root to append the node on, must be a result of this function.
     * @return string path to the node.
     */
    protected function build_node_path($id, $name = '', $root = '') {
        $path = $id;
        if (!empty($name)) {
            $path .= '|' . urlencode($name);
        }
        if (!empty($root)) {
            $path = trim($root, '/') . '/' . $path;
        }
        return $path;
    }

    /**
     * Returns information about a node in a path.
     *
     * @see self::build_node_path()
     * @param string $node to extrat information from.
     * @return array about the node.
     */
    protected function explode_node_path($node) {
        if (strpos($node, '|') !== false) {
            list($id, $name) = explode('|', $node, 2);
            $name = urldecode($name);
        } else {
            $id = $node;
            $name = '';
        }
        $id = urldecode($id);
        return [
            0 => $id,
            1 => $name,
            'id' => $id,
            'name' => $name
        ];
    }


    /**
     * List the files and folders.
     *
     * @param  string $path path to browse.
     * @param  string $page page to browse.
     * @return array of result.
     */
    public function get_listing($path='', $page = '') {
        if (empty($path)) {
            $path = $this->build_node_path('root', get_string('pluginname', 'repository_o365'));
        }

        // We analyse the path to extract what to browse.
        $trail = explode('/', $path);
        $uri = array_pop($trail);
        list($id, $name) = $this->explode_node_path($uri);

        // Handle the special keyword 'search', which we defined in self::search() so that
        // we could set up a breadcrumb in the search results. In any other case ID would be
        // 'root' which is a special keyword, or a parent (folder) ID.
        if ($id === 'search') {
            $q = $name;
            $id = 'root';

            // Append the active path for search.
            $str = get_string('searchfor', 'repository_o365', $searchtext);
            $path = $this->build_node_path('search', $str, $path);
        }

        // Query the Drive.
        $parent = $id;
        if ($parent != 'root') {
            $parent = 'items/' . $parent;
        }
        $q = '';
        $results = $this->query($q, $path, $parent);

        $ret = [];
        $ret['dynload'] = true;
        $ret['path'] = $this->build_breadcrumb($path);
        $ret['list'] = $results;
        return $ret;
    }

    /**
     * Search throughout the Google Drive.
     *
     * @param string $searchtext text to search for.
     * @param int $page search page.
     * @return array of results.
     */
    public function search($searchtext, $page = 0) {
        $path = $this->build_node_path('root', get_string('pluginname', 'repository_o365'));
        $str = get_string('searchfor', 'repository_o365', $searchtext);
        $path = $this->build_node_path('search', $str, $path);

        // Query the Drive.
        $parent = 'root';
        $results = $this->query($searchtext, $path, 'root');

        $ret = [];
        $ret['dynload'] = true;
        $ret['path'] = $this->build_breadcrumb($path);
        $ret['list'] = $results;
        return $ret;
    }

    /**
     * Query Google Drive for files and folders using a search query.
     *
     * Documentation about the query format can be found here:
     *   https://developers.google.com/drive/search-parameters
     *
     * This returns a list of files and folders with their details as they should be
     * formatted and returned by functions such as get_listing() or search().
     *
     * @param string $q search query as expected by the Google API.
     * @param string $path parent path of the current files, will not be used for the query.
     * @param int $page page.
     * @return array of files and folders.
     */
    protected function query($q, $path = null, $parent = null, $page = 0) {
        global $OUTPUT;

        $files = [];
        $folders = [];
        $fields = "folder,id,lastModifiedDateTime,name,size,webUrl,thumbnails";
        $params = ['$select' => $fields, '$expand' => 'thumbnails', 'parent' => $parent];

        try {
            // Retrieving files and folders.
            $client = $this->get_user_oauth_client();
            $service = new repository_o365\rest($client);

            if (!empty($q)) {
                $params['search'] = urlencode($q);

                // MS does not return thumbnails on a search.
                unset($params['$expand']);
                $response = $service->call('search', $params);
            } else {
                $response = $service->call('list', $params);
            }
        } catch (Exception $e) {
            if ($e->getCode() == 403 && strpos($e->getMessage(), 'Access Not Configured') !== false) {
                // This is raised when the service Drive API has not been enabled on Google APIs control panel.
                throw new repository_exception('servicenotenabled', 'repository_o365');
            } else {
                throw $e;
            }
        }

        $remotefiles = isset($response->value) ? $response->value : [];
        foreach ($remotefiles as $remotefile) {
            if (!empty($remotefile->folder)) {
                // This is a folder.
                $folders[$remotefile->id] = [
                    'title' => $remotefile->name,
                    'path' => $this->build_node_path($remotefile->id, $remotefile->name, $path),
                    'date' => strtotime($remotefile->lastModifiedDateTime),
                    'thumbnail' => $OUTPUT->pix_url(file_folder_icon(64))->out(false),
                    'thumbnail_height' => 64,
                    'thumbnail_width' => 64,
                    'children' => []
                ];
            } else {
                // We can download all other file types.
                $title = $remotefile->name;
                $sourceurl = new moodle_url('https://graph.microsoft.com/v1.0/me/drive/items/' . $remotefile->id . '/content');
                $source = $sourceurl->out(false);

                // Adds the file to the file list. Using the itemId along with the name as key
                // of the array because Google Drive allows files with identical names.
                $thumb = '';
                $thumbwidth = 0;
                $thumbheight = 0;
                $extendedinfoerr = false;

                if (empty($remotefile->thumbnails)) {
                    // Try and get it directly from the item.
                    $params = ['id' => $remotefile->id, '$select' => $fields, '$expand' => 'thumbnails'];
                    try {
                        $response = $service->call('get', $params);
                        $remotefile = $response;
                    } catch (Exception $e) {
                        // This is not a failure condition - we just could not get extended info about the file.
                        $extendedinfoerr = true;
                    }
                }

                if (!empty($remotefile->thumbnails)) {
                    $thumbs = $remotefile->thumbnails;
                    if (count($thumbs)) {
                        $first = reset($thumbs);
                        if (!empty($first->medium) && !empty($first->medium->url)) {
                            $thumb = $first->medium->url;
                            $thumbwidth = min($first->medium->width, 64);
                            $thumbheight = min($first->medium->height, 64);
                        }
                    }
                }

                $files[$remotefile->id] = [
                    'title' => $title,
                    'source' => $source,
                    'date' => strtotime($remotefile->lastModifiedDateTime),
                    'size' => isset($remotefile->size) ? $remotefile->size : null,
                    'thumbnail' => $thumb,
                    'thumbnail_height' => $thumbwidth,
                    'thumbnail_width' => $thumbheight,
                ];
            }
        }

        // Filter and order the results.
        $files = array_filter($files, [$this, 'filter']);
        core_collator::ksort($files, core_collator::SORT_NATURAL);
        core_collator::ksort($folders, core_collator::SORT_NATURAL);
        return array_merge(array_values($folders), array_values($files));
    }

    /**
     * Logout.
     *
     * @return string
     */
    public function logout() {
        $client = $this->get_user_oauth_client();
        $client->log_out();
        return parent::logout();
    }

    /**
     * Get a file.
     *
     * @param string $reference reference of the file.
     * @param string $file name to save the file to.
     * @return string JSON encoded array of information about the file.
     */
    public function get_file($reference, $filename = '') {
        global $CFG;

        $client = $this->get_user_oauth_client();

        $path = $this->prepare_file($filename);
        $options = ['filepath' => $path, 'timeout' => 15, 'followlocation' => true, 'maxredirs' => 5];
        $result = $client->download_one($reference, null, $options);

        if ($result) {
            @chmod($path, $CFG->filepermissions);
            return [
                'path' => $path,
                'url' => $reference
            ];
        }
        throw new repository_exception('cannotdownload', 'repository');
    }

    /**
     * Prepare file reference information.
     *
     * We are using this method to clean up the source to make sure that it
     * is a valid source.
     *
     * @param string $source of the file.
     * @return string file reference.
     */
    public function get_file_reference($source) {
        return clean_param($source, PARAM_URL);
    }

    /**
     * What kind of files will be in this repository?
     *
     * @return array return '*' means this repository support any files, otherwise
     *               return mimetypes of files, it can be an array
     */
    public function supported_filetypes() {
        return '*';
    }

    /**
     * Tells how the file can be picked from this repository.
     *
     * Maximum value is FILE_INTERNAL | FILE_EXTERNAL | FILE_REFERENCE.
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL;
    }

    /**
     * Return names of the general options.
     * By default: no general option name.
     *
     * @return array
     */
    public static function get_type_option_names() {
        return ['issuerid', 'pluginname'];
    }

    /**
     * Store the access token.
     */
    public function callback() {
        $client = $this->get_user_oauth_client();
        // This will upgrade to an access token if we have an authorization code.

        $client->is_logged_in();
    }

    /**
     * Edit/Create Admin Settings Moodle form.
     *
     * @param moodleform $mform Moodle form (passed by reference).
     * @param string $classname repository class name.
     */
    public static function type_config_form($mform, $classname = 'repository') {

        $url = (string)new moodle_url('/admin/tool/oauth2/issuers.php');

        $mform->addElement('static', null, '', get_string('oauth2serviceslink', 'repository_o365', $url));

        parent::type_config_form($mform);
        $options = [];
        $issuers = \core\oauth2\api::get_all_issuers();

        foreach ($issuers as $issuer) {
            $options[$issuer->get('id')] = s($issuer->get('name'));
        }
        $mform->addElement('select', 'issuerid', get_string('issuer', 'repository_o365'), $options);
        $mform->addHelpButton('issuerid', 'issuer', 'repository_o365');

        $strrequired = get_string('required');
        $mform->addRule('issuerid', $strrequired, 'required', null, 'client');
    }
}
// Icon from: http://www.iconspedia.com/icon/google-2706.html.
