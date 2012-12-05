/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used in server status pages
 * @name            Server Status
 */

var pma_token,
    url_query,
    server_time_diff,
    server_os,
    is_superuser,
    server_db_isLocal;

// Add a tablesorter parser to properly handle thousands seperated numbers and SI prefixes
AJAX.registerOnload('server_status.js', function() {

    var $js_data_form = $('#js_data');
    pma_token =         $js_data_form.find("input[name=pma_token]").val();
    url_query =         $js_data_form.find("input[name=url_query]").val();
    server_time_diff  = eval($js_data_form.find("input[name=server_time_diff]").val());
    server_os =         $js_data_form.find("input[name=server_os]").val();
    is_superuser =      $js_data_form.find("input[name=is_superuser]").val();
    server_db_isLocal = $js_data_form.find("input[name=server_db_isLocal]").val();

});
