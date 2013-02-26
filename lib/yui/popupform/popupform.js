YUI.add('moodle-core-popupform', function(Y) {

        function POPUPFORM(config) {
            if (!config) {
                config = {};
            }

            // Override the default options provided by the parent class.
            if (typeof config.draggable === 'undefined') {
                config.draggable = false;
            }

            if (typeof config.constrain === 'undefined') {
                config.constrain = true;
            }

            if (typeof config.modal === 'undefined') {
                config.modal = true;
            }

            if (typeof config.visible === 'undefined') {
                config.visible = false;
            }

            console.log('constructor');

            console.log(config);
            POPUPFORM.superclass.constructor.apply(this, [config]);
        }

        var POPUPFORMNAME = 'core-popupform';

        Y.extend(POPUPFORM, M.core.dialogue, {

                /**
                 * Initialize the module
                 */
                initializer : function(config) {
                    console.log("initializer");
                    Y.one('body').delegate('click', this.showPopupForm, '.core-popupform-link');
                    // Hide and then render the dialogue.
                    console.log("Make it hide");
                    console.log(this);
                    // Set up the dialogue with initial content.
                    //this.hide();
                    //this.render();

                    return this;
                },

                /**
                 * Add a class to the link so it can be targeted by the delegated event.
                 * @param config object with id of link.
                 */
                initLink: function(config) {
                    console.log(this);
                    console.log("initLink");
                    Y.one('#' + config.id).addClass('core-popupform-link')
                    this.hide();
                },

                showPopupForm : function(e) {
                    console.log(this);
                    console.log("showPopupForm");
                    e.preventDefault();

                    // Get the href from the link.
                    var href = this.get('href');

                 //   this.show();
                    // Load the url with ajax
                    // Retrieve the actual help text we should use.
                    var config = {
                        method: 'get',
                        context: this,
                        sync: false,
                        data: {
                            fragment: 1
                        },
                        on: {
                            complete: function(tid, response) {
                                console.log('Ajax loaded');
                                console.log(this);
                                console.log(response.responseText);
                            }
                        }
                    };

                    Y.io(href, config);
                }

            },
            {
                NAME : POPUPFORMNAME,
                ATTRS : {}
            }
        );

        M.core = M.core || {};
        M.core.popupform = M.core.popupform || null;
        M.core_popupform_init = M.core_popupform_init || function(config) {
            if (!M.core.popupform) {
                M.core.popupform = new POPUPFORM(config);
            }
            M.core.popupform.initLink(config);
            return M.core.popupform;
        };
    },
    '@VERSION@', {
        requires : ['base', 'io-base', 'moodle-core-notification', 'json-parse']
    }
);
