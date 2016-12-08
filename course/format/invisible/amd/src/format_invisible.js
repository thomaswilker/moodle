define(['jquery', 'theme_ouaclean/jquery.sticky'], function ($) {

    /** Smooth Scroll to Element
     ======================================================= */
    function slide_to(id, margin, speed) { // selector must be an #ID string with hash
        margin = margin || 0;
        speed = speed || 333;
        var target = $(id).offset().top; //get position to scroll to

        target = target - margin;
        $('html, body').animate({
            scrollTop: target
        }, speed);
    }

    $("#course-nav-scroll").click(function () {
        slide_to($(this).attr('data-target'));
    });

    /** Expand / Collapse Menu
     ======================================================= */
    $(".course-header .toggle").click(function () {
        var $regionmain = $("#region-main");
        $regionmain.parents('#page').toggleClass('fullwidth');
        $regionmain.toggleClass("fullwidth");
        $("#region-nav, #region-side").toggleClass("hide");
    });

    return {
        initialise: function () {
        }
    };
});
