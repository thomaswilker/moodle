YUI.add('moodle-core-actionmenu', function (Y, NAME) {

/**
 * Provides drop down menus for list of action links.
 *
 * @module moodle-core-actionmenu
 */

CSS = {
    XXX : 'xxx'
};

RESOURCES = {
    MENUICON : {
        pix : 't/contextmenu',
        component : 'moodle'
    }
};

/**
 * Action menu support.
 * This converts a generic list of links into a drop down menu opened by hovering or clicking
 * on a menu icon.
 *
 * @namespace M.core.actionmenu
 * @class ActionMenu
 * @constructor
 * @extends Y.Base
 */
var ACTIONMENU = function() {
    ACTIONMENU.superclass.constructor.apply(this, arguments);
};

Y.extend(ACTIONMENU, Y.Base, {
    initializer : function() {
        var alignpoints = [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL];

        if (right_to_left()) {
            alignpoints = [Y.WidgetPositionAlign.TR, Y.WidgetPositionAlign.BR];
        }

        Y.all('.commands').each(function() {
            // Prepend menu icon before the list.
            var imgnode = Y.Node.create('<img/>');
            var imgsrc = M.util.image_url(RESOURCES.MENUICON.pix, RESOURCES.MENUICON.component);
            var linknode = Y.Node.create('<a/>');

            linknode.setAttribute('href', '#');
            linknode.addClass('actionmenu');
            imgnode.setAttribute('src', imgsrc);

            linknode.appendChild(imgnode);
            this.get('parentNode').insertBefore(linknode, this);

            var overlay = new Y.Overlay({
                bodyContent : this,
                visible: false,
                align: {node: imgnode, points: alignpoints},
                zIndex: 10
            });

            overlay.render();
            overlay.get('boundingBox').on('focusoutside', overlay.hide, overlay);

            linknode.setData('actionmenu', overlay);
        });

        Y.one('body').delegate('click', this.toggleMenu, '.actionmenu');
        Y.one('body').on('key', this.hideAllMenus, 'esc', this);
    },

    hideAllMenus : function() {
        Y.log('actionmenu:hide all menus');
        // Hide all actionmenus.
        Y.all('.actionmenu').each(function() {
            var overlay = this.getData('actionmenu');
            if (overlay) {
                overlay.hide();
            }
        });
    },

    toggleMenu : function(e) {
        Y.log('actionmenu:toggle menu click handler');
        var overlay = this.getData('actionmenu');

        if (overlay.get('visible')) {
            overlay.hide();
        } else {
            M.core.actionmenu.instance.hideAllMenus();
            overlay.show();
        }
        e.preventDefault();
    }

}, {
    NAME : 'moodle-core-actionmenu',
    ATTRS : { }
});

/**
 * Core namespace.
 */
M.core = M.core || {};

/**
 * Actionmenu namespace.
 */
M.core.actionmenu = M.core.actionmenu || {};

/**
 * Init function - will only ever create one instance of the actionmenu class.
 */
M.core.actionmenu.init = M.core.actionmenu.init || function(params) {
    M.core.actionmenu.instance = M.core.actionmenu.instance || new ACTIONMENU(params);
};


}, '@VERSION@', {"requires": ["base", "node", "overlay", "event"]});
