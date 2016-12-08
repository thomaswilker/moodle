/**
 * Configure and Initialise a bootstrap tour
 * This instace is for: MOD_BOOK
 *
 * @module     block_oua_help_tour
  * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 */
/*jshint unused: vars */
/*jshint maxlen:200 */
define(['jquery', 'block_oua_help_tour/bootstrap-tour', 'core/ajax', 'core/log', 'core/str', 'core/notification'], function ($, $bst, $ajax, $log, $str, $notify) {

    return {
        initialise: function ($params) {
            var disableTourOnClose = false;
            $('body').on('change', '#disabletour', function () {
                /* Keep track of checkebox in between panels */
                disableTourOnClose = this.checked;
            });
            // Instance the tour
            /*global Tour*/
            var tour = new Tour({
                name: "help_tour_book",
                backdrop: false,
                storage: false,
                onShown: function () {
                    /* Keep the checkbox checked in sync with disableTour var */
                    $("#disabletour").attr('checked', disableTourOnClose);
                },
                onEnd: function (tour) {
                    /* If user has checked the box, ajax save their preference */
                    if (disableTourOnClose) {
                        $ajax.call([{
                            methodname: 'block_oua_help_tour_disable',
                            args: {helptourname: this.name},
                            done: function ($disablereturn) {
                            }
                        }]);
                    } else {
                        // On close, disable tour for this moodle session.
                        $ajax.call([{
                            methodname: 'block_oua_help_tour_disable_for_session',
                            args: {helptourname: this.name},
                            done: function ($disablereturn) {
                            }
                        }]);
                    }
                },
                template: '<div class="popover" role="tooltip"> <div class="arrow"></div> ' +
                '<h3 class="popover-title"></h3>' +
                '<div class="popover-content"></div> <div class="popover-navigation">' +
                '<div class="btn-group"> <button class="btn btn-sm btn-default" data-role="prev">&laquo; Prev</button>' +
                '<button class="btn btn-sm btn-default" data-role="next">Next &raquo;</button>' +
                '<button class="btn btn-sm btn-default" data-role="pause-resume" data-pause-text="Pause" data-resume-text="Resume">Pause</button>' +
                '</div> <button class="btn btn-sm btn-default" data-role="end"></button> </div> </div>',
                steps: [
                    {
                        element: "nav.book-nav",
                        title: "Navigating the book activity",
                        content: "<p>The book activity has recently been updated.</p> " +
                        "<p>Navigate through pages of the book by selecting \"Next Page\" and \"Previous Page\" " +
                        " or by swiping left and right on mobile devices " +
                        "Your current page is indicated in the progress bar.</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "bottom",
                    },
                    {
                        element: "a.toc-link",
                        title: "Navigating the book activity",
                        content: "<p>See the table of contents by clicking \"Table of Contents\"" +
                        "</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "top",
                        onShow: function () {
                            /* iOS phones are having an issue with showing help overlay popups on areas initially hidden
                             such as the navbar menu. The help popup applies relative positioning and z-index to the
                             highlighted element, and layers a tour backdrop and a step backdrop under the element. This works fine
                             on all desktop browsers but on iOS the stacking context ( relative stacking order of elements in the DOM
                             hierarchy ) places the backdrops on top of the elements ( hiding the text ) or shows incorrect
                             placement of the tour backdrops. So far this issue hasn't been resolved using positions or z-index
                             updates.
                             For now, we will hide the background layer only for this particular step and for mobile.
                             */

                            /* adding this class to the body so apply css media queries to show/hide the background layer */
                            $('body').addClass('body-tour-backdrop');

                            $('a.toc-link').addClass('active');
                        },
                        onHide: function () {
                            $('a.toc-link').removeClass('active');
                        }
                    },

                ]
            });


            // Start the tour
            tour.start(true);
        }
    };
});
