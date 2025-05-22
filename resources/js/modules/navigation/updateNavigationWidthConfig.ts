import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';
import { CommonParams } from '../common.ts';

export default function updateNavigationWidthConfig (value: number): void {
    $.ajax({
        url: 'index.php?route=/navigation/update-width',
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
