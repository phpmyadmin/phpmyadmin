/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

function showSettings (selector) {
    // Keeping a clone to restore in case the user cancels the operation
    var $clone = $(selector + ' .page_settings').clone(true);

    $('#pageSettingsModalApplyButton').on('click', function () {
        $('.config-form').trigger('submit');
    });

    $('#pageSettingsModalCloseButton,#pageSettingsModalCancelButton').on('click', function () {
        $(selector + ' .page_settings').replaceWith($clone);
        $('#pageSettingsModal').modal('hide');
    });

    $('#pageSettingsModal').modal('show');
    $('#pageSettingsModal').find('.modal-body').first().html($(selector));
    $(selector).css('display', 'block');
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
