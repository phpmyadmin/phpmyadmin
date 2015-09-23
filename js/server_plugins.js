/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in server plugins pages
 */
AJAX.registerOnload('server_plugins.js', function () {
        // Make columns sortable, but only for tables with more than 1 data row
    var $tables = $('#plugins_plugins table:has(tbody tr + tr)');
    $tables.tablesorter({
        sortList: [[0, 0]],
        widgets: ['zebra']
    });
    $tables.find('thead th')
        .append('<div class="sorticon"></div>');
});
