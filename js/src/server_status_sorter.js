/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { $ } from './utils/JqueryExtended';
import 'tablesorter';
import { PMA_Messages as messages } from './variables/export_variables';

$(function () {
    $.tablesorter.addParser({
        id: 'fancyNumber',
        is: function (s) {
            return (/^[0-9]?[0-9,\.]*\s?(k|M|GG|T|%)?$/).test(s);
        },
        format: function (s) {
            var num = $.tablesorter.formatFloat(
                s.replace(messages.strThousandsSeparator, '')
                    .replace(messages.strDecimalSeparator, '.')
            );

            var factor = 1;
            switch (s.charAt(s.length - 1)) {
            case '%':
                factor = -2;
                break;
            // Todo: Complete this list (as well as in the regexp a few lines up)
            case 'k':
                factor = 3;
                break;
            case 'M':
                factor = 6;
                break;
            case 'G':
                factor = 9;
                break;
            case 'T':
                factor = 12;
                break;
            }

            return num * Math.pow(10, factor);
        },
        type: 'numeric'
    });

    $.tablesorter.addParser({
        id: 'withinSpanNumber',
        is: function (s) {
            return (/<span class="original"/).test(s);
        },
        format: function (s, table, html) {
            var res = html.innerHTML.match(/<span(\s*style="display:none;"\s*)?\s*class="original">(.*)?<\/span>/);
            return (res && res.length >= 3) ? res[2] : 0;
        },
        type: 'numeric'
    });
});
