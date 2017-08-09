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
 * AJAX helper for the database record listing.
 *
 * @module     mod_data/recordlist
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/custom_interaction_events'],
        function($, Ajax, Templates, Notification, Str, CustomEvents) {

    /**
     * RecordList class.
     * @param {String} listSelector
     * @param {String} paginationSelector
     * @param {Integer} databaseId
     * @param {Boolean} useTemplates
     */
    var RecordList = function(listSelector, paginationSelector, databaseId, useTemplates) {
        this.recordsPerPage = 50;
        this.currentPage = 0;

        this.listSelector = listSelector;
        this.paginationSelector = paginationSelector;
        this.databaseId = databaseId;
        this.useTemplates = useTemplates;

        this.fetchRecords().catch(Notification.exception);
    };
    RecordList.prototype = Object.create(RecordList.prototype);

    /** @type {String} Selector for the page region containing the list of records. */
    RecordList.prototype.listSelector = null;

    /** @type {String} Selector for the page region containing the pagination of records. */
    RecordList.prototype.paginationSelector = null;

    /** @type {Integer} databaseId The database instance id (same as cm->instance). */
    RecordList.prototype.databaseId = null;

    /** @type {Promise} loadingTemplate The loading template. */
    RecordList.prototype.loadingTemplate = null;

    /** @type {Promise} navSearchResultsStr The navigation search results string. */
    RecordList.prototype.navSearchResultsStr = null;

    /** @type {Promise} paginationListeners Promise resolved when pagination listeners have been added. */
    RecordList.prototype.paginationListeners = null;

    /**
     * Get the pagination navigation string.
     *
     * @method getNavSearchResultsStr
     * @return {Promise}
     */
    RecordList.prototype.getNavSearchResultsStr = function() {
        if (this.navSearchResultsStr) {
            return this.navSearchResultsStr;
        }
        this.navSearchResultsStr = Str.get_string('navsearchresults', 'mod_data');
        return this.navSearchResultsStr;
    };

    /**
     * This module provides some setable page params (but only a few).
     *
     * @method setStateVariable
     * @param {String} name
     * @param {String} value
     * @return {Promise}
     */
    RecordList.prototype.setStateVariable = function(name, value) {
        switch (name) {
            case 'page':
                this.currentPage = parseInt(value, 10);
                break;
            case 'perpage':
                this.recordsPerPage = parseInt(value, 10);
                break;
        }
    };

    /**
     * Attach event listeners to the pagination bars.
     *
     * @method attachPaginationListeners
     * @return {Promise}
     */
    RecordList.prototype.attachPaginationListeners = function() {
        if (this.paginationListeners) {
            return this.paginationListeners;
        }

        this.paginationListeners = $.when({}).then(function() {
            CustomEvents.define($(this.paginationSelector), [CustomEvents.events.activate]);
            $(this.paginationSelector).on(CustomEvents.events.activate, 'a', function(e, data) {
                var url = $(e.currentTarget).attr('href');

                // The urls start with # and then contain a variable to set and a value.
                // After setting the variable - refresh the display.
                url = url.substring(1);

                var i = 0;
                var params = url.split('&');

                for (i = 0; i < params.length; i++) {
                    var namevalue = params[i];
                    var parts = namevalue.split('=');
                    if (parts.length > 1) {
                        var name = parts[0];
                        var value = parts[1];
                        this.setStateVariable(name, value);
                    }
                }

                this.fetchRecords();

                e.stopPropagation();
                data.originalEvent.preventDefault();
            }.bind(this));
        }.bind(this));

        return this.paginationListeners;
    };

    /**
     * Show a loading spinner before making ajax requests.
     *
     * @method showLoading
     * @return {Promise}
     */
    RecordList.prototype.showLoading = function() {
        if (this.loadingTemplate) {
            return this.loadingTemplate;
        }
        this.loadingTemplate = Templates.render('core/loading', {}).then(function(html, js) {
            $(this.listSelector).each(function(index, element) {
                Templates.replaceNodeContents(element, html, js);
            });
            return true;
        }.bind(this));
        return this.loadingTemplate;
    };

    /**
     * Load the current page of entries, respecting the current filters.
     *
     * @method fetchRecords
     * @return {Promise}
     */
    RecordList.prototype.fetchRecords = function() {
        return this.showLoading().then(function() {
            // Fetch the records.
            var params = [{
                methodname: 'mod_data_search_entries',
                args: {
                    'databaseid': this.databaseId,
                    'page': this.currentPage,
                    'perpage': this.recordsPerPage,
                    'returncontents': this.useTemplates
                }
            }];

            return Ajax.call(params)[0];
        }.bind(this)).then(this.displayRecords.bind(this));
    };

    /**
     * Take the records returned by the fetchRecords method
     * and display them.
     *
     * @method displayRecords
     * @param {Object} response The response from the webservice
     * @return {Promise}
     */
    RecordList.prototype.displayRecords = function(response) {
        return this.displayPagination(response.totalcount).then(function() {
            console.log(response.entries);
            if (this.useTemplates) {
                $(this.listSelector).html(response.listviewcontents);
            }
            return true;
        }.bind(this));
    };

    /**
     * Build the context for a pagination bar.
     *
     * @method buildPaginationContext
     * @param {Integer} total - the number of results.
     * @return {Promise}
     */
    RecordList.prototype.buildPaginationContext = function(total) {
        var maxPage = Math.ceil(total / this.recordsPerPage);
        var context = {
            haspages: (maxPage > 1)
        };

        if (this.currentPage > 0) {
            context.first = {
                url: ('#page=0'),
                page: 1
            };
            context.previous = {
                url: ('#page=' + (this.currentPage - 1)),
            };
        }
        if (this.currentPage < maxPage) {
            context.next = {
                url: ('#page=' + (this.currentPage + 1)),
            };
            context.last = {
                url: ('#page=' + (maxPage - 1)),
                page: maxPage
            };
        }
        var page = 0;
        context.pages = [];

        for (page = this.currentPage - 2; page <= this.currentPage + 2; page++) {
            if (page >= 0 && page < maxPage) {
                var onepage = {
                    url: ('#page=' + page),
                    page: (page + 1),
                    active: (page == this.currentPage)
                };
                context.pages[context.pages.length] = onepage;
                if (context.first && onepage.page == context.first.page) {
                    context.first = false;
                }
                if (context.last && onepage.page == context.last.page) {
                    context.last = false;
                }
            }
        }

        // Finally, maybe fetch the string for the label.
        return this.getNavSearchResultsStr().then(function(str) {
            context.label = str;
            return context;
        });
    };

    /**
     * Display a pagination bar based on the number of results.
     *
     * @method displayPagination
     * @param {Integer} total - the number of results.
     * @return {Promise}
     */
    RecordList.prototype.displayPagination = function(total) {
        return this.buildPaginationContext(total).then(function(context) {
            return Templates.render('core/paging_bar', context);
        }).then(function(html, js) {
            $(this.paginationSelector).each(function(index, element) {
                Templates.replaceNodeContents(element, html, js);
            });
            return true;
        }.bind(this)).then(this.attachPaginationListeners.bind(this));
    };

    return /** @alias module:mod_data/recordlist */ RecordList;
});
