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
                name: "help_tour_nav",
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
                '<div class="btn-group"> <button class="btn btn-sm btn-default hidden" data-role="prev">&laquo; Prev</button>' +
                '<button class="btn btn-sm btn-default hidden" data-role="next">Next &raquo;</button>' +
                '<button class="btn btn-sm btn-default hidden" data-role="pause-resume" data-pause-text="Pause" data-resume-text="Resume">Pause</button>' +
                '</div> <button class="btn btn-sm btn-default" data-role="end"></button> </div> </div>',
                steps: [
                    {
                        element: "body.format-invisible div.classroom-activity-nav",
                        title: "Navigating the classroom",
                        content: "<p>Activity navigation has recently changed.</p> " +
                        "<p>Go to the previous and next activity by selecting \"Prev Activity\" or \"Next Activity\"" +
                        "</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "bottom",
                        onShow: function () {

                        },
                        onHide: function () {

                        }
                    }

                ]
            });


            // Start the tour
            tour.start(true);
        }
    };
});
