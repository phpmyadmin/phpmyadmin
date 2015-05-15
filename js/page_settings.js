/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used for page related settings
 * @name            Page related settings
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

AJAX.registerTeardown('page_settings.js', function () {
    $('#page_settings_icon').css('display', 'none');
    $('#page_settings_icon').unbind('click');
});

AJAX.registerOnload('page_settings.js', function () {
    $('#page_settings_icon').css('display', 'inline');
    $('#page_settings_icon').bind('click', show_settings);
});

function show_settings() {
    $('.page_settings_modal')
    .dialog({
        title: "Page related settings",
        width: 700,
        minHeight: 250,
        modal: true,
        buttons: {
            "Apply": function() {
                $('.config-form').submit();
            },
            "Cancel": function () {
                $(this).dialog('close');
            }
        }
    });
}
