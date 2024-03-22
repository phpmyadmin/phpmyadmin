import $ from 'jquery';

/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQueryUI
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
    // @ts-ignore
    $('#pageSettingsModal').find('.modal-body').first().html($(selector));
    $(selector).css('display', 'block');
}

function showPageSettings () {
    showSettings('#page_settings_modal');
}

function showNaviSettings () {
    showSettings('#pma_navigation_settings');
}

const PageSettings = {
    off: (): void => {
        $('#page_settings_icon').css('display', 'none');
        $('#page_settings_icon').off('click');
        $('#pma_navigation_settings_icon').off('click');
    },

    on: (): void => {
        if ($('#page_settings_modal').length) {
            $('#page_settings_icon').css('display', 'inline');
            $('#page_settings_icon').on('click', showPageSettings);
        }

        $('#pma_navigation_settings_icon').on('click', showNaviSettings);
    },
};

export { PageSettings };
