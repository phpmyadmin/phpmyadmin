<?php
/* $Id$ */
error_reporting(E_ALL);
/**
 * Count amount of navigation tabs
 */
$db_details_links_count_tabs = 0;


/**
 * Prepares links
 */
if ($table_info_num_rows > 0) {
    $lnk2    = 'sql.php3';
    $arg2    = $url_query
             . '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($table))
             . '&amp;pos=0';
    $lnk4    = 'tbl_select.php3';
    $arg4    = $url_query;
    $ln6_stt = (PMA_MYSQL_INT_VERSION >= 40000)
             ? 'TRUNCATE '
             : 'DELETE FROM ';
    $lnk6    = 'sql.php3';
    $arg6    = $url_query . '&amp;sql_query='
             . urlencode($ln6_stt . PMA_backquote($table))
             .  '&amp;zero_rows='
             .  urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)));
    $att6    = 'class="drop" onclick="return confirmLink(this, \'' . $ln6_stt . PMA_jsFormat($table) . '\')"';
} else {
    $lnk2    = '';
    $arg2    = '';
    $lnk4    = '';
    $arg4    = '';
    $lnk6    = '';
    $arg6    = '';
    $att6    = '';
}

// The use of $sub_part when setting $arg7 would work if all sub-pages
// scripts were prefixed by "tbl_properties", but this is not the case
// for now. The 'back' is supposed to be set to the current sub-page. This
// is necessary when you have js deactivated, you click on Drop, then click
// cancel, and want to get back to the same sub-page.

if (!isset($sub_part)) {
    $sub_part = '';
}
$arg7 = ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query) . '&amp;back=tbl_properties' . $sub_part . '.php3&amp;reload=1&amp;sql_query=' . urlencode('DROP TABLE ' . PMA_backquote($table) ) . '&amp;zero_rows=' . urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table)));
$att7 = 'class="drop" onclick="return confirmLink(this, \'DROP TABLE ' . PMA_jsFormat($table) . '\')"';

/**
 * Displays links
 */
?>
<table border="0" cellspacing="0" cellpadding="3" width="100%" class="tabs">
    <tr>
        <td width="8">&nbsp;</td>
<?php
echo PMA_printTab($strStructure, 'tbl_properties_structure.php3', $url_query);
echo PMA_printTab($strBrowse, $lnk2, $arg2);
echo PMA_printTab($strSQL, 'tbl_properties.php3', $url_query);
echo PMA_printTab($strSelect, $lnk4, $arg4);
echo PMA_printTab($strInsert, 'tbl_change.php3', $url_query);
echo PMA_printTab($strExport, 'tbl_properties_export.php3', $url_query);
echo PMA_printTab($strOperations, 'tbl_properties_operations.php3', $url_query);
echo PMA_printTab($strOptions, 'tbl_properties_options.php3', $url_query);
echo PMA_printTab($strEmpty, $lnk6, $arg6, $att6);
echo PMA_printTab($strDrop, 'sql.php3', $arg7, $att7);
echo "\n";
?>
    </tr>
</table>
<br />

