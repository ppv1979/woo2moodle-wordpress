// purpose: register a woo2moodle button in the rich editor in the admin interface
(function() {
    tinymce.create('tinymce.plugins.woo2m', {
        init : function(ed, url) {
            ed.addButton('woo2m', {
                title : 'WooCommerce 2 Moodle',
                image : url+'/icon.svg',
                onclick : function() {
                     ed.selection.setContent('[woo2moodle cohort="" group="" course="" class="woo2moodle" target="_self" authtext="" activity="0"]' + ed.selection.getContent() + '[/woo2moodle]');
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('woo2m', tinymce.plugins.woo2m);
})();
