/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for server_replication.php
 *
 */

var random_server_id = Math.floor(Math.random() * 10000000);
var conf_prefix = "server-id=" + random_server_id + "\nlog_bin=mysql-bin\nlog_error=mysql-bin.err\n";

function update_config()
{
    var conf_ignore = "binlog_ignore_db=";
    var conf_do = "binlog_do_db=";
    var database_list = '';

    if ($('#db_select option:selected').size() === 0) {
        $('#rep').text(conf_prefix);
    } else if ($('#db_type option:selected').val() == 'all') {
        $('#db_select option:selected').each(function () {
            database_list += conf_ignore + $(this).val() + "\n";
        });
        $('#rep').text(conf_prefix + database_list);
    } else {
        $('#db_select option:selected').each(function () {
            database_list += conf_do + $(this).val() + "\n";
        });
        $('#rep').text(conf_prefix + database_list);
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('replication.js', function () {
    $('#db_type').unbind('change');
    $('#db_select').unbind('change');
    $('#master_status_href').unbind('click');
    $('#master_slaves_href').unbind('click');
    $('#slave_status_href').unbind('click');
    $('#slave_control_href').unbind('click');
    $('#slave_errormanagement_href').unbind('click');
    $('#slave_synchronization_href').unbind('click');
    $('#db_reset_href').unbind('click');
});

AJAX.registerOnload('replication.js', function () {
    $('#rep').text(conf_prefix);
    $('#db_type').change(update_config);
    $('#db_select').change(update_config);

    $('#master_status_href').click(function () {
        $('#replication_master_section').toggle();
    });
    $('#master_slaves_href').click(function () {
        $('#replication_slaves_section').toggle();
    });
    $('#slave_status_href').click(function () {
        $('#replication_slave_section').toggle();
    });
    $('#slave_control_href').click(function () {
        $('#slave_control_gui').toggle();
    });
    $('#slave_errormanagement_href').click(function () {
        $('#slave_errormanagement_gui').toggle();
    });
    $('#slave_synchronization_href').click(function () {
        $('#slave_synchronization_gui').toggle();
    });
    $('#db_reset_href').click(function () {
        $('#db_select option:selected').prop('selected', false);
    });
});
