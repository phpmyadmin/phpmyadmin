import $ from 'jquery';
import { CommonParams } from '../common.ts';

/**
 * @param {HTMLElement} elem
 * @param {string} parameters
 * @return {string}
 */
export default function getJsConfirmCommonParam (elem, parameters) {
    var $elem = $(elem);
    var params = parameters;
    var sep = CommonParams.get('arg_separator');
    if (params) {
        // Strip possible leading ?
        if (params.startsWith('?')) {
            params = params.substring(1);
        }

        params += sep;
    } else {
        params = '';
    }

    params += 'is_js_confirmed=1' + sep + 'ajax_request=true' + sep + 'fk_checks=' + ($elem.find('#fk_checks').is(':checked') ? 1 : 0);

    return params;
}
