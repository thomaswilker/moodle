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
define(['jquery', 'core/log', 'core/str', 'core/templates', 'core/notification'], function($, log, str, templates, notification) {
    var KEYS = {
        DOWN: 40,
        END: 35,
        ENTER: 13,
        ESCAPE: 27,
        HOME: 36,
        LEFT: 37,
        NUMPAD_ADD: 107,
        NUMPAD_DECIMAL: 110,
        NUMPAD_DIVIDE: 111,
        NUMPAD_ENTER: 108,
        NUMPAD_MULTIPLY: 106,
        NUMPAD_SUBTRACT: 109,
        PAGE_DOWN: 34,
        PAGE_UP: 33,
        PERIOD: 190,
        RIGHT: 39,
        SPACE: 32,
        TAB: 9,
        UP: 38
    };

    var selectItem = function(index, inputId, datalistId) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));
        var length = newDatalist.children('[aria-hidden=false]').length;
        index = index % length;
        while (index < 0) {
            index += length;
        }
        var element = $(newDatalist.children('[aria-hidden=false]').get(index));
        var globalIndex = $(newDatalist.children('[role=option]')).index(element);
        var itemId = datalistId + '-' + globalIndex;

        newDatalist.children().attr('aria-selected', false);
        element.attr('aria-selected', true).attr('id', itemId);
        newInput.attr('aria-activedescendant', itemId);
    };

    var selectNextItem = function(inputId, datalistId) {
        var newDatalist = $(document.getElementById(datalistId));
        var element = newDatalist.children('[aria-selected=true]');
        var current = newDatalist.children('[aria-hidden=false]').index(element);
        selectItem(current+1, inputId, datalistId);
    };
    var selectPreviousItem = function(inputId, datalistId) {
        var newDatalist = $(document.getElementById(datalistId));
        var element = newDatalist.children('[aria-selected=true]');
        var current = newDatalist.children('[aria-hidden=false]').index(element);
        selectItem(current-1, inputId, datalistId);
    };

    var closeSuggestions = function(inputId, datalistId) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));

        newInput.attr('aria-expanded', false).attr('aria-activedescendant', '');
        newDatalist.hide().attr('aria-hidden', true);
    };

    var updateSuggestions = function(query, inputId, datalistId, originalSelect) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));
        var matchingElements = false;

        newDatalist.show().attr('aria-hidden', false);;
        newDatalist.children().each(function(index, node) {
            node = $(node);
            if (node.text().indexOf(query) > -1) {
                node.show().attr('aria-hidden', false);
                matchingElements = true;
            } else {
                node.hide().attr('aria-hidden', true);
            }
        });
        if (matchingElements) {
            newInput.attr('aria-expanded', true);
            selectItem(0, inputId, datalistId);
        } else {
            newDatalist.hide();
            newDatalist.attr('aria-hidden', true);
            newInput.attr('aria-expanded', false);
        }
    };

    var activateCurrentItem = function(inputId, datalistId) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));
        var query = newDatalist.children('[aria-selected=true]').html();
        newInput.val(query);
        closeSuggestions(inputId, datalistId);
    };

    var updateAjax = function(e, selector, inputId, datalistId, originalSelect, ajaxHandler) {
        var query = $(e.currentTarget).val();
        var results = ajaxHandler.transport(selector, query, function(results) {
            var processedResults = ajaxHandler.processResults(selector, results);
            processedResults.unshift( { label: query, value: query} );
            templates.render(
                'core/form-autocomplete-datalist',
                { inputId: inputId, datalistId: datalistId, options: processedResults}
            ).done(function(datalist) {
                var newDatalist = $(document.getElementById(datalistId));
                newDatalist.replaceWith(datalist);
            }).fail(notification.exception);
        }, notification.exception);
    };

    var addNavigation = function(inputId, datalistId, downArrowId, originalSelect) {
        var inputElement = $(document.getElementById(inputId));
        inputElement.on('keydown', function(e) {
            switch (e.keyCode) {
                case KEYS.DOWN:
                    if (inputElement.attr('aria-expanded') === "true") {
                        selectNextItem(inputId, datalistId);
                    } else {
                        updateSuggestions(inputElement.val(), inputId, datalistId, originalSelect);
                    }
                    return false;
                case KEYS.UP:
                    selectPreviousItem(inputId, datalistId);
                    return false;
                case KEYS.ENTER:
                    activateCurrentItem(inputId, datalistId);
                    return false;
                case KEYS.ESCAPE:
                    if (inputElement.attr('aria-expanded') === "true") {
                        closeSuggestions(inputId, datalistId);
                    }
                    return false;
            }
            return true;
        });
        inputElement.on('blur', function(e) {
            closeSuggestions(inputId, datalistId);
        });
        var arrowElement = $(document.getElementById(downArrowId));
        arrowElement.on('click', function(e) {
            updateSuggestions(inputElement.val(), inputId, datalistId, originalSelect);
        });
        datalistElement = $(document.getElementById(datalistId));
        datalistElement.parent().on('click', '[role=option]', function(e) {
            var element = $(e.currentTarget).closest('[role=option]');
            var newDatalist = $(document.getElementById(datalistId));
            var current = newDatalist.children('[aria-hidden=false]').index(element);
            selectItem(current, inputId, datalistId);
            activateCurrentItem(inputId, datalistId);
        });
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
            if (typeof tags === "undefined") {
                tags = false;
            }
            if (typeof ajax === "undefined") {
                ajax = false;
            }

            var originalSelect = $(selector);
            if (!originalSelect) {
                log.debug('Selector not found: ' + selector);
                return false;
            }

            // Hide the original select.
            originalSelect.hide().attr('aria-hidden', true);

            var selectId = originalSelect.attr('id');
            var multiple = originalSelect.attr('multiple');
            var inputId = 'form-autocomplete-input-' + $.now();
            var datalistId = 'form-autocomplete-datalist-' + $.now();
            var downArrowId = 'form-autocomplete-downarrow-' + $.now();

            var originalLabel = $('[for=' + selectId + ']');
            // Create the new markup and insert it after the select.
            var options = [];
            originalSelect.children('option').each(function(index, option) {
                options[index] = { label: option.innerHTML, value: $(option).attr('value') };
            });

            var renderInput = templates.render(
                'core/form-autocomplete-input',
                { downArrowId: downArrowId,
                  inputId: inputId,
                  datalistId: datalistId,
                  placeholder: placeholder,
                  multiple: multiple }
            );
            var renderDatalist = templates.render(
                'core/form-autocomplete-datalist',
                { inputId: inputId, datalistId: datalistId, options: options}
            );

            $.when(renderSelected, renderInput, renderDatalist).done(function(input, datalist) {
                originalSelect.after(datalist);
                originalSelect.after(input);
                originalLabel.attr('for', inputId);
                addNavigation(inputId, datalistId, downArrowId, originalSelect);

                var newInput = $(document.getElementById(inputId));
                var newDatalist = $(document.getElementById(datalistId));
                newDatalist.hide().attr('aria-hidden', true);

                if (ajax) {
                    require([ajax], function(ajaxHandler) {
                        var handler = function(e) {
                            updateAjax(e, selector, inputId, datalistId, originalSelect, ajaxHandler);
                        };
                        newInput.on("input keypress", handler);
                    });
                }
                newInput.on('input', function(e) {
                    var query = $(e.currentTarget).val();
                    updateSuggestions(query, inputId, datalistId, originalSelect);
                });
            });
        }
    };
});
