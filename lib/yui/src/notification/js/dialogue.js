/**
 * The generic dialogue class for use in Moodle.
 *
 * @module moodle-core-notification
 * @submodule moodle-core-notification-dialogue
 */

var DIALOGUE_NAME = 'Moodle dialogue',
    DIALOGUE;

/**
 * A re-usable dialogue box with Moodle classes applied.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.dialogue
 * @extends Y.Panel
 */
DIALOGUE = function(config) {
    COUNT++;
    var id = 'moodle-dialogue-'+COUNT;
    config.notificationBase =
        Y.Node.create('<div class="'+CSS.BASE+'">')
              .append(Y.Node.create('<div id="'+id+'" role="dialog" aria-labelledby="'+id+'-header-text" class="'+CSS.WRAP+'"></div>')
              .append(Y.Node.create('<div class="'+CSS.HEADER+' yui3-widget-hd"></div>'))
              .append(Y.Node.create('<div class="'+CSS.BODY+' yui3-widget-bd"></div>'))
              .append(Y.Node.create('<div class="'+CSS.FOOTER+' yui3-widget-ft"></div>')));
    Y.one(document.body).append(config.notificationBase);

    if (config.additionalBaseClass) {
        config.notificationBase.addClass(config.additionalBaseClass);
    }

    config.srcNode =    '#'+id;
    config.width =      config.width || '400px';
    config.visible =    config.visible || false;
    config.center =     config.centered || true;
    config.centered =   false;
    config.COUNT = COUNT;

    if (config.width === 'auto') {
        delete config.width;
    }

    // lightbox param to keep the stable versions API.
    if (config.lightbox !== false) {
        config.modal = true;
    }
    delete config.lightbox;

    // closeButton param to keep the stable versions API.
    if (config.closeButton === false) {
        config.buttons = null;
    } else {
        config.buttons = [
            {
                section: Y.WidgetStdMod.HEADER,
                classNames: 'closebutton',
                action: function () {
                    this.hide();
                }
            }
        ];
    }
    DIALOGUE.superclass.constructor.apply(this, [config]);

    if (config.closeButton !== false) {
        // The buttons constructor does not allow custom attributes
        this.get('buttons').header[0].setAttribute('title', this.get('closeButtonTitle'));
    }
};
Y.extend(DIALOGUE, Y.Panel, {
    // Window resize event listener.
    _resizeevent : null,
    // Orientation change event listener.
    _orientationevent : null,
    // Original overflow value.
    _windowoverflow : null,

    /**
     * Initialise the dialogue.
     *
     * @method initializer
     * @return void
     */
    initializer : function(config) {
        var bb;

        this.after('visibleChange', this.visibilityChanged, this);
        this.render();
        this.show();
        this.set('COUNT', COUNT);

        // Workaround upstream YUI bug http://yuilibrary.com/projects/yui3/ticket/2532507
        // and allow setting of z-index in theme.
        bb = this.get('boundingBox');
        if (config.zIndex) {
            bb.setStyle('zIndex', config.zIndex);
        } else {
            bb.setStyle('zIndex', null);
        }

        if (config.extraClasses) {
            Y.Array.each(config.extraClasses, bb.addClass, bb);
        }
        this.makeResponsive();
    },
    /**
     * Event listener for the visibility changed event.
     *
     * @method visibilityChanged
     * @return void
     */
    visibilityChanged : function(e) {
        var titlebar;
        if (e.attrName === 'visible') {
            this.get('maskNode').addClass(CSS.LIGHTBOX);
            if (e.prevVal && !e.newVal) {
                if (this._resizeevent) {
                    this._resizeevent.detach();
                    this._resizeevent = null;
                }
                if (this._orientationevent) {
                    this._orientationevent.detach();
                    this._orientationevent = null;
                }
            }
            if (this.get('center') && !e.prevVal && e.newVal) {
                this.centerDialogue();
            }
            if (this.get('draggable')) {
                titlebar = '#' + this.get('id') + ' .' + CSS.HEADER;
                this.plug(Y.Plugin.Drag, {handles : [titlebar]});
                Y.one(titlebar).setStyle('cursor', 'move');
            }
        }
    },
    /**
     * If the responsive attribute is set on the dialog, and the window size is
     * smaller than the responsive width - make the dialog fullscreen.
     *
     * @method makeResponsive
     * @return void
     */
    makeResponsive : function() {
        var bb = this.get('boundingBox'), windowroot, content;

        if (this.shouldResizeFullscreen()) {
            // Make this dialogue fullscreen on a small screen.
            // Disable the page scrollbars.
            windowroot = Y.one('body');
            if (Y.UA.ie > 0) {
                // Remember the previous value:
                windowroot = Y.one('html');
            }
            // Remember the previous value.
            this._windowoverflow = windowroot.getStyle('overflow');
            windowroot.setStyle('overflow', 'hidden');
            // Size and position the fullscreen dialog.

            bb.addClass(DIALOGUE_PREFIX+'-fullscreen');
            bb.setStyle('left', '0px')
                .setStyle('top', '0px')
                .setStyle('width', '100%')
                .setStyle('height', '100%')
                .setStyle('overflow', 'auto');

            content = Y.one('#' + this.get('id') + ' .' + CSS.BODY);
            content.setStyle('overflow', 'auto');
            window.scrollTo(0, 0);
        } else {
            if (this.get('responsive')) {
                // We must reset any of the fullscreen changes.
                bb.removeClass(DIALOGUE_PREFIX+'-fullscreen')
                    .setStyles({'overflow' : 'inherit',
                                'width' : this.get('width'),
                                'height' : this.get('height')});
                content = Y.one('#' + this.get('id') + ' .' + CSS.BODY);
                content.setStyle('overflow', 'inherit');

                if (Y.UA.ie > 0) {
                    Y.one('html').setStyle('overflow', this._windowoverflow);
                } else {
                    Y.one('body').setStyle('overflow', this._windowoverflow);
                }
            }
        }
    },
    /**
     * Center the dialog on the screen.
     *
     * @method centerDialogue
     * @return void
     */
    centerDialogue : function() {
        var bb = this.get('boundingBox'),
            hidden = bb.hasClass(DIALOGUE_PREFIX+'-hidden'),
            x, y;
        if (hidden) {
            bb.setStyle('top', '-1000px').removeClass(DIALOGUE_PREFIX+'-hidden');
        }
        x = Math.max(Math.round((bb.get('winWidth') - bb.get('offsetWidth'))/2), 15);
        y = Math.max(Math.round((bb.get('winHeight') - bb.get('offsetHeight'))/2), 15) + Y.one(window).get('scrollTop');
        bb.setStyles({ 'left' : x, 'top' : y});

        this.makeResponsive();
        if (hidden) {
            bb.addClass(DIALOGUE_PREFIX+'-hidden');
        }
    },
    /**
     * Hide this dialogue
     *
     * @method hide
     * @return Boolean
     */
    hide : function() {
        if (Y.UA.ie > 0) {
            Y.one('html').setStyle('overflow', 'auto');
        } else {
            Y.one('body').setStyle('overflow', 'auto');
        }
        return this.set("visible", false);
    },
    /**
     * Return if this dialogue should be fullscreen or not.
     * Responsive attribute must be true and we should not be in an iframe and the screen width should
     * be less than the responsive width.
     *
     * @method shouldResizeFullscreen
     * @return Boolean
     */
    shouldResizeFullscreen : function() {
        return (window === window.parent) && this.get('responsive') &&
               Math.floor(Y.one(document.body).get('winWidth')) < this.get('responsiveWidth');
    }
}, {
    NAME : DIALOGUE_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {
        notificationBase : {

        },

        /**
         * Whether to display the dialogue modally and with a
         * lightbox style.
         *
         * @attribute lightbox
         * @type Boolean
         * @default true
         */
        lightbox : {
            validator : Y.Lang.isBoolean,
            value : true
        },

        /**
         * Whether to display a close button on the dialogue.
         *
         * Note, we do not recommend hiding the close button as this has
         * potential accessibility concerns.
         *
         * @attribute closeButton
         * @type Boolean
         * @default true
         */
        closeButton : {
            validator : Y.Lang.isBoolean,
            value : true
        },

        /**
         * The title for the close button if one is to be shown.
         *
         * @attribute closeButtonTitle
         * @type String
         * @default 'Close'
         */
        closeButtonTitle : {
            validator : Y.Lang.isString,
            value : 'Close'
        },

        /**
         * Whether to display the dialogue centrally on the screen.
         *
         * @attribute center
         * @type Boolean
         * @default true
         */
        center : {
            validator : Y.Lang.isBoolean,
            value : true
        },

        /**
         * Whether to make the dialogue movable around the page.
         *
         * @attribute draggable
         * @type Boolean
         * @default false
         */
        draggable : {
            validator : Y.Lang.isBoolean,
            value : false
        },

        /**
         * Used to generate a unique id for the dialogue.
         *
         * @attribute COUNT
         * @type Integer
         * @default 0
         */
        COUNT: {
            value: 0
        },

        /**
         * Used to disable the fullscreen resizing behaviour if required.
         *
         * @attribute responsive
         * @type Boolean
         * @default true
         */
        responsive : {
            validator : Y.Lang.isBoolean,
            value : true
        },

        /**
         * The width that this dialogue should be resized to fullscreen.
         *
         * @attribute responsiveWidth
         * @type Integer
         * @default 768
         */
        responsiveWidth : {
            value : 768
        }
    }
});

M.core.dialogue = DIALOGUE;
