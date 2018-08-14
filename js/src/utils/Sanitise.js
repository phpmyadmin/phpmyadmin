/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * @access public
 *
 * @param {string} unsafe    Unsafe html which needs to be escaped
 *
 * @return {string}
 *
 * HTML escaping
 */
function escapeHtml (unsafe) {
    if (typeof(unsafe) !== 'undefined') {
        return unsafe
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    } else {
        return false;
    }
}

/**
 * @access public
 *
 * @param {string} unsafe     Unsafe javascript
 *
 * @return {string}
 */
function escapeJsString (unsafe) {
    if (typeof(unsafe) !== 'undefined') {
        return unsafe
            .toString()
            .replace('\x00', '')
            .replace('\\', '\\\\')
            .replace('\'', '\\\'')
            .replace('&#039;', '\\\&#039;')
            .replace('"', '\"')
            .replace('&quot;', '\&quot;')
            .replace('\n', '\n')
            .replace('\r', '\r')
            .replace(/<\/script/gi, '</\' + \'script');
    } else {
        return false;
    }
}

/**
 * decode a string URL_encoded
 *
 * @param string str
 * @return string the URL-decoded string
 */
function PMA_urldecode (str) {
    if (typeof str !== 'undefined') {
        return decodeURIComponent(str.replace(/\+/g, '%20'));
    }
}

/**
 * endecode a string URL_decoded
 *
 * @param string str
 * @return string the URL-encoded string
 */
function PMA_urlencode (str) {
    if (typeof str !== 'undefined') {
        return encodeURIComponent(str).replace(/\%20/g, '+');
    }
}

/**
 * Module export
 */
export {
    escapeHtml,
    escapeJsString,
    PMA_urlencode
};
