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
 * Atto text editor accessibilitychecker plugin.
 *
 * This plugin adds some functions to do things that screen readers do not do well.
 * Specifically, listing the active styles for the selected text,
 * listing the images in the page, listing the links in the page.
 *
 * @package    editor-atto
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.atto_accessibilitychecker = M.atto_accessibilitychecker || {
    /**
     * The window used to display the accessibility ui.
     *
     * @property dialogue
     * @type M.core.dialogue
     * @default null
     */
    dialogue : null,

    /**
     * Display the ui dialogue.
     *
     * @method init
     * @param Event e
     * @param string elementid
     */
    display_ui : function(e, elementid) {
        e.preventDefault();
        if (!M.editor_atto.is_active(elementid)) {
            M.editor_atto.focus(elementid);
        }
        var dialogue;
        if (!M.atto_accessibilitychecker.dialogue) {
            dialogue = new M.core.dialogue({
                visible: false,
                modal: true,
                close: true,
                draggable: true,
                width: '800px'
            });
        } else {
            dialogue = M.atto_accessibilitychecker.dialogue;
        }

        dialogue.set('bodyContent', M.atto_accessibilitychecker.get_form_content(elementid));
        dialogue.set('headerContent', M.util.get_string('pluginname', 'atto_accessibilitychecker'));
        dialogue.render();
        dialogue.centerDialogue();

        dialogue.show();
        M.atto_accessibilitychecker.dialogue = dialogue;
    },

    /**
     * Add this button to the form.
     *
     * @method init
     * @param {Object} params
     */
    init : function(params) {
        var iconurl = M.util.image_url('e/visual_blocks', 'core');
        M.editor_atto.add_toolbar_button(params.elementid, 'accessibilitychecker', iconurl, params.group, this.display_ui);
    },

    /**
     * Generate the HTML that lists the found warnings.
     *
     * @method add_warnings
     * @param Y.Node list - node to append the html to.
     * @param String description - description of this failure.
     * @param Y.Node[] nodes - list of failing nodes.
     */
    add_warnings : function(list, description, nodes) {
        var warning, fails, i, rawhtml, cloned;

        if (nodes.length > 0) {
            warning = Y.Node.create('<p class="warning">' + description + '</p>');
            fails = Y.Node.create('<ol></ol');
            i = 0;
            for (i = 0; i < nodes.length; i++) {
                rawhtml = Y.Node.create('<p></p>');
                cloned = nodes[i].cloneNode(true);
                cloned.removeAttribute('id');
                rawhtml.append(cloned);
                fails.append(Y.Node.create('<li>' + Y.Escape.html(rawhtml.getHTML()) + '</li>'));
            }

            warning.append(fails);
            list.append(warning);
        }
    },

    /**
     * List the accessibility warnings for the current editor
     *
     * @method list_warnings
     * @param string elementid
     * @return String
     */
    list_warnings : function(elementid) {

        var list = Y.Node.create('<div></div>');

        var editable = M.editor_atto.get_editable_node(elementid);

        // Missing alternatives.
        var missingalt = [], dodgyalt = [], dodgylinks = [], dodgycontrast = [], filenameregex = /\.\w{0,3}$/;

        // Images with no alt text or dodgy alt text.
        var alt;
        editable.all('img').each(function (img) {
            alt = img.getAttribute('alt');
            if (typeof alt === 'undefined' || alt === '') {
                missingalt.push(img);
            } else {
                if (filenameregex.test(alt)) {
                    dodgyalt.push(img);
                }
            }
        }, this);

        this.add_warnings(list, M.util.get_string('imagesmissingalt', 'atto_accessibilitychecker'), missingalt);
        this.add_warnings(list, M.util.get_string('imagesaltnotmeaningful', 'atto_accessibilitychecker'), dodgyalt);

        // Links with dodgy text.
        var linktext;
        editable.all('a').each(function (link) {
            linktext = link.get('text');
            if (typeof linktext !== 'undefined' && linktext !== '') {
                if (filenameregex.test(linktext)) {
                    dodgylinks.push(link);
                }
            }
        }, this);

        // Contrast ratios.
        var foreground, background, foregroundhsl, backgroundhsl, ratio;
        editable.all('*').each(function (node) {
            // Check for non-empty text.
            if (node.get('text').trim() !== '') {
                foreground = node.getComputedStyle('color');
                background = node.getComputedStyle('backgroundColor');
                if (background === 'transparent') {
                    background = 'rgb(255, 255, 255)';
                }

                foregroundhsl = Y.Color.toArray(Y.Color.toHSL(foreground));
                backgroundhsl = Y.Color.toArray(Y.Color.toHSL(background));
                if (foregroundhsl[2] > backgroundhsl[2]) {
                    ratio = (foregroundhsl[2] + 0.05) / (backgroundhsl[2] + 0.05);
                } else {
                    ratio = (backgroundhsl[2] + 0.05) / (foregroundhsl[2] + 0.05);
                }
                if (ratio <= 4.5) {
                    dodgycontrast.push(node);
                }
            }
        }, this);

        this.add_warnings(list, M.util.get_string('needsmorecontrast', 'atto_accessibilitychecker'), dodgycontrast);

        if (!list.hasChildNodes()) {
            list.append('<p>' + M.util.get_string('nowarnings', 'atto_accessibilitychecker') + '</p>');
        }
        // Append the list of current styles.
        return list;
    },

    /**
     * Return the HTML of the form to show in the dialogue.
     *
     * @method get_form_content
     * @param string elementid
     * @return string
     */
    get_form_content : function(elementid) {
        // Current styles.
        var html = '<div><p id="atto_accessibilitychecker_warningslabel">' +
                M.util.get_string('report', 'atto_accessibilitychecker') +
                '<br/>' +
                '<span id="atto_accessibilitychecker_listwarnings" ' +
                'aria-labelledby="atto_accessibilitychecker_warningslabel"/></p></div>';

        var content = Y.Node.create(html);

        content.one('#atto_accessibilitychecker_listwarnings').append(this.list_warnings(elementid));
        content.one('#atto_accessibilitychecker_listwarnings').setStyle('wordWrap', 'break-word');

        return content;
    }

};
