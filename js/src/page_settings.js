/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 */

/**
 * Module import
 */
import {
    showNaviSettings,
    showPageSettings
} from './functions/page_settings';

/**
 * @package PhpMyAdmin
 *
 * Page Settings
 */

/**
 * Unbind all event handlers before tearing down a page.
 */
function teardownPageSettings () {
    $('#page_settings_icon').css('display', 'none');
    $('#page_settings_icon').off('click');
    $('#pma_navigation_settings_icon').off('click');
}

/**
 * Binding event handler on page load.
 */
function onloadPageSettings () {
    if ($('#page_settings_modal').length) {
        $('#page_settings_icon').css('display', 'inline');
        $('#page_settings_icon').on('click', showPageSettings);
    }
    $('#pma_navigation_settings_icon').on('click', showNaviSettings);
}

export {
    teardownPageSettings,
    onloadPageSettings
};
