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
?>
<table border="0" cellspacing="0" cellpadding="3" width="100%" class="tabs">
    <tr>
        <td width="8">&nbsp;</td>
<?php
echo PMA_printTab($strDatabases, 'server_databases.php', $url_query);
if ($cfg['ShowMysqlInfo']) {
    echo PMA_printTab($strStatus, 'server_status.php', $url_query);
}
if ($cfg['ShowMysqlVars']) {
    echo PMA_printTab($strServerTabVariables, 'server_variables.php', $url_query);
}
if (PMA_MYSQL_INT_VERSION >= 40100) {
    echo PMA_printTab($strCharsets, 'server_collations.php', $url_query);
}
if ($is_superuser) {
    echo PMA_printTab($strPrivileges, 'server_privileges.php', $url_query);
}
echo PMA_printTab($strServerTabProcesslist, 'server_processlist.php', $url_query);
echo PMA_printTab($strExport, 'server_export.php', $url_query);
?>
    </tr>
</table>
<br />
