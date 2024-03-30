import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';
import { CommonParams } from '../common.ts';

export function setConfigValue (value: number): void {
    $.ajax({
        url: 'index.php?route=/config/set',
        type: 'POST',
        dataType: 'json',
        data: {
            'ajax_request': true,
            server: CommonParams.get('server'),
            value: value,
        },
        success: function (data) {
            if (data.success !== true) {
                // Try to find a message to display
                if (data.error || data.message) {
                    ajaxShowMessage(data.error || data.message);
                }
            }
        }
    });
}
