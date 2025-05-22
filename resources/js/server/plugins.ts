import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';

/**
 * Make columns sortable, but only for tables with more than 1 data row.
 */
function makeColumnsSortable () {
    var $tables = $('#plugins_plugins table:has(tbody tr + tr)');
    $tables.tablesorter({
        sortList: [[0, 0]],
        headers: {
            1: { sorter: false }
        }
    });

    $tables.find('thead th')
        .append('<div class="sorticon"></div>');
}

AJAX.registerOnload('server/plugins.js', function () {
    makeColumnsSortable();
});
