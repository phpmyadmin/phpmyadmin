<?php
/* $Id$ */


/**
 * Prepares links
 */
// Export link if there is at least one table
if ($num_tables > 0) {
    $lnk3 = '<a href="db_details_export.php3?' . $url_query . '">';
    $lnk4 = '<a href="db_search.php3?' . $url_query . '">';
}
else {
    $lnk3 = '';
    $lnk4 = '';
}
// Drop link if allowed
if (!$cfg['AllowUserDropDatabase']) {
    // Check if the user is a Superuser
    $result                       = @PMA_mysql_query('USE mysql');
    $cfg['AllowUserDropDatabase'] = (!PMA_mysql_error());
}
if ($cfg['AllowUserDropDatabase']) {
    $lnk5 = '<a href="sql.php3?' . $url_query . '&amp;sql_query='
          . urlencode('DROP DATABASE ' . PMA_backquote($db))
          . '&amp;zero_rows='
          . urlencode(sprintf($strDatabaseHasBeenDropped, htmlspecialchars(PMA_backquote($db))))
          . '&amp;goto=main.php3&amp;back=db_details' . $sub_part . '.php3&amp;reload=1"' . "\n"
          . '         class="drop" '
          . 'onclick="return confirmLink(this, \'DROP DATABASE ' . PMA_jsFormat($db) . '\')">';
}
else {
    $lnk5 = '';
}


/**
 * Displays links
 */
?>
<p>
    [&nbsp;
    <a href="db_details.php3?<?php echo $url_query; ?>&amp;db_query_force=1">
        <b><?php echo $strSQL; ?></b></a>&nbsp;|
    <a href="db_details_structure.php3?<?php echo $url_query; ?>">
        <b><?php echo $strStructure; ?></b></a>&nbsp;|
    <?php echo $lnk3 . "\n"; ?>
         <b><?php echo $strExport; ?></b><?php if ($lnk3) echo '</a>' ?>&nbsp;|
    <?php echo $lnk4 . "\n"; ?>
         <b><?php echo $strSearch; ?></b><?php if ($lnk4) echo '</a>'; echo "\n";
    /**
     * Query by example and dump of the db
     * Only displayed if there is at least one table in the db
     */
    if ($num_tables > 0) {
        ?>
       |&nbsp;<b><a href="tbl_qbe.php3?<?php echo $url_query; ?>"><?php echo $strQBE; ?></a></b>
        <?php
    } // end if
    
    echo "\n";
    ?>
    &nbsp;]&nbsp;&nbsp;&nbsp;

<?php
if ($lnk5) {
    ?>
    [&nbsp;
    <?php echo $lnk5 . "\n"; ?>
         <b><?php echo $strDrop; ?></b></a>
    &nbsp;]
    <?php
} // end if
echo "\n";
?>
</p>
<hr />
