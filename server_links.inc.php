<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Check parameters

require_once('./libraries/common.lib.php');
PMA_checkParameters(array('is_superuser', 'url_query'));

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
 * Prepares links
 */
if ($is_superuser) {
    $cfg['ShowMysqlInfo'] = TRUE;
    $cfg['ShowMysqlVars'] = TRUE;
}


/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
}


/**
 * Displays tab links
 */
if ($cfg['LightTabs']) {
    echo '&nbsp;';
} else {
    echo '<table border="0" cellspacing="0" cellpadding="0" width="100%">' . "\n"
       . '    <tr>' . "\n"
       . '        <td class="nav" align="left" nowrap="nowrap" valign="bottom">'
       . '            <table border="0" cellpadding="0" cellspacing="0"><tr>'
       . '                <td nowrap="nowrap"><img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  .'" width="2" height="1" border="0" alt="" /></td>'
       . '                <td class="navSpacer"><img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  . '" width="1" height="1" border="0" alt="" /></td>';
}
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_db.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strDatabases.'" />' : '') . $strDatabases, 'server_databases.php', $url_query);
if ($cfg['ShowMysqlInfo']) {
    echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_status.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strStatus.'" />' : '') . $strStatus, 'server_status.php', $url_query);
}
if ($cfg['ShowMysqlVars']) {
    echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_vars.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strServerTabVariables.'" />' : '') . $strServerTabVariables, 'server_variables.php', $url_query);
}
if (PMA_MYSQL_INT_VERSION >= 40100) {
    echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_asci.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strCharsets.'" />' : '') . $strCharsets, 'server_collations.php', $url_query);
}
if ($is_superuser) {
    echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_rights.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strPrivileges.'" />' : '') . $strPrivileges, 'server_privileges.php', $url_query);
}
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_process.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strServerTabProcesslist.'" />' : '') . $strServerTabProcesslist, 'server_processlist.php', $url_query);
echo PMA_printTab(($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 'b_export.png" width="16" height="16" border="0" hspace="2" align="absmiddle" alt="'.$strExport.'" />' : '') . $strExport, 'server_export.php', $url_query);
if (!$cfg['LightTabs']) {
    echo '                <td nowrap="nowrap"><img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  . '" width="2" height="1" border="0" alt="" /></td>'
       . '            </tr></table>' . "\n"
       . '        </td>' . "\n"
       . '    </tr>' . "\n"
       . '</table>';
} else {
    echo '<br />';
}

?>
<br />
