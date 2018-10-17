/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * jqplot formatter for byte values
 *
 * @package phpMyAdmin
 */
(function ($) {
    'use strict';
    var formatByte = function (val, index) {
        var units = [
            PMA_messages.strB,
            PMA_messages.strKiB,
            PMA_messages.strMiB,
            PMA_messages.strGiB,
            PMA_messages.strTiB,
            PMA_messages.strPiB,
            PMA_messages.strEiB
        ];
        while (val >= 1024 && index <= 6) {
            val /= 1024;
            index++;
        }
        var format = '%.1f';
        if (Math.floor(val) === val) {
            format = '%.0f';
        }
        return $.jqplot.sprintf(
            format + ' ' + units[index], val
        );
    };
    /**
     * The index indicates what unit the incoming data will be in.
     * 0 for bytes, 1 for kilobytes and so on...
     */
    $.jqplot.byteFormatter = function (index) {
        index = index || 0;
        return function (format, val) {
            if (typeof val === 'number') {
                val = parseFloat(val) || 0;
                return formatByte(val, index);
            } else {
                return String(val);
            }
        };
    };
}(jQuery));
