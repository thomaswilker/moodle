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
 * Class representing a list of annotations.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * ANNOTATION
 *
 * @namespace M.assignfeedback_editpdf
 * @class annotation
 * @param M.assignfeedback_editpdf.editor editor
 * @param Int gradeid
 * @param Int pageno
 * @param Int x
 * @param Int y
 * @param Int endx
 * @param Int endy
 * @param String type
 * @param String colour
 * @param M.assignfeedback_editpdf.point[] path
 */
ANNOTATION = function(editor, gradeid, pageno, x, y, endx, endy, type, colour, path) {

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
     * Comment page number
     * @property pageno
     * @type Int
     * @public
     */
    this.pageno = pageno || 0;

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
     * Ending x position
     * @property endx
     * @type Int
     * @public
     */
    this.endx = endx || 0;

    /**
     * Ending y position
     * @property endy
     * @type Int
     * @public
     */
    this.endy = endy || 0;

    /**
     * Path
     * @property path
     * @type M.assignfeedback_editpdf.point[]
     * @public
     */
    this.path = path || [];

    /**
     * Tool.
     * @property type
     * @type String
     * @public
     */
    this.type = type || 'rect';

    /**
     * Annotation colour.
     * @property colour
     * @type String
     * @public
     */
    this.colour = colour || 'red';

    /**
     * Reference to M.assignfeedback_editpdf.drawable
     * @property drawable
     * @type M.assignfeedback_editpdf.drawable
     * @public
     */
    this.drawable = false;

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
            endx : this.endx,
            endy : this.endy,
            type : this.type,
            path : this.path,
            pageno : this.pageno,
            colour : this.colour
        };
    };

    /**
     * Draw an annotation
     * @protected
     * @method draw_annotation
     * @param annotation
     * @return M.assignfeedback_editpdf.drawable
     */
    this.draw = function() {
        var drawable,
            positions,
            xy,
            width,
            height,
            topleftx,
            toplefty,
            annotationtype,
            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            offsetcanvas = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            shape,
            first;

        drawable = new M.assignfeedback_editpdf.drawable(this.editor);

        if (this.type === 'stamp') {
            // Find the matching stamp
            Y.each(this.editor.stamps, function(stamp) {
                if (this.path === stamp.url.replace(/^.*[\\\/]/, '')) {
                    // We need to put the image as background otherwise the browser will try to drag the image.
                    // Also we don't want to disable the image drag event (dragstart event), so we use background image.
                    stampnode = Y.Node.create('<div class="stamp" style="background-image:url(\'' + stamp.url + '\')"/>');
                    Y.one('.drawingcanvas').append(stampnode);
                    stampnode.setStyles({
                        height: stamp.height,
                        width: stamp.width
                    });
                    stampnode.setXY([this.x, this.y]);

                    drawable.nodes.push(stampnode);
                }
            }, this);
        }

        if (this.type === 'line') {
            shape = this.editor.graphic.addShape({
                type: Y.Path,
                fill: false,
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[this.colour]
                }
            });

            shape.moveTo(this.x, this.y);
            shape.lineTo(this.endx, this.endy);
            shape.end();
        }

        if (this.type === 'pen') {
            shape = this.editor.graphic.addShape({
               type: Y.Path,
                fill: false,
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[this.colour]
                }
            });

            first = true;
            // Recreate the pen path array.
            positions = this.path.split(':');
            // Redraw all the lines.
            Y.each(positions, function(position) {
                xy = position.split(',');
                if (first) {
                    shape.moveTo(xy[0], xy[1]);
                    first = false;
                } else {
                    shape.lineTo(xy[0], xy[1]);
                }
            }, this);

            shape.end();
        }

        if (this.type === 'rectangle' || this.type === 'oval' ) {
            if (this.type === 'rectangle') {
                annotationtype = Y.Rect;
            } if (this.type === 'oval') {
                annotationtype = Y.Ellipse;
            }

            // Convert data to integer to avoid wrong > or < results.
            this.x = parseInt(this.x, 10);
            this.y = parseInt(this.y, 10);
            this.endx = parseInt(this.endx, 10);
            this.endy = parseInt(this.endy, 10);

            // Work out the boundary box.
            topleftx = this.x;
            if (this.endx > topleftx) {
                width = this.endx - topleftx;
            } else {
                topleftx = this.endx;
                width = this.x - topleftx;
            }
            toplefty = this.y;

            if (this.endy > toplefty) {
                height = this.endy - toplefty;
            } else {
                toplefty = this.endy;
                height = this.y - toplefty;
            }

            shape = this.editor.graphic.addShape({
                type: annotationtype,
                width: width,
                height: height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: ANNOTATIONCOLOUR[this.colour]
                },
                x: topleftx,
                y: toplefty
            });
        }
        if (this.type === 'highlight' ) {
            // Convert data to integer to avoid wrong > or < results.
            this.x = parseInt(this.x, 10);
            this.y = parseInt(this.y, 10);
            this.endx = parseInt(this.endx, 10);
            this.endy = parseInt(this.endy, 10);

            // Work out the boundary box.
            topleftx = this.x;
            if (this.endx > topleftx) {
                width = this.endx - topleftx;
            } else {
                topleftx = this.endx;
                width = this.x - topleftx;
            }
            toplefty = this.y;

            if (this.endy > toplefty) {
                height = this.endy - toplefty;
            } else {
                toplefty = this.endy;
                height = this.y - toplefty;
            }

            highlightcolour = ANNOTATIONCOLOUR[this.colour];

            // Add an alpha channel to the rgb colour.

            highlightcolour = highlightcolour.replace('rgb', 'rgba');
            highlightcolour = highlightcolour.replace(')', ',0.5)');

            shape = this.editor.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                stroke: false,
                fill: {
                    color: highlightcolour
                },
                x: topleftx,
                y: toplefty
            });
        }

        drawable.shapes.push(shape);
        if (this.editor.currentannotation === this) {
            // Draw a highlight around the annotation.
            this.x = parseInt(this.x, 10);
            this.y = parseInt(this.y, 10);
            this.endx = parseInt(this.endx, 10);
            this.endy = parseInt(this.endy, 10);

            // Work out the boundary box.
            topleftx = this.x;
            if (this.endx > topleftx) {
                width = this.endx - topleftx;
            } else {
                topleftx = this.endx;
                width = this.x - topleftx;
            }
            toplefty = this.y;

            if (this.endy > toplefty) {
                height = this.endy - toplefty;
            } else {
                toplefty = this.endy;
                height = this.y - toplefty;
            }

            shape = this.editor.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: SELECTEDBORDERCOLOUR
                },
                fill: {
                   color: SELECTEDFILLCOLOUR
                },
                x: topleftx,
                y: toplefty
            });
            drawable.shapes.push(shape);

            // Add a delete X to the annotation.
            var deleteicon = Y.Node.create('<img src="' + M.util.image_url('trash', 'assignfeedback_editpdf') + '"/>'),
                deletelink = Y.Node.create('<a href="#" role="button"></a>');

            deleteicon.setAttrs({
                'alt': M.util.get_string('deleteannotation', 'assignfeedback_editpdf')
            });
            deleteicon.setStyles({
                'backgroundColor' : 'white',
                'border' : '2px solid ' + SELECTEDBORDERCOLOUR
            });
            deletelink.addClass('deleteannotationbutton');
            deletelink.append(deleteicon);

            drawingregion.append(deletelink);
            deletelink.setData('annotation', this);
            deletelink.setStyle('zIndex', '1000');

            deletelink.on('click', this.remove, this);
            deletelink.on('key', this.remove, 'space,enter', this);

            deletelink.setX(offsetcanvas[0] + topleftx + width - 20);
            deletelink.setY(offsetcanvas[1] + toplefty + 2);
            drawable.nodes.push(deletelink);
        }
        this.drawable = drawable;

        return drawable;
    };

    /**
     * Delete an annotation
     * @protected
     * @method remove
     * @param event
     */
    this.remove = function() {
        var annotations;

        annotations = this.editor.pages[this.editor.currentpage].annotations;
        for (i = 0; i < annotations.length; i++) {
            if (annotations[i] === this) {
                annotations.splice(i, 1);
                this.drawable.erase();
                this.editor.save_current_page();
                return;
            }
        }
    };

};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.annotation = ANNOTATION;
