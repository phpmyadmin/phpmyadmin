/**
 * Functions used in server plugins pages
 */

var pma_theme_image; // filled in server_plugins.php

$(function() {
    // Add tabs
    $('#pluginsTabs').tabs({
        // Tab persistence
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        show: function() { menuResize(); }
    });

    // Make columns sortable, but only for tables with more than 1 data row
    var $tables = $('#plugins_plugins table:has(tbody tr + tr:first)')
        /*.add('#plugins_modules table')*/;
    $tables.tablesorter({
        sortList: [[0,0]],
        widgets: ['zebra']
    });
    $tables.find('thead th')
        .append('<img class="sortableIcon" src="' + pma_theme_image + 'cleardot.gif" alt="">');
});