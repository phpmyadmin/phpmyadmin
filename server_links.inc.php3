<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Counts amount of navigation tabs
 */
$server_links_count_tabs = 0;


/**
 * If coming from a Show MySQL link on the home page,
 * put something in $sub_part
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
 * Displays tab links
 */
?>
<table border="0" cellspacing="0" cellpadding="3" width="100%" class="tabs">
    <tr>
        <td width="8">&nbsp;</td>
<?php
echo PMA_printTab($strHome, 'main.php3', $url_query);
if ($cfg['ShowMysqlInfo']) {
    echo PMA_printTab($strStatus, 'server_status.php3', $url_query);
}
if ($cfg['ShowMysqlVars']) {
    echo PMA_printTab($strServerTabVariables, 'server_variables.php3', $url_query);
}
if ($is_superuser) {
    echo PMA_printTab($strPrivileges, 'server_privileges.php3', $url_query);
}
echo PMA_printTab($strServerTabProcesslist, 'server_processlist.php3', $url_query);
?>
    </tr>
</table>
<br />