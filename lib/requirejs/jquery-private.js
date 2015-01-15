// This module depends on the real jquery - and returns the non-global version of it.
define(['jquery'], function ($) {
    var link = document.createElement("link");
    link.type = "text/css";
    link.rel = "stylesheet";
    link.href = M.cfg.wwwroot + '/lib/jquery/ui-1.11.1/theme/smoothness/jquery-ui.min.css';
    document.getElementsByTagName("head")[0].appendChild(link);
    return $.noConflict( true );
});
