/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for server_replication.php
 *
 */

var random_server_id = Math.floor(Math.random() * 10000000);
var conf_prefix = 'server-id=' + random_server_id + '\nlog_bin=mysql-bin\nlog_error=mysql-bin.err\n';

function update_config () {
    var conf_ignore = 'binlog_ignore_db=';
    var conf_do = 'binlog_do_db=';
    var database_list = '';

    if ($('#db_select option:selected').size() === 0) {
        $('#rep').text(conf_prefix);
    } else if ($('#db_type option:selected').val() === 'all') {
        $('#db_select option:selected').each(function () {
            database_list += conf_ignore + $(this).val() + '\n';
        });
        $('#rep').text(conf_prefix + database_list);
    } else {
        $('#db_select option:selected').each(function () {
            database_list += conf_do + $(this).val() + '\n';
        });
        $('#rep').text(conf_prefix + database_list);
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('replication.js', function () {
    $('#db_type').off('change');
    $('#db_select').off('change');
    $('#master_status_href').off('click');
    $('#master_slaves_href').off('click');
    $('#slave_status_href').off('click');
    $('#slave_control_href').off('click');
    $('#slave_errormanagement_href').off('click');
    $('#slave_synchronization_href').off('click');
    $('#db_reset_href').off('click');
    $('#db_select_href').off('click');
    $('#reset_slave').off('click');
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
        $('#db_select').trigger('change');
    });
    $('#db_select_href').click(function () {
        $('#db_select option').prop('selected', true);
        $('#db_select').trigger('change');
    });
    $('#reset_slave').click(function (e) {
        e.preventDefault();
        var $anchor = $(this);
        var question = PMA_messages.strResetSlaveWarning;
        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            PMA_ajaxShowMessage();
            AJAX.source = $anchor;
            var params = getJSConfirmCommonParam({
                'ajax_page_request': true,
                'ajax_request': true
            }, $anchor.getPostData());
            $.post(url, params, AJAX.responseHandler);
        });
    });
});
