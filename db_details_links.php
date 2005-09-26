<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


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

$is_superuser = PMA_DBI_try_query('SELECT COUNT(*) FROM mysql.user', NULL, PMA_DBI_QUERY_STORE);

/**
 * Prepares links
 */
// Export link if there is at least one table
if ($num_tables > 0) {
    $tab_export['link'] = 'db_details_export.php';
    $tab_search['link'] = 'db_search.php';
    $tab_qbe['link']    = 'db_details_qbe.php';
}
$tab_import['link'] = 'db_import.php';
$tab_structure['link'] = 'db_details_structure.php';
$tab_sql['link']       = 'db_details.php';
$tab_sql['args']['db_query_force'] = 1;
$tab_operation['link'] = 'db_operations.php';

if ($is_superuser) {
    $tab_privileges['link'] = 'server_privileges.php';
    $tab_privileges['args']['checkprivs']       = $db;
    // stay on database view
    $tab_privileges['args']['viewing_mode'] = 'db';
}

// Drop link if allowed
if (!$GLOBALS['cfg']['AllowUserDropDatabase']) {
    // Check if the user is a Superuser
    $GLOBALS['cfg']['AllowUserDropDatabase'] = $is_superuser;
}
// rabus: Don't even try to drop information_schema. You won't be able to.
// Believe me. You won't.
$GLOBALS['cfg']['AllowUserDropDatabase'] = $GLOBALS['cfg']['AllowUserDropDatabase'] && !(PMA_MYSQL_INT_VERSION >= 50000 && $db == 'information_schema');
if ($GLOBALS['cfg']['AllowUserDropDatabase']) {
    $tab_drop['link'] = 'sql.php';
    $tab_drop['args']['sql_query']  = 'DROP DATABASE ' . PMA_backquote($db);
    $tab_drop['args']['zero_rows']  = sprintf($GLOBALS['strDatabaseHasBeenDropped'], htmlspecialchars(PMA_backquote($db)));
    $tab_drop['args']['goto']       = 'main.php';
    $tab_drop['args']['back']       = 'db_details' . $sub_part . '.php';
    $tab_drop['args']['reload']     = 1;
    $tab_drop['args']['purge']      = 1;
    $tab_drop['attr'] = 'onclick="return confirmLinkDropDB(this, \'DROP DATABASE ' . PMA_jsFormat($db) . '\')"';
    $tab_drop['class'] = 'caution';
}

// text
$tab_structure['text']  = $GLOBALS['strStructure'];
$tab_sql['text']        = $GLOBALS['strSQL'];
$tab_export['text']     = $GLOBALS['strExport'];
$tab_import['text']     = $GLOBALS['strImport'];
$tab_search['text']     = $GLOBALS['strSearch'];
$tab_drop['text']       = $GLOBALS['strDrop'];
$tab_qbe['text']        = $GLOBALS['strQBE'];
$tab_operation['text']  = $GLOBALS['strOperations'];
if ($is_superuser) {
    $tab_privileges['text'] = $GLOBALS['strPrivileges'];
}

// icons
$tab_structure['icon']  = 'b_props.png';
$tab_sql['icon']        = 'b_sql.png';
$tab_export['icon']     = 'b_export.png';
$tab_import['icon']     = 'b_import.png';
$tab_search['icon']     = 'b_search.png';
$tab_drop['icon']       = 'b_deltbl.png';
$tab_qbe['icon']        = 's_db.png';
$tab_operation['icon']  = 'b_tblops.png';
if ($is_superuser) {
    $tab_privileges['icon'] = 's_rights.png';
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
$tabs[] =& $tab_import;
$tabs[] =& $tab_operation;
if ($is_superuser) {
    $tabs[] =& $tab_privileges;
}
if ($GLOBALS['cfg']['AllowUserDropDatabase']) {
    $tabs[] =& $tab_drop;
}

echo PMA_getTabs( $tabs );
unset( $tabs );

/**
 * Settings for relations stuff
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

// Get additional information about tables for tooltip is done in db_details_db_info.php only once
if ($cfgRelation['commwork']) {
    $comment = PMA_getComments($db);

    /**
     * Displays table comment
     */
    if (is_array($comment)) {
        ?>
    <!-- DB comment -->
    <p id="dbComment"><i>
        <?php echo htmlspecialchars(implode(' ', $comment)) . "\n"; ?>
    </i></p>
        <?php
    } // end if
}

/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}
?>
<br />
