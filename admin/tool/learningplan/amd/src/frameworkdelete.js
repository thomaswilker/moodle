define(['jquery', 'core/templates', 'core/ajax', 'core/notification'], function($, templates, ajax, notification) {
    // Private variables and functions.

    /** @var {Number} frameworkid The id of the framework */
    var frameworkid = 0;

    /**
     * Callback to replace the dom element with the rendered template.
     *
     * @param {string} newhtml The new html to insert.
     * @param {string} newjs The new js to run.
     */
    var updatePage = function(newhtml, newjs) {
        $('[data-region="managecompetencies"]').replaceWith(newhtml);
        templates.runTemplateJS(newjs);
    };

    /**
     * Callback to render the page template again and update the page.
     *
     * @param {Object} context The context for the template.
     */
    var reloadList = function(context) {
        templates.render('tool_learningplan/manage_competency_frameworks_page', context)
            .done(updatePage)
            .fail(notification.exception);
    };

    /**
     * Delete a framework and reload the page.
     */
    var doDelete = function() {

        // We are chaining ajax requests here.
        var requests = ajax.call([{
            methodname: 'tool_learningplan_delete_competency_framework',
            args: { id: frameworkid }
        }, {
            methodname: 'tool_learningplan_data_for_competency_frameworks_manage_page',
            args: []
        }]);
        requests[1].done(reloadList).fail(notification.exception);
    };

    /**
     * Handler for "Delete competency framework" actions.
     * @param {Event} e
     */
    var confirmDelete = function(e) {
        e.preventDefault();

        var id = $(this).attr('data-frameworkid');
        frameworkid = id;

        notification.confirm(
            'Confirm',
            'Delete competency framework "blah"?',
            'Delete',
            'Cancel',
            doDelete
        );

    };


    return {
        // Public variables and functions.
        /**
         * Initialise this plugin. Just attaches some event handlers to the delete entries in the menu.
         */
        init: function() {
            // Init this module.
            $('[data-region="managecompetencies"]').on(
                "click",
                '[data-action="deletecompetencyframework"]',
                confirmDelete
            );
        }

    };
});
