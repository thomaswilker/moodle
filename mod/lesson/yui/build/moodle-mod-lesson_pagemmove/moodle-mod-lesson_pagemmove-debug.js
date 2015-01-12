YUI.add('moodle-mod-lesson_pagemmove', function (Y, NAME) {

/**
 * A tool for moving and creating lessons.
 *
 * @module moodle-mod-lesson_pagemove
 */

/**
 * A tool for moving and creating lessons.
 *
 * @class M.mod-lesson_pagemmove.PagemMove
 * @extends Base
 * @constructor
 */
function PagemMove() {
    EventFilter.superclass.constructor.apply(this, arguments);
}

Y.extend(PagemMove, Y.Base, {

    initializer: function() {
        console.log('This works?');
    }

}, {
    NAME: 'pagemMove',
    ATTRS: {
        /**
         * Data for the table.
         *
         * @attribute tabledata.
         * @type Array
         * @writeOnce
         */
        // tabledata: {
        //     value: null
        // }
    }
});


Y.namespace('M.mod-lesson_pagemmove').init = function(config) {
    return new PagemMove(config);
};


}, '@VERSION@');
