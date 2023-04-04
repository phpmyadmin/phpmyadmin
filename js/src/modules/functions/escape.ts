/**
 * @param {string} value
 * @return {string}
 */
export function escapeHtml (value = '') {
    const element = document.createElement('span');
    element.appendChild(document.createTextNode(value));

    return element.innerHTML;
}

/**
 * JavaScript escaping
 *
 * @param {any} unsafe
 * @return {string | false}
 */
export function escapeJsString (unsafe) {
    if (typeof (unsafe) !== 'undefined') {
        return unsafe
            .toString()
            .replace('\x00', '')
            .replace('\\', '\\\\')
            .replace('\'', '\\\'')
            .replace('&#039;', '\\&#039;')
            .replace('"', '\\"')
            .replace('&quot;', '\\&quot;')
            .replace('\n', '\n')
            .replace('\r', '\r')
            .replace(/<\/script/gi, '</\' + \'script');
    } else {
        return false;
    }
}

/**
 * @param {string} s
 * @return {string}
 */
export function escapeBacktick (s) {
    return s.replace('`', '``');
}

/**
 * @param {string} s
 * @return {string}
 */
export function escapeSingleQuote (s) {
    return s.replace('\\', '\\\\').replace('\'', '\\\'');
}
