<?php
/* $Id$ */

// count amount of navigation tabs
$db_details_links_count_tabs = 0;

/**
 * Prepares links
 */
if ($num_rows > 0) {
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
    $att6    = 'onclick="return confirmLink(this, \'' . $ln6_stt . PMA_jsFormat($table) . '\')"';
} else {
    $lnk2 = '';
    $lnk4 = '';
    $lnk6 = '';
}

$lnk7 = "sql.php3";
$arg7 = ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query) . '&amp;back=tbl_properties' . $sub_part . '.php3&amp;reload=1&amp;sql_query= ' . urlencode('DROP TABLE ' . PMA_backquote($table) ) . '&amp;zero_rows=' . urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table)));
$att7 = 'class="drop" onclick="return confirmLink(this, \'DROP TABLE ' . PMA_jsFormat($table) . '\')"';

/**
 * Displays links
 */
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td width="8">&nbsp;</td>
<?php
echo printTab($strSQL,"tbl_properties.php3",$url_query);
echo printTab($strBrowse,$lnk2,$arg2);
echo printTab($strStructure,"tbl_properties_structure.php3",$url_query);
echo printTab($strSelect,$lnk4,$arg4);
echo printTab($strInsert,"tbl_change.php3",$url_query);
echo printTab($strEmpty,$lnk6,$arg6,$att6);
echo printTab($strExport,"tbl_properties_export.php3",$url_query);
echo printTab($strOperations,"tbl_properties_operations.php3",$url_query);
echo printTab($strOptions,"tbl_properties_options.php3",$url_query);
echo printTab($strDrop,"sql.php3",$arg7,$att7);

?>

	</tr>
	<tr>
		<td colspan="<?php echo ($db_details_links_count_tabs*2+1); ?>" bgcolor="gray" class="topline"><img width="1" height="1" alt="" src="images/spacer.gif" /></td>
	</tr>
</table>