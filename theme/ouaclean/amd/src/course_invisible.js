define(['jquery', 'theme_ouaclean/jquery.sticky'], function ($) {

    return {
        initialise: function () {

            /** STICKY SCROLL FOR NAV HEADERS
             ========================================================*/
            var activitynav = $('.classroom-activity-nav.sticky');

            //apply stickies
            activitynav.sticky({topSpacing: 0, zIndex: 999});

            /** STICKY SCROLL FOR BOOK & LESSON NAV
             ========================================================*/
            var booknav = $('.book-header, .lesson-header');
            booknav.sticky({
                topSpacing: 36,
                bottomSpacing: 100,
                zIndex: 999,
                responsiveWidth: true,
                getWidthFrom: '.book-header'
            });

            var sticky = $('.sticky');
            var stickyWrapper = $('.sticky-wrapper');
            var navBottom = 0;

            stickyWrapper.each(function (i, thiswrapper) {
                var nextBottom = $(thiswrapper).offset().top + $(thiswrapper).height();
                if (nextBottom > navBottom) {
                    navBottom = nextBottom;
                }
            });
            var maincontainer = $('div[role=main]');

            var lastScrollTop = 0;
            $(window).scroll(function () {
                var st = $(this).scrollTop();
                if ((lastScrollTop !== 0 && st > lastScrollTop && st > navBottom) && st < maincontainer.height()) {
                    // On scroll down, fade out the sticky nav
                    // Only do it after first scroll down.
                    // At the bottom of main content nav, show the nav again.
                    sticky.addClass('sticky-pinned-hide').removeClass('sticky-pinned-show');
                } else {
                    // Show the nav on scroll up.
                    sticky.addClass('sticky-pinned-show').removeClass('sticky-pinned-hide');
                }
                lastScrollTop = st;
            });
        }
    };
});
