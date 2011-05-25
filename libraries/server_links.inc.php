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

PMA_checkParameters(array('is_superuser', 'url_query'), TRUE, FALSE);

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
     */
    $tabs = array();

    $tabs['databases']['icon'] = 's_db.png';
    $tabs['databases']['link'] = 'server_databases.php';
    $tabs['databases']['text'] = __('Databases');

    $tabs['sql']['icon'] = 'b_sql.png';
    $tabs['sql']['link'] = 'server_sql.php';
    $tabs['sql']['text'] = __('SQL');

    $tabs['status']['icon'] = 's_status.png';
    $tabs['status']['link'] = 'server_status.php';
    $tabs['status']['text'] = __('Status');

    if (! empty($binary_logs)) {
        $tabs['binlog']['icon'] = 's_tbl.png';
        $tabs['binlog']['link'] = 'server_binlog.php';
        $tabs['binlog']['text'] = __('Binary log');
    }
    $tabs['process']['icon'] = 's_process.png';
    $tabs['process']['link'] = 'server_processlist.php';
    $tabs['process']['text'] = __('Processes');

    if ($is_superuser && !PMA_DRIZZLE) {
        $tabs['rights']['icon'] = 's_rights.png';
        $tabs['rights']['link'] = 'server_privileges.php';
        $tabs['rights']['text'] = __('Privileges');
    }

    $tabs['export']['icon'] = 'b_export.png';
    $tabs['export']['link'] = 'server_export.php';
    $tabs['export']['text'] = __('Export');

    $tabs['import']['icon'] = 'b_import.png';
    $tabs['import']['link'] = 'server_import.php';
    $tabs['import']['text'] = __('Import');

    $tabs['vars']['icon'] = 's_vars.png';
    $tabs['vars']['link'] = 'server_variables.php';
    $tabs['vars']['text'] = __('Variables');

    $tabs['charset']['icon'] = 's_asci.png';
    $tabs['charset']['link'] = 'server_collations.php';
    $tabs['charset']['text'] = __('Charsets');

    if (!PMA_DRIZZLE) {
        $tabs['engine']['icon'] = 'b_engine.png';
        $tabs['engine']['link'] = 'server_engines.php';
        $tabs['engine']['text'] = __('Engines');
    }

    if ($is_superuser && !PMA_DRIZZLE) {
        $tabs['replication']['icon'] = 's_replication.png';
        $tabs['replication']['link'] = 'server_replication.php';
        $tabs['replication']['text'] = __('Replication');
    }

    $tabs['synchronize']['icon'] = 's_sync.png';
    $tabs['synchronize']['link'] = 'server_synchronize.php';
    $tabs['synchronize']['text'] = __('Synchronize');

    $tabs['settings']['icon'] = 'b_tblops.png';
    $tabs['settings']['link'] = 'prefs_manage.php';
    $tabs['settings']['text'] = __('Settings');
    $tabs['settings']['active'] = in_array(basename($GLOBALS['PMA_PHP_SELF']),
        array('prefs_forms.php', 'prefs_manage.php'));

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
