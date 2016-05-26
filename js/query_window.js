/* vim: set expandtab sw=4 ts=4 sts=4: */

$(document).ready(function() {
    $('#pma_navigation_collapser').click();
    $('#pma_navigation_collapser').remove();
    $('#pma_console_container').remove();
    $('#topmenu').remove();
    $('#floating_menubar').remove();
    $('#sqlqueryform').attr('target','newtab');
});
