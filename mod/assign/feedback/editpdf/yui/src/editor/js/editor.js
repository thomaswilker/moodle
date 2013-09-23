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
        var data, i, j, comment;

        try {
            data = Y.JSON.parse(responsetext);
        } catch (e) {
             this.dialogue.hide();
             return new M.core.exception(e);
        }

        this.pagecount = data.pagecount;
        this.pages = data.pages;

        for (i = 0; i < this.pages.length; i++) {
            for (j = 0; j < this.pages[i].comments.length; j++) {
                comment = this.pages[i].comments[j];
                this.pages[i].comments[j] = new M.assignfeedback_editpdf.comment(this,
                                                                                 comment.gradeid,
                                                                                 comment.pageno,
                                                                                 comment.x,
                                                                                 comment.y,
                                                                                 comment.width,
                                                                                 comment.colour,
                                                                                 comment.rawtext);
            }
            for (j = 0; j < this.pages[i].annotations.length; j++) {
                annotation = this.pages[i].annotations[j];
                this.pages[i].annotations[j] = new M.assignfeedback_editpdf.annotation(this,
                                                                                 annotation.gradeid,
                                                                                 annotation.pageno,
                                                                                 annotation.x,
                                                                                 annotation.y,
                                                                                 annotation.endx,
                                                                                 annotation.endy,
                                                                                 annotation.type,
                                                                                 annotation.colour,
                                                                                 annotation.path);
            }
        }

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
            comments[i] = this.pages[this.currentpage].comments[i].clean();
        }
        for (i = 0; i < this.pages[this.currentpage].annotations.length; i++) {
            annotations[i] = this.pages[this.currentpage].annotations[i].clean();
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
        this.drawables.push(annotation.draw());
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

            data = new M.assignfeedback_editpdf.comment(this,
                this.get('gradeid'),
                this.currentpage,
                x,
                y,
                width,
                this.currentedit.commentcolour,
                ''
            );

            this.pages[this.currentpage].comments.push(data);
            this.drawables.push(data.draw(true));
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

            data = new M.assignfeedback_editpdf.annotation(
                this,
                this.get('gradeid'),
                this.currentpage,
                minx,
                miny,
                maxx,
                maxy,
                this.currentedit.tool,
                this.currentedit.annotationcolour,
                this.currentedit.path
            );

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(data.draw());

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

            data = new M.assignfeedback_editpdf.annotation(
                this,
                this.get('gradeid'),
                this.currentpage,
                this.currentedit.start.x,
                this.currentedit.start.y,
                this.currentedit.end.x,
                this.currentedit.end.y,
                this.currentedit.tool,
                this.currentedit.annotationcolour,
                this.currentedit.path
            );

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(data.draw());
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
        } else {
            data = new M.assignfeedback_editpdf.annotation(
                this,
                this.get('gradeid'),
                this.currentpage,
                this.currentedit.start.x,
                this.currentedit.start.y,
                this.currentedit.end.x,
                this.currentedit.end.y,
                this.currentedit.tool,
                this.currentedit.annotationcolour,
                this.currentedit.path
            );

            this.pages[this.currentpage].annotations.push(data);
            this.drawables.push(data.draw());
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
        comment.deleteme = false;
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
            link.on('click', function() { this.commentmenu.hide(); this.currentcomment.remove(); }, this);
            link.on('key', function() { this.commentmenu.hide(); this.currentcomment.remove(); }, 'enter,space', this);

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
            this.drawables.push(page.annotations[i].draw());
        }
        for (i = 0; i < page.comments.length; i++) {
            this.drawables.push(page.comments[i].draw(false));
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
