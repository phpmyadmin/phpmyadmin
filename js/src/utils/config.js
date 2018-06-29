/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import CommonParams from '../variables/common_params';
import { PMA_ajaxShowMessage } from '../utils/show_ajax_messages';

/**
 * Sets a configuration value.
 *
 * A configuration value may be set in both browser's local storage and
 * remotely in server's configuration table.
 *
 * If the `only_local` argument is `true`, the value is store is stored only in
 * browser's local storage and may be lost if the user resets his browser's
 * settings.
 *
 * NOTE: Depending on server's configuration, the configuration table may be or
 * not persistent.
 *
 * @access public
 *
 * @param  {string}     key         Configuration key.
 *
 * @param  {object}     value       Configuration value.
 *
 * @param  {boolean}    onlyLocal  Configuration type.
 *
 * @return {void}
 */
function configSet (key, value, onlyLocal) {
    onlyLocal = (typeof onlyLocal !== 'undefined') ? onlyLocal : false;
    var serialized = JSON.stringify(value);
    localStorage.setItem(key, serialized);
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        dataType: 'json',
        data: {
            key: key,
            type: 'config-set',
            server: CommonParams.get('server'),
            value: serialized,
        },
        success: function (data) {
            // Updating value in local storage.
            if (! data.success) {
                PMA_ajaxShowMessage(data.message);
            }
            // Eventually, call callback.
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
 * @access public
 *
 * @param  {string}     key         Configuration key.
 *
 * @param  {boolean}    cached      Configuration type.
 *
 * @return {object}                 Configuration value.
 */
function configGet (key, cached) {
    cached = (typeof cached !== 'undefined') ? cached : true;
    var value = localStorage.getItem(key);
    if (cached && value !== undefined && value !== null) {
        return JSON.parse(value);
    }

    // Result not found in local storage or ignored.
    // Hitting the server.
    $.ajax({
        // TODO: This is ugly, but usually when a configuration is needed,
        // processing cannot continue until that value is found.
        // Another solution is to provide a callback as a parameter.
        async: false,
        url: 'ajax.php',
        type: 'POST',
        dataType: 'json',
        data: {
            type: 'config-get',
            server: CommonParams.get('server'),
            key: key
        },
        success: function (data) {
            // Updating value in local storage.
            if (data.success) {
                localStorage.setItem(key, JSON.stringify(data.value));
            } else {
                PMA_ajaxShowMessage(data.message);
            }
            // Eventually, call callback.
        }
    });
    return JSON.parse(localStorage.getItem(key));
}

/**
 * Module export
 */
export {
    configGet,
    configSet
};
