// TODO: tablesorter shouldn't sort already sorted columns
function initTableSorter(tabid) {
    var $table, opts;
    switch (tabid) {
    case 'statustabs_queries':
        $table = $('#serverstatusqueriesdetails');
        opts = {
            sortList: [[3, 1]],
            widgets: ['fast-zebra'],
            headers: {
                1: { sorter: 'fancyNumber' },
                2: { sorter: 'fancyNumber' }
            }
        };
        break;
    }
    $table.tablesorter(opts);
    $table.find('tr:first th')
        .append('<img class="icon sortableIcon" src="themes/dot.gif" alt="">');
}

$(function () {
    $.tablesorter.addParser({
        id: "fancyNumber",
        is: function (s) {
            return (/^[0-9]?[0-9,\.]*\s?(k|M|G|T|%)?$/).test(s);
        },
        format: function (s) {
            var num = jQuery.tablesorter.formatFloat(
                s.replace(PMA_messages.strThousandsSeparator, '')
                 .replace(PMA_messages.strDecimalSeparator, '.')
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
        type: "numeric"
    });

    $.tablesorter.addParser({
        id: "withinSpanNumber",
        is: function (s) {
            return (/<span class="original"/).test(s);
        },
        format: function (s, table, html) {
            var res = html.innerHTML.match(/<span(\s*style="display:none;"\s*)?\s*class="original">(.*)?<\/span>/);
            return (res && res.length >= 3) ? res[2] : 0;
        },
        type: "numeric"
    });

    // faster zebra widget: no row visibility check, faster css class switching, no cssChildRow check
    $.tablesorter.addWidget({
        id: "fast-zebra",
        format: function (table) {
            if (table.config.debug) {
                var time = new Date();
            }
            $("tr:even", table.tBodies[0])
                .removeClass(table.config.widgetZebra.css[0])
                .addClass(table.config.widgetZebra.css[1]);
            $("tr:odd", table.tBodies[0])
                .removeClass(table.config.widgetZebra.css[1])
                .addClass(table.config.widgetZebra.css[0]);
            if (table.config.debug) {
                $.tablesorter.benchmark("Applying Fast-Zebra widget", time);
            }
        }
    });
});
