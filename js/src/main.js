/* global Indexes */
/* global Navigation */

window.AJAX.registerOnload('functions.js', () => window.AJAX.removeSubmitEvents());
$(window.AJAX.loadEventHandler());

/**
 * Attach a generic event handler to clicks on pages and submissions of forms.
 */
$(document).on('click', 'a', window.AJAX.requestHandler);
$(document).on('submit', 'form', window.AJAX.requestHandler);

$(document).on('ajaxError', window.AJAX.getFatalErrorHandler());

window.AJAX.registerTeardown('keyhandler.js', window.KeyHandlerEvents.off());
window.AJAX.registerOnload('keyhandler.js', window.KeyHandlerEvents.on());

window.crossFramingProtection();

window.AJAX.registerTeardown('config.js', window.Config.off());
window.AJAX.registerOnload('config.js', window.Config.on());

$.ajaxPrefilter(Functions.addNoCacheToAjaxRequests());

window.AJAX.registerTeardown('functions.js', Functions.off());
window.AJAX.registerOnload('functions.js', Functions.on());

$(Functions.dismissNotifications());
$(Functions.initializeMenuResizer());
$(Functions.floatingMenuBar());
$(Functions.breadcrumbScrollToTop());

$(Navigation.onload());

window.AJAX.registerTeardown('indexes.js', Indexes.off());
window.AJAX.registerOnload('indexes.js', Indexes.on());

$(() => Functions.checkNumberOfFields());

window.AJAX.registerTeardown('page_settings.js', window.PageSettings.off());
window.AJAX.registerOnload('page_settings.js', window.PageSettings.on());
