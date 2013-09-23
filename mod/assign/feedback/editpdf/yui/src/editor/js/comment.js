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
 * Class representing a list of comments.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * COMMENT
 *
 * @namespace M.assignfeedback_editpdf
 * @class quickcommentlist
 * @param M.assignfeedback_editpdf.editor editor
 * @param Int gradeid
 * @param Int pageno
 * @param Int x
 * @param Int y
 * @param Int width
 * @param String colour
 * @param String rawtext
 */
COMMENT = function(editor, gradeid, pageno, x, y, width, colour, rawtext) {

    /**
     * Reference to M.assignfeedback_editpdf.editor.
     * @property editor
     * @type M.assignfeedback_editpdf.editor
     * @public
     */
    this.editor = editor;

    /**
     * Grade id
     * @property gradeid
     * @type Int
     * @public
     */
    this.gradeid = gradeid || 0;

    /**
     * X position
     * @property x
     * @type Int
     * @public
     */
    this.x = x || 0;

    /**
     * Y position
     * @property y
     * @type Int
     * @public
     */
    this.y = y || 0;

    /**
     * Comment width
     * @property width
     * @type Int
     * @public
     */
    this.width = width || 0;

    /**
     * Comment rawtext
     * @property rawtext
     * @type String
     * @public
     */
    this.rawtext = rawtext || '';

    /**
     * Comment page number
     * @property pageno
     * @type Int
     * @public
     */
    this.pageno = pageno || 0;

    /**
     * Comment background colour.
     * @property colour
     * @type String
     * @public
     */
    this.colour = colour || 'yellow';

    /**
     * Reference to M.assignfeedback_editpdf.drawable
     * @property drawable
     * @type M.assignfeedback_editpdf.drawable
     * @public
     */
    this.drawable = false;

    /**
     * Boolean used by a timeout to delete empty comments after a short delay.
     * @property deleteme
     * @type Boolean
     * @public
     */
    this.deleteme = false;

    /**
     * Clean a comment record, returning an oject with only fields that are valid.
     * @public
     * @method clean
     * @return {}
     */
    this.clean = function() {
        return {
            gradeid : this.gradeid,
            x : this.x,
            y : this.y,
            width : this.width,
            rawtext : this.rawtext,
            pageno : this.currentpage,
            colour : this.colour
        };
    };

    /**
     * Draw a comment.
     * @public
     * @method draw_comment
     * @param boolean focus - Set the keyboard focus to the new comment if true
     * @return M.assignfeedback_editpdf.drawable
     */
    this.draw = function(focus) {
        var drawable = new M.assignfeedback_editpdf.drawable(this.editor),
            node,
            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            offsetcanvas = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            offsetdialogue = Y.one(SELECTOR.DIALOGUE).getXY(),
            offsetleft = offsetcanvas[0] - offsetdialogue[0],
            offsettop = offsetcanvas[1] - offsetdialogue[1],
            container,
            menu;

        // Lets add a contenteditable div.
        node = Y.Node.create('<textarea/>');
        container = Y.Node.create('<div class="commentdrawable"/>');
        menu = Y.Node.create('<a href="#"><img src="' + this.editor.get('menuicon') + '"/></a>');
        container.append(node);
        container.append(menu);
        if (this.width < 100) {
            this.width = 100;
        }
        container.setStyles({
            position: 'absolute',
            left: (parseInt(this.x, 10) + offsetleft) + 'px',
            top: (parseInt(this.y, 10) + offsettop) + 'px'
        });
        node.setStyles({
            width: this.width + 'px',
            backgroundColor: COMMENTCOLOUR[this.colour]
        });

        drawingregion.append(container);
        drawable.nodes.push(container);
        node.set('value', this.rawtext);
        node.setStyle('height', node.get('scrollHeight') - 8 + 'px');
        this.attach_events(node, menu);
        if (focus) {
            node.focus();
        }
        this.drawable = drawable;

        return drawable;
    };

    /**
     * Delete an empty comment if it's menu hasn't been opened in time.
     * @method delete_comment_later
     */
    this.delete_comment_later = function() {
        if (this.deleteme) {
            this.remove();
        }
    };

    /**
     * Comment nodes have a bunch of event handlers attached to them directly.
     * This is all done here for neatness.
     *
     * @protected
     * @method attach_comment_events
     * @param node - The Y.Node representing the comment.
     * @param menu - The Y.Node representing the menu.
     */
    this.attach_events = function(node, menu) {
        // Save the text on blur.
        node.on('blur', function() {
            // Save the changes back to the comment.
            this.rawtext = node.get('value');
            this.width = parseInt(node.getStyle('width'), 10);

            // Trim.
            if (this.rawtext.replace(/^\s+|\s+$/g, "") === '') {
                // Delete empty comments.
                this.deleteme = true;
                Y.later(400, this, this.delete_comment_later);
            }
            this.editor.save_current_page();
        }, this);

        // For delegated event handler.
        menu.setData('comment', this);

        node.on('keyup', function() {
            var scrollHeight = node.get('scrollHeight') - 8;
            node.setStyle('height', scrollHeight + 'px');
        });

        node.on('gesturemovestart', function(e) {
            node.setData('dragging', true);
            node.setData('offsetx', e.clientX - node.getX());
            node.setData('offsety', e.clientY - node.getY());
        });
        node.on('gesturemoveend', function() {
            node.setData('dragging', false);
            this.editor.save_current_page();
        }, null, this);
        node.on('gesturemove', function(e) {
            var x = e.clientX - node.getData('offsetx'),
                y = e.clientY - node.getData('offsety'),
                canvas = Y.one(SELECTOR.DRAWINGCANVAS),
                offsetcanvas = canvas.getXY(),
                canvaswidth,
                canvasheight,
                nodewidth,
                nodeheight,
                offsetleft = offsetcanvas[0],
                offsettop = offsetcanvas[1];

            canvaswidth = parseInt(canvas.getStyle('width'), 10);
            canvasheight = parseInt(canvas.getStyle('height'), 10);
            nodewidth = parseInt(node.getStyle('width'), 10);
            nodeheight = parseInt(node.getStyle('height'), 10);

            // Constrain the comment to the canvas.
            if (x < offsetleft) {
                x = offsetleft;
            }
            if (y < offsettop) {
                y = offsettop;
            }
            if (x - offsetleft + nodewidth > canvaswidth) {
                x = offsetleft + canvaswidth - nodewidth;
            }
            if (y - offsettop + nodeheight > canvasheight) {
                y = offsettop + canvasheight - nodeheight;
            }

            this.x = x - offsetleft;
            this.y = y - offsettop;

            node.ancestor().setX(x);
            node.ancestor().setY(y);
        });
    };

    /**
     * Delete a comment.
     * @method remove
     */
    this.remove = function() {
        var i = 0, comments;

        comments = this.editor.pages[this.editor.currentpage].comments;
        for (i = 0; i < comments.length; i++) {
            if (comments[i] === this) {
                comments.splice(i, 1);
                this.drawable.erase();
                this.editor.save_current_page();
                return;
            }
        }
    };

};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.comment = COMMENT;
