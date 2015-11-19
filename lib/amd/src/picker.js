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
 * Competency rule base module.
 *
 * @package    core
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'tool_lp/dialogue', 'core/str', 'core/templates', 'core/notification'],
        function($, Dialogue, str, templates, notification) {

    /**
     * Picker
     *
     * This picker is useful when you need to select lots of things (> 5) from a list of
     * lots and lots of things (> 1000).
     *
     * @param {Function} fetchItemsHandler
     */
    var Picker = function(selectedItems, triggerNodes, title) {
        this._selectedItems = selectedItems;
        this._triggerNodes = triggerNodes;
        this._title = title;
        this._attachListeners();
        this._getLoadingHTML();
    };

    /**
     * @method
     * Fetch a chunk of items based on the search.
     * Returns a promise that when resolved, contains the results.
     * @param {String} The current search results.
     * @return {Promise}
     */
    Picker.prototype.fetchItems = function(query) {
        var result = $.Deferred();
        result.resolve([]);
        return result.promise();
    };

    /**
     * @method
     * Render the loading template.
     */
    Picker.prototype._getLoadingHTML = function() {
        var self = this;
        templates.render('tool_lp/loading', {}).done(function(html, js) {
            self._loadingHTML = html;
        });
    };
    /**
     * @method
     * Return a promise, that when resolved - contains the HTML label for the item.
     * @param {Object} item
     * @return {Promise}
     */
    Picker.prototype.displayItems = function(item) {
        var result = $.Deferred();
        result.resolve(JSON.stringify(item));
        return result.promise();
    };

    /** @type {Array} Selected items */
    Picker.prototype._selectedItems = null;

    /** @type {JQuery} JQuery list with items to open the picker */
    Picker.prototype._triggerNodes = null;

    /** @type {Dialogue} The picker dialogue */
    Picker.prototype._dialogue = null;

    /** @type {Promise} Promise resolved when the dialoge has been built */
    Picker.prototype._dialogueLoaded = $.Deferred();

    /** @type {String} Loading HTML */
    Picker.prototype._loadingHTML = '';

    /**
     * @method
     * Build the UI.
     */
    Picker.prototype._updateSearch = function() {
        var query = this._find('#pickersearch').val();
        var searchResultsNode = this._find('#searchresults');
        templates.replaceNodeContents(searchResultsNode, this._loadingHTML, '');
        var searchRequest = this.fetchItems(query);

        searchRequest.done(function(items) {
            if (!items.length) {
                templates.render('core/picker-dialogue-noresults', {}).done(function(html, js) {
                    templates.replaceNodeContents(searchResultsNode, html, js);
                }).fail(notification.exception);
            }
        }).fail(notification.exception);

    };

    /**
     * Find a node in the dialogue.
     *
     * @param {String} selector
     * @method _find
     */
    Picker.prototype._find = function(selector) {
        return $(this._dialogue.getContent()).find(selector);
    };

    /**
     * @method
     * Attach event listeners to the specified trigger nodes.
     */
    Picker.prototype._attachListeners = function() {
        var self = this;
        this._triggerNodes.on('click', function(e) {
            e.preventDefault();
            templates.render('core/picker-dialogue-init', {}).done(function(html, js) {
                self._dialogue = new Dialogue(self._title, html, function() {self._dialogueLoaded.resolve();}, '760px');

                self._dialogueLoaded.done(function() {
                    self._updateSearch.apply(self);
                });
            }).fail(notification.exception);
            return false;
        });
    };

    return /** @alias module:core/picker */ Picker;

});
