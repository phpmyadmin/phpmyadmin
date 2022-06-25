window.AJAX.registerOnload('functions.js', () => window.AJAX.removeSubmitEvents());
$(window.AJAX.loadEventHandler());

/**
 * Attach a generic event handler to clicks on pages and submissions of forms.
 */
$(document).on('click', 'a', window.AJAX.requestHandler);
$(document).on('submit', 'form', window.AJAX.requestHandler);

$(document).on('ajaxError', window.AJAX.getFatalErrorHandler());
