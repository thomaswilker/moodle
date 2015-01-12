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

var SELECTORS = {
    MAINCONTAINER: '.mod_lesson_main',
    LESSONPAGES: '.mod_lesson_pages',
    PAGEMODULES: '.mod_lesson_page_element'

    };

Y.namespace('M.mod_lesson').PagemMove = Y.extend(PagemMove, Y.Base, {

    initializer: function() {
        var allLessonPages = Y.all(SELECTORS.PAGEMODULES);

        YUI().use('dd-delegate', 'dd-drop-plugin', function(Y) {

            var del = new Y.DD.Delegate( {
                container: SELECTORS.MAINCONTAINER,
                nodes: SELECTORS.PAGEMODULES

            });

            del.on('drag:end', function(e) {

                // Keep it in the main container.

                // Get x and y coordinates and update the database.

            });


        });

        allLessonPages.each(function (lessonPage) {
            // Click and drag of the lesson page.
            lessonPage.on('click', function () {
                console.log(lessonPage);
                // console.log(lessonPage.getXY());
                // lessonPage.setStyle('position', 'absolute');
                // lessonPage.setStyle('left', '25px');
                // lessonPage.setStyle('top', '55px');

            });
        });

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
        // lessondata: {
        //     value: null
        // }
    }
});


// Y.namespace('M.mod_lesson-pagemmove') = function() {};
Y.namespace('M.mod_lesson.PagemMove').init = function(config) {
    return new PagemMove(config);
};