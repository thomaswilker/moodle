define(['core/dragdrop-reorder',
        'core/str',
        'core/notification',
        'jquery',
        'core/ajax'],
       function(dragdrop, str, notification, $, ajax) {
    // Private variables and functions.

    /**
     * Handle a drop on a node.
     *
     * @param {DOMNode} drag
     * @param {DOMNode} drop
     */
    var handleDrop = function(drag, drop) {
        var from = $(drag).data('frameworkid');
        var to = $(drop).data('frameworkid');

        var requests = ajax.call([{
            methodname: 'tool_learningplan_reorder_competency_framework',
            args: { from: from, to: to }
        }]);
        requests[0].fail(notification.exception);

    };

    return {
        // Public variables and functions.
        /**
         * Initialise this plugin. It loads some strings, then adds the drag/drop functions.
         */
        init: function() {
            // Init this module.
            str.get_string('movecompetencyframework', 'tool_learningplan').done(
                function(movestring) {
                    dragdrop.dragdrop('movecompetencyframework',
                                      movestring,
                                      { identifier: 'movecompetencyframework', component: 'tool_learningplan'},
                                      { identifier: 'movecompetencyframeworkafter', component: 'tool_learningplan'},
                                      'drag-samenode',
                                      'drag-parentnode',
                                      'drag-handlecontainer',
                                      handleDrop);
                }
            ).fail(notification.exception);
        }

    };
});
