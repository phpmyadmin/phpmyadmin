/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in server plugins pages
 */
// import { jQuery as $ } from './utils/extend_jquery';
export function onload1 () {
    // Make columns sortable, but only for tables with more than 1 data row
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
