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
