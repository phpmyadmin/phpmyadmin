<?php
/* $Id$ */
?>
<p>
    [ <?php if ($num_rows > 0) { 
    ?>
    <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0">
    <?php
          } // end if
    ?>
        <b><?php echo $strBrowse; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <?php if ($num_rows > 0) { 
    ?>
    <a href="tbl_select.php3?<?php echo $url_query; ?>">
    <?php
          } // end if
    ?>
        <b><?php echo $strSelect; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <?php
    if ($num_rows > 0) {
        echo '<a href="sql.php3?' . $url_query . '&amp;sql_query=';
        if (PMA_MYSQL_INT_VERSION >= 40000) {
            echo urlencode('TRUNCATE ' . PMA_backquote($table))
                 . '&amp;zero_rows='
                 . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
                 . '"' . "\n"
                 . '         onclick="return confirmLink(this, \'TRUNCATE ';
        } // end if
        else {
            echo urlencode('DELETE FROM ' . PMA_backquote($table))
                 . '&amp;zero_rows='
                 . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
                 . '"'
                 . "\n"
                 . '         onclick="return confirmLink(this, \'DELETE FROM ';
        } // end else
        echo PMA_jsFormat($table)
             . '\')">'
             . "\n";
    } // end if
    ?>
         <b><?php echo $strEmpty; ?></b></a> ]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strDrop; ?></b></a> ]
</p>
