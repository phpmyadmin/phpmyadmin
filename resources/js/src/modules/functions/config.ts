import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';
import { CommonParams } from '../common.ts';

/**
 * Sets a configuration value.
 *
 * A configuration value may be set in both browser's local storage and
 * remotely in server's configuration table.
 *
 * NOTE: Depending on server's configuration, the configuration table may be or
 * not persistent.
 *
 * @param {string} key   Configuration key.
 * @param {object} value Configuration value.
 */
export function setConfigValue (key, value): void {
    // Updating value in local storage.
    var serialized = JSON.stringify(value);
    localStorage.setItem(key, serialized);

    $.ajax({
        url: 'index.php?route=/config/set',
        type: 'POST',
        dataType: 'json',
        data: {
            'ajax_request': true,
            key: key,
            server: CommonParams.get('server'),
            value: serialized,
        },
        success: function (data) {
            if (data.success !== true) {
                // Try to find a message to display
                if (data.error || data.message || false) {
                    ajaxShowMessage(data.error || data.message);
                }
            }
        }
    });
}

/**
 * Gets a configuration value. A configuration value will be searched in
 * browser's local storage first and if not found, a call to the server will be
 * made.
 *
 * If value should not be cached and the up-to-date configuration value from
 * right from the server is required, the third parameter should be `false`.
 *
 * @param {string}   key             Configuration key.
 * @param {boolean}  cached          Configuration type.
 * @param {Function} successCallback The callback to call after the value is successfully received
 * @param {Function} failureCallback The callback to call when the value can not be received
 */
export function getConfigValue (key, cached, successCallback, failureCallback = undefined): void {
    var isCached = (typeof cached !== 'undefined') ? cached : true;
    var value = localStorage.getItem(key);
    if (isCached && value !== undefined && value !== null) {
        return JSON.parse(value);
    }

    // Result not found in local storage or ignored.
    // Hitting the server.
    $.ajax({
        url: 'index.php?route=/config/get',
        type: 'POST',
        dataType: 'json',
        data: {
            'ajax_request': true,
            server: CommonParams.get('server'),
            key: key
        },
        success: function (data) {
            if (data.success !== true) {
                // Try to find a message to display
                if (data.error || data.message || false) {
                    ajaxShowMessage(data.error || data.message);
                }

                // Call the callback if it is defined
                if (typeof failureCallback === 'function') {
                    failureCallback();
                }

                // return here, exit non success mode
                return;
            }

            // Updating value in local storage.
            localStorage.setItem(key, JSON.stringify(data.value));
            // Call the callback if it is defined
            if (typeof successCallback === 'function') {
                // Feed it the value previously saved like on async mode
                successCallback(JSON.parse(localStorage.getItem(key)));
            }
        }
    });
}
