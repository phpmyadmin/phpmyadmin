/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for server_synchronize.php 
 *
 */
$(document).ready(function() {
    $('.server_selector').change(function(evt) {
        var server = $(evt.target).val();
        if (server == 'cur') {
            $(this).closest('tbody').children('.current-server').css('display', '');
            $(this).closest('tbody').children('.remote-server').css('display', 'none');
        } else if (server == 'rmt') {
            $(this).closest('tbody').children('.current-server').css('display', 'none');
            $(this).closest('tbody').children('.remote-server').css('display', '');
        } else {
            $(this).closest('tbody').children('.current-server').css('display', 'none');
            $(this).closest('tbody').children('.remote-server').css('display', '');
            var parts = server.split('||||');
            $(this).closest('tbody').find('.server-host').val(parts[0]);
            $(this).closest('tbody').find('.server-port').val(parts[1]);
            $(this).closest('tbody').find('.server-socket').val(parts[2]);
            $(this).closest('tbody').find('.server-user').val(parts[3]);
            $(this).closest('tbody').find('.server-pass').val('');
            $(this).closest('tbody').find('.server-db').val(parts[4])
        }
        });
});
