// This module depends on the real jquery - and returns the non-global version of it.
define(['jquery'], function ($) {
    return $.noConflict( true );
});
