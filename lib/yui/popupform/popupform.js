YUI.add('moodle-core-popupform', function(Y) {

    function POPUPFORM() {
        POPUPFORM.superclass.constructor.apply(this, arguments);
    }

    POPUPFORM.NAME = 'core-popupform';
    var SELECTORS = {
            CLICKABLELINKS: '.core-popupform-link',
            CONTENTWRAP: '.core-popupform-content',
            SUBMITBUTTONS: '.fsubmit input'
        },
        CSS = {
            LINKCLASS: 'core-popupform-link',
            CONTENTWRAP: 'core-popupform-content',
            SPINNER: 'core-popupform-spinner'
        },
        RESOURCES = {
            WAITICON: {
                pix: 'i/loading_small',
                component: 'moodle'
            }
        },
        ATTRS = {};

    Y.extend(POPUPFORM, Y.Base, {

        panel: null,
        spinner: null,
        listeners: [],

        /**
         * Initialize the module
         */
        initializer : function(config) {
            Y.one('body').delegate('click', this.showPopupForm, SELECTORS.CLICKABLELINKS);
            return this;
        },

        /**
         * Add a class to the link so it can be targeted by the delegated event.
         * @param config object with id of link.
         */
        initLink: function(config) {
            console.log(this);
            console.log("initLink");
            Y.one('#' + config.id).addClass(CSS.LINKCLASS)
        },

        showPopupForm : function(e) {
            console.log(this);
            console.log("showPopupForm");
            e.preventDefault();
            if (!M.core.popupform.spinner) {
                var imgURL = M.util.image_url(RESOURCES.WAITICON.pix, RESOURCES.WAITICON.component),
                    spinnerNode = Y.Node.create('<div class="' + CSS.SPINNER + '"><img src="' + imgURL + '"/>');

                M.core.popupform.spinner = spinnerNode;
            }
            if (!M.core.popupform.panel) {
                M.core.popupform.panel = new M.core.dialogue({
                    bodyhandler: this.set_body_content,
                    footerhandler: this.set_footer,
                    initialheadertext: '',
                    initialfootertext: '',
                    width: '800px'
                });
            }

            M.core.popupform.panel.setStdModContent(Y.WidgetStdMod.BODY, M.core.popupform.spinner, Y.WidgetStdMod.REPLACE);
            M.core.popupform.panel.show();

            // Get the href from the link.
            var href = this.get('href');

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
                        if (response.status === 200) {
                            var newcontent = '<div class="' + CSS.CONTENTWRAP + '">' + response.responseText + '</div>';
                            console.log(newcontent);
                            M.core.popupform.addContentAndRunScripts(newcontent);
                        } else {
                            console.log(response);
                            alert('Huh!');
                        }
                    }
                }
            };

            Y.io(href, config);
        },

        attachListeners: function() {
            M.core.popupform.listeners.push(Y.one(SELECTORS.CONTENTWRAP).delegate('click', M.core.popupform.ajaxFormSubmit, SELECTORS.SUBMITBUTTONS));
        },

        detachListeners: function() {
            while (M.core.popupform.listeners.length > 0) {
                var listener = M.core.popupform.listeners.pop();
                listener.detach();
            }
        },

        addContentAndRunScripts: function(content) {
            M.core.popupform.detachListeners();
            var newcontentnode = Y.Node.create(content);
            var bodynode = Y.one('body');
            // Adding the style tags to the dom should apply the css rules.
            bodynode.appendChild(newcontentnode.all('style'));
            M.core.popupform.panel.setStdModContent(Y.WidgetStdMod.BODY, newcontentnode, Y.WidgetStdMod.REPLACE);
            M.core.popupform.panel.centerDialogue();
            M.core.popupform.attachListeners();
            // Adding the script tags to the dom should run the javascript.
            // Do this with straight DOM as YUI sucks.
            newcontentnode.all('script').each(function (node) {
                var newscriptnode = document.createElement('script');
                if (node.get('src')) {
                    newscriptnode.src = node.get('src');
                } else {
                    newscriptnode.innerHTML = node.getContent();
                }
                document.body.appendChild(newscriptnode);
            });
        },

        ajaxFormSubmit: function(e) {
            e.preventDefault();
            console.log(this);
            M.core.popupform.detachListeners();

            var formnode = this.ancestor('form');
            var href = formnode.get('action');

            var config = {
                method: 'POST',
                form: {
                    id: formnode
                },
                on: {
                    complete: function(tid, response) {
                        M.core.popupform.panel.hide();
                    }
                }
            }

            Y.io(href, config);
            // Perform an ajax form submission instead, then close the window and trigger events on the opening page.
        }

    });

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
    requires : ['base', 'io-base', 'io-form', 'moodle-core-notification', 'json-parse']
});
