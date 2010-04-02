/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for server_replication.php 
 *
 */
$(document).ready(function() {
    $('#master_status_href').click(function() {
        $('#replication_master_section').toggle(); 
        });
    $('#master_slaves_href').click(function() {
        $('#replication_slaves_section').toggle(); 
        });
    $('#slave_status_href').click(function() {
        $('#replication_slave_section').toggle(); 
        });
    $('#slave_control_href').click(function() {
        $('#slave_control_gui').toggle(); 
        });
    $('#slave_errormanagement_href').click(function() {
        $('#slave_errormanagement_gui').toggle(); 
        });
    $('#slave_synchronization_href').click(function() {
        $('#slave_synchronization_gui').toggle(); 
        });
});
