define( ['jquery'], function($) {

    /* Scroll to Top
    ======================================================= */
    $(document).on( 'scroll', function() {
        display_scroll_top_button();
    });

    function display_scroll_top_button() {
         var scrollButton = $('#scroll-top');

        // Only show button when user scrolls below the page fold
        if ( $(window).scrollTop() > $(window).height() ) {
            if ( scrollButton.hasClass('hide') ) {
                scrollButton.removeClass('hide').hide().fadeIn('slow'); /* hide and fadein slowly */
            }
        // Hide it again when user scrolls above the page fold
        } else {
            if ( !scrollButton.hasClass('hide') ) {
                scrollButton.fadeOut('slow', function() { scrollButton.addClass('hide'); }); /* show and fade out slowly */
            }
        }
    }

    // Scroll to the Top on click
    $('#scroll-top').on('click', function() {
       $('html, body').animate({scrollTop: 0}, 333, 'linear'); /* scroll to the top in 333 milliseconds */
    });

});