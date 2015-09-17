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

    var closeSuggestionsTimer = null;

    var activateItem = function(index, inputId, datalistId) {
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

    var activateNextItem = function(inputId, datalistId) {
        var newDatalist = $(document.getElementById(datalistId));
        var element = newDatalist.children('[aria-selected=true]');
        var current = newDatalist.children('[aria-hidden=false]').index(element);
        activateItem(current+1, inputId, datalistId);
    };
    var activatePreviousItem = function(inputId, datalistId) {
        var newDatalist = $(document.getElementById(datalistId));
        var element = newDatalist.children('[aria-selected=true]');
        var current = newDatalist.children('[aria-hidden=false]').index(element);
        activateItem(current-1, inputId, datalistId);
    };

    var closeSuggestions = function(inputId, datalistId, selectionId) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));

        newInput.attr('aria-expanded', false).attr('aria-activedescendant', selectionId);
        newDatalist.hide().attr('aria-hidden', true);
    };

    var updateSuggestions = function(query, inputId, datalistId, originalSelect, multiple, tags) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));
        var matchingElements = false;
        var options = [];
        originalSelect.children('option').each(function(index, option) {
            options[index] = { label: option.innerHTML, value: $(option).attr('value') };
        });

        var renderDatalist = templates.render(
            'core/form-autocomplete-datalist',
            { inputId: inputId, datalistId: datalistId, options: options, multiple: multiple}
        ).done(function(newHTML) {
            newDatalist.replaceWith(newHTML);
            newDatalist = $(document.getElementById(datalistId));
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
                if (!tags) {
                    activateItem(0, inputId, datalistId);
                }
            } else {
                newDatalist.hide();
                newDatalist.attr('aria-hidden', true);
                newInput.attr('aria-expanded', false);
            }
        });

    };

    var createItem = function(inputId, datalistId, selectionId, multiple, originalSelect) {
        var newInput = $(document.getElementById(inputId));
        var query = newInput.val();
        var found = false;
        if (!multiple) {
            originalSelect.children('option').prop('selected', false);
        }
        originalSelect.children('option').each(function(index, ele) {
            if ($(ele).attr('value') == query) {
                found = true;
                $(ele).prop('selected', !$(ele).prop('selected'));
            }
        });
        if (!found) {
            var option = $('<option>');
            option.append(query);
            option.attr('value', query);
            originalSelect.append(option);
            option.prop('selected', true);
        }
        var newSelection = $(document.getElementById(selectionId));
        var items = [];
        originalSelect.children('option').each(function(index, ele) {
            if ($(ele).prop('selected')) {
                items.push( { label: $(ele).html(), value: $(ele).attr('value') } );
            }
        });
        var context = {
            selectionId: selectionId,
            items: items
        };
        templates.render('core/form-autocomplete-selection', context).done(function(newHTML) {
            newSelection.empty().append($(newHTML).html());
        }).fail(notification.exception);
        newInput.val('');
        closeSuggestions(inputId, datalistId, selectionId);
        originalSelect.change();
    };

    var updateSelectionList = function(selectionId, originalSelect) {
        var items = [];
        var newSelection = $(document.getElementById(selectionId));
        originalSelect.children('option').each(function(index, ele) {
            if ($(ele).prop('selected')) {
                items.push( { label: $(ele).html(), value: $(ele).attr('value') } );
            }
        });
        var context = {
            selectionId: selectionId,
            items: items
        };
        templates.render('core/form-autocomplete-selection', context).done(function(newHTML) {
            newSelection.empty().append($(newHTML).html());
        }).fail(notification.exception);
        originalSelect.change();
    };

    var selectCurrentItem = function(inputId, datalistId, selectionId, multiple, originalSelect) {
        var newInput = $(document.getElementById(inputId));
        var newDatalist = $(document.getElementById(datalistId));
        // Here loop through datalist and set val to join of all selected items.
        var allText = '';

        var selectedItemValue = newDatalist.children('[aria-selected=true]').attr('data-value');
        // The select will either be a single or multi select, so the following will either
        // select one or more items correctly.
        if (!multiple) {
            originalSelect.children('option').prop('selected', false);
        }
        originalSelect.children('option').each(function(index, ele) {
            if ($(ele).attr('value') == selectedItemValue) {
                $(ele).prop('selected', !$(ele).prop('selected'));
            }
        });
        updateSelectionList(selectionId, originalSelect);
        newInput.val('');
        closeSuggestions(inputId, datalistId, selectionId);
    };

    var updateAjax = function(e, selector, inputId, datalistId, originalSelect, multiple, tags, ajaxHandler) {
        var query = $(e.currentTarget).val();
        var results = ajaxHandler.transport(selector, query, function(results) {
            var processedResults = ajaxHandler.processResults(selector, results);
            originalSelect.children('options').each(function(optionIndex, option) {
                if (!option.prop('selected')) {
                    option.detach();
                    option.destroy();
                }
            });
            $.each(processedResults, function(resultIndex, result) {
                var option = $('<option>');
                option.append(result.label);
                option.attr('value', result.value);
                originalSelect.append(option);
                option.prop('selected', true);
            });
            updateSuggestions('', inputId, datalistId, originalSelect, multiple, tags);
        }, notification.exception);
    };

    var addNavigation = function(inputId, datalistId, downArrowId, selectionId, originalSelect, multiple, tags) {
        var inputElement = $(document.getElementById(inputId));
        inputElement.on('keydown', function(e) {
            switch (e.keyCode) {
                case KEYS.DOWN:
                    if (inputElement.attr('aria-expanded') === "true") {
                        activateNextItem(inputId, datalistId);
                    } else {
                        updateSuggestions(inputElement.val(), inputId, datalistId, originalSelect, multiple, tags);
                    }
                    e.preventDefault();
                    return false;
                case KEYS.UP:
                    activatePreviousItem(inputId, datalistId);
                    e.preventDefault();
                    return false;
                case KEYS.ENTER:
                    var datalistElement = $(document.getElementById(datalistId));
                    if (inputElement.attr('aria-expanded') === "true" && (datalistElement.children('[aria-selected=true]').length > 0)) {
                        selectCurrentItem(inputId, datalistId, selectionId, multiple, originalSelect);
                    } else if (tags) {
                        createItem(inputId, datalistId, selectionId, multiple, originalSelect);
                    }
                    e.preventDefault();
                    return false;
                case KEYS.ESCAPE:
                    if (inputElement.attr('aria-expanded') === "true") {
                        closeSuggestions(inputId, datalistId, selectionId);
                    }
                    e.preventDefault();
                    return false;
            }
            return true;
        });
        inputElement.on('blur focus', function(e) {
            // We may be blurring because we have clicked on the suggestion list. We
            // dont want to close the selection list before the click event fires, so
            // we have to delay.
            closeSuggestionsTimer = window.setTimeout(function() {
                closeSuggestions(inputId, datalistId, selectionId);
            }, 500);
        });
        var arrowElement = $(document.getElementById(downArrowId));
        arrowElement.on('click', function(e) {
            if (closeSuggestionsTimer) {
                window.clearTimeout(closeSuggestionsTimer);
            }
            updateSuggestions(inputElement.val(), inputId, datalistId, originalSelect, multiple, tags);
        });
        datalistElement = $(document.getElementById(datalistId));
        datalistElement.parent().on('click', '[role=option]', function(e) {
            var element = $(e.currentTarget).closest('[role=option]');
            var newDatalist = $(document.getElementById(datalistId));
            var current = newDatalist.children('[aria-hidden=false]').index(element);
            activateItem(current, inputId, datalistId);
            selectCurrentItem(inputId, datalistId, selectionId, multiple, originalSelect);
        });
        var selectionElement = $(document.getElementById(selectionId));
        selectionElement.parent().on('click', '[role=listitem]', function(e) {
            var value = $(e.currentTarget).attr('data-value');

            originalSelect.children('option').each(function(index, ele) {
                if ($(ele).attr('value') == value) {
                    $(ele).prop('selected', !$(ele).prop('selected'));
                }
            });

            updateSelectionList(selectionId, originalSelect);
        });
        inputElement.on('input', function(e) {
            var query = $(e.currentTarget).val();
            updateSuggestions(query, inputId, datalistId, originalSelect, multiple, tags);
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
            var selectionId = 'form-autocomplete-selection-' + $.now();
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
                  selectionId: selectionId,
                  placeholder: placeholder,
                  multiple: multiple }
            );
            var renderDatalist = templates.render(
                'core/form-autocomplete-datalist',
                { inputId: inputId, datalistId: datalistId, options: options, multiple: multiple}
            );
            var renderSelection = templates.render(
                'core/form-autocomplete-selection',
                { selectionId: selectionId, items: []}
            );

            $.when(renderInput, renderDatalist, renderSelection).done(function(input, datalist, selection) {
                originalSelect.after(selection);
                originalSelect.after(datalist);
                originalSelect.after(input);
                originalLabel.attr('for', inputId);
                addNavigation(inputId, datalistId, downArrowId, selectionId, originalSelect, multiple, tags);

                var newInput = $(document.getElementById(inputId));
                var newDatalist = $(document.getElementById(datalistId));
                newDatalist.hide().attr('aria-hidden', true);

                if (ajax) {
                    require([ajax], function(ajaxHandler) {
                        var handler = function(e) {
                            updateAjax(e, selector, inputId, datalistId, originalSelect, multiple, tags, ajaxHandler);
                        };
                        newInput.on("input keypress", handler);
                    });
                }
                updateSelectionList(selectionId, originalSelect);
            });
        }
    };
});
