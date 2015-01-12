YUI.add('moodle-mod_lesson-pagemmove', function (Y, NAME) {

/**
 * A tool for moving and creating lessons.
 *
 * @module moodle-mod_lesson-pagemove
 */

/**
 * A tool for moving and creating lessons.
 *
 * @class M.mod_lesson-pagemmove.PagemMove
 * @extends Base
 * @constructor
 */
function PagemMove() {
    PagemMove.superclass.constructor.apply(this, arguments);
}

Y.namespace('M.mod_lesson').PagemMove = Y.extend(PagemMove, Y.Base, {

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


// Y.namespace('M.mod_lesson-pagemmove') = function() {};
Y.namespace('M.mod_lesson.PagemMove').init = function(config) {
    return new PagemMove(config);
};

}, '@VERSION@');
