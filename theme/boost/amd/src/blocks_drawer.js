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
 * Contain the logic for the blocks drawer.
 *
 * @package    theme_boost
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/custom_interaction_events', 'core/notification'],
     function($, CustomEvents, Notification) {

    var SELECTORS = {
        CONTAINER_REGION: '[data-region="blocks-drawer"]',
        TOGGLE_REGION: '[data-region="blocks-drawer-toggle"]',
        TOGGLE_ACTION: '[data-action="toggle-blocks-drawer"]',
        BODY: 'body'
    };

    /**
     * Constructor for the BlocksDrawer.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var BlocksDrawer = function() {
        if (!$(SELECTORS.CONTAINER_REGION).length) {
            Notification.exception({message: 'Page is missing a blocks drawer region'});
        }

        if (!$(SELECTORS.TOGGLE_REGION).length) {
            Notification.exception({message: 'Page is missing a blocks drawer toggle region'});
        }
        if (!$(SELECTORS.TOGGLE_ACTION).length) {
            Notification.exception({message: 'Page is missing a blocks drawer toggle link'});
        }
        var drawer = $(SELECTORS.CONTAINER_REGION);
        var toggle = $(SELECTORS.TOGGLE_REGION);
        var hidden = drawer.attr('aria-hidden') == 'true';
        var body = $(SELECTORS.BODY);
        if (!hidden) {
            body.addClass('blocks-drawer-open');
            drawer.attr('aria-hidden', 'false');
        } else {
            // Close.
            drawer.addClass('closed');
            toggle.addClass('closed');
            drawer.attr('aria-hidden', 'true');
        }

        this.registerEventListeners();
    };

    /**
     * Open / close the blocks drawer.
     *
     * @method toggleBlocksDrawer
     */
    BlocksDrawer.prototype.toggleBlocksDrawer = function() {
        var drawer = $(SELECTORS.CONTAINER_REGION);
        var toggle = $(SELECTORS.TOGGLE_REGION);
        var body = $(SELECTORS.BODY);

        body.addClass('blocks-drawer-ease');
        var hidden = drawer.attr('aria-hidden') == 'true';
        if (hidden) {
            // Open.
            drawer.removeClass('closed');
            toggle.removeClass('closed');
            drawer.attr('aria-hidden', 'false');
            body.addClass('blocks-drawer-open');
            M.util.set_user_preference('blocks-drawer-open', 'true');
        } else {
            // Close.
            drawer.addClass('closed');
            toggle.addClass('closed');
            body.removeClass('blocks-drawer-open');
            drawer.attr('aria-hidden', 'true');
            M.util.set_user_preference('blocks-drawer-open', 'false');
        }
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    BlocksDrawer.prototype.registerEventListeners = function() {
        var button = $(SELECTORS.TOGGLE_ACTION);

        CustomEvents.define(button, [CustomEvents.events.activate]);
        button.on(CustomEvents.events.activate, function(e, data) {
            this.toggleBlocksDrawer();
            data.originalEvent.preventDefault();
        }.bind(this));
    };

    return {
        'init': function() {
            return new BlocksDrawer();
        }
    };
});
