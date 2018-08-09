/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from '../variables/export_variables';

/**
 * @param {Element} selector     jQuery element for which setting need to be show
 *
 * @access private
 *
 * @returns {void}
 */
function showSettings (selector) {
    var buttons = {};
    buttons[messages.strApply] = function () {
        $('.config-form').submit();
    };

    buttons[messages.strCancel] = function () {
        $(this).dialog('close');
    };

    // Keeping a clone to restore in case the user cancels the operation
    var $clone = $(selector + ' .page_settings').clone(true);
    $(selector)
        .dialog({
            title: messages.strPageSettings,
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

/**
 * @access public
 *
 * @return {void}
 */
function showPageSettings () {
    showSettings('#page_settings_modal');
}

/**
 * @access public
 *
 * @return {void}
 */
function showNaviSettings () {
    showSettings('#pma_navigation_settings');
}

/**
 * Module export
 */
export {
    showNaviSettings,
    showPageSettings
};
