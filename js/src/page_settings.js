/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

function showSettings (selector) {
    var buttons = {};
    buttons[Messages.strApply] = function () {
        $('.config-form').trigger('submit');
    };

    buttons[Messages.strCancel] = function () {
        $(this).dialog('close');
    };

    // Keeping a clone to restore in case the user cancels the operation
    var $clone = $(selector + ' .page_settings').clone(true);
    $(selector)
        .dialog({
            title: Messages.strPageSettings,
            width: 700,
            minHeight: 250,
            modal: true,
            open: function () {
                $(this).dialog('option', 'maxHeight', $(window).height() - $(this).offset().top);
            },
            close: function () {
                $(selector + ' .page_settings').replaceWith($clone);
            },
            buttons: buttons
        });
}

function showPageSettings () {
    showSettings('#page_settings_modal');
}

function showNaviSettings () {
    showSettings('#pma_navigation_settings');
}

AJAX.registerTeardown('page_settings.js', function () {
    $('#page_settings_icon').css('display', 'none');
    $('#page_settings_icon').off('click');
    $('#pma_navigation_settings_icon').off('click');
});

AJAX.registerOnload('page_settings.js', function () {
    if ($('#page_settings_modal').length) {
        $('#page_settings_icon').css('display', 'inline');
        $('#page_settings_icon').on('click', showPageSettings);
    }
    $('#pma_navigation_settings_icon').on('click', showNaviSettings);
});
