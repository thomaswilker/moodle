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
ANNOTATION = function(config) {
    ANNOTATION.superclass.constructor.apply(this, [config]);
};

ANNOTATION.NAME = "annotation";
ANNOTATION.ATTRS = {};

Y.extend(ANNOTATION, Y.Base, {
    /**
     * Reference to M.assignfeedback_editpdf.editor.
     * @property editor
     * @type M.assignfeedback_editpdf.editor
     * @public
     */
    editor : null,

    /**
     * Grade id
     * @property gradeid
     * @type Int
     * @public
     */
    gradeid : 0,

    /**
     * Comment page number
     * @property pageno
     * @type Int
     * @public
     */
    pageno : 0,

    /**
     * X position
     * @property x
     * @type Int
     * @public
     */
    x : 0,

    /**
     * Y position
     * @property y
     * @type Int
     * @public
     */
    y : 0,

    /**
     * Ending x position
     * @property endx
     * @type Int
     * @public
     */
    endx : 0,

    /**
     * Ending y position
     * @property endy
     * @type Int
     * @public
     */
    endy : 0,

    /**
     * Path
     * @property path
     * @type String - list of points like x1,y1:x2,y2
     * @public
     */
    path : '',

    /**
     * Tool.
     * @property type
     * @type String
     * @public
     */
    type : 'rect',

    /**
     * Annotation colour.
     * @property colour
     * @type String
     * @public
     */
    colour : 'red',

    /**
     * Reference to M.assignfeedback_editpdf.drawable
     * @property drawable
     * @type M.assignfeedback_editpdf.drawable
     * @public
     */
    drawable : false,

    /**
     * Initialise the annotation.
     *
     * @method initializer
     * @return void
     */
    initializer : function(config) {
        this.editor = config.editor || null;
        this.gradeid = parseInt(config.gradeid, 10) || 0;
        this.pageno = parseInt(config.pageno, 10) || 0;
        this.x = parseInt(config.x, 10) || 0;
        this.y = parseInt(config.y, 10) || 0;
        this.endx = parseInt(config.endx, 10) || 0;
        this.endy = parseInt(config.endy, 10) || 0;
        this.path = config.path || '';
        this.type = config.type || 'rect';
        this.colour = config.colour || 'red';
        this.drawable = false;
    },

    /**
     * Clean a comment record, returning an oject with only fields that are valid.
     * @public
     * @method clean
     * @return {}
     */
    clean : function() {
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
    },

    /**
     * Draw a selection around this annotation if it is selected.
     * @public
     * @method draw_highlight
     * @return M.assignfeedback_editpdf.drawable
     */
    draw_highlight : function() {
        var bounds,
            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            offsetcanvas = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            shape;

        if (this.editor.currentannotation === this) {
            // Draw a highlight around the annotation.
            bounds = new M.assignfeedback_editpdf.rect();
            bounds.bound([new M.assignfeedback_editpdf.point(this.x, this.y),
                          new M.assignfeedback_editpdf.point(this.endx, this.endy)]);

            shape = this.editor.graphic.addShape({
                type: Y.Rect,
                width: bounds.width,
                height: bounds.height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: SELECTEDBORDERCOLOUR
                },
                fill: {
                   color: SELECTEDFILLCOLOUR
                },
                x: bounds.x,
                y: bounds.y
            });
            this.drawable.shapes.push(shape);

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
            deletelink.setStyle('zIndex', '200');

            deletelink.on('click', this.remove, this);
            deletelink.on('key', this.remove, 'space,enter', this);

            deletelink.setX(offsetcanvas[0] + bounds.x + bounds.width - 20);
            deletelink.setY(offsetcanvas[1] + bounds.y + 4);
            this.drawable.nodes.push(deletelink);
        }
        return this.drawable;
    },

    /**
     * Draw an annotation
     * @public
     * @method draw
     * @return M.assignfeedback_editpdf.drawable|false
     */
    draw : function() {
        // Should be overridden by the subclass.
        this.draw_highlight();
        return this.drawable;
    },

        /**
            positions,
            xy,
            bounds,
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

            bounds = new M.assignfeedback_editpdf.rect();
            bounds.bound([new M.assignfeedback_editpdf.point(this.x, this.y),
                          new M.assignfeedback_editpdf.point(this.endx, this.endy)]);

            shape = this.editor.graphic.addShape({
                type: annotationtype,
                width: bounds.width,
                height: bounds.height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: ANNOTATIONCOLOUR[this.colour]
                },
                x: bounds.x,
                y: bounds.y
            });
        }
        if (this.type === 'highlight' ) {
            bounds = new M.assignfeedback_editpdf.rect();
            bounds.bound([new M.assignfeedback_editpdf.point(this.x, this.y),
                          new M.assignfeedback_editpdf.point(this.endx, this.endy)]);

            highlightcolour = ANNOTATIONCOLOUR[this.colour];

            // Add an alpha channel to the rgb colour.

            highlightcolour = highlightcolour.replace('rgb', 'rgba');
            highlightcolour = highlightcolour.replace(')', ',0.5)');

            shape = this.editor.graphic.addShape({
                type: Y.Rect,
                width: bounds.width,
                height: bounds.height,
                stroke: false,
                fill: {
                    color: highlightcolour
                },
                x: bounds.x,
                y: bounds.y
            });
        }

        drawable.shapes.push(shape);
        if (this.editor.currentannotation === this) {
            // Draw a highlight around the annotation.
            bounds = new M.assignfeedback_editpdf.rect();
            bounds.bound([new M.assignfeedback_editpdf.point(this.x, this.y),
                          new M.assignfeedback_editpdf.point(this.endx, this.endy)]);

            shape = this.editor.graphic.addShape({
                type: Y.Rect,
                width: bounds.width,
                height: bounds.height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: SELECTEDBORDERCOLOUR
                },
                fill: {
                   color: SELECTEDFILLCOLOUR
                },
                x: bounds.x,
                y: bounds.y
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
            deletelink.setStyle('zIndex', '200');

            deletelink.on('click', this.remove, this);
            deletelink.on('key', this.remove, 'space,enter', this);

            deletelink.setX(offsetcanvas[0] + bounds.x + bounds.width - 20);
            deletelink.setY(offsetcanvas[1] + bounds.y + 4);
            drawable.nodes.push(deletelink);
        }
        this.drawable = drawable;

        return drawable;
    },
    */

    /**
     * Delete an annotation
     * @protected
     * @method remove
     * @param event
     */
    remove : function() {
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
    },

    /**
     * Move an annotation to a new location.
     * @public
     * @param int newx
     * @param int newy
     * @method move_annotation
     */
    move : function(newx, newy) {
        var diffx = newx - this.x,
            diffy = newy - this.y,
            newpath, oldpath, xy,
            x, y;

        this.x += diffx;
        this.y += diffy;
        this.endx += diffx;
        this.endy += diffy;

        if (this.path) {
            newpath = [];
            oldpath = this.path.split(':');
            Y.each(oldpath, function(position) {
                xy = position.split(',');
                x = parseInt(xy[0], 10);
                y = parseInt(xy[1], 10);
                newpath.push((x + diffx) + ',' + (y + diffy));
            });

            this.path = newpath.join(':');

        }
        this.drawable.erase();
        this.editor.drawables.push(this.draw());
    }

});

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.annotation = ANNOTATION;
