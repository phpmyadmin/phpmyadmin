<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Displays form for creating a table (if user has privileges for that)

require_once('./libraries/check_user_privileges.lib.php');

$is_create_table_priv = FALSE;
// TODO: escaped wildcard patterns
$mysql_wildcards = array('%','_');
$preg_patterns = array('(.*)', '.');

foreach($dbs_where_create_table_allowed as $allowed_db) {
    // '*' indicates a global CREATE priv
    if ($allowed_db == '*' || preg_match('@' .str_replace($mysql_wildcards, $preg_patterns, $allowed_db) . '@i', $db)) {
        $is_create_table_priv = TRUE;
        break;
    }
}
if ($is_create_table_priv) {
?>
    <!-- Create a new table -->
<form method="post" action="tbl_create.php" onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidFieldCount']); ?>', 1))">
     <table border="0" cellpadding="2" cellspacing="0">
     <tr>
     <td class="tblHeaders" colspan="3" nowrap="nowrap"><?php
        echo PMA_generate_common_hidden_inputs($db);
        if($cfg['PropertiesIconic']){ echo '<img src="' . $pmaThemeImage . 'b_newtbl.png" border="0" width="16" height="16" hspace="2" align="middle" />'; }
        // if you want navigation:
        $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url() . '&amp;db=' . urlencode($GLOBALS['db']) . '">'
                   . htmlspecialchars($GLOBALS['db']) . '</a>';
        // else use
        // $strDBLink = htmlspecialchars($db);
    echo '             ' . sprintf($strCreateNewTable, $strDBLink) . ':&nbsp;' . "\n";
    echo '     </td></tr>';
    echo '     <tr bgcolor="'.$cfg['BgcolorOne'].'"><td nowrap="nowrap">';
    echo '             ' . $strName . ':&nbsp;' . "\n";
    echo '     </td>';
    echo '     <td nowrap="nowrap">';
    echo '             ' . '<input type="text" name="table" maxlength="64" size="30" class="textfield" />';
    echo '     </td><td>&nbsp;</td></tr>';
    echo '     <tr bgcolor="'.$cfg['BgcolorOne'].'"><td nowrap="nowrap">';
    if (!isset($strNumberOfFields)) {
        $strNumberOfFields = $strFields;
    }
    echo '             ' . $strNumberOfFields . ':&nbsp;' . "\n";
    echo '     </td>';
    echo '     <td nowrap="nowrap">';
    echo '             ' . '<input type="text" name="num_fields" size="2" class="textfield" />' . "\n";
    echo '     </td>';
    echo '     <td align="right">';
    echo '             ' . '&nbsp;<input type="submit" value="' . $strGo . '" />' . "\n";
    echo '     </td> </tr>';
    echo '     </table>';
    echo '</form>' . "\n";
} else {
?>
     <table border="0" cellpadding="2" cellspacing="0">
     <tr>
     <td class="tblHeaders" colspan="3" nowrap="nowrap"><?php
        if($cfg['PropertiesIconic']) { 
            echo '<img src="' . $pmaThemeImage . 'b_newtbl.png" border="0" width="16" height="16" hspace="2" align="middle" />' . "\n"; 
        }
        $strDBLink = htmlspecialchars($db);
    echo '             ' . sprintf($strCreateNewTable, $strDBLink) . ':&nbsp;' . "\n";
    echo '     </td></tr>' . "\n";
    echo '     <tr>' . "\n";
    echo '     <td>' . "\n";
    echo '<span class="noPrivileges">'
        . ($cfg['ErrorIconic'] ? '<img src="' . $pmaThemeImage . 's_error2.png" width="11" height="11" hspace="2" border="0" align="middle" />' : '')
        . '' . $strNoPrivileges .'</span>' . "\n";
    echo '     </td>' . "\n";
    echo '     </tr>' . "\n";
    echo '     </table>' . "\n";

} // end if
?>
