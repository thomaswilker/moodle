YUI.add('moodle-atto_indent-button', function (Y, NAME) {

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

/*
 * @package    atto_indent
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module     moodle-atto_indent-button
 */

/**
 * Atto text editor indent plugin.
 *
 * @namespace M.atto_indent
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_indent').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function() {

        this.addButton({
            icon: 'e/decrease_indent',
            title: 'outdent',
            buttonName: 'outdent',
            callback: this.outdent
        });

        this.addButton({
            icon: 'e/increase_indent',
            title: 'indent',
            buttonName: 'indent',
            callback: this.indent
        });
    },

    /**
     * Indents the currently selected content.
     *
     * @method indent
     */
    indent: function() {
        // Save the current selection - we want to restore this.
        var selection = window.rangy.saveSelection();

        // Remove display:none from rangy markers so browser doesn't delete them.
        this.editor.all('.rangySelectionBoundary').setStyle('display', null);

        // Mark all existing block quotes in case the user has actually added some.
        this.editor.all('blockquote').addClass('pre-existing');

        // Run the indent command.
        document.execCommand('indent', false, null);

        // Any new blockquote it should be marked with indent class.
        this.editor.all('blockquote').addClass('editor-indent');
        this.editor.all('.pre-existing').removeClass('editor-indent');

        // Clean pre-existing blockquote classes.
        this.editor.all('blockquote[class="pre-existing"]').removeAttribute('class');
        this.editor.all('blockquote.pre-existing').removeClass('pre-existing');

        // Set correct margin.
        var margindir = (Y.one('body.dir-ltr')) ? 'marginLeft' : 'marginRight';
        this.editor.all('blockquote.editor-indent').setStyle(margindir, '30px');

        // Change new indent to a div.
        this.replaceTags(this.editor.all('blockquote.editor-indent'), 'div');

        // Restore the original selection.
        window.rangy.restoreSelection(selection);

        // Remove the selection markers - a clean up really.
        window.rangy.removeMarkers(selection);

        // Mark the text as having been updated.
        this.markUpdated();
    },

    /**
     * Outdents the currently selected content.
     *
     * @method outdent
     */
    outdent: function() {
        // Save the selection we will want to restore it.
        var selection = window.rangy.saveSelection();

        // Replace existing blockquotes so the browser does not outdent them.
        this.editor.all('blockquote').addClass('pre-existing');
        this.replaceTags(this.editor.all('.pre-existing'), 'div');

        // Replace all div indents with blockquote indents so that we can rely on the browser functionality.
        this.replaceTags(this.editor.all('.editor-indent'), 'blockquote');

        // Restore the users selection - otherwise the next outdent operation won't work!
        window.rangy.restoreSelection(selection);
        // And save it once more.
        selection = window.rangy.saveSelection();

        // Outdent.
        document.execCommand('outdent', false, null);

        // Change the remaining blockquotes to div indents.
        this.replaceTags(this.editor.all('blockquote'), 'div');

        // Restore pre-existant blockquotes and remove marker class.
        this.replaceTags(this.editor.all('.pre-existing'), 'blockquote');
        this.editor.all('[class="pre-existing"]').removeAttribute('class');
        this.editor.all('.pre-existing').removeClass('pre-existing');

        // Restore the selection again.
        window.rangy.restoreSelection(selection);

        // Clean up any left over selection markers.
        window.rangy.removeMarkers(selection);

        // Mark the text as having been updated.
        this.markUpdated();
    },

    /**
     * Replaces all the tags in a node list with new type.
     * @method replaceTags
     * @param NodeList nodelist
     * @param String tag
     */
    replaceTags: function(nodelist, tag) {
        // We mark elements in the node list for iterations.
        nodelist.setAttribute('data-iterate', true);
        var node = this.editor.one('[data-iterate="true"]');
        while (node) {
            var clone = Y.Node.create('<' + tag + ' />')
                .setAttrs(node.getAttrs())
                .removeAttribute('data-iterate');
            // Copy class and style if not blank.
            if (node.getAttribute('style')) {
                clone.setAttribute('style', node.getAttribute('style'));
            }
            if (node.getAttribute('class')) {
                clone.setAttribute('class', node.getAttribute('class'));
            }
            // We use childNodes here because we are interested in both type 1 and 3 child nodes.
            var children = node.getDOMNode().childNodes, child;
            child = children[0];
            while (typeof child !== "undefined") {
                clone.append(child);
                child = children[0];
            }
            node.replace(clone);
            node = this.editor.one('[data-iterate="true"]');
        }
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
