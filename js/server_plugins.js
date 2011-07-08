/**
 * Functions used in server plugins pages
 */

$(function() {
    // Add tabs
    $('#pluginsTabs').tabs({
        // Tab persistence
        cookie: { name: 'pma_serverStatusTabs', expires: 1 },
        // Fixes line break in the menu bar when the page overflows and scrollbar appears
        show: function() { menuResize(); }
    });
});