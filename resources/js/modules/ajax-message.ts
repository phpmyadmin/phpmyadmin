import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import highlightSql from './sql-highlight.ts';

/**
 * Number of AJAX messages shown since page load.
 * @type {number}
 */
let ajaxMessageCount = 0;

/**
 * Show a message on the top of the page for an Ajax request
 *
 * Sample usage:
 *
 * 1) var $msg = ajaxShowMessage();
 * This will show a message that reads "Loading...". Such a message will not
 * disappear automatically and cannot be dismissed by the user. To remove this
 * message either the ajaxRemoveMessage($msg) function must be called or
 * another message must be show with ajaxShowMessage() function.
 *
 * 2) var $msg = ajaxShowMessage(window.Messages.strProcessingRequest);
 * This is a special case. The behaviour is same as above,
 * just with a different message
 *
 * 3) var $msg = ajaxShowMessage('The operation was successful');
 * This will show a message that will disappear automatically and it can also
 * be dismissed by the user.
 *
 * 4) var $msg = ajaxShowMessage('Some error', false);
 * This will show a message that will not disappear automatically, but it
 * can be dismissed by the user after they have finished reading it.
 *
 * @param message string containing the message to be shown.
 *                optional, defaults to 'Loading...'
 * @param timeout number of milliseconds for the message to be visible
 *                optional, defaults to 5000. If set to 'false', the notification will never disappear
 * @param type    string to dictate the type of message shown. optional, defaults to normal notification.
 *                If set to 'error', the notification will show message with red background.
 *                If set to 'warning', the notification will show with a yellow background.
 *                If set to 'success', the notification will show with a green background.
 * @return        jQuery Element that holds the message div this object can be passed
 *                to ajaxRemoveMessage() to remove the notification
 */
const ajaxShowMessage = function (
    message: string|null = null,
    timeout: number|false|null = null,
    type: 'error'|'warning'|'success'|null = null,
): JQuery<HTMLElement>|true {
    // Handle the case when a empty data.message is passed.
    // We don't want the empty message
    if (message === '') {
        return true;
    }

    // Create a parent element for the AJAX messages, if necessary
    if ($('#loading_parent').length === 0) {
        $('<div id="loading_parent"></div>').prependTo('#page_content');
    }

    // Remove all old messages, if any
    $('[role="tooltip"]').remove();
    $('span.ajax_notification[id^=ajax_message_num]').remove();

    const msg = message ?? window.Messages.strLoading;
    // Determine type of message, add styling as required
    let html = msg;
    if (type === 'error') {
        html = `<div class="alert alert-danger" role="alert">${msg}</div>`;
    } else if (type === 'warning') {
        html = `<div class="alert alert-warning" role="alert">${msg}</div>`;
    } else if (type === 'success') {
        html = `<div class="alert alert-success" role="alert">${msg}</div>`;
    }

    /** A jQuery object containing the reference to the created AJAX message with unique id */
    const $retval = $(
        `<span class="ajax_notification" id="ajax_message_num_${++ajaxMessageCount}"></span>`,
    )
        .hide()
        .appendTo('#loading_parent')
        .html(html)
        .show();
    // If the notification is self-closing we should create a callback to remove it
    /** Whether the notification will automatically disappear */
    const selfClosing =
        msg !== window.Messages.strLoading &&
        msg !== window.Messages.strProcessingRequest &&
        timeout !== false;
    if (selfClosing) {
        $retval
            .delay(timeout ?? 5000)
            .fadeOut('medium', function () {
                bootstrap.Tooltip.getInstance(this)?.dispose();

                // Remove the notification
                $(this).remove();
            });
    }

    // If the notification is dismissable we need to add the relevant class to it
    // and add a tooltip so that the users know that it can be removed
    const dismissable =
        msg !== window.Messages.strLoading &&
        msg !== window.Messages.strProcessingRequest;
    if (dismissable) {
        $retval.addClass('dismissable').css('cursor', 'pointer');
        /**
         * Add a tooltip to the notification to let the user know that they
         * can dismiss the ajax notification by clicking on it.
         */
        bootstrap.Tooltip.getOrCreateInstance($retval.get(0), { title: window.Messages.strDismiss })
            .setContent({ '.tooltip-inner': window.Messages.strDismiss });
    }

    // Hide spinner if this is not a loading message
    if (msg !== window.Messages.strLoading) {
        $retval.css('background-image', 'none');
    }

    highlightSql($retval);

    return $retval;
};

/**
 * Removes the message shown for an Ajax operation when it's completed
 *
 * @param {JQuery} $thisMessageBox Element that holds the notification
 */
const ajaxRemoveMessage = function ($thisMessageBox: JQuery | boolean): void {
    if ($thisMessageBox !== undefined && typeof $thisMessageBox !== 'boolean' && $thisMessageBox instanceof $) {
        bootstrap.Tooltip.getInstance($thisMessageBox.get(0))?.dispose();

        $thisMessageBox
            .stop(true, true)
            .fadeOut('medium');

        $thisMessageBox.remove();
    }
};

declare global {
    interface Window {
        getAjaxMessageCount: () => number;
        ajaxShowMessage: typeof ajaxShowMessage;
    }
}

window.getAjaxMessageCount = () => ajaxMessageCount;
window.ajaxShowMessage = ajaxShowMessage;

export { ajaxShowMessage, ajaxRemoveMessage };
