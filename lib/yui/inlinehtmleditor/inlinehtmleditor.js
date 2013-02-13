YUI.add('moodle-core-inlinehtmleditor', function(Y) {
    /**
     * Provides the base inlinehtmleditor class.
     *
     * @module moodle-core-inlinehtmleditor
     */

    /**
     * A base class for a inlinehtmleditor.
     *
     * @param {Object} config Object literal specifying inlinehtmleditor configuration properties.
     * @class M.core.inlinehtmleditor
     * @constructor
     * @extends M.core.dialogue
     */
    function INLINEHTMLEDITOR(config) {
        if (!config) {
            config = {};
        }

        // Override the default options provided by the parent class.
        if (typeof config.draggable === 'undefined') {
            config.draggable = true;
        }

        if (typeof config.constrain === 'undefined') {
            config.constrain = false;
        }

        if (typeof config.width === 'undefined') {
            config.width = 600;
        }

        if (typeof config.lightbox === 'undefined') {
            config.lightbox = false;
        }

        INLINEHTMLEDITOR.superclass.constructor.apply(this, [config]);
    }

    var SELECTORS = {
            CLICKABLELINKS: 'a.inlinehtmleditor',
            CLOSEBUTTON: '.closebutton'
        },

        CSS = {
            PANELTEXT: 'inlinehtmleditortext'
        },
        RESOURCES = {
            WAITICON: {
                pix: 'i/loading_small',
                component: 'moodle'
            }
        },
        ATTRS = {};

    /**
     * Static property provides a string to identify the JavaScript class.
     *
     * @property NAME
     * @type String
     * @static
     */
    INLINEHTMLEDITOR.NAME = 'moodle-core-inlinehtmleditor';

    /**
     * Static property used to define the CSS prefix applied to inlinehtmleditor dialogues.
     *
     * @property CSS_PREFIX
     * @type String
     * @static
     */
    INLINEHTMLEDITOR.CSS_PREFIX = 'moodle-dialogue';

    /**
     * Static property used to define the default attribute configuration for the Tooltip.
     *
     * @property ATTRS
     * @type String
     * @static
     */
    INLINEHTMLEDITOR.ATTRS = ATTRS;

    /**
     * The initial value of the header region before the content finishes loading.
     *
     * @attribute initialheadertext
     * @type String
     * @default ''
     * @writeOnce
     */
    ATTRS.initialheadertext = {
        value: M.util.get_string('updatetext', 'core')
    };

    /**
      * The initial value of the body region before the content finishes loading.
      *
      * The supplid string will be wrapped in a div with the CSS.PANELTEXT class and a standard Moodle spinner
      * appended.
      *
      * @attribute initialbodytext
      * @type String
      * @default ''
      * @writeOnce
      */
    ATTRS.initialbodytext = {
        value: '',
        setter: function(content) {
            var parentnode = Y.Node.one('.inlinehtmleditorform').remove(false).removeClass('notinitialised');

            parentnode.all('.hidden').removeClass('hidden');
            /*
            var parentnode,
                spinner;
            parentnode = Y.Node.create('<div />')
                .addClass(CSS.PANELTEXT);

            spinner = Y.Node.create('<img />')
                .setAttribute('src', M.util.image_url(RESOURCES.WAITICON.pix, RESOURCES.WAITICON.component))
                .addClass('spinner');

            if (content) {
                // If we have been provided with content, add it to the parent and make
                // the spinner appear correctly inline
                parentnode.set('text', content);
                spinner.addClass('iconsmall');
            } else {
                // If there is no loading message, just make the parent node a lightbox
                parentnode.addClass('content-lightbox');
            }

            parentnode.append(spinner);
            */
            return parentnode;
        }
    };

    /**
     * The initial value of the footer region before the content finishes loading.
     *
     * If a value is supplied, it will be wrapped in a <div> first.
     *
     * @attribute initialfootertext
     * @type String
     * @default ''
     * @writeOnce
     */
    ATTRS.initialfootertext = {
        value: null,
        setter: function(content) {
            if (content) {
                return Y.Node.create('<div />')
                    .set('text', content);
            }
        }
    };

    /**
     * The function which handles setting the content of the title region.
     * The specified function will be called with a context of the inlinehtmleditor instance.
     *
     * The default function will simply set the value of the title to object.heading as returned by the AJAX call.
     *
     * @attribute headerhandler
     * @type Function|String|null
     * @default set_header_content
     */
    ATTRS.headerhandler = {
        value: 'set_header_content'
    };

    /**
     * The function which handles setting the content of the body region.
     * The specified function will be called with a context of the inlinehtmleditor instance.
     *
     * The default function will simply set the value of the body area to a div containing object.text as returned
     * by the AJAX call.
     *
     * @attribute bodyhandler
     * @type Function|String|null
     * @default set_body_content
     */
    ATTRS.bodyhandler = {
        value: 'set_body_content'
    };

    /**
     * The function which handles setting the content of the footer region.
     * The specified function will be called with a context of the inlinehtmleditor instance.
     *
     * By default, the footer is not set.
     *
     * @attribute footerhandler
     * @type Function|String|null
     * @default null
     */
    ATTRS.footerhandler = {
        value: null
    };

    Y.extend(INLINEHTMLEDITOR, M.core.dialogue, {
        // The bounding box.
        bb: null,

        // Any event listeners we may need to cancel later.
        listenevents: [],

        // The align position. This differs for RTL languages so we calculate once and store.
        alignpoints: [
            Y.WidgetPositionAlign.TL,
            Y.WidgetPositionAlign.RC
        ],

        initializer: function() {
            console.log('INIT');
            // Set the initial values for the handlers.
            // These cannot be set in the attributes section as context isn't present at that time.
            if (!this.get('bodyhandler')) {
                this.set('bodyhandler', this.set_body_content);
            }

            // Set up the dialogue with initial content.
            this.setAttrs({
                headerContent: this.get('initialheadertext'),
                bodyContent: this.get('initialbodytext'),
                footerContent: this.get('initialfootertext'),
                zIndex: 150
            });

            // Hide and then render the dialogue.
            this.hide();
            this.render();

            Y.one('body').delegate('click', this.display_panel, SELECTORS.CLICKABLELINKS, this);
            // Hook into a few useful areas.
            this.bb = this.get('boundingBox');

            // Change the alignment if this is an RTL language.
            if (right_to_left()) {
                this.alignpoints = [
                    Y.WidgetPositionAlign.TR,
                    Y.WidgetPositionAlign.LC
                ];
            }

            return this;
        },

        /**
         * Display the inlinehtmleditor for the clicked link.
         *
         * The anchor for the clicked link is used, additionally appending ajax=1 to the parameters.
         *
         * @method display_panel
         * @param {EventFacade} e The event from the clicked link. This is used to determine the clicked URL.
         */
        display_panel: function(e) {
            var clickedlink, thisevent, ajaxurl, config;

            // Prevent the default click action and prevent the event triggering anything else.
            e.preventDefault();
            e.stopPropagation();

            // Cancel any existing listeners and close the panel if it's already open.
            this.cancel_events();

            // Grab the clickedlink - this contains the URL we fetch and we align the panel to it.
            clickedlink = e.target.ancestor('a', true);

            // Align with the link that was clicked.
            this.align(clickedlink, this.alignpoints);

            // Reset the initial text to a spinner while we retrieve the text.
            this.setAttrs({
                headerContent: this.get('initialheadertext'),
                bodyContent: this.get('initialbodytext'),
                footerContent: this.get('initialfootertext')
            });

            // Now that initial setup has begun, show the panel.
            this.show();

            // Add some listen events to close on.
            thisevent = this.bb.delegate('click', this.close_panel, SELECTORS.CLOSEBUTTON, this);
            this.listenevents.push(thisevent);

            thisevent = Y.one('body').on('key', this.close_panel, 'esc', this);
            this.listenevents.push(thisevent);

            // Listen for mousedownoutside events - clickoutside is broken on IE.
            thisevent = this.bb.on('mousedownoutside', this.close_panel, this);
            this.listenevents.push(thisevent);

            ajaxurl = clickedlink.get('href');

            // Retrieve the mform help text we should use.
            config = {
                method: 'get',
                context: this,
                sync: false,
                on: {
                    complete: function(tid, response) {
                        this._set_panel_contents(response.responseText, ajaxurl);
                    }
                }
            };

            Y.io(clickedlink.get('href'), config);
        },

        _set_panel_contents: function(response, ajaxurl) {
            var responseobject;

            // Attempt to parse the response into an object.
            try {
                responseobject = Y.JSON.parse(response);
                if (responseobject.error) {
                    this.close_panel();
                    return new M.core.ajaxException(responseobject);
                }
            } catch (error) {
                this.close_panel();
                return new M.core.exception({
                    name: error.name,
                    message: "Unable to retrieve the requested content. The following error was returned: " + error.message
                });
            }

            // Set the contents using various handlers.
            // We must use Y.bind to ensure that the correct context is used when the default handlers are overridden.
            //Y.bind(this.get('bodyhandler'), this, responseobject)();

            this.get('buttons').header[0].focus();
        },

        set_body_content: function(responseobject) {
            var bodycontent = Y.Node.create('<div />')
                .set('innerHTML', responseobject.text)
                .setAttribute('role', 'alert')
                .addClass(CSS.PANELTEXT);
            this.set('bodyContent', bodycontent);
        },

        close_panel: function(e) {
            // Hide the panel first.
            this.hide();

            // Cancel the listeners that we added in display_panel.
            this.cancel_events();

            // Prevent any default click that the close button may have.
            if (e) {
                e.preventDefault();
            }
        },

        cancel_events: function() {
            // Detach all listen events to prevent duplicate triggers.
            var thisevent;
            while (this.listenevents.length) {
                thisevent = this.listenevents.shift();
                thisevent.detach();
            }
        }
    });
    M.core = M.core || {};
    M.core.inlinehtmleditor = M.core.inlinehtmleditor || null;
    M.core.init_inlinehtmleditor = M.core.init_inlinehtmleditor || function(config) {
        console.log(config);
        // Only set up a single instance of the inlinehtmleditor.
        if (!M.core.inlinehtmleditor) {
            M.core.inlinehtmleditor = new INLINEHTMLEDITOR(config);
        }
        return M.core.inlinehtmleditor;
    };
},
'@VERSION@', {
    requires: ['base', 'io-base', 'moodle-core-notification', 'json-parse',
            'widget-position', 'widget-position-align', 'event-outside']
}
);
