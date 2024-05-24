import $ from 'jquery';

function createPageSettingsModal (): void {
    if ($('#pageSettingsModal').length > 0) {
        return;
    }

    const pageSettingsModalTemplate = '<div class="modal fade" id="pageSettingsModal" tabindex="-1" aria-labelledby="pageSettingsModalLabel" aria-hidden="true">' +
        '  <div class="modal-dialog modal-lg" id="pageSettingsModalDialog">' +
        '    <div class="modal-content">' +
        '      <div class="modal-header">' +
        '        <h5 class="modal-title" id="pageSettingsModalLabel">' + window.Messages.strPageSettings + '</h5>' +
        '        <button type="button" class="btn-close" id="pageSettingsModalCloseButton" aria-label="' + window.Messages.strClose + '"></button>' +
        '      </div>' +
        '      <div class="modal-body"></div>' +
        '      <div class="modal-footer">' +
        '        <button type="button" class="btn btn-secondary" id="pageSettingsModalApplyButton">' + window.Messages.strApply + '</button>' +
        '        <button type="button" class="btn btn-secondary" id="pageSettingsModalCancelButton">' + window.Messages.strCancel + '</button>' +
        '      </div>' +
        '    </div>' +
        '  </div>' +
        '</div>';
    $(pageSettingsModalTemplate).appendTo('body');
}

/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQueryUI
 */

function showSettings (selector) {
    createPageSettingsModal();

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
