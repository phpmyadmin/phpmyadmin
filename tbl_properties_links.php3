<?php
/* $Id$ */


/**
 * Prepares links
 */
if ($num_rows > 0) {
    $lnk2    = '<a href="sql.php3?' . $url_query
             . '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($table))
             . '&amp;pos=0">';
    $lnk4    = '<a href="tbl_select.php3?' . $url_query . '">';
    $ln6_stt = (PMA_MYSQL_INT_VERSION >= 40000)
             ? 'TRUNCATE '
             : 'DELETE FROM ';
    $lnk6    = '<a href="sql.php3?' . $url_query . '&amp;sql_query='
             . urlencode($ln6_stt . PMA_backquote($table))
             .  '&amp;zero_rows='
             .  urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
             .  '"' . "\n"
             . '         onclick="return confirmLink(this, \'' . $ln6_stt . PMA_jsFormat($table) . '\')">';
} else {
    $lnk2 = '';
    $lnk4 = '';
    $lnk6 = '';
}


/**
 * Displays links
 */
?>
<p>
    [&nbsp;
    <a href="tbl_properties.php3?<?php echo $url_query; ?>">
        <b><?php echo $strHome; ?></b></a>&nbsp;|
    <?php echo $lnk2 . "\n"; ?>
        <b><?php echo $strBrowse; ?></b><?php if ($lnk2) echo '</a>'; ?>&nbsp;|
    <a href="tbl_properties_structure.php3?<?php echo $url_query; ?>">
        <b><?php echo $strStructure; ?></b></a>&nbsp;|
    <?php echo $lnk4 . "\n"; ?>
        <b><?php echo $strSelect; ?></b><?php if ($lnk4) echo '</a>'; ?>&nbsp;|
    <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a>&nbsp;|
    <?php echo $lnk6 . "\n"; ?>
        <b><?php echo $strEmpty; ?></b><?php if ($lnk6) echo '</a>'; ?>&nbsp;|
    <a href="tbl_properties_export.php3?<?php echo $url_query; ?>">
        <b><?php echo $strExport; ?></b></a>&nbsp;|
    <a href="tbl_properties_operations.php3?<?php echo $url_query; ?>">
        <b><?php echo $strOperations; ?></b></a>&nbsp;|
    <a href="tbl_properties_options.php3?<?php echo $url_query; ?>">
        <b><?php echo $strOptions; ?></b></a>
    &nbsp;]&nbsp;&nbsp;&nbsp;
    [&nbsp;
    <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties' . $sub_part . '.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
        class="drop" onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
        <b><?php echo $strDrop; ?></b></a>
    &nbsp;]
</p>
<hr />
