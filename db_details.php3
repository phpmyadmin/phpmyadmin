<?php
/* $Id$ */

/**
 * Gets the variables sent to this script and diplays headers
 */
require('./grab_globals.inc.php3');
if (!isset($message)) {
    include('./header.inc.php3');
} else {
    show_message($message);
}


/**
 * Displays an html table with all the tables contained into the current
 * database
 */
// 1. Gets the list of the tables
$tables     = mysql_list_tables($db);
$num_tables = @mysql_numrows($tables);

// speedup view on locked tables - staybyte - 11 June 2001
if ($num_tables > 0 && MYSQL_MAJOR_VERSION >= 3.23 && intval(MYSQL_MINOR_VERSION) >= 3) {
    // Special speedup for newer MySQL Versions (in 4.0 format changed)
    if ($cfgSkipLockedTables == true && MYSQL_MAJOR_VERSION == 3.23 && intval(MYSQL_MINOR_VERSION) >= 30) {
        $query  = 'SHOW OPEN TABLES FROM ' . db_name($db);
        $result = mysql_query($query);
        // Blending out tables in use
        if ($result != false && mysql_num_rows($result) > 0) {
            while ($tmp = mysql_fetch_array($result)) {
                // if in use memorize tablename
                if (preg_match('/in_use=[1-9]+/', $tmp['Comment'])) {
                    $sot_cache[$tmp[0]] = true;
                }
            }
            mysql_free_result($result);

            if (isset($sot_cache)) {
                $query  = 'SHOW TABLES FROM ' . db_name($db);
                $result = mysql_query($query);
                if ($result != false && mysql_num_rows($result) > 0) {
                    while ($tmp = mysql_fetch_array($result)) {
                        if (!isset($sot_cache[$tmp[0]])) {
                            $sts_result  = mysql_query('SHOW TABLE STATUS FROM ' . db_name($db) . ' LIKE \'' . addslashes($tmp[0]) . '\'');
                            $sts_tmp     = mysql_fetch_array($sts_result);
                            $tbl_cache[] = $sts_tmp;
                        } else { // table in use
                            $tbl_cache[] = array('Name' => $tmp[0]);
                        }
                    }
                    mysql_free_result($result);
                    $sot_ready = true;
                }
            }
        }
    }
    if (!isset($sot_ready)) {
        $result = mysql_query('SHOW TABLE STATUS FROM ' . db_name($db));
        if ($result != false && mysql_num_rows($result) > 0) {
            while ($sts_tmp = mysql_fetch_array($result)) {
                $tbl_cache[] = $sts_tmp;
            }
            mysql_free_result($result);
        }
    }
}

// 2. Displays tables
if ($num_tables == 0) {
    echo $strNoTablesFound . "\n";
}
// show table size on mysql >= 3.23 - staybyte - 11 June 2001
else if (MYSQL_MAJOR_VERSION >= 3.23 && isset($tbl_cache)) {
    ?>



<!-- TABLE LIST -->

<table border="<?php echo $cfgBorder; ?>">
<tr>
    <th><?php echo ucfirst($strTable); ?></th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
    <th><?php echo ((!empty($strSize)) ? ucfirst($strSize) : '&nbsp;'); ?></th>
</tr>
    <?php
    $i = $sum_entries = $sum_size = 0;
    while (list($keyname, $sts_data) = each($tbl_cache)) {
        $table   = $sts_data['Name'];
        $query   = "?server=$server&lang=$lang&db=$db&table=$table&goto=db_details.php3";
        $bgcolor = ($i++ % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td class="data">
        <b><?php echo $table; ?></b>
    </td>
    <td>
        <a href="sql.php3<?php echo $query; ?>&sql_query=<?php echo urlencode("SELECT * FROM $table"); ?>&pos=0"><?php echo $strBrowse; ?></a>
    </td>
    <td>
        <a href="tbl_select.php3<?php echo $query; ?>"><?php echo $strSelect; ?></a>
    </td>
    <td>
        <a href="tbl_change.php3<?php echo $query; ?>"><?php echo $strInsert; ?></a>
    </td>
    <td>
        <a href="tbl_properties.php3<?php echo $query; ?>"><?php echo $strProperties; ?></a>
    </td>
    <td>
        <a href="sql.php3<?php echo $query; ?>&reload=true&sql_query=<?php echo urlencode("DROP TABLE $table"); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . $table . ' ' . $strHasBeenDropped); ?>"><?php echo $strDrop; ?></a>
    </td>
    <td>
        <a href="sql.php3<?php echo $query; ?>&sql_query=<?php echo urlencode("DELETE FROM $table"); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . $table . ' ' . $strHasBeenEmptied); ?>"><?php echo $strEmpty; ?></a>
    </td>
        <?php
        echo "\n";
        if (isset($sts_data['Rows'])) {
            $tblsize                    =  $sts_data['Data_length'] + $sts_data['Index_length'];
            $sum_size                   += $tblsize;
            $sum_entries                += $sts_data['Rows'];
            list($formated_size, $unit) =  format_byte_down($tblsize, 3, 1);
            ?>
    <td align="right">
        <?php echo number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
    </td>
    <td align="right">
        &nbsp;&nbsp;
        <a href="tbl_properties.php3<?php echo $query; ?>>#showusage"><?php echo $formated_size . ' ' . $unit; ?></a>
    </td>
            <?php
        } else {
            ?>
    <td colspan="3" align="center">
        <?php if (!empty($strInUse)) {echo $strInUse;}; echo "\n"; ?>
    </td>
            <?php
        }
        echo "\n";
        ?>
</tr>
        <?php
    }
    // Show Summary
    list ($sum_formated,$unit)=format_byte_down($sum_size,3,1);
    echo "\n";
    ?>
<tr bgcolor="<?php echo $cfgThBgcolor; ?>">
    <td colspan="7" align="center">
        <?php if (!empty($strSum)) {echo $strSum;}; echo "\n"; ?>
    </td>
    <td align="right">
        <?php echo number_format($sum_entries, 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
    </td>
    <td align="right">
        <?php echo $sum_formated . ' '. $unit . "\n"; ?>
    </td>
</tr>
</table>
    <?php
} // end case mysql >= 3.23

else {
    $i = 0;
    echo "\n";
    ?>



<!-- TABLE LIST -->

<table border="<?php echo $cfgBorder; ?>">
<tr>
    <th><?php echo ucfirst($strTable); ?></th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
</tr>
    <?php
    while ($i < $num_tables) {
        $table   = mysql_tablename($tables, $i);
        $query   = "?server=$server&lang=$lang&db=$db&table=$table&goto=db_details.php3";
        $bgcolor = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td class="data">
        <b><?php echo $table; ?></b>
    </td>
    <td>
        <a href="sql.php3<?php echo $query; ?>&sql_query=<?php echo urlencode("SELECT * FROM $table"); ?>&pos=0"><?php echo $strBrowse; ?></a>
    </td>
    <td>
        <a href="tbl_select.php3<?php echo $query; ?>"><?php echo $strSelect; ?></a>
    </td>
    <td>
        <a href="tbl_change.php3<?php echo $query; ?>"><?php echo $strInsert; ?></a>
    </td>
    <td>
        <a href="tbl_properties.php3<?php echo $query; ?>"><?php echo $strProperties; ?></a>
    </td>
    <td>
        <a href="sql.php3<?php echo $query; ?>&reload=true&sql_query=<?php echo urlencode("DROP TABLE $table"); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . $table . ' ' . $strHasBeenDropped); ?>"><?php echo $strDrop; ?></a>
    </td>
    <td>
        <a href="sql.php3<?php echo $query; ?>&sql_query=<?php echo urlencode("DELETE FROM $table"); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . $table . ' ' . $strHasBeenEmptied); ?>"><?php echo $strEmpty; ?></a>
    </td>
    <td align="right">
        <?php echo number_format(count_records($db, $table), 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
    </td>
</tr>
        <?php
        $i++;
    }
    echo "\n";
    ?>
</table>
	<?php
} // end case mysql < 3.23

$query = "?server=$server&lang=$lang&db=$db&goto=db_details.php3";
echo "\n";
?>
<hr />



<?php
/**
 * Database work
 */
?>
<!-- DATABASE WORK -->
<ul>
<?php
if ($num_tables > 0) {
    ?>
    <!-- Printable view of a table -->
    <li style="margin-bottom: 10px">
        <a href="db_printview.php3<?php echo $query; ?>"><?php echo $strPrintView; ?></a>
    </li>
    <?php
}
?>

    <!-- Query box, sql file loader and bookmark support -->
    <li>
        <form method="post" action="db_readdump.php3" enctype="multipart/form-data">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="goto" value="db_details.php3" />
            <input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>" />
            <?php echo $strRunSQLQuery . $db . ' ' . show_docu('manual_Reference.html#SELECT'); ?>&nbsp;:<br />
<textarea name="sql_query" cols="40" rows="3" wrap="virtual" style="width: <?php echo $cfgMaxInputsize; ?>; margin-bottom: 5px">
<?php echo (isset($sql_query) ? $sql_query : '');?>
</textarea><br />
            <?php echo "<i>$strOr</i> $strLocationTextfile"; ?>&nbsp;:<br />
            <input type="file" name="sql_file" style="margin-bottom: 5px" /><br />
<?php
// Bookmark Support
if ($cfgBookmark['db'] && $cfgBookmark['table']) {
    if (($bookmark_list = list_bookmarks($db, $cfgBookmark)) && count($bookmark_list) > 0) {
        echo "            <i>$strOr</i> $strBookmarkQuery&nbsp;:<br />\n";
        echo '            <select name="id_bookmark" style="margin-bottom: 5px">' . "\n";
        echo '                <option value=""></option>' . "\n";
        while(list($key,$value) = each($bookmark_list)) {
            echo '                <option value="' . htmlentities($value) . '">' . htmlentities($key) . '</option>' . "\n";
        }
        echo '            </select>' . "\n";
        echo '            <input type="radio" name="action_bookmark" value="0" checked="checked" />' . $strSubmit . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="1" />' . $strBookmarkView . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="2" />' . $strDelete . "\n";
        echo '            <br />' . "\n";
    }
}
?>
            <input type="submit" name="SQL" value="<?php echo $strGo; ?>" />
        </form>
    </li>


<?php
/**
 * Query by example and dump of the db
 * Only displayed if there is at least one table in the db
 */
if ($num_tables > 0) {
    ?>
    <!-- Query by an example -->
    <li style="margin-bottom: 10px">
        <a href="tbl_qbe.php3<?php echo $query;?>"><?php echo $strQBE; ?></a>
    </li>
    
    <!-- Dump of a database -->
    <li>
        <form method="post" action="tbl_dump.php3" name="db_dump">
        <?php echo $strViewDumpDB; ?><br />
        <table>
    <?php
    $tables     = mysql_list_tables($db);
    $num_tables = @mysql_numrows($tables);
    if ($num_tables > 1) {
        echo "\n";
        ?>
        <tr>
            <td colspan="2">
                <select name="table_select[]" size="5" multiple="multiple">
        <?php
        $i = 0;
        echo "\n";
        while ($i < $num_tables) {
            $table = mysql_tablename($tables, $i);
            echo '                    <option value="' . $table . '">' . $table . '</option>' . "\n";
            $i++;
        }
        ?>
                </select>
            </td>
        </tr>
        <?php
    }
    echo "\n";
    ?>
        <tr>
            <td>
                <input type="radio" name="what" value="structure" checked="checked" />
                <?php echo $strStrucOnly . "\n"; ?>
            </td>
            <td>
                <input type="checkbox" name="drop" value="1" />
                <?php echo $strStrucDrop . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td>
                <input type="radio" name="what" value="data" />
                <?php echo $strStrucData . "\n"; ?>
            </td>
             <td>
                <input type="checkbox" name="showcolumns" value="yes" />
                <?php echo $strCompleteInserts . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td>
                <input type="radio" name="what" value="dataonly" />
                <?php echo $strDataOnly . "\n"; ?>
            </td>
            <td>
                <input type="checkbox" name="asfile" value="sendit"<?php if (function_exists('gzencode')) { ?>onclick="if (!document.forms['db_dump'].elements['asfile'].checked) document.forms['db_dump'].elements['gzip'].checked = false<?php }; ?>" />
                <?php echo $strSend . "\n"; ?>
    <?php
    // gzip encode feature
    if (function_exists('gzencode')) {
        echo "\n";
        ?>
                (<input type="checkbox" name="gzip" value="gzip" onclick="document.forms['db_dump'].elements['asfile'].checked = true" /><?php echo $strGzip; ?>)
        <?php
    }
    echo "\n";
    ?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input type="submit" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
        </table>
        <input type="hidden" name="server" value="<?php echo $server; ?>" />
        <input type="hidden" name="lang" value="<?php echo $lang;?>" />
        <input type="hidden" name="db" value="<?php echo $db;?>" />
        </form>
    </li>
    <?php
} // end of create dump if there is at least one table in the db
?>

    <!-- Create a new table --> 
    <li>
        <form method="post" action="tbl_create.php3">
        <input type="hidden" name="server" value="<?php echo $server; ?>" />
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="db" value="<?php echo $db; ?>" />
<?php
echo '        ' . $strCreateNewTable . $db . '&nbsp;:<br />' . "\n";
echo '        ' . $strName . '&nbsp;:&nbsp;' . "\n";
echo '        ' . '<input type="text" name="table" />' . "\n";
//    echo '        ' . $strNumberIndexes. '&nbsp;:&nbsp;' . "\n";
//    echo '        ' . '<input type="text" name="num_indexes" size="2" />' . "\n";
echo '        ' . '<br />' . "\n";
echo '        ' . $strFields . '&nbsp;:&nbsp;' . "\n"; 
echo '        ' . '<input type="text" name="num_fields" size="2" />' . "\n";
echo '        ' . '&nbsp;<input type="submit" value="' . $strGo . '" />' . "\n";
?>
        </form>
    </li>

    <!-- Drop table -->
    <li>
        <a href="sql.php3?server=<?php echo $server; ?>&lang=<?php echo $lang; ?>&db=<?php echo $db; ?>&sql_query=<?php echo urlencode('DROP DATABASE ' . db_name($db)); ?>&zero_rows=<?php echo urlencode($strDatabase . ' ' . db_name($db) . ' ' . $strHasBeenDropped); ?>&goto=main.php3&reload=true"><?php echo $strDropDB . ' ' . $db;?></a>
        <?php echo show_docu('manual_Reference.html#DROP_DATABASE') . "\n"; ?>
    </li>
</ul>


<?php
/**
 * Displays the footer
 */
require('./footer.inc.php3');
echo "\n";
?>

</body>

</html>