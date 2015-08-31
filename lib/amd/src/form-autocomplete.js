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
 * Autocomplete wrapper for select2 library.
 *
 * @module     core/form-autocomplete
 * @class      autocomplete
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.0
 */
/* globals require: false */
define(['jquery', 'core/css', 'core/str', 'core/select2'], function($, css, str) {
    var config = {
        placeholder: ""
    };

    return /** @alias module:core/form-autocomplete */ {
        // Public variables and functions.
        /**
         * Turn a boring select box into an auto-complete beast.
         *
         * @method enhance
         * @param {string} select The selector that identifies the select box.
         * @param {boolean} tags Whether to allow support for tags (can define new entries).
         * @param {string} ajax Name of an AMD module to handle ajax requests. If specified, the AMD
         *                      module must expose 2 functions "transport" and "processResults".
         *                      For more info on those functions see: https://select2.github.io/options.html#ajax
         */
        enhance: function(selector, tags, ajax, placeholder) {
            css.load('/lib/form/style/select2.css');

            if (typeof tags === "undefined") {
                tags = false;
            }
            if (typeof ajax === "undefined") {
                ajax = false;
            }

            str.get_strings([
                { component: 'error', key: 'error' },
                { component: 'form', key: 'err_maxlength', param: { format: '%TOKEN%'} },
                { component: 'form', key: 'err_minlength', param: { format: '%TOKEN%'} },
                { component: 'core', key: 'loading' },
                { component: 'form', key: 'err_maxselected', param: { format: '%TOKEN%'} },
                { component: 'core', key: 'noresults' },
                { component: 'form', key: 'searchingdots' }
            ]).done(function(strs) {
                config.placeholder = placeholder;
                config.tags = tags;
                config.tokenSeparators = [',', ' '];
                config.selectOnClose = true;
                config.closeOnSelect = false;

                var language = {
                    errorLoading: function() { return strs[0]; },
                    inputTooLong: function(e) { return strs[1].replace('%TOKEN%', e.maximum); },
                    inputTooShort: function(e) { return strs[2].replace('%TOKEN%', e.minimum); },
                    loadingMore: function() { return strs[3]; },
                    maximumSelected: function(e) { return strs[4].replace('%TOKEN%', e.maximum); },
                    noResults: function() { return strs[5]; },
                    searching: function() { return strs[6]; }
                };
                config.language = language;

                var select2 = null;
                if (ajax) {
                    require([ajax], function(ajaxHandler) {
                        config.ajax = {
                            delay: 500,
                            processResults: ajaxHandler.processResults,
                            transport: ajaxHandler.transport
                        };
                        select2 = $(selector).select2(config);
                    });
                } else {
                    select2 = $(selector).select2(config);
                }

                // Update the for tag on the connected label.
                var ele = select2.next(".select2").find('.select2-selection [role="textbox"]');

                var newid = ele.attr('id');
                if (typeof newid === "undefined") {
                    newid = 'select2' + $.now();
                    ele.attr('id', newid);
                }
                var oldid = select2.attr('id');

                $('[for="' + oldid + '"]').on('click', function() { ele.focus(); });
                $('[for="' + oldid + '"]').attr('for', newid);
            });
        }
    };
});
