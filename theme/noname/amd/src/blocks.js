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
 * Manipulate blocks into a menu/popup.
 *
 * @module     core/blocks
 * @copyright  2016 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates'], function($, Templates) {

    return /** @alias module:theme_noname/blocks */ {
        /**
         * Construct the blocks menu and add event listeners.
         *
         * @method init
         * @param {string} blockselector Selector to find the blocks region
         * @param {string} blocksmenuselector Selector to find the blocks menu
         * @param {string} blocksmenubuttonselector Selector to find the blocks menu button
         */
        init: function(blocksselector, blocksmenuselector, blocksmenubuttonselector) {
            var hiddenblocks = $(blocksselector);
            var allblocks = [];

            hiddenblocks.find('[data-region="block"]').each(function(index, block) {
                var title = $(block).find('[data-region="block-title"]').text(),
                    id = $(block).attr('id'),
                    type = $(block).data('type');

                if (type != "navigation" && type != 'settings') {
                    allblocks.push({
                        title : title,
                        id : id
                    });
                }
            }).bind(this);

            allblocks = allblocks.sort(function(a, b) {
                var titleA = a.title.toUpperCase();
                var titleB = b.title.toUpperCase();
                if (titleA < titleB) {
                    return -1;
                }
                if (titleA > titleB) {
                    return 1;
                }
                return 0;
            });
            if (allblocks.length > 0) {
                Templates.render('theme_noname/blocks_menu', { blocks: allblocks }).done(function(html, js) {
                    Templates.replaceNodeContents($(blocksmenuselector), html, js);
                });
            } else {
                $(blocksmenubuttonselector).hide();
            }

            return true;
        }
    };
});
