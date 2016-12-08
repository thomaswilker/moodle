/**
 * Configure and Initialise a bootstrap tour
 * This instace is for: DASHBOARD
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
                name: "help_tour_dashboard",
                backdrop: true,
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
                        element: "#region-main div.block_oua_course_list ul.nav",
                        title: "Welcome to your Dashboard!",
                        content: "<h2>How to enter your classroom</h2> " +
                        "<p>To enter your classroom and access your learning materials, select the unit name. " +
                        "Alternatively, you can select the activities dropdown menu and enter the classroom at the desired topic.</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "bottom",
                    },
                    {
                        element: "div.welcome-user ul.nav li.units",
                        title: "Welcome to your Dashboard!",
                        content: "<h2>How to enter your classroom</h2> " +
                        "<p>To enter your classroom and access your learning materials, select the unit name. " +
                        "Alternatively, you can select the activities dropdown menu and enter the classroom at the desired topic.</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "bottom",
                    },
                    {
                        element: "div.block_oua_connections ul.nav",
                        title: "Welcome to your Dashboard!",
                        content: "<h2>How to connect with others</h2>" +
                        "<p>The LMS is filled with tools to help students engage and share." +
                        "You can connect with users enrolled in the same units in the Suggested Connections tab.</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "bottom",
                    },
                    {
                        element: "#moodle-navbar li.help",
                        title: "Welcome to your Dashboard!",
                        content: "<h2>Learn more about your LMS</h2> " +
                        "<p>For more information and details on how to use the LMS, visit the Help section through the " +
                        "link below or select Help at any time from the top of your browser.</p>" +
                        "<label><input id='disabletour' name='disabletour' type='checkbox'> Never show this again</label>",
                        placement: "bottom",
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

                            /* mobile view expand menu before step */
                            if ($("button.navbar-toggle").is(":visible")) {
                                $('#moodle-navbar').collapse('show');
                            }
                        },
                        onHide: function () {
                            /* removing this class added as part of onShow */
                            $('body').removeClass('body-tour-backdrop');
                            
                            /* mobile view, hide menu after step */
                            $('#moodle-navbar').collapse('hide');
                        }
                    }
                ]
            });


            // Start the tour
            tour.start(true);
        }
    };
});
