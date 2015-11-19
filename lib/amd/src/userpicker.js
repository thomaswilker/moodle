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

define(['jquery', 'core/picker', 'core/str'], function($, Picker, str) {

    /**
     * User Picker
     *
     * This picker is useful when you need to select lots of users (> 5) from a list of
     * lots and lots of users.
     *
     * @param {Array} Initially selected items.
     * @param {JQuery} JQuery nodes that will trigger this picker.
     */
    /**
     * UserPicker class inherits from Picker.
     */
    var UserPicker = function() {
        Picker.apply(this, arguments);
    };
    UserPicker.prototype = Object.create(Picker.prototype);

    /** @type {Function} Fetch items handler */
    //UserPicker.prototype.fetchItems = function() {
    //};

    /** @type {Function} Display items handler */
    //UserPicker.prototype._displayItems = function() {
    //};

    return /** @alias module:core/picker */ UserPicker;

});
