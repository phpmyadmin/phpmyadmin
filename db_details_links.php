<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Counts amount of navigation tabs
 */
$db_details_links_count_tabs = 0;


/**
 * If coming from a Show MySQL link on the home page,
 * put something in $sub_part
 */
if (empty($sub_part)) {
    $sub_part = '_structure';
}


/**
 * Prepares links
 */
// Export link if there is at least one table
if ($num_tables > 0) {
    $lnk3 = 'db_details_export.php';
    $arg3 = $url_query;
    $lnk4 = 'db_search.php';
    $arg4 = $url_query;
}
else {
    $lnk3 = '';
    $arg3 = '';
    $lnk4 = '';
    $arg4 = '';
}
// Drop link if allowed
if (!$cfg['AllowUserDropDatabase']) {
    // Check if the user is a Superuser
    $cfg['AllowUserDropDatabase'] = PMA_DBI_select_db('mysql');
    PMA_DBI_select_db($db);
}
if ($cfg['AllowUserDropDatabase']) {
    $lnk5 = 'sql.php';
    $arg5 = $url_query . '&amp;sql_query='
          . urlencode('DROP DATABASE ' . PMA_backquote($db))
          . '&amp;zero_rows='
          . urlencode(sprintf($strDatabaseHasBeenDropped, htmlspecialchars(PMA_backquote($db))))
          . '&amp;goto=main.php&amp;back=db_details' . $sub_part . '.php&amp;reload=1&amp;purge=1';
    $att5 = 'onclick="return confirmLinkDropDB(this, \'DROP DATABASE ' . PMA_jsFormat($db) . '\')"';
    $class5 = 'Drop';
}
else {
    $lnk5 = '';
    $class5 = 'Drop';
}


/**
 * Displays tab links
 */

if ($cfg['LightTabs']) {
    echo '&nbsp;';
} else {
    echo '<table border="0" cellspacing="0" cellpadding="0" width="100%" id="topmenu">' . "\n"
       . '    <tr>' . "\n"
       . '        <td class="nav" align="left" nowrap="nowrap" valign="bottom">'
       . '            <table border="0" cellpadding="0" cellspacing="0"><tr>'
       . '                <td nowrap="nowrap"><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="2" height="1" border="0" alt="" /></td>'
       . '                <td class="navSpacer"><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="1" height="1" border="0" alt="" /></td>';
}

echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_props.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strStructure.'" />' : '') . $strStructure, 'db_details_structure.php', $url_query);
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_sql.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strSQL.'" />' : '') . $strSQL, 'db_details.php', $url_query . '&amp;db_query_force=1');
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_export.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strExport.'" />' : '') . $strExport, $lnk3, $arg3);
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_search.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strSearch.'" />' : '') . $strSearch, $lnk4, $arg4);
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_db.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strQBE.'" />' : '') . $strQBE, ($num_tables > 0) ? 'db_details_qbe.php' : '', $url_query);
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_tblops.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strOperations.'" />' : '') . $strOperations,'db_operations.php', $url_query);

// Displays drop link
if ($lnk5) {
   echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_deltbl.png" width="16" height="16" border="0" hspace="2" align="middle" alt="'.$strDrop.'" />' : '') . $strDrop, $lnk5, $arg5, $att5, $class5);
} // end if
echo "\n";

if (!$cfg['LightTabs']) {
    echo '                <td nowrap="nowrap"><img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  . '" width="2" height="1" border="0" alt="" /></td>'
       . '            </tr></table>' . "\n"
       . '        </td>' . "\n"
       . '    </tr>' . "\n"
       . '</table>';
} else {
    echo '<br />';
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
