YUI.add('moodle-assignfeedback_editpdf-editor', function (Y, NAME) {

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
 * Class representing a 2d point.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * POINT
 *
 * @namespace M.assignfeedback_editpdf
 * @param int x
 * @param int y
 * @class point
 */
POINT = function(x, y) {

    /**
     * X coordinate.
     * @property x
     * @type int
     * @public
     */
    this.x = x;

    /**
     * Y coordinate.
     * @property y
     * @type int
     * @public
     */
    this.y = y;

};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.point = POINT;
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
 * Class representing a partially completed edit operation.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * EDIT
 *
 * @namespace M.assignfeedback_editpdf
 * @class edit
 */
EDIT = function() {

    /**
     * Starting point for the edit.
     * @property start
     * @type M.assignfeedback_editpdf.point|false
     * @public
     */
    this.start = false;

    /**
     * Finishing point for the edit.
     * @property end
     * @type M.assignfeedback_editpdf.point|false
     * @public
     */
    this.end = false;

    /**
     * Starting time for the edit.
     * @property starttime
     * @type int
     * @public
     */
    this.starttime = 0;

    /**
     * Starting point for the currently selected annotation.
     * @property annotationstart
     * @type M.assignfeedback_editpdf.point|false
     * @public
     */
    this.annotationstart = false;

    /**
     * The currently selected tool
     * @property tool
     * @type String
     * @public
     */
    this.tool = "comment";

    /**
     * The currently comment colour
     * @property commentcolour
     * @type String
     * @public
     */
    this.commentcolour = 'yellow';

    /**
     * The currently annotation colour
     * @property annotationcolour
     * @type String
     * @public
     */
    this.annotationcolour = 'red';

    /**
     * List of points the the current drawing path.
     * @property path
     * @type M.assignfeedback_editpdf.point[]
     * @public
     */
    this.path = [];
};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.edit = EDIT;
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
 * Class representing a drawable thing which contains both
 * Y.Nodes, and Y.Shapes.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * DRAWABLE
 *
 * @namespace M.assignfeedback_editpdf
 * @param M.assignfeedback_editpdf.editor editor
 * @class drawable
 */
DRAWABLE = function(editor) {

    /**
     * Reference to M.assignfeedback_editpdf.editor.
     * @property editor
     * @type M.assignfeedback_editpdf.editor
     * @public
     */
    this.editor = editor;

    /**
     * Array of Y.Shape
     * @property shapes
     * @type Y.Shape[]
     * @public
     */
    this.shapes = [];

    /**
     * Array of Y.Node
     * @property nodes
     * @type Y.Node[]
     * @public
     */
    this.nodes = [];

    /**
     * Delete the shapes from the drawable.
     * @protected
     * @method erase_drawable
     */
    this.erase = function() {
        if (this.shapes) {
            while (this.shapes.length > 0) {
                this.editor.graphic.removeShape(this.shapes.pop());
            }
        }
        if (this.nodes) {
            while (this.nodes.length > 0) {
                this.nodes.pop().remove();
            }
        }
    };

};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.drawable = DRAWABLE;
var DROPDOWN_NAME = "Dropdown menu",
    DROPDOWN;

/**
 * DROPDOWN
 * This is a drop down list of buttons triggered (and aligned to) a button.
 *
 * @namespace M.assignfeedback_editpdf.widget.dropdown
 * @class dropdown
 * @constructor
 * @extends Y.Base
 */
DROPDOWN = function(config) {
    config.draggable = false;
    config.centered = false;
    config.width = 'auto';
    config.lightbox = false;
    config.visible = false;
    config.zIndex = 100;
    config.footerContent = '';
    DROPDOWN.superclass.constructor.apply(this, [config]);
};

Y.extend(DROPDOWN, M.core.dialogue, {
    /**
     * Initialise the menu.
     *
     * @method initializer
     * @return void
     */
    initializer : function(config) {
        var button, body, headertext, bb;
        DROPDOWN.superclass.initializer.call(this, config);

        bb = this.get('boundingBox');
        bb.addClass('assignfeedback_editpdf_dropdown');

        // Align the menu to the button that opens it.
        button = this.get('buttonNode');

        // Close the menu when clicked outside (excluding the button that opened the menu).
        body = this.bodyNode;

        headertext = Y.Node.create('<h3/>');
        headertext.addClass('accesshide');
        headertext.setHTML(this.get('headerText'));
        body.prepend(headertext);

        body.on('clickoutside', function(e) {
            if (e.target !== button && e.target.ancestor() !== button) {
                e.preventDefault();
                this.hide();
            }
        }, this);

        button.on('click', this.show, this);
        button.on('key', this.show, 'enter,space', this);
    },

    /**
     * Override the show method to align to the button.
     *
     * @method show
     * @return void
     */
    show : function() {
        var button = this.get('buttonNode');

        result = DROPDOWN.superclass.show.call(this);
        this.align(button, [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]);
    }
}, {
    NAME : DROPDOWN_NAME,
    ATTRS : {
        /**
         * The header for the drop down (only accessible to screen readers).
         *
         * @attribute headerText
         * @type String
         * @default ''
         */
        headerText : {
            value : ''
        },

        /**
         * The button used to show/hide this drop down menu.
         *
         * @attribute buttonNode
         * @type Y.Node
         * @default null
         */
        buttonNode : {
            value : null
        }
    }
});

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.dropdown = DROPDOWN;
var COLOURPICKER_NAME = "Colourpicker",
    COLOURPICKER;

/**
 * COLOURPICKER
 * This is a drop down list of colours.
 *
 * @namespace M.assignfeedback_editpdf.colourpicker
 * @class dropdown
 * @constructor
 * @extends Y.Base
 */
COLOURPICKER = function(config) {
    COLOURPICKER.superclass.constructor.apply(this, [config]);
};

Y.extend(COLOURPICKER, M.assignfeedback_editpdf.dropdown, {

    /**
     * Initialise the menu.
     *
     * @method initializer
     * @return void
     */
    initializer : function(config) {
        var colourlist = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>'),
            body;

        // Build a list of coloured buttons.
        Y.each(this.get('colours'), function(rgb, colour) {
            var button, listitem, title, img;

            title = M.util.get_string(colour, 'assignfeedback_editpdf');
            img = M.util.image_url('commentcolour', 'assignfeedback_editpdf');
            button = Y.Node.create('<button><img alt="' + title + '" src="' + img + '"/></button>');
            button.setAttribute('data-colour', colour);
            button.setAttribute('data-rgb', rgb);
            button.addClass('colour_' + colour);
            button.setStyle('backgroundImage', 'none');
            button.one('img').setStyle('background', rgb);
            if (colour === 'clear') {
                button.one('img').setStyle('borderStyle', 'dashed');
            }
            listitem = Y.Node.create('<li/>');
            listitem.append(button);
            colourlist.append(listitem);
        }, this);

        body = Y.Node.create('<div/>');

        // Set the call back.
        colourlist.delegate('click', this.callback_handler, 'button', this);
        colourlist.delegate('key', this.callback_handler, 'down:13', 'button', this);

        // Set the accessible header text.
        this.set('headerText', M.util.get_string('colourpicker', 'assignfeedback_editpdf'));

        // Set the body content.
        body.append(colourlist);
        this.set('bodyContent', body);

        COLOURPICKER.superclass.initializer.call(this, config);
    },
    callback_handler : function(e) {
        var callback = this.get('callback'),
            callbackcontext = this.get('context'),
            bind;

        this.hide();

        // Call the callback with the specified context.
        bind = Y.bind(callback, callbackcontext, e);

        bind();
    }
}, {
    NAME : COLOURPICKER_NAME,
    ATTRS : {
        /**
         * The list of colours this colour picker supports.
         *
         * @attribute colours
         * @type {String: String} (The keys of the array are the colour names and the values are localized strings)
         * @default {}
         */
        colours : {
            value : {}
        },

        /**
         * The function called when a new colour is chosen.
         *
         * @attribute callback
         * @type function
         * @default null
         */
        callback : {
            value : null
        },

        /**
         * The context passed to the callback when a colour is chosen.
         *
         * @attribute context
         * @type Y.Node
         * @default null
         */
        context : {
            value : null
        }
    }
});

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.colourpicker = COLOURPICKER;
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
 * Class representing a users quick comment.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * QUICKCOMMENT
 *
 * @namespace M.assignfeedback_editpdf
 * @class quickcomment
 */
QUICKCOMMENT = function(id, rawtext, width, colour) {

    /**
     * Quick comment text.
     * @property rawtext
     * @type String
     * @public
     */
    this.rawtext = rawtext || '';

    /**
     * ID of the comment
     * @property id
     * @type Int
     * @public
     */
    this.id = id || 0;

    /**
     * Width of the comment
     * @property width
     * @type Int
     * @public
     */
    this.width = width || 100;

    /**
     * Colour of the comment.
     * @property colour
     * @type String
     * @public
     */
    this.colour = colour || "yellow";
};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.quickcomment = QUICKCOMMENT;
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
 * Class representing a users list of quick comments.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

/**
 * QUICKLIST
 *
 * @namespace M.assignfeedback_editpdf
 * @class quickcommentlist
 */
QUICKCOMMENTLIST = function(editor) {

    /**
     * Reference to M.assignfeedback_editpdf.editor.
     * @property editor
     * @type M.assignfeedback_editpdf.editor
     * @public
     */
    this.editor = editor;
    Y.log(this.editor);
    Y.log(this.editor.get('userid'));

    /**
     * Array of Comments
     * @property shapes
     * @type M.assignfeedback_editpdf.quickcomment[]
     * @public
     */
    this.comments = [];

    /**
     * Add a comment to the users quicklist.
     *
     * @protected
     * @method add
     */
    this.add = function(comment) {
        var ajaxurl = AJAXBASE,
            config;

        // Do not save empty comments.
        if (comment.rawtext === '') {
            return;
        }

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'addtoquicklist',
                'userid' : this.editor.get('userid'),
                'commenttext' : comment.rawtext,
                'width' : comment.width,
                'colour' : comment.colour,
                'attemptnumber' : this.editor.get('attemptnumber'),
                'assignmentid' : this.editor.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    var jsondata, quickcomment;
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        } else {
                            quickcomment = new M.assignfeedback_editpdf.quickcomment(jsondata.id,
                                                                                     jsondata.rawtext,
                                                                                     jsondata.width,
                                                                                     jsondata.colour);
                            this.comments.push(quickcomment);
                        }
                    } catch (e) {
                        return new M.core.exception(e);
                    }
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);
    };

    /**
     * Remove a comment from the users quicklist.
     *
     * @public
     * @method remove
     */
    this.remove = function(comment) {
        var ajaxurl = AJAXBASE,
            config;

        // Should not happen.
        if (!comment) {
            return;
        }

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'removefromquicklist',
                'userid' : this.editor.get('userid'),
                'commentid' : comment.id,
                'attemptnumber' : this.editor.get('attemptnumber'),
                'assignmentid' : this.editor.get('assignmentid')
            },
            on: {
                success: function() {
                    var i;

                    // Find and remove the comment from the quicklist.
                    i = this.comments.indexOf(comment);
                    if (i >= 0) {
                        this.comments.splice(i, 1);
                    }
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);
    };

    /**
     * Load the users quick comments list.
     *
     * @protected
     * @method load_quicklist
     */
    this.load = function() {
        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'get',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'loadquicklist',
                'userid' : this.editor.get('userid'),
                'attemptnumber' : this.editor.get('attemptnumber'),
                'assignmentid' : this.editor.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    var jsondata;
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        } else {
                            Y.each(jsondata, function(comment) {
                                var quickcomment = new M.assignfeedback_editpdf.quickcomment(comment.id,
                                                                                             comment.rawtext,
                                                                                             comment.width,
                                                                                             comment.colour);
                                this.comments.push(quickcomment);
                            }, this);
                        }
                    } catch (e) {
                        return new M.core.exception(e);
                    }
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);
    };
};

M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};
M.assignfeedback_editpdf.quickcommentlist = QUICKCOMMENTLIST;
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
 * Provides an in browser PDF editor.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

// Globals.
var AJAXBASE = M.cfg.wwwroot + '/mod/assign/feedback/editpdf/ajax.php',
    CSS = {
        DIALOGUE : 'assignfeedback_editpdf_widget'
    },
    SELECTOR = {
        PREVIOUSBUTTON : '.' + CSS.DIALOGUE + ' .navigate-previous-button',
        NEXTBUTTON : '.' + CSS.DIALOGUE + ' .navigate-next-button',
        SEARCHCOMMENTSBUTTON : '.' + CSS.DIALOGUE + ' .searchcommentsbutton',
        SEARCHFILTER : '.assignfeedback_editpdf_searchcomments input',
        SEARCHCOMMENTSLIST : '.assignfeedback_editpdf_searchcomments ul',
        PAGESELECT : '.' + CSS.DIALOGUE + ' .navigate-page-select',
        LOADINGICON : '.' + CSS.DIALOGUE + ' .loading',
        DRAWINGREGION : '.' + CSS.DIALOGUE + ' .drawingregion',
        DRAWINGCANVAS : '.' + CSS.DIALOGUE + ' .drawingcanvas',
        SAVE : '.' + CSS.DIALOGUE + ' .savebutton',
        COMMENTCOLOURBUTTON : '.' + CSS.DIALOGUE + ' .commentcolourbutton',
        COMMENTMENU : ' .commentdrawable a',
        ANNOTATIONCOLOURBUTTON : '.' + CSS.DIALOGUE + ' .annotationcolourbutton',
        DELETEANNOTATIONBUTTON : '.' + CSS.DIALOGUE + ' .deleteannotationbutton',
        STAMPSBUTTON : '.' + CSS.DIALOGUE + ' .currentstampbutton',
        DIALOGUE : '.' + CSS.DIALOGUE
    },
    SELECTEDBORDERCOLOUR = 'rgba(200, 200, 255, 0.9)',
    SELECTEDFILLCOLOUR = 'rgba(200, 200, 255, 0.5)',
    COMMENTCOLOUR = {
        'white' : 'rgb(255,255,255)',
        'yellow' : 'rgb(255,255,176)',
        'red' : 'rgb(255,176,176)',
        'green' : 'rgb(176,255,176)',
        'blue' : 'rgb(208,208,255)',
        'clear' : 'rgba(255,255,255, 0)'
    },
    ANNOTATIONCOLOUR = {
        'white' : 'rgb(255,255,255)',
        'yellow' : 'rgb(255,255,0)',
        'red' : 'rgb(255,0,0)',
        'green' : 'rgb(0,255,0)',
        'blue' : 'rgb(0,0,255)',
        'black' : 'rgb(0,0,0)'
    },
    CLICKTIMEOUT = 300,
    TOOLSELECTOR = {
        'comment': '.' + CSS.DIALOGUE + ' .commentbutton',
        'pen': '.' + CSS.DIALOGUE + ' .penbutton',
        'line': '.' + CSS.DIALOGUE + ' .linebutton',
        'rectangle': '.' + CSS.DIALOGUE + ' .rectanglebutton',
        'oval': '.' + CSS.DIALOGUE + ' .ovalbutton',
        'stamp': '.' + CSS.DIALOGUE + ' .stampbutton',
        'select': '.' + CSS.DIALOGUE + ' .selectbutton',
        'highlight': '.' + CSS.DIALOGUE + ' .highlightbutton'
    },
    STROKEWEIGHT = 4;

/**
 * Stamp
 *
 * @namespace M.assignfeedback_editpdf.editor
 * @class Stamp
 */
Stamp = function() {
    /**
     * Stamp pluginfile url (without wwwroot)
     * @property type
     * @type String
     * @public
     */
    this.url = '';

    /**
     * Stamp width
     * @property type
     * @type Integer
     * @public
     */
    this.width = 0;

    /**
     * Stamp height
     * @property type
     * @type Integer
     * @public
     */
    this.height = 0;
};

/**
 * EDITOR
 * This is an in browser PDF editor.
 *
 * @namespace M.assignfeedback_editpdf.editor
 * @class Editor
 * @constructor
 * @extends Y.Base
 */
EDITOR = function() {
    EDITOR.superclass.constructor.apply(this, arguments);
};
EDITOR.prototype = {

    // Instance variables.
    /**
     * The dialogue used for all action menu displays.
     * @property type
     * @type M.core.dialogue
     * @protected
     */
    dialogue : null,

    /**
     * The number of pages in the pdf.
     * @property type
     * @type int
     * @protected
     */
    pagecount : 0,

    /**
     * The active page in the editor.
     * @property type
     * @type int
     * @protected
     */
    currentpage : 0,

    /**
     * A list of page objects. Each page has a list of comments and annotations.
     * @property type
     * @type array
     * @protected
     */
    pages : [],

    /**
     * The yui node for the loading icon.
     * @property type
     * @type Y.Node
     * @protected
     */
    loadingicon : null,

    /**
     * Image object of the current page image.
     * @property type
     * @type Image
     * @protected
     */
    pageimage : null,

    /**
     * YUI Graphic class for drawing shapes.
     * @property type
     * @type Y.Graphic
     * @protected
     */
    graphic : null,

    /**
     * Info about the current edit operation.
     * @property currentedit
     * @type M.assignfeedback_editpdf.edit
     * @protected
     */
    currentedit : new M.assignfeedback_editpdf.edit(),

    /**
     * Current drawable.
     * @property currentdrawable
     * @type M.assignfeedback_editpdf.drawable (or false)
     * @protected
     */
    currentdrawable : false,

    /**
     * Current drawables.
     * @property drawables
     * @type array(M.assignfeedback_editpdf.drawable)
     * @protected
     */
    drawables : [],

    /**
     * The comment menu dialogue.
     * @property commentmenu
     * @type M.core.dialogue
     * @protected
     */
    commentmenu : null,

    /**
     * Current comment when the comment menu is open.
     * @property currentcomment
     * @type Object
     * @protected
     */
    currentcomment : null,

    /**
     * Current annotation when the select tool is used.
     * @property currentannotation
     * @type Object
     * @protected
     */
    currentannotation : null,

    /**
     * The link that opened the current comment menu.
     * @property currentcommentmenulink
     * @type Y.Node
     * @protected
     */
    currentcommentmenulink : null,

    /**
     * The users comments quick list
     * @property quicklist
     * @type M.assignfeedback_editpdf.quickcommentlist
     * @protected
     */
    quicklist : null,

    /**
     * The search comments window.
     * @property searchcommentswindow
     * @type M.core.dialogue
     * @protected
     */
    searchcommentswindow : null,


    /**
     * The selected stamp picture.
     * @property currentstamp
     * @type String
     * @protected
     */
    currentstamp : null,

    /**
     * The stamps.
     * @property stamps
     * @type Array
     * @protected
     */
    stamps : [],

    /**
     * The current stamp node id.
     * @property currentstampnodeid
     * @type Integer
     * @protected
     */
    currentstampnodeid : null,

    /**
     * The current stamp picker.
     * @property currentstamppicker
     * @type M.core.dialogue
     * @protected
     */
    currentstamppicker: null,

    /**
     * Called during the initialisation process of the object.
     * @method initializer
     */
    initializer : function() {
        var link,
            deletelink;

        this.quicklist = new M.assignfeedback_editpdf.quickcommentlist(this);

        Y.log('Initialising M.assignfeedback_editpdf.editor');
        link = Y.one('#' + this.get('linkid'));

        link.on('click', this.link_handler, this);
        link.on('key', this.link_handler, 'down:13', this);

        Y.log(this.get('deletelinkid'));
        deletelink = Y.one('#' + this.get('deletelinkid'));
        deletelink.on('click', this.delete_link_handler, this);
        deletelink.on('key', this.delete_link_handler, 'down:13', this);

        this.currentedit.start = false;
        this.currentedit.end = false;
    },

    /**
     * Called to show/hide buttons and set the current colours/stamps.
     * @method refresh_button_state
     */
    refresh_button_state : function() {
        var button, currenttoolnode;
        // Initalise the colour buttons.
        button = Y.one(SELECTOR.COMMENTCOLOURBUTTON);
        button.setStyle('backgroundImage', 'none');
        button.one('img').setStyle('background', COMMENTCOLOUR[this.currentedit.commentcolour]);

        if (this.currentedit.commentcolour === 'clear') {
            button.one('img').setStyle('borderStyle', 'dashed');
        } else {
            button.one('img').setStyle('borderStyle', 'solid');
        }

        button = Y.one(SELECTOR.ANNOTATIONCOLOURBUTTON);
        button.setStyle('backgroundImage', 'none');
        button.one('img').setStyle('backgroundColor', ANNOTATIONCOLOUR[this.currentedit.annotationcolour]);

        currenttoolnode = Y.one(TOOLSELECTOR[this.currentedit.tool]);
        currenttoolnode.addClass('assignfeedback_editpdf_selectedbutton');
        currenttoolnode.setAttribute('aria-pressed', 'true');
    },

    /**
     * Called to open the pdf editing dialogue.
     * @method link_handler
     */
    link_handler : function(e) {
        var drawingcanvas, drawingregion;
        Y.log('Launch pdf editor');
        e.preventDefault();

        if (!this.dialogue) {
            this.dialogue = new M.core.dialogue({
                headerContent: this.get('header'),
                bodyContent: this.get('body'),
                footerContent: this.get('footer'),
                width: '840px',
                visible: true
            });

            this.dialogue.centerDialogue();
            // Add custom class for styling.
            this.dialogue.get('boundingBox').addClass(CSS.DIALOGUE);

            this.loadingicon = Y.one(SELECTOR.LOADINGICON);

            drawingcanvas = Y.one(SELECTOR.DRAWINGCANVAS);
            this.graphic = new Y.Graphic({render : SELECTOR.DRAWINGCANVAS});

            drawingcanvas.on('gesturemovestart', this.edit_start, null, this);
            drawingcanvas.on('gesturemove', this.edit_move, null, this);
            drawingcanvas.on('gesturemoveend', this.edit_end, null, this);

            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            drawingregion.delegate('click', this.open_comment_menu, SELECTOR.COMMENTMENU, this);
            drawingregion.delegate('key', this.open_comment_menu, 'down:13', SELECTOR.COMMENTMENU, this);

            this.refresh_button_state();
        } else {
            this.dialogue.show();
        }

        this.load_all_pages();
    },

    /**
     * Called to delete the last generated pdf.
     * @method link_handler
     */
    delete_link_handler : function(e) {
        var downloadlink,
            deletelink;

        Y.log('Delete generated pdf');
        e.preventDefault();

        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'deletefeedbackdocument',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function() {
                    downloadlink = Y.one('#' + this.get('downloadlinkid'));
                    deletelink = Y.one('#' + this.get('deletelinkid'));

                    downloadlink.addClass('hidden');
                    deletelink.addClass('hidden');
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);

    },

    /**
     * Called to load the information and annotations for all pages.
     * @method load_all_pages
     */
    load_all_pages : function() {
        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'get',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'loadallpages',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    this.all_pages_loaded(response.responseText);
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);
    },

    /**
     * The info about all pages in the pdf has been returned.
     * @param string The ajax response as text.
     * @protected
     * @method all_pages_loaded
     */
    all_pages_loaded : function(responsetext) {
        var data;

        try {
            data = Y.JSON.parse(responsetext);
        } catch (e) {
             this.dialogue.hide();
             return new M.core.exception(e);
        }

        this.pagecount = data.pagecount;
        this.pages = data.pages;

        // Update the ui.
        this.quicklist.load();
        this.setup_navigation();
        this.setup_toolbar();
        this.change_page();
        this.setup_save_cancel();
    },

    /**
     * Setup stamp picker
     * @protected
     * @method setup_stamp_picker
     * @param Y.Node button - The button to open the picker
     * @param stamps - List of stamps (from this.stamps)
     * @param {function} callback when a new stamp is chosen.
     */
    setup_stamp_picker : function(node, stamps, callback) {
        var stamplist = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>'),
            stamppicker,
            body,
            headertext,
            showhandler;

        Y.each(stamps, function(stamp, stampindex) {
            var button, listitem;

            button = Y.Node.create('<button/>');
            button.setAttribute('title', M.util.get_string('stamp', 'assignfeedback_editpdf', stampindex));
            button.setAttribute('stampindex', stampindex);
            button.setStyle('backgroundImage', 'url(\'' + stamp.url + '\')');
            button.setStyle('backgroundSize', '100% 100%');
            button.setStyle('backgroundRepeat', 'no-repeat');
            button.setStyle('height', '30px');
            button.setStyle('width', '40px');
            button.setStyle('borderStyle', 'solid');
            listitem = Y.Node.create('<li/>');
            listitem.append(button);
            stamplist.append(listitem);
        }, this);

        body = Y.Node.create('<div/>');

        stamplist.delegate('click', callback, 'button', this);
        stamplist.delegate('key', callback, 'down:13', 'button', this);
        headertext = Y.Node.create('<h3/>');
        headertext.addClass('accesshide');
        headertext.setHTML(M.util.get_string('stamppicker', 'assignfeedback_editpdf'));
        body.append(headertext);
        body.append(stamplist);

        stamppicker = new M.core.dialogue({
            extraClasses : ['assignfeedback_editpdf_colourpicker'],
            draggable: false,
            centered: false,
            width: 'auto',
            lightbox: false,
            visible: false,
            bodyContent: body,
            footerContent: '',
            align: {node: node, points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]}
        });

        body.on('clickoutside', function(e) {
            if (e.target !== node && e.target.ancestor() !== node) {
                e.preventDefault();
                stamppicker.hide();
            }
        });

        showhandler = function() {
            this.currentstamppicker = stamppicker;
            stamppicker.show();
        };
        node.on('click', showhandler, this);
        node.on('key', showhandler, 'down:13', this);

    },

    /**
     * Attach listeners and enable the color picker buttons.
     * @protected
     * @method setup_toolbar
     */
    setup_toolbar : function() {
        var toolnode,
            commentcolourbutton,
            annotationcolourbutton,
            searchcommentsbutton,
            i,
            stampurls,
            stamponload,
            picker;

        // Setup the tool buttons.
        Y.each(TOOLSELECTOR, function(selector, tool) {
            toolnode = Y.one(selector);
            toolnode.on('click', this.handle_toolbutton, this, tool);
            toolnode.on('key', this.handle_toolbutton, 'down:13', this, tool);
            toolnode.setAttribute('aria-pressed', 'false');
        }, this);

        // Set the default tool.
        searchcommentsbutton = Y.one(SELECTOR.SEARCHCOMMENTSBUTTON);
        searchcommentsbutton.on('click', this.open_search_comments, this);
        searchcommentsbutton.on('key', this.open_search_comments, 'down:13', this);


        commentcolourbutton = Y.one(SELECTOR.COMMENTCOLOURBUTTON);
        picker = new M.assignfeedback_editpdf.colourpicker({
            buttonNode: commentcolourbutton,
            colours: COMMENTCOLOUR,
            callback: function (e) {
                var colour = e.target.getAttribute('data-colour');
                if (!colour) {
                    colour = e.target.ancestor().getAttribute('data-colour');
                }
                this.currentedit.commentcolour = colour;
                this.refresh_button_state();
            },
            context: this
        });

        annotationcolourbutton = Y.one(SELECTOR.ANNOTATIONCOLOURBUTTON);
        picker = new M.assignfeedback_editpdf.colourpicker({
            buttonNode: annotationcolourbutton,
            colours: ANNOTATIONCOLOUR,
            callback: function (e) {
                var colour = e.target.getAttribute('data-colour');
                if (!colour) {
                    colour = e.target.ancestor().getAttribute('data-colour');
                }
                this.currentedit.annotationcolour = colour;
                this.refresh_button_state();
            },
            context: this
        });

        // Save all stamps into the stamps variable.
        stampurls = this.get('stampfileurls');
        Y.log(stampurls);
        stamponload = function() {
            this.rootcontext.stamps[this.stampindex].height = this.height;
            this.rootcontext.stamps[this.stampindex].width = this.width;
        };
        for (i = 0; i < stampurls.length; i++) {
            var stamp = new Stamp();
            stamp.url = M.cfg.wwwroot + stampfileurl;
            this.stamps[i] = stamp;
            // Find out the image height/width.
            var img = new Image();
            img.src = M.cfg.wwwroot + stampfileurl;
            img.stampindex = i;
            img.rootcontext = this;
            img.onload = stamponload;
        }

        // Setup the stamp picker
        stampsbutton = Y.one(SELECTOR.STAMPSBUTTON);
        if (stampurls.length <= 0) {
            stampsbutton.setAttribute('disabled', 'true');
        } else {
            this.currentstamp = 0;
            stampsbutton.setStyle('backgroundImage', 'url(\'' + this.stamps[this.currentstamp].url + '\')');
            stampsbutton.setStyle('backgroundSize', '100% 100%');
            stampsbutton.setStyle('backgroundRepeat', 'no-repeat');
            this.setup_stamp_picker(stampsbutton, this.stamps, function (e) {
                this.currentstamp = e.target.getAttribute('stampindex');
                button = Y.one(SELECTOR.STAMPSBUTTON);
                button.setStyle('backgroundImage', 'url(\'' + this.stamps[this.currentstamp].url + '\')');
                button.setStyle('backgroundSize', '100% 100%');
                button.setStyle('backgroundRepeat', 'no-repeat');
                this.currentstamppicker.hide();
            });
        }
    },

    /**
     * Change the current tool.
     * @protected
     * @method handle_toolbutton
     */
    handle_toolbutton : function(e, tool) {
        var currenttoolnode;

        e.preventDefault();

        // Change style of the pressed button.
        currenttoolnode = Y.one(TOOLSELECTOR[this.currentedit.tool]);
        currenttoolnode.removeClass('assignfeedback_editpdf_selectedbutton');
        currenttoolnode.setAttribute('aria-pressed', 'false');
        this.currentedit.tool = tool;
        this.refresh_button_state();
    },

    /**
     * Attach listeners and enable the save/cancel buttons.
     * @protected
     * @method setup_save_cancel
     */
    setup_save_cancel : function() {
        var save;

        save = Y.one(SELECTOR.SAVE);

        save.on('mousedown', this.handle_save, this);
        save.on('key', this.handle_save, 'down:13', this);
        save.removeAttribute('disabled');
    },

    /**
     * Hide the popup - but don't save anything anywhere.
     * @protected
     * @method handle_cancel
     */
    handle_cancel : function(e) {
        e.preventDefault();
        this.dialogue.hide();
    },

    /**
     * JSON encode the current page data - stripping out drawable references which cannot be encoded.
     * @protected
     * @method stringify_current_page
     * @return string
     */
    stringify_current_page : function() {
        var comments = [],
            annotations = [],
            page,
            i = 0;

        for (i = 0; i < this.pages[this.currentpage].comments.length; i++) {
            comments[i] = this.clean_comment_data(this.pages[this.currentpage].comments[i]);
        }
        for (i = 0; i < this.pages[this.currentpage].annotations.length; i++) {
            annotations[i] = this.clean_annotation_data(this.pages[this.currentpage].annotations[i]);
        }

        page = { comments : comments, annotations : annotations };

        return Y.JSON.stringify(page);
    },

    /**
     * Hide the popup - after generating a new pdf.
     * @protected
     * @method handle_save
     */
    handle_save : function(e) {
        e.preventDefault();

        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'generatepdf',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    var jsondata, downloadlink, deletelink, downloadfilename;
                    Y.log(response.responseText);
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        } else {

                            if (jsondata.url) {
                                // We got a valid response with a url and filename for the generated pdf.
                                downloadlink = Y.one('#' + this.get('downloadlinkid'));
                                downloadfilename = Y.one('#' + this.get('downloadlinkid') + ' span');
                                deletelink = Y.one('#' + this.get('deletelinkid'));

                                // Update the filename and show the download and delete links.
                                downloadfilename.setHTML(jsondata.filename);
                                downloadlink.setAttribute('href', jsondata.url);
                                downloadlink.removeClass('hidden');
                                deletelink.removeClass('hidden');

                            }
                            this.dialogue.hide();
                        }
                    } catch (e) {
                        return new M.core.exception(e);
                    }
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);
    },

    /**
     * Event handler for mousedown or touchstart.
     * @protected
     * @param Event
     * @method edit_start
     */
    edit_start : function(e) {
        var offset = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            scrolltop = document.body.scrollTop,
            scrollleft = document.body.scrollLeft,
            point = {x : e.clientX - offset[0] + scrollleft,
                     y : e.clientY - offset[1] + scrolltop};

        if (this.currentedit.starttime) {
            return;
        }

        this.currentedit.starttime = new Date().getTime();
        this.currentedit.start = point;
        this.currentedit.end = {x : point.x, y : point.y};

        if (this.currentannotation) {
            // Used to calculate drag offset.
            this.currentedit.annotationstart = { x : this.currentannotation.x,
                                                 y : this.currentannotation.y };
        }
        if (this.currentedit.tool === 'stamp') {
            this.redraw_current_edit();
        }
    },

    /**
     * Generate a drawable from the current in progress edit.
     * @protected
     * @method get_current_drawable
     */
    get_current_drawable : function() {
        var drawable = new M.assignfeedback_editpdf.drawable(this),
            shape, width, height, x, y, highlightcolour, first;

        if (!this.currentedit.start || !this.currentedit.end) {
            return false;
        }

        if (this.currentedit.tool === 'pen') {

            shape = this.graphic.addShape({
               type: Y.Path,
                fill: false,
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[this.currentedit.annotationcolour]
                }
            });

            // Redraw all the lines.
            first = true;
            Y.each(this.currentedit.path, function(position) {
                if (first) {
                    shape.moveTo(position.x, position.y);
                    first = false;
                } else {
                    shape.lineTo(position.x, position.y);
                }
            }, this);
            shape.end();
        }

        // Work out the boundary box.
        x = this.currentedit.start.x;
        if (this.currentedit.end.x > x) {
            width = this.currentedit.end.x - x;
        } else {
            x = this.currentedit.end.x;
            width = this.currentedit.start.x - x;
        }
        y = this.currentedit.start.y;
        if (this.currentedit.end.y > y) {
            height = this.currentedit.end.y - y;
        } else {
            y = this.currentedit.end.y;
            height = this.currentedit.start.y - y;
        }

        if (this.currentedit.tool === 'stamp') {
            // Delete previous stamp if it exists.

            // Redraw stamp.
            this.currentstampnodeid = (Math.random()*10000000000000000)+1;
            // We need to put the image as background otherwise the browser will try to drag the image.
            // We don't want to disable the on dragstart event.
            stampnode = Y.Node.create('<div id="'+this.currentstampnodeid+
                '" class="stamp" style="background-image:url(\'' + this.stamps[this.currentstamp].url + '\')"/>');
            Y.one('.drawingcanvas').append(stampnode);
            stampnode.setStyles({
                position: "absolute",
                left: this.currentedit.end.x,
                top: this.currentedit.end.y,
                height: this.stamps[this.currentstamp].height,
                width: this.stamps[this.currentstamp].width
            });

            drawable.nodes.push(stampnode);
            return drawable;
        }

        if (this.currentedit.tool === 'comment') {
            // We will draw a box with the current background colour.
            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                fill: {
                   color: COMMENTCOLOUR[this.currentedit.commentcolour]
                },
                x: x,
                y: y
            });
        }

        if (this.currentedit.tool === 'line') {
            shape = this.graphic.addShape({
               type: Y.Path,
                fill: false,
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[this.currentedit.annotationcolour]
                }
            });

            shape.moveTo(this.currentedit.start.x, this.currentedit.start.y);
            shape.lineTo(this.currentedit.end.x, this.currentedit.end.y);
            shape.end();
        }

        if (this.currentedit.tool === 'rectangle' || this.currentedit.tool === 'oval') {

            if (this.currentedit.tool === 'rectangle') {
                tooltype = Y.Rect;
            } if (this.currentedit.tool === 'oval') {
                tooltype = Y.Ellipse;
            }

            shape = this.graphic.addShape({
                type: tooltype,
                width: width,
                height: height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: ANNOTATIONCOLOUR[this.currentedit.annotationcolour]
                },
                x: x,
                y: y
            });
        }

        if (this.currentedit.tool === 'highlight') {
            highlightcolour = ANNOTATIONCOLOUR[this.currentedit.annotationcolour];

            // Add an alpha channel to the rgb colour.

            highlightcolour = highlightcolour.replace('rgb', 'rgba');
            highlightcolour = highlightcolour.replace(')', ',0.5)');

            Y.log(highlightcolour);
            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: 16,
                stroke: false,
                fill: {
                    color: highlightcolour
                },
                x: x,
                y: this.currentedit.start.y
            });
        }

        drawable.shapes.push(shape);

        return drawable;
    },

    /**
     * Redraw the active edit.
     * @protected
     * @method redraw_active_edit
     */
    redraw_current_edit : function() {
        if (this.currentdrawable) {
            this.currentdrawable.erase();
        }
        this.currentdrawable = this.get_current_drawable();
    },

    /**
     * Move an annotation to a new location.
     * @protected
     * @param Event
     * @method move_annotation
     */
    move_annotation : function(annotation, newx, newy) {
        var diffx = newx - annotation.x,
            diffy = newy - annotation.y,
            newpath, oldpath, xy;

        annotation.x += diffx;
        annotation.y += diffy;
        annotation.endx += diffx;
        annotation.endy += diffy;

        if (annotation.path) {
            newpath = [];
            oldpath = annotation.path.split(':');
            Y.each(oldpath, function(position) {
                xy = position.split(',');
                newpath.push((parseInt(xy[0], 10) + diffx) + ',' + (parseInt(xy[1], 10) + diffy));
            });

            annotation.path = newpath.join(':');

        }
        annotation.drawable.erase();
        this.drawables.push(this.draw_annotation(annotation));
    },

    /**
     * Event handler for mousemove.
     * @protected
     * @param Event
     * @method edit_move
     */
    edit_move : function(e) {
        var canvas = Y.one(SELECTOR.DRAWINGCANVAS),
            width = parseInt(canvas.getStyle('width'), 10),
            height = parseInt(canvas.getStyle('height'), 10),
            offset = canvas.getXY(),
            scrolltop = document.body.scrollTop,
            scrollleft = document.body.scrollLeft,
            point = {x : e.clientX - offset[0] + scrollleft,
                     y : e.clientY - offset[1] + scrolltop};

        // Ignore events out of the canvas area.
        if (point.x < 0 || point.x > width || point.y < 0 || point.y > height) {
            return;
        }

        if (this.currentedit.tool === 'pen') {
            this.currentedit.path.push(point);
        }

        if (this.currentedit.tool === 'select') {
            if (this.currentannotation && this.currentedit) {
                this.move_annotation(this.currentannotation,
                                     this.currentedit.annotationstart.x + point.x - this.currentedit.start.x,
                                     this.currentedit.annotationstart.y + point.y - this.currentedit.start.y);
            }
        } else {
            if (this.currentedit.start) {
                this.currentedit.end = point;
                this.redraw_current_edit();
            }
        }
    },

    /**
     * Clean a comment record, returning an oject with only fields that are valid.
     * @protected
     * @method clean_comment_data
     * @param comment
     * @return string
     */
    clean_comment_data : function(comment) {
        return {
            gradeid : comment.gradeid,
            x : comment.x,
            y : comment.y,
            width : comment.width,
            rawtext : comment.rawtext,
            pageno : comment.currentpage,
            colour : comment.colour
        };
    },

    /**
     * Clean a annotation record, returning an oject with only fields that are valid.
     * @protected
     * @method clean_annotation_data
     * @param annotation
     * @return string
     */
    clean_annotation_data : function(annotation) {
        return {
            gradeid : annotation.gradeid,
            x : annotation.x,
            y : annotation.y,
            endx : annotation.endx,
            endy : annotation.endy,
            type : annotation.type,
            path : annotation.path,
            pageno : annotation.pageno,
            colour : annotation.colour
        };
    },

    /**
     * Event handler for mouseup or touchend.
     * @protected
     * @param Event
     * @method edit_end
     */
    edit_end : function() {
        var data,
            width,
            height,
            x,
            y,
            duration,
            thepath,
            selected = false;

        duration = new Date().getTime() - this.currentedit.start;

        if (duration < CLICKTIMEOUT || this.currentedit.start === false) {
            return;
        }

        if (this.currentedit.tool === 'comment') {
            // Work out the boundary box.
            x = this.currentedit.start.x;
            if (this.currentedit.end.x > x) {
                width = this.currentedit.end.x - x;
            } else {
                x = this.currentedit.end.x;
                width = this.currentedit.start.x - x;
            }
            y = this.currentedit.start.y;
            if (this.currentedit.end.y > y) {
                height = this.currentedit.end.y - y;
            } else {
                y = this.currentedit.end.y;
                height = this.currentedit.start.y - y;
            }
            if (width < 100) {
                width = 100;
            }

            // Save the current edit to the server and the current page list.

            data = {
                gradeid : this.get('gradeid'),
                x : x,
                y : y,
                width : width,
                rawtext : '',
                pageno : this.currentpage,
                colour : this.currentedit.commentcolour
            };

            this.pages[this.currentpage].comments.push(data);
            this.drawables.push(this.draw_comment(data, true));
        } else if (this.currentedit.tool === 'pen') {
            // Create the path string.
            thepath = '';
            var minx = null,
                miny = null,
                maxx = null,
                maxy = null;
            Y.each(this.currentedit.path, function(position) {
                thepath = thepath + position.x + "," + position.y + ":";
                if (minx === null) {
                    minx = maxx = position.x;
                    miny = maxy = position.y;
                } else {
                    if (position.x < minx) {
                        minx = position.x;
                    }
                    if (position.y < miny) {
                        miny = position.y;
                    }
                    if (position.x > maxx) {
                        maxx = position.x;
                    }
                    if (position.y > maxy) {
                        maxy = position.y;
                    }
                }
            }, this);
            // Remove the last ":".
            thepath = thepath.substring(0, thepath.length - 1);

            data = {
                gradeid : this.get('gradeid'),
                path : thepath,
                type : 'pen',
                pageno : this.currentpage,
                colour : this.currentedit.annotationcolour,
                x : minx,
                y : miny,
                endx : maxx,
                endy : maxy
            };

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(this.draw_annotation(data));

            // Reset the mouse position for the pen tool.
            this.currentedit.path = [];
        } else if (this.currentedit.tool === 'highlight') {
            // Work out the boundary box.
            x = this.currentedit.start.x;
            if (this.currentedit.end.x > x) {
                width = this.currentedit.end.x - x;
            } else {
                x = this.currentedit.end.x;
                width = this.currentedit.start.x - x;
            }
            y = this.currentedit.start.y;
            height = 16;

            data = {
                    gradeid : this.get('gradeid'),
                    x : x,
                    y : y,
                    endx : x + width,
                    endy : y + height,
                    type : this.currentedit.tool,
                    pageno : this.currentpage,
                    colour : this.currentedit.annotationcolour
                };

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(this.draw_annotation(data));
        } else if (this.currentedit.tool === 'select') {
            x = this.currentedit.end.x;
            y = this.currentedit.end.y;
            annotations = this.pages[this.currentpage].annotations;
            Y.each(annotations, function(annotation) {
                if (((x - annotation.x) * (x - annotation.endx)) <= 0 &&
                    ((y - annotation.y) * (y - annotation.endy)) <= 0) {
                    selected = annotation;
                }
            });

            if (selected) {
                this.currentannotation = selected;
            }
            this.redraw();
        } else if (this.currentedit.tool === 'stamp') {
            // In path we will save the file name.
            data = {
                gradeid : this.get('gradeid'),
                x : this.currentedit.start.x,
                y : this.currentedit.start.y,
                endx : this.currentedit.end.x,
                endy : this.currentedit.end.y,
                type : this.currentedit.tool,
                pageno : this.currentpage,
                colour : this.currentedit.annotationcolour,
                path : this.stamps[this.currentstamp].url.replace(/^.*[\\\/]/, '')
            };

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(this.draw_annotation(data));
        } else {
            data = {
                gradeid : this.get('gradeid'),
                x : this.currentedit.start.x,
                y : this.currentedit.start.y,
                endx : this.currentedit.end.x,
                endy : this.currentedit.end.y,
                type : this.currentedit.tool,
                pageno : this.currentpage,
                colour : this.currentedit.annotationcolour
            };

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(this.draw_annotation(data));
        }

        this.save_current_page();

        this.currentedit.starttime = 0;
        this.currentedit.start = false;
        this.currentedit.end = false;
        if (this.currentdrawable) {
            this.currentdrawable.erase();
        }
        this.currentdrawable = false;
    },

    /**
     * Save all the annotations and comments for the current page.
     * @protected
     * @method save_current_page
     */
    save_current_page : function() {
        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'savepage',
                'index' : this.currentpage,
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid'),
                'page' : this.stringify_current_page()
            },
            on: {
                success: function(tid, response) {
                    var jsondata;
                    Y.log(response.responseText);
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        }
                    } catch (e) {
                        return new M.core.exception(e);
                    }
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);

    },

    /**
     * Handle a delete annotation event (click on the button)
     * @protected
     * @method delete_annotation
     * @param event
     */
    delete_annotation : function(e) {
        var target = e.target,
            annotation = target.getData('annotation'),
            annotations;

        if (!annotation) {
            target = target.ancestor();
            annotation = target.getData('annotation');
        }

        annotations = this.pages[this.currentpage].annotations;
        for (i = 0; i < annotations.length; i++) {
            if (annotations[i] === annotation) {
                annotations.splice(i, 1);
                annotation.drawable.erase();
                this.save_current_page();
                return;
            }
        }
    },

    /**
     * Draw an annotation
     * @protected
     * @method draw_annotation
     * @param annotation
     * @return M.assignfeedback_editpdf.drawable
     */
    draw_annotation : function(annotation) {
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

        drawable = new M.assignfeedback_editpdf.drawable(this);

        if (annotation.type === 'stamp') {
            // Find the matching stamp
            Y.each(this.stamps, function(stamp) {
                if (annotation.path === stamp.url.replace(/^.*[\\\/]/, '')) {
                    // Redraw stamp.
                    this.currentstampnodeid = (Math.random()*10000000000000000)+1;
                    // We need to put the image as background otherwise the browser will try to drag the image.
                    // Also we don't want to disable the image drag event (dragstart event), so we use background image.
                    stampnode = Y.Node.create('<div id="'+this.currentstampnodeid+
                        '" class="stamp" style="background-image:url(\'' + stamp.url + '\')"/>');
                    Y.one('.drawingcanvas').append(stampnode);
                    stampnode.setStyles({
                        position: "absolute",
                        left: annotation.endx,
                        top: annotation.endy,
                        height: stamp.height,
                        width: stamp.width
                    });

                    // Resize the stamp to the correct heigth/width.
                    var img = new Image();
                    img.src = stamp.url;
                    img.stampnode = stampnode;
                    img.onload = function() {
                        this.stampnode.setStyles({
                            height: this.height,
                            width: this.width
                        });
                    };

                    drawable.nodes.push(stampnode);
                }
            }, this);
            return drawable;

        }

        if (annotation.type === 'line') {
            shape = this.graphic.addShape({
                type: Y.Path,
                fill: false,
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[annotation.colour]
                }
            });

            shape.moveTo(annotation.x, annotation.y);
            shape.lineTo(annotation.endx, annotation.endy);
            shape.end();
        }

        if (annotation.type === 'pen') {
            shape = this.graphic.addShape({
               type: Y.Path,
                fill: false,
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[annotation.colour]
                }
            });

            first = true;
            // Recreate the pen path array.
            positions = annotation.path.split(':');
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

        if (annotation.type === 'rectangle' || annotation.type === 'oval' ) {
            if (annotation.type === 'rectangle') {
                annotationtype = Y.Rect;
            } if (annotation.type === 'oval') {
                annotationtype = Y.Ellipse;
            }

            // Convert data to integer to avoid wrong > or < results.
            annotation.x = parseInt(annotation.x, 10);
            annotation.y = parseInt(annotation.y, 10);
            annotation.endx = parseInt(annotation.endx, 10);
            annotation.endy = parseInt(annotation.endy, 10);

            // Work out the boundary box.
            topleftx = annotation.x;
            if (annotation.endx > topleftx) {
                width = annotation.endx - topleftx;
            } else {
                topleftx = annotation.endx;
                width = annotation.x - topleftx;
            }
            toplefty = annotation.y;

            if (annotation.endy > toplefty) {
                height = annotation.endy - toplefty;
            } else {
                toplefty = annotation.endy;
                height = annotation.y - toplefty;
            }

            shape = this.graphic.addShape({
                type: annotationtype,
                width: width,
                height: height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: ANNOTATIONCOLOUR[annotation.colour]
                },
                x: topleftx,
                y: toplefty
            });
        }
        if (annotation.type === 'highlight' ) {
            // Convert data to integer to avoid wrong > or < results.
            annotation.x = parseInt(annotation.x, 10);
            annotation.y = parseInt(annotation.y, 10);
            annotation.endx = parseInt(annotation.endx, 10);
            annotation.endy = parseInt(annotation.endy, 10);

            // Work out the boundary box.
            topleftx = annotation.x;
            if (annotation.endx > topleftx) {
                width = annotation.endx - topleftx;
            } else {
                topleftx = annotation.endx;
                width = annotation.x - topleftx;
            }
            toplefty = annotation.y;

            if (annotation.endy > toplefty) {
                height = annotation.endy - toplefty;
            } else {
                toplefty = annotation.endy;
                height = annotation.y - toplefty;
            }

            highlightcolour = ANNOTATIONCOLOUR[annotation.colour];

            // Add an alpha channel to the rgb colour.

            highlightcolour = highlightcolour.replace('rgb', 'rgba');
            highlightcolour = highlightcolour.replace(')', ',0.5)');

            shape = this.graphic.addShape({
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

        if (!shape) {
            return drawable;
        }

        drawable.shapes.push(shape);
        if (this.currentannotation === annotation) {
            // Draw a highlight around the annotation.
            annotation.x = parseInt(annotation.x, 10);
            annotation.y = parseInt(annotation.y, 10);
            annotation.endx = parseInt(annotation.endx, 10);
            annotation.endy = parseInt(annotation.endy, 10);

            // Work out the boundary box.
            topleftx = annotation.x;
            if (annotation.endx > topleftx) {
                width = annotation.endx - topleftx;
            } else {
                topleftx = annotation.endx;
                width = annotation.x - topleftx;
            }
            toplefty = annotation.y;

            if (annotation.endy > toplefty) {
                height = annotation.endy - toplefty;
            } else {
                toplefty = annotation.endy;
                height = annotation.y - toplefty;
            }

            shape = this.graphic.addShape({
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
            deletelink.setData('annotation', annotation);
            deletelink.setStyle('zIndex', '1000');

            deletelink.on('click', this.delete_annotation, this);
            deletelink.on('key', this.delete_annotation, 'space,enter', this);

            deletelink.setX(offsetcanvas[0] + topleftx + width - 20);
            deletelink.setY(offsetcanvas[1] + toplefty + 2);
            drawable.nodes.push(deletelink);
        }
        annotation.drawable = drawable;

        return drawable;
    },

    /**
     * Delete a comment from the current page.
     * @protected
     * @method delete_comment
     * @param comment
     */
    delete_comment : function(comment) {
        var i = 0, comments;

        comments = this.pages[this.currentpage].comments;
        for (i = 0; i < comments.length; i++) {
            if (comments[i] === comment) {
                comments.splice(i, 1);
                comment.drawable.erase();
                this.save_current_page();
                return;
            }
        }
    },

    /**
     * Draw a comment.
     * @protected
     * @method draw_comment
     * @param comment
     * @param boolean focus - Set the keyboard focus to the new comment if true
     * @return M.assignfeedback_editpdf.drawable
     */
    draw_comment : function(comment, focus) {
        var drawable = new M.assignfeedback_editpdf.drawable(this),
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
        menu = Y.Node.create('<a href="#"><img src="' + this.get('menuicon') + '"/></a>');
        container.append(node);
        container.append(menu);
        if (comment.width < 100) {
            comment.width = 100;
        }
        container.setStyles({
            position: 'absolute',
            left: (parseInt(comment.x, 10) + offsetleft) + 'px',
            top: (parseInt(comment.y, 10) + offsettop) + 'px'
        });
        node.setStyles({
            width: comment.width + 'px',
            backgroundColor: COMMENTCOLOUR[comment.colour]
        });

        drawingregion.append(container);
        drawable.nodes.push(container);
        node.set('value', comment.rawtext);
        node.setStyle('height', node.get('scrollHeight') - 8 + 'px');
        this.attach_comment_events(comment, node, menu);
        if (focus) {
            node.focus();
        }
        comment.drawable = drawable;

        return drawable;
    },

    /**
     * Event handler to add a comment to the users quicklist.
     *
     * @protected
     * @method add_to_quicklist
     */
    add_to_quicklist : function() {
        this.commentmenu.hide();
        this.quicklist.add(this.currentcomment);
    },

    /**
     * Event handler to remove a comment from the users quicklist.
     *
     * @protected
     * @method remove_from_quicklist
     */
    remove_from_quicklist : function(e) {
        var target = e.target,
            comment = target.getData('comment');

        this.commentmenu.hide();

        if (!comment) {
            target = target.ancestor();
            comment = target.getData('comment');
        }

        // Should not happen.
        if (!comment) {
            return;
        }

        this.quicklist.remove(comment);
    },

    /**
     * A quick comment was selected in the list, update the active comment and redraw the page.
     *
     * @param Event e
     * @protected
     * @method set_comment_from_quick_comment
     */
    set_comment_from_quick_comment : function(e) {
        var target = e.target,
            comment = target.getData('comment');

        this.commentmenu.hide();

        if (!comment) {
            target = target.ancestor();
            comment = target.getData('comment');
        }

        // Should not happen.
        if (!comment || !this.currentcomment) {
            return;
        }
        this.currentcomment.rawtext = comment.rawtext;
        this.currentcomment.width = comment.width;
        this.currentcomment.colour = comment.colour;

        this.save_current_page();

        this.redraw();
    },

    /**
     * Event handler to filter the list of comments.
     *
     * @protected
     * @method filter_search_comments
     */
    filter_search_comments : function() {
        var filternode,
            commentslist,
            filtertext;

        filternode = Y.one(SELECTOR.SEARCHFILTER);
        commentslist = Y.one(SELECTOR.SEARCHCOMMENTSLIST);

        filtertext = filternode.get('value');

        commentslist.all('li').each(function (node) {
            if (node.get('text').indexOf(filtertext) !== -1) {
                node.show();
            } else {
                node.hide();
            }
        });


    },

    /**
     * Event handler to focus on a selected comment.
     *
     * @param Event e
     * @protected
     * @method focus_on_comment
     */
    focus_on_comment : function(e) {
        var target = e.target.ancestor('li'),
            comment = target.getData('comment');

        this.searchcommentswindow.hide();

        if (comment.pageno === this.currentpage) {
            comment.drawable.nodes[0].one('textarea').focus();
        } else {
            // Comment is on a different page.
            this.currentpage = comment.pageno;
            this.change_page();
            comment.drawable.nodes[0].one('textarea').focus();
        }
    },

    /**
     * Event handler to open the comment search interface.
     *
     * @param Event e
     * @protected
     * @method open_search_comments
     */
    open_search_comments : function(e) {
        var commentlist, commentfilter, container, placeholder;

        if (!this.searchcommentswindow) {
            container = Y.Node.create('<div/>');

            placeholder = M.util.get_string('filter', 'assignfeedback_editpdf');
            commentfilter = Y.Node.create('<input type="text" size="20" placeholder="' + placeholder + '"/>');
            container.append(commentfilter);
            commentlist = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>');
            container.append(commentlist);

            this.searchcommentswindow = new M.core.dialogue({
                extraClasses : ['assignfeedback_editpdf_searchcomments'],
                draggable: false,
                centered: true,
                lightbox: true,
                width: '400px',
                visible: false,
                zIndex: 100,
                headerContent: M.util.get_string('searchcomments', 'assignfeedback_editpdf'),
                bodyContent: container,
                footerContent: ''
            });

            commentfilter.on('keyup', this.filter_search_comments, null, this);

            commentlist.delegate('click', this.focus_on_comment, 'a', this);
            commentlist.delegate('key', this.focus_on_comment, 'enter,space', 'a', this);
        } else {
            commentlist = this.searchcommentswindow.get('boundingBox').one('ul');
            commentlist.all('li').remove(true);
        }

        // Rebuild the latest list of comments.
        Y.each(this.pages, function(page) {
            Y.each(page.comments, function(comment) {
                var commentnode = Y.Node.create('<li><a href="#" tabindex="-1"><pre>' + comment.rawtext + '</pre></a></li>');
                commentlist.append(commentnode);
                commentnode.setData('comment', comment);
            }, this);
        }, this);

        this.searchcommentswindow.centerDialogue();
        this.searchcommentswindow.show();
        e.preventDefault();
    },

    /**
     * Event handler to open the quicklist/delete menu for a comment.
     *
     * @param Event e
     * @protected
     * @method open_comment_menu
     */
    open_comment_menu : function(e) {
        var target = e.target,
            comment = e.target.getData('comment'),
            commentlinks,
            link;

        // Cancel deleting of empty comment.
        this.commenttodelete = null;

        if (!comment) {
            // The event triggered on the img tag, not the a.
            target = target.ancestor();
            comment = target.getData('comment');
        }

        this.currentcomment = comment;
        this.currentcommentmenulink = target;

        // Build the comment menu only the first time.
        if (!this.commentmenu) {
            // Build the list of comments.
            commentlinks = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>');

            link = Y.Node.create('<li><a tabindex="-1" href="#">' + M.util.get_string('addtoquicklist', 'assignfeedback_editpdf') + '</a></li>');
            link.on('click', this.add_to_quicklist, this);
            link.on('key', this.add_to_quicklist, 'enter,space', this);

            commentlinks.append(link);

            link = Y.Node.create('<li><a tabindex="-1" href="#">' + M.util.get_string('deletecomment', 'assignfeedback_editpdf') + '</a></li>');
            link.on('click', function() { this.commentmenu.hide(); this.delete_comment(this.currentcomment); }, this);
            link.on('key', function() { this.commentmenu.hide(); this.delete_comment(this.currentcomment); }, 'enter,space', this);

            commentlinks.append(link);

            link = Y.Node.create('<li><hr/></li>');
            commentlinks.append(link);

            this.commentmenu = new M.core.dialogue({
                extraClasses : ['assignfeedback_editpdf_commentmenu'],
                draggable: false,
                centered: false,
                lightbox: false,
                width: 'auto',
                visible: false,
                zIndex: 100,
                bodyContent: commentlinks,
                footerContent: '',
                align: {node: target, points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]}
            });
            // Close the menu on click outside.
            commentlinks.on('clickoutside', function(e) {
                if (e.target !== this.currentcommentmenulink && e.target.ancestor() !== this.currentcommentmenulink) {
                    e.preventDefault();
                    this.commentmenu.hide();
                }
            }, this);
        } else {
            this.commentmenu.align( target, [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]);
            commentlinks = this.commentmenu.get('boundingBox').one('ul');
            commentlinks.all('.quicklist_comment').remove(true);
        }

        // Now build the list of quicklist comments.
        Y.each(this.quicklist.comments, function(comment) {
            var listitem = Y.Node.create('<li class="quicklist_comment"></li>'),
                linkitem = Y.Node.create('<a href="#" tabindex="-1">' + comment.rawtext + '</a>'),
                deletelinkitem = Y.Node.create('<a href="#" tabindex="-1" class="delete_quicklist_comment">' +
                                               '<img src="' + M.util.image_url('t/delete', 'core') + '" ' +
                                               'alt="' + M.util.get_string('deletecomment', 'assignfeedback_editpdf') + '"/>' +
                                               '</a>');
            listitem.append(linkitem);
            listitem.append(deletelinkitem);
            listitem.setData('comment', comment);

            commentlinks.append(listitem);

            linkitem.on('click', this.set_comment_from_quick_comment, this);
            linkitem.on('key', this.set_comment_from_quick_comment, 'space,enter', this);

            deletelinkitem.setData('comment', comment);

            deletelinkitem.on('click', this.remove_from_quicklist, this);
            deletelinkitem.on('key', this.remove_from_quicklist, 'space,enter', this);
        }, this);

        this.commentmenu.show();
    },

    /**
     * Delete an empty comment if it's menu hasn't been opened in time.
     * @method delete_comment_later
     */
    delete_comment_later : function() {
        if (this.commenttodelete !== null) {
            this.delete_comment(this.commenttodelete);
        }
    },

    /**
     * Comment nodes have a bunch of event handlers attached to them directly.
     * This is all done here for neatness.
     *
     * @protected
     * @method attach_comment_events
     * @param comment - The comment structure
     * @param node - The Y.Node representing the comment.
     */
    attach_comment_events : function(comment, node, menu) {
        // Save the text on blur.
        node.on('blur', function() {
            // Save the changes back to the comment.
            comment.rawtext = node.get('value');
            comment.width = parseInt(node.getStyle('width'), 10);

            // Trim.
            if (comment.rawtext.replace(/^\s+|\s+$/g, "") === '') {
                // Delete empty comments.
                this.commenttodelete = comment;
                Y.later(400, this, this.delete_comment_later);
            }
            this.save_current_page();
        }, this);

        // For delegated event handler.
        menu.setData('comment', comment);

        node.on('keyup', function() {
            var scrollHeight = node.get('scrollHeight') - 8;
            this.setStyle('height', scrollHeight + 'px');
        });

        node.on('gesturemovestart', function(e) {
            Y.log('gesturemovestart (comment)');
            node.setData('dragging', true);
            node.setData('offsetx', e.clientX - node.getX());
            node.setData('offsety', e.clientY - node.getY());
        });
        node.on('gesturemoveend', function() {
            Y.log('gesturemoveend (comment)');
            node.setData('dragging', false);
            this.save_current_page();
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

            Y.log('gesturemove (comment)');
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

            comment.x = x - offsetleft;
            comment.y = y - offsettop;

            node.ancestor().setX(x);
            node.ancestor().setY(y);
        });
    },

    /**
     * Redraw all the comments and annotations.
     * @protected
     * @method redraw
     */
    redraw : function() {
        var i,
            page;

        page = this.pages[this.currentpage];
        while (this.drawables.length > 0) {
            this.drawables.pop().erase();
        }

        for (i = 0; i < page.annotations.length; i++) {
            this.drawables.push(this.draw_annotation(page.annotations[i]));
        }
        for (i = 0; i < page.comments.length; i++) {
            this.drawables.push(this.draw_comment(page.comments[i], false));
        }
    },

    /**
     * Load the image for this pdf page and remove the loading icon (if there).
     * @protected
     * @method change_page
     */
    change_page : function() {
        var drawingcanvas = Y.one(SELECTOR.DRAWINGCANVAS),
            page,
            previousbutton,
            nextbutton;

        previousbutton = Y.one(SELECTOR.PREVIOUSBUTTON);
        nextbutton = Y.one(SELECTOR.NEXTBUTTON);

        if (this.currentpage > 0) {
            previousbutton.removeAttribute('disabled');
        } else {
            previousbutton.setAttribute('disabled', 'true');
        }
        if (this.currentpage < (this.pagecount - 1)) {
            nextbutton.removeAttribute('disabled');
        } else {
            nextbutton.setAttribute('disabled', 'true');
        }

        page = this.pages[this.currentpage];
        this.loadingicon.hide();
        drawingcanvas.setStyle('backgroundImage', 'url("' + page.url + '")');

        this.redraw();
    },

    /**
     * Now we know how many pages there are,
     * we can enable the navigation controls.
     * @protected
     * @method setup_navigation
     */
    setup_navigation : function() {
        var pageselect,
            i,
            option,
            previousbutton,
            nextbutton;

        pageselect = Y.one(SELECTOR.PAGESELECT);

        options = pageselect.all('option');
        if (options.size() <= 1) {
            for (i = 0; i < this.pages.length; i++) {
                option = Y.Node.create('<option/>');
                option.setAttribute('value', i);
                option.setHTML((i+1));
                pageselect.append(option);
            }
        }
        pageselect.removeAttribute('disabled');
        pageselect.on('change', function() {
            this.currentpage = pageselect.get('value');
            this.change_page();
        }, this);

        previousbutton = Y.one(SELECTOR.PREVIOUSBUTTON);
        nextbutton = Y.one(SELECTOR.NEXTBUTTON);

        previousbutton.on('click', this.previous_page, this);
        previousbutton.on('key', this.previous_page, 'down:13', this);
        nextbutton.on('click', this.next_page, this);
        nextbutton.on('key', this.next_page, 'down:13', this);
    },

    /**
     * Navigate to the previous page.
     * @protected
     * @method previous_page
     */
    previous_page : function() {
        this.currentpage--;
        if (this.currentpage < 0) {
            this.currentpage = 0;
        }
        this.change_page();
    },

    /**
     * Navigate to the next page.
     * @protected
     * @method next_page
     */
    next_page : function() {
        this.currentpage++;
        if (this.currentpage >= this.pages.length) {
            this.currentpage = this.pages.length - 1;
        }
        this.change_page();
    }



};

Y.extend(EDITOR, Y.Base, EDITOR.prototype, {
    NAME : 'moodle-assignfeedback_editpdf-editor',
    ATTRS : {
        userid : {
            validator : Y.Lang.isInteger,
            value : 0
        },
        assignmentid : {
            validator : Y.Lang.isInteger,
            value : 0
        },
        attemptnumber : {
            validator : Y.Lang.isInteger,
            value : 0
        },
        header : {
            validator : Y.Lang.isString,
            value : ''
        },
        body : {
            validator : Y.Lang.isString,
            value : ''
        },
        footer : {
            validator : Y.Lang.isString,
            value : ''
        },
        linkid : {
            validator : Y.Lang.isString,
            value : ''
        },
        deletelinkid : {
            validator : Y.Lang.isString,
            value : ''
        },
        downloadlinkid : {
            validator : Y.Lang.isString,
            value : ''
        },
        menuicon : {
            validator : Y.Lang.isString,
            value : ''
        },
        stampfileurls : {
            validator : Y.Lang.isArray,
            value : ''
        }
    }
});

/**
 * Assignfeedback edit pdf namespace.
 * @static
 * @class assignfeedback_editpdf
 */
M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};

/**
 * Editor namespace
 * @namespace M.assignfeedback_editpdf.editor
 * @class editor
 * @static
 */
M.assignfeedback_editpdf.editor = M.assignfeedback_editpdf.editor || {};

/**
 * Init function - will create a new instance every time.
 * @method init
 * @static
 * @param {Object} params
 */
M.assignfeedback_editpdf.editor.init = M.assignfeedback_editpdf.editor.init || function(params) {
    return new EDITOR(params);
};


}, '@VERSION@', {
    "requires": [
        "base",
        "event",
        "node",
        "io",
        "graphics",
        "json",
        "event-move",
        "querystring-stringify-simple",
        "moodle-core-notification-dialog",
        "moodle-core-notification-exception",
        "moodle-core-notification-ajaxexception"
    ]
});
