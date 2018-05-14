import { PMA_messages as PMA_messages } from '../variables//export_variables';
import { PMA_sprintf } from '../utils/sprintf';
import PMA_commonParams from '../variables/common_params';
/**
 * Displays a confirmation box before submitting a "DROP/DELETE/ALTER" query.
 * This function is called while clicking links
 *
 * @param theLink     object the link
 * @param theSqlQuery object the sql query to submit
 *
 * @return boolean  whether to run the query or not
 */
export function confirmLink (theLink, theSqlQuery) {
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (PMA_messages.strDoYouReally === '' || typeof(window.opera) !== 'undefined') {
        return true;
    }

    var is_confirmed = confirm(PMA_sprintf(PMA_messages.strDoYouReally, theSqlQuery));
    if (is_confirmed) {
        if (typeof(theLink.href) !== 'undefined') {
            theLink.href += PMA_commonParams.get('arg_separator') + 'is_js_confirmed=1';
        } else if (typeof(theLink.form) !== 'undefined') {
            theLink.form.action += '?is_js_confirmed=1';
        }
    }

    return is_confirmed;
} // end of the 'confirmLink()' function

export function getJSConfirmCommonParam (elem, params) {
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
