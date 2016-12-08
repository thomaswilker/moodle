YUI.add('moodle-theme_ouaclean_navtabs', function(Y) {

    // Define a name space to call
    M.theme_ouaclean_navtabs = M.theme_ouaclean_navtabs || {};
    M.theme_ouaclean_navtabs.tabs = {
        init: function() {
            if (Y.one('div.course-content')) {
                Y.one('div.course-content').insert(Y.one(".block_tabbed_navigation"), 'before');
                Y.one(".block_tabbed_navigation").append(Y.one('div.course-content'));
                Y.one('.block_tabbed_navigation div.content').setAttribute('role', 'navigation');
                Y.one('.block_tabbed_navigation .nav-tabs').show();

                if (Y.one('ul.topics')) {
                    Y.one('ul.topics').addClass('tab-content');
                } else if (Y.one('ul.weeks')) {
                    Y.one('ul.weeks').addClass('tab-content');
                }

                Y.all('li.section.main').addClass('tab-pane');
                Y.all('li.section.main').setAttribute('role', 'tabpanel');
                Y.one('li#section-1').addClass('active');
                Y.one('li#section-0').removeClass('tab-pane');
                Y.one('li#section-0').setAttribute('role', '');

                $('ul.nav-tabs').tabCollapse();
            }
        },
    };
}, '@VERSION@', {
    requires: ['node']
});