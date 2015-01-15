define(['core/mustache', 'jquery'], function(mustache, $) {

    // Private variables and functions.

    var templateCache = {};

    /**
     * Render a template and then call the callback with the result.
     */
    var doRender = function(templateSource, context, callback) {
        var result = mustache.render(templateSource, context);
        callback(true, result);
    };

    return {
        // Public variables and functions.
        /**
         * Load a template and call doRender on it.
         */
        renderTemplate: function(component, templateName, context, callback) {
            var key = component + '/' + templateName;
            if (key in templateCache) {
                doRender(templateCache.key, context, callback);
                return;
            }

            var settings = {
                success: function(templateSource) {
                    templateCache.key = templateSource;
                    doRender(templateSource, context, callback);
                },
                error: function() {
                    callback(false, '');
                }
            }

            $.ajax(M.cfg.wwwroot + '/theme/template.php/-1/' + component + '/' + templateName, settings);
        }
    };
});
