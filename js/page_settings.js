/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

function show_settings() {
    var buttons = {};
    buttons[PMA_messages.strApply] = function() {
        $('.config-form').submit();
    };

    buttons[PMA_messages.strCancel] = function () {
        $(this).dialog('close');
    };

    $('.page_settings_modal')
    .dialog({
        title: PMA_messages.strPageSettings,
        width: 700,
        minHeight: 250,
        modal: true,
        open: function() {
            $(this).dialog('option', 'maxHeight', $(window).height() - $(this).offset().top);
        },
        buttons: buttons
    });
}

AJAX.registerTeardown('page_settings.js', function () {
    $('#page_settings_icon').css('display', 'none');
    $('#page_settings_icon').unbind('click');
});

AJAX.registerOnload('page_settings.js', function () {
    $('#page_settings_icon').css('display', 'inline');
    $('#page_settings_icon').bind('click', show_settings);
});
