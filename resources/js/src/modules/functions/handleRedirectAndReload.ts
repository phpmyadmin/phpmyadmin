import { CommonParams } from '../common.ts';

/**
 * Handle redirect and reload flags sent as part of AJAX requests
 *
 * @param {Object} data ajax response data
 */
export default function handleRedirectAndReload (data) {
    if (parseInt(data.redirect_flag) === 1) {
        // add one more GET param to display session expiry msg
        if (window.location.href.indexOf('?') === -1) {
            window.location.href += '?session_expired=1';
        } else {
            window.location.href += CommonParams.get('arg_separator') + 'session_expired=1';
        }

        window.location.reload();
    } else if (parseInt(data.reload_flag) === 1) {
        window.location.reload();
    }
}
