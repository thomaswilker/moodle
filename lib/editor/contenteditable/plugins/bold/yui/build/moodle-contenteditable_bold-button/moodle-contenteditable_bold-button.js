YUI.add('moodle-contenteditable_bold-button', function (Y, NAME) {

var BOLD = function() {
    BOLD.superclass.constructor.apply(this, arguments);
};

BOLD.NAME = 'contenteditable_bold';
BOLD.ATTRS = {};

Y.extend(BOLD, Y.Base, {
    initializer : function(params) {
        var toolbar = Y.one('#' + params.elementid + '_toolbar');
        var button = Y.Node.create('<button><b>B</b></button');

        toolbar.append(button);
    }
});

M.contenteditable_bold = M.contenteditable_bold || {};
M.contenteditable_bold.init = function(id, params) {
    return new BOLD(id, params);
};


}, '@VERSION@', {"requires": ["node"]});
