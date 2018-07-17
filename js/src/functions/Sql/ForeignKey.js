import { PMA_getImage } from '../get_image';
import PMA_commonParams from '../../variables/common_params';
import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
/**
 * Get checkbox for foreign key checks
 *
 * @return string
 */
export function getForeignKeyCheckboxLoader () {
    var html = '';
    html    += '<div>';
    html    += '<div class="load-default-fk-check-value">';
    html    += PMA_getImage('ajax_clock_small');
    html    += '</div>';
    html    += '</div>';
    return html;
}

export function loadForeignKeyCheckbox () {
    // Load default foreign key check value
    var params = {
        'ajax_request': true,
        'server': PMA_commonParams.get('server'),
        'get_default_fk_check_value': true
    };
    $.get('sql.php', params, function (data) {
        var html = '<input type="hidden" name="fk_checks" value="0" />' +
            '<input type="checkbox" name="fk_checks" id="fk_checks"' +
            (data.default_fk_check_value ? ' checked="checked"' : '') + ' />' +
            '<label for="fk_checks">' + PMA_messages.strForeignKeyCheck + '</label>';
        $('.load-default-fk-check-value').replaceWith(html);
    });
}

function getJSConfirmCommonParam (elem, params) {
    var $elem = $(elem);
    var sep = PMA_commonParams.get('arg_separator');
    if (params) {
        // Strip possible leading ?
        if (params.substring(0,1) === '?') {
            params = params.substr(1);
        }
        params += sep;
    } else {
        params = '';
    }
    params += 'is_js_confirmed=1' + sep + 'ajax_request=true' + sep + 'fk_checks=' + ($elem.find('#fk_checks').is(':checked') ? 1 : 0);
    return params;
}
