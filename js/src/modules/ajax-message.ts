import $ from 'jquery';
import tooltip from './tooltip.ts';
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
 * @param {string|null} message string containing the message to be shown.
 *                              optional, defaults to 'Loading...'
 * @param {any} timeout         number of milliseconds for the message to be visible
 *                              optional, defaults to 5000. If set to 'false', the
 *                              notification will never disappear
 * @param {string|null} type    string to dictate the type of message shown.
 *                              optional, defaults to normal notification.
 *                              If set to 'error', the notification will show message
 *                              with red background.
 *                              If set to 'success', the notification will show with
 *                              a green background.
 * @return {JQuery<Element>}   jQuery Element that holds the message div
 *                              this object can be passed to ajaxRemoveMessage()
 *                              to remove the notification
 */
const ajaxShowMessage = function (message = null, timeout = null, type = null) {
    var msg = message;
    var newTimeOut = timeout;
    /**
     * @var self_closing Whether the notification will automatically disappear
     */
    var selfClosing = true;
    /**
     * @var dismissable Whether the user will be able to remove
     *                  the notification by clicking on it
     */
    var dismissable = true;
    // Handle the case when a empty data.message is passed.
    // We don't want the empty message
    if (msg === '') {
        return true;
    } else if (! msg) {
        // If the message is undefined, show the default
        msg = window.Messages.strLoading;
        dismissable = false;
        selfClosing = false;
    } else if (msg === window.Messages.strProcessingRequest) {
        // This is another case where the message should not disappear
        dismissable = false;
        selfClosing = false;
    }

    // Figure out whether (or after how long) to remove the notification
    if (newTimeOut === undefined || newTimeOut === null) {
        newTimeOut = 5000;
    } else if (newTimeOut === false) {
        selfClosing = false;
    }

    // Determine type of message, add styling as required
    if (type === 'error') {
        msg = '<div class="alert alert-danger" role="alert">' + msg + '</div>';
    } else if (type === 'success') {
        msg = '<div class="alert alert-success" role="alert">' + msg + '</div>';
    }

    // Create a parent element for the AJAX messages, if necessary
    if ($('#loading_parent').length === 0) {
        $('<div id="loading_parent"></div>')
            .prependTo('#page_content');
    }

    // Update message count to create distinct message elements every time
    ajaxMessageCount++;
    // Remove all old messages, if any
    $('span.ajax_notification[id^=ajax_message_num]').remove();
    /**
     * @var $retval    a jQuery object containing the reference
     *                 to the created AJAX message
     */
    var $retval = $(
        '<span class="ajax_notification" id="ajax_message_num_' +
        ajaxMessageCount +
        '"></span>'
    )
        .hide()
        .appendTo('#loading_parent')
        .html(msg)
        .show();
    // If the notification is self-closing we should create a callback to remove it
    if (selfClosing) {
        $retval
            .delay(newTimeOut)
            .fadeOut('medium', function () {
                if ($(this).is(':data(tooltip)')) {
                    $(this).uiTooltip('destroy');
                }

                // Remove the notification
                $(this).remove();
            });
    }

    // If the notification is dismissable we need to add the relevant class to it
    // and add a tooltip so that the users know that it can be removed
    if (dismissable) {
        $retval.addClass('dismissable').css('cursor', 'pointer');
        /**
         * Add a tooltip to the notification to let the user know that they
         * can dismiss the ajax notification by clicking on it.
         */
        tooltip($retval, 'span', window.Messages.strDismiss);
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
        $thisMessageBox
            .stop(true, true)
            .fadeOut('medium');

        if ($thisMessageBox.is(':data(tooltip)')) {
            $thisMessageBox.uiTooltip('destroy');
        } else {
            $thisMessageBox.remove();
        }
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
