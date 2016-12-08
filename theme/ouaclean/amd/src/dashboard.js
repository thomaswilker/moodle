define( ['jquery'], function($) {
    var lastResizeState = '';

    $( document ).ready( function() {
        dashboard_units_mobile_view();
        reset_collapsed_trigger();
    });
    $( window ).resize( function() {
        dashboard_units_mobile_view();
    });

    function dashboard_units_mobile_view() {
        var ouaUnitsTab = $('ul.nav-tabs li a[href="#navtabx"]').parent('li'),
            ouaUnitsContent = $('#navtabx'),
            ouaNextTab = ouaUnitsTab.next();

        // Units is Visible in Content Section
        if( !ouaUnitsTab.is(':visible') ) {

            // Make the next Tab active if Units is currently active
            if ( ouaUnitsTab.hasClass('active')) {
                ouaUnitsTab.removeClass('active'); /* Remove Units Tab active state */
                ouaNextTab.addClass('active'); /* Add Message Tab active state */
                ouaUnitsContent.removeClass('active'); /* Hide Units Content in navigation */
                ouaUnitsContent.next().addClass('active'); /* Show Next Content in navigation */
            }
            lastResizeState = 'desktop';

        // Units is Hidden from Content Section
        } else {
            if(lastResizeState == 'desktop') {
                // Always make Units active and visible in navigation
                ouaUnitsTab.siblings().removeClass('active');
                ouaUnitsContent.siblings().removeClass('active');
                ouaUnitsTab.addClass('active');
                ouaUnitsContent.addClass('active');
                lastResizeState = 'mobile';
            }
        }
    }

    function reset_collapsed_trigger(){
        $('.course-list').on('click', '.more', function(e){
            e.preventDefault();
            $(this).toggleClass('collapsed');
        });
    }

});
