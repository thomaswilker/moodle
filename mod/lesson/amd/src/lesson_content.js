define(['jquery'], function ($) {
    $("ul.toc").on('scroll', function () {
        updateScrollPosition($(this));
    });
    $('.lesson-nav .dropdown').on('shown.bs.dropdown', function () {
        // force scroll to trigger update.
        updateScrollPosition($('.lesson-nav ul.toc'));
        scrollToActiveToc();
    });
    var updateScrollPosition = function (scrollWrap) {
        var $noscroll = scrollWrap[0].scrollHeight === undefined;
        // Detect scroll to bottom or scroll to top.
        if ($noscroll || (scrollWrap.scrollTop() + scrollWrap.innerHeight() >= scrollWrap[0].scrollHeight)) {
            scrollWrap.addClass('scroll-limit-bottom');
        } else {
            scrollWrap.removeClass('scroll-limit-bottom');
        }
        // Detect scroll to bottom or scroll to top.
        if ($noscroll || scrollWrap.scrollTop() < 6) {
            scrollWrap.addClass('scroll-limit-top');
        } else {
            scrollWrap.removeClass('scroll-limit-top');
        }
    };
    var scrollToActiveToc = function () {
        var $scrollTo = $('.lesson-nav ul.toc li.active');
        var $scrollwrapper = $('.lesson-nav .dropdown.open .dropdown-menu ul');

        var $scrollwrapperstart = $scrollwrapper.scrollTop();
        var $scrollwrapperend = $scrollwrapperstart + $scrollwrapper.height(); //end of wrapper
        var $itempos = $scrollTo.position().top; // -8
        if ($scrollTo && ($itempos < 0 || $scrollwrapperstart + $itempos > $scrollwrapperend)) {
            // Scroll it into view if its not in view.
            $scrollwrapper.animate({
                scrollTop: $scrollwrapperstart - 10 + $itempos
            }, 100);
        }
    };
});
