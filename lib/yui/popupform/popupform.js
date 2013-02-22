YUI.add('moodle-core-popupform',
    function(Y) {
        var POPUPFORMNAME = 'core-popupform',

            POPUPFORM = function() {
                POPUPFORM.superclass.constructor.apply(this, arguments);
            };

        Y.extend(POPUPFORM, Y.Base, {

                /**
                 * Initialize the module
                 */
                initializer : function() {
                    
                },

            },
            {
                NAME : POPUPFORMNAME,
                ATTRS : {}
            }
        );

        M.core_popupform = M.core_popupform || {};

        // We might have multiple instances of the a popup form
        M.core_popupform.instances = M.core_popupform.instances || [];
        M.core_popupform.init = M.core_popupform.init || function(config) {
            var popupform = new POPUPFORM(config);
            M.core_popupform.instances.push(popupform);
            return popupform;
        };
    },
    '@VERSION@', {
        requires : ['base']
    }
);
