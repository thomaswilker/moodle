YUI.add('moodle-atto_undo-button', function (Y, NAME) {

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
 * Atto text editor undo plugin.
 *
 * @package    editor-undo
 * @copyright  2014 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.atto_undo = M.atto_undo || {

    /**
     * The maximum saved number of undo steps.
     *
     * @property maxundos
     * @type {Integer} The maximum number of saved undos.
     * @default 40
     */
    maxundos : 40,

    /**
     * History of edits.
     *
     * @property undostack
     * @type {Object} the keys will be the elementids of the editable region and the values a list of strings.
     * @default {}
     */
    undostack : {},

    /**
     * History of edits.
     *
     * @property redostack
     * @type {Object} the keys will be the elementids of the editable region and the values a list of strings.
     * @default {}
     */
    redostack : {},

    /**
     * Handle a click on undo
     *
     * @method undo_handler
     * @param {Y.Event} The click event
     * @param {String} The id for the editor
     */
    undo_handler : function(e, elementid) {
        e.preventDefault();
        var editable = M.editor_atto.get_editable_node(elementid);

        M.atto_undo.redostack[elementid].push(editable.getHTML());
        var last = M.atto_undo.undostack[elementid].pop();
        if (last === editable.getHTML()) {
            last = M.atto_undo.undostack[elementid].pop();
        }
        if (last) {
            editable.setHTML(last);
            // Put it back in the undo stack so a new event wont clear the redo stack.
            M.atto_undo.undostack[elementid].push(last);
            M.editor_atto.add_widget_highlight(elementid, 'undo', 'redo');
        }
    },

    /**
     * Handle a click on redo
     *
     * @method redo_handler
     * @param {Y.Event} The click event
     * @param {String} The id for the editor
     */
    redo_handler : function(e, elementid) {
        e.preventDefault();
        var editable = M.editor_atto.get_editable_node(elementid);

        M.atto_undo.undostack[elementid].push(editable.getHTML());
        var last = M.atto_undo.redostack[elementid].pop();
        editable.setHTML(last);
        M.atto_undo.undostack[elementid].push(last);
    },

    /**
     * If we are significantly different from the last saved version, save a new version.
     *
     * @method redo_handler
     * @param {Y.Event} The click event
     * @param {String} The id for the editor
     */
    change_listener : function(e) {
        var elementid = e.elementid;
        var editable = M.editor_atto.get_editable_node(elementid);

        if (e.event.type.indexOf('key') !== -1) {
            // These are the 4 arrow keys.
            if ((e.event.keyCode !== 39) &&
                (e.event.keyCode !== 37) &&
                (e.event.keyCode !== 40) &&
                (e.event.keyCode !== 38)) {
                // Skip this event type. We only want focus/mouse/arrow events.
                return;
            }
        }

        if (typeof M.atto_undo.undostack[elementid] === 'undefined') {
            M.atto_undo.undostack[elementid] = [];
        }

        var last = M.atto_undo.undostack[elementid][M.atto_undo.undostack[elementid].length-1];
        if (last !== editable.getHTML()) {
            M.atto_undo.undostack[elementid].push(editable.getHTML());
            M.atto_undo.redostack[elementid] = [];
            M.editor_atto.remove_widget_highlight(elementid, 'undo', 'redo');
        }

        while (M.atto_undo.undostack[elementid].length > M.atto_undo.maxundos) {
            M.atto_undo.undostack[elementid].shift();
        }

        // Show in the buttons if undo/redo is possible.
        if (M.atto_undo.undostack[elementid].length) {
           M.editor_atto.add_widget_highlight(elementid, 'undo', 'undo');
        } else {
           M.editor_atto.remove_widget_highlight(elementid, 'undo', 'undo');
        }
    },

    /**
     * Add the buttons to the toolbar
     *
     * @method init
     * @param {object} params containing elementid and group
     */
    init : function(params) {

        // Undo button.
        var iconurl = M.util.image_url('e/undo', 'core');
        M.editor_atto.add_toolbar_button(params.elementid, 'undo', iconurl, params.group, M.atto_undo.undo_handler, 'undo', M.util.get_string('undo', 'atto_undo'));
        M.editor_atto.add_button_shortcut({action: 'undo', keys: 90});

        // Redo button.
        iconurl = M.util.image_url('e/redo', 'core');
        M.editor_atto.add_toolbar_button(params.elementid, 'undo', iconurl, params.group, M.atto_undo.redo_handler, 'redo', M.util.get_string('redo', 'atto_undo'));
        M.editor_atto.add_button_shortcut({action: 'redo', keys: 89});

        M.editor_atto.on('atto:selectionchanged', M.atto_undo.change_listener);
    }
};


}, '@VERSION@', {"requires": ["node", "moodle-editor_atto-editor-shortcut"]});
