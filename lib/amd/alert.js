define(['jquery', 'jqueryui', 'core/templates'], function($, ui, templates) {
    // Private variables and functions.

    var showDialog = function(templateLoaded, source) {
        if (!templateLoaded) {
            return;
        }
        $(source).dialog({
            modal: true
        });
    };
    // None.
    // Constructor.
    var dialog = function(title, message, accepttext) {

        this.title = title;
        this.message = message;
        this.accepttext = accepttext;

        templates.renderTemplate('core', 'alert', this, showDialog);
    };

    // Public variables and functions.
    dialog.prototype = {
        // None.
    };

    return dialog;
});
