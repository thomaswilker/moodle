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
 * Load a stylesheet from JS.
 *
 * @module     core/css
 * @class      css
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.0
 */
define(['jquery', 'core/config', 'core/log'], function($, config, log) {

    var loaded = [];

    return /** @alias module:core/form-autocomplete */ {
        // Public variables and functions.
        /**
         * Load a theme stylesheet from JS.
         *
         * @method load
         * @param {string} relativeUrl The url of the style sheet relative to the current theme folder.
         */
        load: function(relativeUrl) {
            if (typeof loaded[relativeUrl] === "undefined") {
                var fullUrl = config.wwwroot + relativeUrl;

                if(document.createStyleSheet) {
                    try {
                        document.createStyleSheet(fullUrl);
                    } catch (e) {
                        log.warn('Stylesheet not loaded: ' + fullUrl + ', ' + e.message);
                    }
                } else {
                    $('<link rel="stylesheet" type="text/css">').attr('href', fullUrl).appendTo('head');
                }
                loaded[relativeUrl] = true;
            }
        }
    };
});
