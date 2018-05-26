/**
 * HTML escaping
 */

export function escapeHtml (unsafe) {
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

export function escapeJsString (unsafe) {
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