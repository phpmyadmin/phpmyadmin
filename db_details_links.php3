<?php
/* $Id$ */

// count amount of navigation tabs
$db_details_links_count_tabs = 0;

/**
 * Prepares links
 */
// Export link if there is at least one table
if ($num_tables > 0) {
    $lnk3 = 'db_details_export.php3';
    $arg3 = $url_query;
    $lnk4 = 'db_search.php3';
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
    $links_result                       = @PMA_mysql_query('USE mysql');
    $cfg['AllowUserDropDatabase'] = (!PMA_mysql_error());
}
if ($cfg['AllowUserDropDatabase']) {
    $lnk5 = 'sql.php3';
    $arg5 = $url_query . '&amp;sql_query='
          . urlencode('DROP DATABASE ' . PMA_backquote($db))
          . '&amp;zero_rows='
          . urlencode(sprintf($strDatabaseHasBeenDropped, htmlspecialchars(PMA_backquote($db))))
          . '&amp;goto=main.php3&amp;back=db_details' . $sub_part . '.php3&amp;reload=1"';
    $att5 = 'class="drop" '
          . 'onclick="return confirmLink(this, \'DROP DATABASE ' . PMA_jsFormat($db) . '\')"';
}
else {
    $lnk5 = '';
}

/**
 * Displays tab links
 */
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="tabs">
	<tr>
		<td width="8">&nbsp;</td>
<?php
echo printTab($strStructure,"db_details_structure.php3",$url_query);
echo printTab($strSQL,"db_details.php3",$url_query."&amp;db_query_force=1");
echo printTab($strExport,$lnk3,$arg3);
echo printTab($strSearch,$lnk4,$arg4);

/**
 * Query by example and dump of the db
 * Only displayed if there is at least one table in the db
 */
if ($num_tables > 0) {
	echo printTab($strQBE,"db_details_qbe.php3",$url_query);
} // end if

/**
 * Displays drop link
 */
if ($lnk5) {
   echo printTab($strDrop,$lnk5,$arg5,$att5);
} // end if
echo "\n";
?>
	</tr>
</table>
<br />
