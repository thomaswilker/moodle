define( ['jquery', 'theme_bootstrap/bootstrap'], function($) {

    // Popover
    var popoverOptions = { placement : 'top' };
    $('[data-toggle="popover"]').popover(popoverOptions);

});
