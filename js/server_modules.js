/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in server modules pages
 */
AJAX.registerOnload('server_modules.js', function () {
    // Make columns sortable
    var $table = $('#plugins_modules table');
    $table.tablesorter({
        sortList: [[0, 0]],
        headers: {
            1: {sorter: false},
            3: {sorter: "digit"}
        }
    });
    $table.find('thead th')
        .append('<div class="sorticon"></div>');
});
