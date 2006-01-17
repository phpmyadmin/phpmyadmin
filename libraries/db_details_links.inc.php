<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * If coming from a Show MySQL link on the home page,
 * put something in $sub_part
 */
if (empty($sub_part)) {
    $sub_part = '_structure';
}

/**
 * Checks for superuser privileges
  */
  // We were checking privileges with 'USE mysql' but users with the global
  // priv CREATE TEMPORARY TABLES or LOCK TABLES can do a 'USE mysql'
  // (even if they cannot see the tables)

$is_superuser = PMA_DBI_try_query('SELECT COUNT(*) FROM mysql.user', null, PMA_DBI_QUERY_STORE);

/**
 * Prepares links
 */
// Drop link if allowed
// rabus: Don't even try to drop information_schema. You won't be able to. Believe me. You won't.
// nijel: Don't allow to easilly drop mysql database, RFE #1327514.
if (($is_superuser || $GLOBALS['cfg']['AllowUserDropDatabase']) && ! $db_is_information_schema && ($db != 'mysql')) {
    $tab_drop['link'] = 'sql.php';
    $tab_drop['args']['sql_query']  = 'DROP DATABASE ' . PMA_backquote($db);
    $tab_drop['args']['zero_rows']  = sprintf($GLOBALS['strDatabaseHasBeenDropped'], htmlspecialchars(PMA_backquote($db)));
    $tab_drop['args']['goto']       = 'main.php';
    $tab_drop['args']['back']       = 'db_details' . $sub_part . '.php';
    $tab_drop['args']['reload']     = 1;
    $tab_drop['args']['purge']      = 1;
    $tab_drop['attr'] = 'onclick="return confirmLinkDropDB(this, \'DROP DATABASE ' . PMA_jsFormat($db) . '\')"';
}

/**
 * export, search and qbe links if there is at least one table
 */
if ( $num_tables > 0 ) {
    $tab_export['link'] = 'db_details_export.php';
    $tab_search['link'] = 'db_search.php';
    $tab_qbe['link']    = 'db_details_qbe.php';
}

$tab_structure['link']  = 'db_details_structure.php';
$tab_structure['text']  = $GLOBALS['strStructure'];
$tab_structure['icon']  = 'b_props.png';

$tab_sql['link']        = 'db_details.php';
$tab_sql['args']['db_query_force'] = 1;
$tab_sql['text']        = $GLOBALS['strSQL'];
$tab_sql['icon']        = 'b_sql.png';

$tab_export['text']     = $GLOBALS['strExport'];
$tab_export['icon']     = 'b_export.png';
$tab_search['text']     = $GLOBALS['strSearch'];
$tab_search['icon']     = 'b_search.png';

$tab_qbe['text']        = $GLOBALS['strQBE'];
$tab_qbe['icon']        = 's_db.png';


if ( ! $db_is_information_schema ) {
    $tab_import['link']     = 'db_import.php';
    $tab_import['text']     = $GLOBALS['strImport'];
    $tab_import['icon']     = 'b_import.png';
    $tab_drop['text']       = $GLOBALS['strDrop'];
    $tab_drop['icon']       = 'b_deltbl.png';
    $tab_drop['class']      = 'caution';
    $tab_operation['link']  = 'db_operations.php';
    $tab_operation['text']  = $GLOBALS['strOperations'];
    $tab_operation['icon']  = 'b_tblops.png';
    if ( $is_superuser ) {
        $tab_privileges['link'] = 'server_privileges.php';
        $tab_privileges['args']['checkprivs']       = $db;
        // stay on database view
        $tab_privileges['args']['viewing_mode'] = 'db';
        $tab_privileges['text'] = $GLOBALS['strPrivileges'];
        $tab_privileges['icon'] = 's_rights.png';
    }
}

/**
 * Displays tab links
 */
$tabs = array();
$tabs[] =& $tab_structure;
$tabs[] =& $tab_sql;
$tabs[] =& $tab_search;
$tabs[] =& $tab_qbe;
$tabs[] =& $tab_export;
if ( ! $db_is_information_schema ) {
    $tabs[] =& $tab_import;
    $tabs[] =& $tab_operation;
    if ( $is_superuser ) {
        $tabs[] =& $tab_privileges;
    }
    if ( $is_superuser || $GLOBALS['cfg']['AllowUserDropDatabase'] ) {
        $tabs[] =& $tab_drop;
    }
}

echo PMA_getTabs( $tabs );
unset( $tabs );

/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}
?>
<br />
