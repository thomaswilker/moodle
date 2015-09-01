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
 * Expose the HTML5 Notification API
 *
 * @module     core/html5-notification
 * @class      html5-notification
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.0
 */
define(['core/url', 'core/log'], function(urlmod, log) {

    return /** @alias module:core/html5-notification */ {
        /**
         * Show a HTML notification.
         *
         * @method notify
         * @param {String} title The notification title
         * @param {String} body The notification body
         * @param {String} url A url to open when the notification is clicked.
         * @return {Promise}
         */
        notify: function(title, content, url, id, icon, iconcomponent) {
            if ("Notification" in window) {
                if (typeof icon === "undefined") {
                    icon = 'notification';
                    iconcomponent = 'message_html5';
                }
                var options = {
                    body: content,
                    icon: urlmod.imageUrl(icon, iconcomponent)
                };
                if (typeof id !== "undefined") {
                    // Prevent spamming the same notification.
                    options.tag = id;
                }
                if (Notification.permission === "granted") {
                    // If it's okay let's create a notification
                    var notification = new Notification(title, options);
                    notification.onclick = function() {window.location.href = url;};
                } else if (Notification.permission !== 'denied') {
                    Notification.requestPermission(function (permission) {
                        // If the user accepts, let's create a notification
                        if (permission === "granted") {
                            var notification = new Notification(title, options);
                            notification.onclick = function() {window.location.href = url;};
                        } else {
                            log.warn('User denied message request');
                        }
                    });
                }
            } else {
                log.warn('Browser does not support notifications API.');
            }
        }
    };
});
