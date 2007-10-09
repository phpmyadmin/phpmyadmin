<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Check parameters
 */
require_once './libraries/common.inc.php';
require_once './libraries/server_common.inc.php';

PMA_checkParameters(array('is_superuser', 'url_query'), TRUE, FALSE);

/**
 * Counts amount of navigation tabs
 */
$server_links_count_tabs = 0;


/**
 * Put something in $sub_part
 */
if (!isset($sub_part)) {
    $sub_part = '';
}


/**
 * Displays tab links
 */
$tabs = array();

$tabs['databases']['icon'] = 's_db.png';
$tabs['databases']['link'] = 'server_databases.php';
$tabs['databases']['text'] = $strDatabases;

$tabs['sql']['icon'] = 'b_sql.png';
$tabs['sql']['link'] = 'server_sql.php';
$tabs['sql']['text'] = $strSQL;

$tabs['status']['icon'] = 's_status.png';
$tabs['status']['link'] = 'server_status.php';
$tabs['status']['text'] = $strStatus;

$tabs['vars']['icon'] = 's_vars.png';
$tabs['vars']['link'] = 'server_variables.php';
$tabs['vars']['text'] = $strServerTabVariables;

$tabs['charset']['icon'] = 's_asci.png';
$tabs['charset']['link'] = 'server_collations.php';
$tabs['charset']['text'] = $strCharsets;

$tabs['engine']['icon'] = 'b_engine.png';
$tabs['engine']['link'] = 'server_engines.php';
$tabs['engine']['text'] = $strEngines;

if ($is_superuser) {
    $tabs['rights']['icon'] = 's_rights.png';
    $tabs['rights']['link'] = 'server_privileges.php';
    $tabs['rights']['text'] = $strPrivileges;
}

if (! empty($binary_logs)) {
    $tabs['binlog']['icon'] = 's_tbl.png';
    $tabs['binlog']['link'] = 'server_binlog.php';
    $tabs['binlog']['text'] = $strBinaryLog;
}
$tabs['process']['icon'] = 's_process.png';
$tabs['process']['link'] = 'server_processlist.php';
$tabs['process']['text'] = $strProcesses;

$tabs['export']['icon'] = 'b_export.png';
$tabs['export']['link'] = 'server_export.php';
$tabs['export']['text'] = $strExport;

$tabs['import']['icon'] = 'b_import.png';
$tabs['import']['link'] = 'server_import.php';
$tabs['import']['text'] = $strImport;

echo PMA_getTabs($tabs);
unset($tabs);


/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

?>
