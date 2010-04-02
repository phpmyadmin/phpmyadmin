/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for server_synchronize.php 
 *
 */
$(document).ready(function() {
    $('.server_selector').change(function() {
        $(this).closest('tbody').children('.toggler').toggle(); 
        });
});
