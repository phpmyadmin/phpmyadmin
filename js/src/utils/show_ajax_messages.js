/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Functions for adding and removing ajax messages
 */

/**
 * Module imports
 */
import { PMA_Messages as messages } from '../variables/export_variables';
import { PMA_highlightSQL } from './sql';

/**
 * @var {int} ajaxMessageCount   Number of AJAX messages shown since page load
 */
let ajaxMessageCount = 0;

/**
 * Create a jQuery UI tooltip
 *
 * @access public
 *
 * @param $elements     jQuery object representing the elements
 *
 * @param item          the item
 *                      (see https://api.jqueryui.com/tooltip/#option-items)
 * @param myContent     content of the tooltip
 *
 * @param additionalOptions to override the default options
 *
 */
function PMA_tooltip ($elements, item, myContent, additionalOptions) {
    if ($('#no_hint').length > 0) {
        return;
    }

    var defaultOptions = {
        content: myContent,
        items:  item,
        tooltipClass: 'tooltip',
        track: true,
        show: false,
        hide: false
    };

    $elements.tooltip($.extend(true, defaultOptions, additionalOptions));
}

/**
 * Show a message on the top of the page for an Ajax request
 *
 * Sample usage:
 *
 * 1) var $msg = PMA_ajaxShowMessage();
 * This will show a message that reads "Loading...". Such a message will not
 * disappear automatically and cannot be dismissed by the user. To remove this
 * message either the PMA_ajaxRemoveMessage($msg) function must be called or
 * another message must be show with PMA_ajaxShowMessage() function.
 *
 * 2) var $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
 * This is a special case. The behaviour is same as above,
 * just with a different message
 *
 * 3) var $msg = PMA_ajaxShowMessage('The operation was successful');
 * This will show a message that will disappear automatically and it can also
 * be dismissed by the user.
 *
 * 4) var $msg = PMA_ajaxShowMessage('Some error', false);
 * This will show a message that will not disappear automatically, but it
 * can be dismissed by the user after he has finished reading it.
 *
 * @access public
 *
 * @param string  message     string containing the message to be shown.
 *                              optional, defaults to 'Loading...'
 * @param mixed   timeout     number of milliseconds for the message to be visible
 *                              optional, defaults to 5000. If set to 'false', the
 *                              notification will never disappear
 * @param string  type        string to dictate the type of message shown.
 *                              optional, defaults to normal notification.
 *                              If set to 'error', the notification will show message
 *                              with red background.
 *                              If set to 'success', the notification will show with
 *                              a green background.
 * @return jQuery object       jQuery Element that holds the message div
 *                              this object can be passed to PMA_ajaxRemoveMessage()
 *                              to remove the notification
 */

const PMA_ajaxShowMessage = (message, timeout, type) => {
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
    if (message === '') {
        return true;
    } else if (! message) {
        // If the message is undefined, show the default
        message = messages.strLoading;
        dismissable = false;
        selfClosing = false;
    } else if (message === messages.strProcessingRequest) {
        // This is another case where the message should not disappear
        dismissable = false;
        selfClosing = false;
    }
    // Figure out whether (or after how long) to remove the notification
    if (timeout === undefined) {
        timeout = 5000;
    } else if (timeout === false) {
        selfClosing = false;
    }
    // Determine type of message, add styling as required
    if (type === 'error') {
        message = '<div class="error">' + message + '</div>';
    } else if (type === 'success') {
        message = '<div class="success">' + message + '</div>';
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
     * @var    $retval    a jQuery object containing the reference
     *                    to the created AJAX message
     */
    var $retval = $(
        '<span class="ajax_notification" id="ajax_message_num_' +
            ajaxMessageCount +
            '"></span>'
    )
        .hide()
        .appendTo('#loading_parent')
        .html(message)
        .show();
    // If the notification is self-closing we should create a callback to remove it
    if (selfClosing) {
        $retval
            .delay(timeout)
            .fadeOut('medium', function () {
                if ($(this).is(':data(tooltip)')) {
                    $(this).tooltip('destroy');
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
         * Add a tooltip to the notification to let the user know that (s)he
         * can dismiss the ajax notification by clicking on it.
         */
        PMA_tooltip(
            $retval,
            'span',
            messages.strDismiss
        );
    }
    PMA_highlightSQL($retval);

    return $retval;
};

/**
 * Removes the message shown for an Ajax operation when it's completed
 *
 * @access public
 *
 * @param jQuery object   jQuery Element that holds the notification
 *
 * @return nothing
 */
function PMA_ajaxRemoveMessage ($thisMsgbox) {
    if ($thisMsgbox !== undefined && $thisMsgbox instanceof $) {
        $thisMsgbox
            .stop(true, true)
            .fadeOut('medium');
        if ($thisMsgbox.is(':data(tooltip)')) {
            $thisMsgbox.tooltip('destroy');
        } else {
            $thisMsgbox.remove();
        }
    }
}

/**
 * Module export
 */
export {
    PMA_ajaxRemoveMessage,
    PMA_ajaxShowMessage,
    PMA_tooltip
};
