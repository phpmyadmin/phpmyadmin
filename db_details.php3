<?php
/* $Id$ */


/**
 * Gets the variables sent to this script and displays headers
 */
require('./grab_globals.inc.php3');
if (!isset($message)) {
    include('./header.inc.php3');
    // Reloads the navigation frame via JavaScript if required
    if (!empty($reload) && $reload == 'true') {
        echo "\n";
        ?>
<script type="text/javascript" language="javascript1.2">
<!--
window.parent.frames['nav'].location.replace('./left.php3?lang=<?php echo $lang; ?>&server=<?php echo $server; ?>&db=<?php echo urlencode($db); ?>');
//-->
</script>
        <?php
    }
    echo "\n";
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
// Not a valid db name -> back to the welcome page
if (mysql_error() != '') {
    header('Location: ' . $cfgPmaAbsoluteUri . 'main.php3?lang=' . $lang . '&server=' . $server . '&reload=true');
    exit();
}

// speedup view on locked tables - staybyte - 11 June 2001
if ($num_tables > 0 && MYSQL_INT_VERSION >= 32303) {
    // Special speedup for newer MySQL Versions (in 4.0 format changed)
    if ($cfgSkipLockedTables == TRUE && MYSQL_INT_VERSION >= 32330) {
        $query  = 'SHOW OPEN TABLES FROM ' . backquote($db);
        $result = mysql_query($query);
        // Blending out tables in use
        if ($result != FALSE && mysql_num_rows($result) > 0) {
            while ($tmp = mysql_fetch_array($result)) {
                // if in use memorize tablename
                if (eregi('in_use=[1-9]+', $tmp)) {
                    $sot_cache[$tmp[0]] = TRUE;
                }
            }
            mysql_free_result($result);

            if (isset($sot_cache)) {
                $query  = 'SHOW TABLES FROM ' . backquote($db);
                $result = mysql_query($query);
                if ($result != FALSE && mysql_num_rows($result) > 0) {
                    while ($tmp = mysql_fetch_array($result)) {
                        if (!isset($sot_cache[$tmp[0]])) {
                            $sts_result  = mysql_query('SHOW TABLE STATUS FROM ' . backquote($db) . ' LIKE \'' . addslashes($tmp[0]) . '\'');
                            $sts_tmp     = mysql_fetch_array($sts_result);
                            $tbl_cache[] = $sts_tmp;
                        } else { // table in use
                            $tbl_cache[] = array('Name' => $tmp[0]);
                        }
                    }
                    mysql_free_result($result);
                    $sot_ready = TRUE;
                }
            }
        }
    }
    if (!isset($sot_ready)) {
        $result = mysql_query('SHOW TABLE STATUS FROM ' . backquote($db));
        if ($result != FALSE && mysql_num_rows($result) > 0) {
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
else if (MYSQL_INT_VERSION >= 32300 && isset($tbl_cache)) {
    ?>



<!-- TABLE LIST -->

<table border="<?php echo $cfgBorder; ?>">
<tr>
    <th><?php echo ucfirst($strTable); ?></th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
    <th><?php echo ucfirst($strSize); ?></th>
</tr>
    <?php
    $i = $sum_entries = $sum_size = 0;
    while (list($keyname, $sts_data) = each($tbl_cache)) {
        $table     = $sts_data['Name'];
        // Sets parameters for links
        $url_query = 'lang=' . $lang
                   . '&server=' . urlencode($server)
                   . '&db=' . urlencode($db)
                   . '&table=' . urlencode($table)
                   . '&goto=db_details.php3';
        $bgcolor   = ($i++ % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td nowrap="nowrap">
        <b><?php echo htmlspecialchars($table); ?>&nbsp;</b>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('SELECT * FROM ' . backquote($table)); ?>&pos=0">
            <?php echo $strBrowse; ?></a>
    </td>
    <td>
        <a href="tbl_select.php3?<?php echo $url_query; ?>">
            <?php echo $strSelect; ?></a>
    </td>
    <td>
        <a href="tbl_change.php3?<?php echo $url_query; ?>">
            <?php echo $strInsert; ?></a>
    </td>
    <td>
        <a href="tbl_properties.php3?<?php echo $url_query; ?>">
            <?php echo $strProperties; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&reload=true&sql_query=<?php echo urlencode('DROP TABLE ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenDropped); ?>">
            <?php echo $strDrop; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('DELETE FROM ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenEmptied); ?>">
            <?php echo $strEmpty; ?></a>
    </td>
        <?php
        echo "\n";
        $mergetable         = FALSE;
        $nonisam            = FALSE;
        if (isset($sts_data['Type'])) {
            if ($sts_data['Type'] == 'MRG_MyISAM') {
                $mergetable = TRUE;
            } else if (!eregi('ISAM|HEAP', $sts_data['Type'])) {
                $nonisam    = TRUE;
            }
        }

        if (isset($sts_data['Rows'])) {
            if ($mergetable == FALSE) {
                if ($nonisam == FALSE) {
                    $tblsize                        =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                       += $tblsize;
                    if ($tblsize > 0) {
                        list($formated_size, $unit) =  format_byte_down($tblsize, 3, 1);
                    } else {
                        list($formated_size, $unit) =  format_byte_down($tblsize, 3, 0);
                    }
                } else {
                    $formated_size                  = '&nbsp;-&nbsp;';
                    $unit                           = '';
                }
                if (isset($sts_data['Rows'])) {
                    $sum_entries                    += $sts_data['Rows'];
                }
            }
            // MyISAM MERGE Table
            else if ($mergetable == TRUE) {
                $formated_size = '&nbsp;-&nbsp;';
                $unit          = '';
            }
            else {
                $formated_size = 'unknown';
                $unit          = '';
            }
            ?>
    <td align="right">
            <?php
            echo "\n" . '        ';
            if ($mergetable == TRUE) {
                echo '<i>' . number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . '</i>' . "\n";
            } else {
                echo number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n";
            }
            ?>
    </td>
    <td align="right" nowrap="nowrap">
        &nbsp;&nbsp;
        <a href="tbl_properties.php3?<?php echo $url_query; ?>#showusage"><?php echo $formated_size . ' ' . $unit; ?></a>
    </td>
            <?php
        } else {
            ?>
    <td colspan="3" align="center">
        <?php echo $strInUse . "\n"; ?>
    </td>
            <?php
        }
        echo "\n";
        ?>
</tr>
        <?php
    }
    // Show Summary
    list($sum_formated,$unit) = format_byte_down($sum_size,3,1);
    echo "\n";
    ?>
<tr bgcolor="<?php echo $cfgThBgcolor; ?>">
    <td align="center">
        <b><?php echo sprintf($strTables, number_format($num_tables, 0, $number_decimal_separator, $number_thousands_separator)); ?></b>
    </td>
    <td colspan="6" align="center">
        <b><?php echo $strSum; ?></b>
    </td>
    <td align="right" nowrap="nowrap">
        <b><?php echo number_format($sum_entries, 0, $number_decimal_separator, $number_thousands_separator); ?></b>
    </td>
    <td align="right" nowrap="nowrap">
        <b><?php echo $sum_formated . ' '. $unit; ?></b>
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
        $table     = mysql_tablename($tables, $i);
        // Sets parameters for links
        $url_query = 'lang=' . $lang
                   . '&server=' . urlencode($server)
                   . '&db=' . urlencode($db)
                   . '&table=' . urlencode($table)
                   . '&goto=db_details.php3';
        $bgcolor   = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td class="data">
        <b><?php echo $table; ?></b>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('SELECT * FROM ' . backquote($table)); ?>&pos=0"><?php echo $strBrowse; ?></a>
    </td>
    <td>
        <a href="tbl_select.php3?<?php echo $url_query; ?>"><?php echo $strSelect; ?></a>
    </td>
    <td>
        <a href="tbl_change.php3?<?php echo $url_query; ?>"><?php echo $strInsert; ?></a>
    </td>
    <td>
        <a href="tbl_properties.php3?<?php echo $url_query; ?>"><?php echo $strProperties; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&reload=true&sql_query=<?php echo urlencode('DROP TABLE ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenDropped); ?>"><?php echo $strDrop; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('DELETE FROM ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenEmptied); ?>"><?php echo $strEmpty; ?></a>
    </td>
    <td align="right">
        <?php count_records($db, $table); echo "\n"; ?>
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

echo "\n";
?>
<hr />


<?php
/**
 * Database work
 */
$url_query = 'lang=' . $lang
           . '&server=' . urlencode($server)
           . '&db=' . urlencode($db)
           . '&goto=db_details.php3';
if (isset($show_query) && $show_query == 'y') {
    // This script has been called by db_readdump.php3
    if (isset($sql_query_cpy)) {
        $query_to_display = $sql_query_cpy;
    }
    // Other cases
    else if (get_magic_quotes_gpc()) {
        $query_to_display = stripslashes($sql_query);
    }
    else {
        $query_to_display = $sql_query;
    }
} else {
    $query_to_display     = '';
}
?>
<!-- DATABASE WORK -->
<script type="text/javascript" language="javascript">
<!--
var errorMsg0 = '<?php echo(str_replace('\'', '\\\'', $strFormEmpty)); ?>';
var errorMsg1 = '<?php echo(str_replace('\'', '\\\'', $strNotNumber)); ?>';
var errorMsg2 = '<?php echo(str_replace('\'', '\\\'', $strNotValidNumber)); ?>';
//-->
</script>
<script src="functions.js" type="text/javascript" language="javascript"></script>

<ul>
<?php
if ($num_tables > 0) {
    ?>
    <!-- Printable view of a table -->
    <li>
        <div style="margin-bottom: 10px"><a href="db_printview.php3?<?php echo $url_query; ?>"><?php echo $strPrintView; ?></a></div>
    </li>
    <?php
}
?>

    <!-- Query box, sql file loader and bookmark support -->
    <li>
        <form method="post" action="db_readdump.php3" enctype="multipart/form-data"
            onsubmit="return emptySqlQuery(this)">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="goto" value="db_details.php3" />
            <input type="hidden" name="zero_rows" value="<?php echo htmlspecialchars($strSuccess); ?>" />
            <input type="hidden" name="prev_sql_query" value="<?php echo ((!empty($query_to_display)) ? urlencode($query_to_display) : ''); ?>" />
            <?php echo $strRunSQLQuery . $db . ' ' . show_docu('manual_Reference.html#SELECT'); ?>&nbsp;:<br />
            <div style="margin-bottom: 5px">
<textarea name="sql_query" cols="<?php echo $cfgTextareaCols; ?>" rows="<?php echo $cfgTextareaRows; ?>" wrap="virtual">
<?php echo ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : ''); ?>
</textarea><br />
            <input type="checkbox" name="show_query" value="y" checked="checked" />&nbsp;
                <?php echo $strShowThisQuery; ?><br />
            </div>
            <?php echo "<i>$strOr</i> $strLocationTextfile"; ?>&nbsp;:<br />
            <div style="margin-bottom: 5px">
            <input type="file" name="sql_file" /><br />
            </div>
<?php
// Bookmark Support
if ($cfgBookmark['db'] && $cfgBookmark['table']) {
    if (($bookmark_list = list_bookmarks($db, $cfgBookmark)) && count($bookmark_list) > 0) {
        echo "            <i>$strOr</i> $strBookmarkQuery&nbsp;:<br />\n";
        echo '            <div style="margin-bottom: 5px">' . "\n";
        echo '            <select name="id_bookmark">' . "\n";
        echo '                <option value=""></option>' . "\n";
        while (list($key, $value) = each($bookmark_list)) {
            echo '                <option value="' . $value . '">' . htmlentities($key) . '</option>' . "\n";
        }
        echo '            </select>' . "\n";
        echo '            <input type="radio" name="action_bookmark" value="0" checked="checked" style="vertical-align: middle" />' . $strSubmit . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="1" style="vertical-align: middle" />' . $strBookmarkView . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="2" style="vertical-align: middle" />' . $strDelete . "\n";
        echo '            <br />' . "\n";
        echo '            </div>' . "\n";
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
    <li>
        <div style="margin-bottom: 10px"><a href="tbl_qbe.php3?<?php echo $url_query; ?>"><?php echo $strQBE; ?></a></div>
    </li>
    
    <!-- Dump of a database -->
    <li>
        <form method="post" action="tbl_dump.php3" name="db_dump">
        <?php echo $strViewDumpDB; ?><br />
        <table>
        <tr>
    <?php
    $colspan    = '';
    $tables     = mysql_list_tables($db);
    $num_tables = @mysql_numrows($tables);
    if ($num_tables > 1) {
        $colspan = ' colspan="2"';
        echo "\n";
        ?>
            <td>
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
        <?php
    }
    echo "\n";
    ?>
            <td valign="middle">
                <input type="radio" name="what" value="structure" checked="checked" />
                <?php echo $strStrucOnly; ?><br />
                <input type="radio" name="what" value="data" />
                <?php echo $strStrucData; ?><br />
                <input type="radio" name="what" value="dataonly" />
                <?php echo $strDataOnly . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td<?php echo $colspan; ?>>
                <input type="checkbox" name="drop" value="1" />
                <?php echo $strStrucDrop . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td<?php echo $colspan; ?>>
                <input type="checkbox" name="showcolumns" value="yes" />
                <?php echo $strCompleteInserts . "\n"; ?>
            </td>
        </tr>
        <tr>
            <td<?php echo $colspan; ?>>
                <input type="checkbox" name="extended_ins" value="yes" />
                <?php echo $strExtendedInserts . "\n"; ?>
            </td>
        </tr>
    <?php
    // Add backquotes checkbox
    if (MYSQL_INT_VERSION >= 32306) {
        ?>
        <tr>
            <td<?php echo $colspan; ?>>
                <input type="checkbox" name="use_backquotes" value="1" />
                <?php echo $strUseBackquotes . "\n"; ?>
            </td>
        </tr>
        <?php
    } // end backquotes feature
    echo "\n";
    ?>
        <tr>
            <td<?php echo $colspan; ?>>
                <input type="checkbox" name="asfile" value="sendit" onclick="return checkTransmitDump(this.form, 'transmit')" />
                <?php echo $strSend . "\n"; ?>
    <?php
    // gzip and bzip2 encode features
    if (PHP_INT_VERSION >= 40004) {
        $is_gzip = (isset($cfgGZipDump) && $cfgGZipDump && @function_exists('gzencode'));
        $is_bzip = (isset($cfgBZipDump) && $cfgBZipDump && @function_exists('bzcompress'));
        if ($is_gzip || $is_bzip) {
            echo "\n" . '                (';
            if ($is_gzip) {
                ?>
                <input type="checkbox" name="gzip" value="gzip" onclick="return checkTransmitDump(this.form, 'gzip')" /><?php echo $strGzip . (($is_bzip) ? '&nbsp;' : '') . "\n"; ?>
                <?php
            }
            if ($is_bzip) {
                echo "\n"
                ?>
                <input type="checkbox" name="bzip" value="bzip" onclick="return checkTransmitDump(this.form, 'bzip')" /><?php echo $strBzip . "\n"; ?>
                <?php
            }
            echo "\n" . '                )';
        }
    }
    echo "\n";
    ?>
            </td>
        </tr>
        <tr>
            <td<?php echo $colspan; ?>>
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
        <form method="post" action="tbl_create.php3"
            onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1, 1000))">
        <input type="hidden" name="server" value="<?php echo $server; ?>" />
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="db" value="<?php echo $db; ?>" />
<?php
echo '        ' . $strCreateNewTable . htmlspecialchars($db) . '&nbsp;:<br />' . "\n";
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

    <!-- Drop database -->
    <li>
        <a href="sql.php3?server=<?php echo $server; ?>&lang=<?php echo $lang; ?>&db=<?php echo $db; ?>&sql_query=<?php echo urlencode('DROP DATABASE ' . backquote($db)); ?>&zero_rows=<?php echo urlencode($strDatabase . ' ' . htmlspecialchars(backquote($db)) . ' ' . $strHasBeenDropped); ?>&goto=main.php3&back=db_details.php3&reload=true">
            <?php echo $strDropDB . ' ' . htmlspecialchars($db); ?></a>
        <?php echo show_docu('manual_Reference.html#DROP_DATABASE') . "\n"; ?>
    </li>
</ul>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
