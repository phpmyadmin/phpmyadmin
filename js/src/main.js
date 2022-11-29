import $ from 'jquery';
import { AJAX } from './ajax.js';
import { Functions } from './functions.js';
import { KeyHandlerEvents } from './keyhandler.js';
import { Navigation } from './navigation.js';
import { PageSettings } from './page_settings.js';

/* global Indexes */

AJAX.registerOnload('main.js', () => AJAX.removeSubmitEvents());
$(AJAX.loadEventHandler());

/**
 * Attach a generic event handler to clicks on pages and submissions of forms.
 */
$(document).on('click', 'a', AJAX.requestHandler);
$(document).on('submit', 'form', AJAX.requestHandler);

$(document).on('ajaxError', AJAX.getFatalErrorHandler());

AJAX.registerTeardown('main.js', KeyHandlerEvents.off());
AJAX.registerOnload('main.js', KeyHandlerEvents.on());

window.crossFramingProtection();

AJAX.registerTeardown('config.js', window.Config.off());
AJAX.registerOnload('config.js', window.Config.on());

$.ajaxPrefilter(Functions.addNoCacheToAjaxRequests());

AJAX.registerTeardown('main.js', Functions.off());
AJAX.registerOnload('main.js', Functions.on());

$(Functions.dismissNotifications());
$(Functions.initializeMenuResizer());
$(Functions.floatingMenuBar());
$(Functions.breadcrumbScrollToTop());

$(Navigation.onload());

AJAX.registerTeardown('indexes.js', Indexes.off());
AJAX.registerOnload('indexes.js', Indexes.on());

$(() => Functions.checkNumberOfFields());

AJAX.registerTeardown('main.js', () => {
    PageSettings.off();
});

AJAX.registerOnload('main.js', () => {
    PageSettings.on();
});
