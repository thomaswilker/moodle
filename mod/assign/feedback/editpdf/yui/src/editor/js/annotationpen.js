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
 * Class representing a pen.
 *
 * @namespace M.assignfeedback_editpdf
 * @class annotationpen
 * @extends annotation
 * @module moodle-assignfeedback_editpdf-editor
 */
ANNOTATIONPEN = function(config) {
    ANNOTATIONPEN.superclass.constructor.apply(this, [config]);
};

ANNOTATIONPEN.NAME = "annotationpen";
ANNOTATIONPEN.ATTRS = {};

Y.extend(ANNOTATIONPEN, M.assignfeedback_editpdf.annotation, {
    /**
     * Draw a pen annotation
     * @protected
     * @method draw
     * @return M.assignfeedback_editpdf.drawable
     */
    draw : function() {
        var drawable,
            shape,
            first,
            positions,
            xy;

        drawable = new M.assignfeedback_editpdf.drawable(this.editor);

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

        drawable.shapes.push(shape);
        this.drawable = drawable;

        return ANNOTATIONPEN.superclass.draw.apply(this);
    }

});

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.annotationpen = ANNOTATIONPEN;
