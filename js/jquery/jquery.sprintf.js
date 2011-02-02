/**
 * sprintf and vsprintf for jQuery
 * somewhat based on http://jan.moesen.nu/code/javascript/sprintf-and-printf-in-javascript/
 *
 * Copyright (c) 2008 Sabin Iacob (m0n5t3r) <iacobs@m0n5t3r.info>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @license http://www.gnu.org/licenses/gpl.html
 * @project jquery.sprintf
 */
(function($){
        var formats = {
                'b': function(val) {return parseInt(val, 10).toString(2);},
                'c': function(val) {return String.fromCharCode(parseInt(val, 10));},
                'd': function(val) {return parseInt(val, 10);},
                'u': function(val) {return Math.abs(val);},
                'f': function(val, p) {
                        p = parseInt(p, 10);
                        val = parseFloat(val);
                        if(isNaN(p && val)) {
                                return NaN;
                        }
                        return p && val.toFixed(p) || val;
                },
                'o': function(val) {return parseInt(val, 10).toString(8);},
                's': function(val) {return val;},
                'x': function(val) {return ('' + parseInt(val, 10).toString(16)).toLowerCase();},
                'X': function(val) {return ('' + parseInt(val, 10).toString(16)).toUpperCase();}
        };

        var re = /%(?:(\d+)?(?:\.(\d+))?|\(([^)]+)\))([%bcdufosxX])/g;

        var dispatch = function(data){
                if(data.length == 1 && typeof data[0] == 'object') { //python-style printf
                        data = data[0];
                        return function(match, w, p, lbl, fmt, off, str) {
                                return formats[fmt](data[lbl]);
                        };
                } else { // regular, somewhat incomplete, printf
                        var idx = 0;
                        return function(match, w, p, lbl, fmt, off, str) {
                                if(fmt == '%') {
                                        return '%';
                                }
                                return formats[fmt](data[idx++], p);
                        };
                }
        };

        $.extend({
                sprintf: function(format) {
                        var argv = Array.apply(null, arguments).slice(1);
                        return format.replace(re, dispatch(argv));
                },
                vsprintf: function(format, data) {
                        return format.replace(re, dispatch(data));
                }
        });
})(jQuery);

