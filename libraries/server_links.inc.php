<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
require_once './libraries/common.inc.php';
require_once './libraries/server_common.inc.php';

PMA_checkParameters(array('is_superuser', 'url_query'), true, false);

// Don't print all these links if in an Ajax request
if (!$GLOBALS['is_ajax_request']) {
    /**
     * Counts amount of navigation tabs
     */
    $server_links_count_tabs = 0;


    /**
     * Put something in $sub_part
     */
    if (! isset($sub_part)) {
        $sub_part = '';
    }


    /**
     * Displays tab links
     * Put the links we assume are used less, towards the end
     */
    $tabs = array();

    $tabs['databases']['icon'] = 'ic_s_db';
    $tabs['databases']['link'] = 'server_databases.php';
    $tabs['databases']['text'] = __('Databases');

    $tabs['sql']['icon'] = 'ic_b_sql';
    $tabs['sql']['link'] = 'server_sql.php';
    $tabs['sql']['text'] = __('SQL');

    $tabs['status']['icon'] = 'ic_s_status';
    $tabs['status']['link'] = 'server_status.php';
    $tabs['status']['text'] = __('Status');

    /*$tabs['process']['icon'] = 's_process.png';
    $tabs['process']['link'] = 'server_processlist.php';
    $tabs['process']['text'] = __('Processes');*/

    if ($is_superuser) {
        $tabs['rights']['icon'] = 'ic_s_rights';
        $tabs['rights']['link'] = 'server_privileges.php';
        $tabs['rights']['text'] = __('Privileges');
    }

    $tabs['export']['icon'] = 'ic_b_export';
    $tabs['export']['link'] = 'server_export.php';
    $tabs['export']['text'] = __('Export');

    $tabs['import']['icon'] = 'ic_b_import';
    $tabs['import']['link'] = 'server_import.php';
    $tabs['import']['text'] = __('Import');

    $tabs['settings']['icon'] = 'ic_b_tblops';
    $tabs['settings']['link'] = 'prefs_manage.php';
    $tabs['settings']['text'] = __('Settings');
    $tabs['settings']['active'] = in_array(basename($GLOBALS['PMA_PHP_SELF']),
        array('prefs_forms.php', 'prefs_manage.php'));

    $tabs['synchronize']['icon'] = 'ic_s_sync';
    $tabs['synchronize']['link'] = 'server_synchronize.php';
    $tabs['synchronize']['text'] = __('Synchronize');

    if (! empty($binary_logs)) {
        $tabs['binlog']['icon'] = 'ic_s_tbl';
        $tabs['binlog']['link'] = 'server_binlog.php';
        $tabs['binlog']['text'] = __('Binary log');
    }

    if ($is_superuser) {
        $tabs['replication']['icon'] = 'ic_s_replication';
        $tabs['replication']['link'] = 'server_replication.php';
        $tabs['replication']['text'] = __('Replication');
    }

    $tabs['vars']['icon'] = 'ic_s_vars';
    $tabs['vars']['link'] = 'server_variables.php';
    $tabs['vars']['text'] = __('Variables');

    $tabs['charset']['icon'] = 'ic_s_asci';
    $tabs['charset']['link'] = 'server_collations.php';
    $tabs['charset']['text'] = __('Charsets');

    $tabs['engine']['icon'] = 'ic_b_engine';
    $tabs['engine']['link'] = 'server_engines.php';
    $tabs['engine']['text'] = __('Engines');

    echo PMA_generate_html_tabs($tabs, array());
    unset($tabs);



    /**
     * Displays a message
     */
    if (!empty($message)) {
        PMA_showMessage($message);
        unset($message);
    }
}// end if($GLOBALS['is_ajax_request'] == true)
?>
