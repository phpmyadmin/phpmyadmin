<?php
/* $Id$ */


/**
 * Prepares links
 */
$lnk3    = '<a href="tbl_properties_export.php3?' . $url_query . '">';

if ($num_rows > 0) {
    $lnk1    = '<a href="sql.php3?' . $url_query
             . '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($table))
             . '&amp;pos=0">';
    $lnk2    = '<a href="tbl_select.php3?' . $url_query . '">';
    $lnk3    = '<a href="tbl_properties_export.php3?' . $url_query . '">';
    $ln4_stt = (PMA_MYSQL_INT_VERSION >= 40000)
             ? 'TRUNCATE '
             : 'DELETE FROM ';
    $lnk4    = '<a href="sql.php3?' . $url_query . '&amp;sql_query='
             . urlencode($ln4_stt . PMA_backquote($table))
             .  '&amp;zero_rows='
             .  urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
             .  '"' . "\n"
             . '         onclick="return confirmLink(this, \'' . $ln4_stt . PMA_jsFormat($table) . '\')">';
} else {
    $lnk1 = '';
    $lnk2 = '';
    $lnk4 = '';
}
?>
<p>
    [ <?php echo $lnk1 . "\n"; ?>
          <b><?php echo $strBrowse; ?></b><?php if ($lnk1) echo '</a>'; ?> ]&nbsp;&nbsp;&nbsp;
    [ <?php echo $lnk2 . "\n"; ?>
          <b><?php echo $strSelect; ?></b><?php if ($lnk2) echo '</a>'; ?> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <?php echo $lnk4 . "\n"; ?>
         <b><?php echo $strEmpty; ?></b><?php if ($lnk4) echo '</a>'; ?> ]&nbsp;&nbsp;&nbsp;
    [ <?php echo $lnk3 . "\n"; ?>
         <b><?php echo $strExport; ?></b><?php if ($lnk3) echo '</a>'; ?> ]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strDrop; ?></b></a> ]
</p>
