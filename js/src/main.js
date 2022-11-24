import $ from 'jquery';
import { AJAX } from './ajax.js';
import { Functions } from './functions.js';
import { Navigation } from './navigation.js';

/* global Indexes */

AJAX.registerOnload('functions.js', () => AJAX.removeSubmitEvents());
$(AJAX.loadEventHandler());

/**
 * Attach a generic event handler to clicks on pages and submissions of forms.
 */
$(document).on('click', 'a', AJAX.requestHandler);
$(document).on('submit', 'form', AJAX.requestHandler);

$(document).on('ajaxError', AJAX.getFatalErrorHandler());

AJAX.registerTeardown('keyhandler.js', window.KeyHandlerEvents.off());
AJAX.registerOnload('keyhandler.js', window.KeyHandlerEvents.on());

window.crossFramingProtection();

AJAX.registerTeardown('config.js', window.Config.off());
AJAX.registerOnload('config.js', window.Config.on());

$.ajaxPrefilter(Functions.addNoCacheToAjaxRequests());

AJAX.registerTeardown('functions.js', Functions.off());
AJAX.registerOnload('functions.js', Functions.on());

$(Functions.dismissNotifications());
$(Functions.initializeMenuResizer());
$(Functions.floatingMenuBar());
$(Functions.breadcrumbScrollToTop());

$(Navigation.onload());

AJAX.registerTeardown('indexes.js', Indexes.off());
AJAX.registerOnload('indexes.js', Indexes.on());

$(() => Functions.checkNumberOfFields());

AJAX.registerTeardown('page_settings.js', window.PageSettings.off());
AJAX.registerOnload('page_settings.js', window.PageSettings.on());
