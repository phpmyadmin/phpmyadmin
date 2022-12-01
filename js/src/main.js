import $ from 'jquery';
import { AJAX } from './modules/ajax.js';
import { Functions } from './modules/functions.js';
import { KeyHandlerEvents } from './modules/keyhandler.js';
import { Navigation } from './navigation.js';
import { PageSettings } from './page_settings.js';
import { crossFramingProtection } from './modules/cross_framing_protection.js';
import { Indexes } from './indexes.js';
import { Config } from './config.js';

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

crossFramingProtection();

AJAX.registerTeardown('main.js', Config.off());
AJAX.registerOnload('main.js', Config.on());

$.ajaxPrefilter(Functions.addNoCacheToAjaxRequests());

AJAX.registerTeardown('main.js', Functions.off());
AJAX.registerOnload('main.js', Functions.on());

$(Functions.dismissNotifications());
$(Functions.initializeMenuResizer());
$(Functions.floatingMenuBar());
$(Functions.breadcrumbScrollToTop());

$(Navigation.onload());

AJAX.registerTeardown('main.js', Indexes.off());
AJAX.registerOnload('main.js', Indexes.on());

$(() => Functions.checkNumberOfFields());

AJAX.registerTeardown('main.js', () => {
    PageSettings.off();
});

AJAX.registerOnload('main.js', () => {
    PageSettings.on();
});
