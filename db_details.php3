<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/bookmark.lib.php3');


/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = 'main.php3'
           . '?lang=' . $lang
           . '&amp;server=' . $server;
$err_url   = 'db_details.php3'
           . '?lang=' . $lang
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db);


/**
 * Ensures the database exists (else move to the "parent" script) and diplays
 * headers
 */
if (!isset($is_db) || !$is_db) {
    // Not a valid db name -> back to the welcome page
    if (!empty($db)) {
        $is_db = @mysql_select_db($db);
    }
    if (empty($db) || !$is_db) {
        header('Location: ' . $cfgPmaAbsoluteUri . 'main.php3?lang=' . $lang . '&server=' . $server . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit();
    }
} // end if (ensures db exists)

// Displays headers
if (!isset($message)) {
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
    // Reloads the navigation frame via JavaScript if required
    if (isset($reload) && $reload) {
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
    PMA_showMessage($message);
}


/**
 * Drop/delete mutliple tables if required
 */
if ((!empty($submit_mult) && isset($selected_tbl))
    || isset($mult_btnDrop)) {
    $action = 'db_details.php3';
    include('./mult_submits.inc.php3');
}


/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 */
// staybyte: speedup view on locked tables - 11 June 2001
if (PMA_MYSQL_INT_VERSION >= 32303) {
    // Special speedup for newer MySQL Versions (in 4.0 format changed)
    if ($cfgSkipLockedTables == TRUE && PMA_MYSQL_INT_VERSION >= 32330) {
        $local_query  = 'SHOW OPEN TABLES FROM ' . PMA_backquote($db);
        $result       = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
        // Blending out tables in use
        if ($result != FALSE && mysql_num_rows($result) > 0) {
            while ($tmp = mysql_fetch_row($result)) {
                // if in use memorize tablename
                if (eregi('in_use=[1-9]+', $tmp[1])) {
                    $sot_cache[$tmp[0]] = TRUE;
                }
            }
            mysql_free_result($result);

            if (isset($sot_cache)) {
                $local_query = 'SHOW TABLES FROM ' . PMA_backquote($db);
                $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
                if ($result != FALSE && mysql_num_rows($result) > 0) {
                    while ($tmp = mysql_fetch_row($result)) {
                        if (!isset($sot_cache[$tmp[0]])) {
                            $local_query = 'SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . addslashes($tmp[0]) . '\'';
                            $sts_result  = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
                            $sts_tmp     = mysql_fetch_array($sts_result);
                            $tables[]    = $sts_tmp;
                        } else { // table in use
                            $tables[]    = array('Name' => $tmp[0]);
                        }
                    }
                    mysql_free_result($result);
                    $sot_ready = TRUE;
                }
            }
        }
    }
    if (!isset($sot_ready)) {
        $local_query = 'SHOW TABLE STATUS FROM ' . PMA_backquote($db);
        $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
        if ($result != FALSE && mysql_num_rows($result) > 0) {
            while ($sts_tmp = mysql_fetch_array($result)) {
                $tables[] = $sts_tmp;
            }
            mysql_free_result($result);
        }
    }
    $num_tables = (isset($tables) ? count($tables) : 0);
} // end if (PMA_MYSQL_INT_VERSION >= 32303)
else {
    $result     = mysql_list_tables($db);
    $num_tables = @mysql_numrows($result);
    for ($i = 0; $i < $num_tables; $i++) {
        $tables[] = mysql_tablename($result, $i);
    }
    mysql_free_result($result);
}


/**
 * Displays an html table with all the tables contained into the current
 * database
 */
?>

<!-- TABLE LIST -->

<?php
// 1. No tables
if ($num_tables == 0) {
    echo $strNoTablesFound . "\n";
}

// 2. Shows table informations on mysql >= 3.23 - staybyte - 11 June 2001
else if (PMA_MYSQL_INT_VERSION >= 32300) {
    ?>
<form action="db_details.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />

<table border="<?php echo $cfgBorder; ?>">
<tr>
    <td></td>
    <th>&nbsp;<?php echo ucfirst($strTable); ?>&nbsp;</th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
    <th><?php echo ucfirst($strType); ?></th>
    <?php
    if ($cfgShowStats) {
        echo '<th>' . ucfirst($strSize) . '</th>';
    }
    echo "\n";
    ?>
</tr>
    <?php
    $i = $sum_entries = $sum_size = 0;
    while (list($keyname, $sts_data) = each($tables)) {
        $table     = $sts_data['Name'];
        // Sets parameters for links
        $url_query = 'lang=' . $lang
                   . '&amp;server=' . $server
                   . '&amp;db=' . urlencode($db)
                   . '&amp;table=' . urlencode($table)
                   . '&amp;goto=db_details.php3';
        $bgcolor   = ($i++ % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_tbl[]" value="<?php echo urlencode($table); ?>" />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;<b><?php echo htmlspecialchars($table); ?>&nbsp;</b>&nbsp;
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0">
            <?php echo $strBrowse; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_select.php3?<?php echo $url_query; ?>">
            <?php echo $strSelect; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_change.php3?<?php echo $url_query; ?>">
            <?php echo $strInsert; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_properties.php3?<?php echo $url_query; ?>">
            <?php echo $strProperties; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
            onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
            <?php echo $strDrop; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('DELETE FROM ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table))); ?>"
            onclick="return confirmLink(this, 'DELETE FROM <?php echo PMA_jsFormat($table); ?>')">
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
                if ($cfgShowStats && $nonisam == FALSE) {
                    $tblsize                        =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                       += $tblsize;
                    if ($tblsize > 0) {
                        list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 1);
                    } else {
                        list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 0);
                    }
                } else if ($cfgShowStats) {
                    $formated_size                  = '&nbsp;-&nbsp;';
                    $unit                           = '';
                }
                $sum_entries                        += $sts_data['Rows'];
            }
            // MyISAM MERGE Table
            else if ($cfgShowStats && $mergetable == TRUE) {
                $formated_size = '&nbsp;-&nbsp;';
                $unit          = '';
            }
            else if ($cfgShowStats) {
                $formated_size = 'unknown';
                $unit          = '';
            }
            ?>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>">
            <?php
            echo "\n" . '        ';
            if ($mergetable == TRUE) {
                echo '<i>' . number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . '</i>' . "\n";
            } else {
                echo number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n";
            }
            ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;<?php echo (isset($sts_data['Type']) ? $sts_data['Type'] : '&nbsp;'); ?>&nbsp;
    </td>
            <?php
            if ($cfgShowStats) {
                echo "\n";
                ?>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;&nbsp;
        <a href="tbl_properties.php3?<?php echo $url_query; ?>#showusage"><?php echo $formated_size . ' ' . $unit; ?></a>
    </td>
                <?php
                echo "\n";
            } // end if
        } else {
            ?>
    <td colspan="3" align="center" bgcolor="<?php echo $bgcolor; ?>">
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
    if ($cfgShowStats) {
        list($sum_formated, $unit) = PMA_formatByteDown($sum_size, 3, 1);
    }
    echo "\n";
    ?>
<tr>
    <td></td>
    <th align="center" nowrap="nowrap">
        &nbsp;<b><?php echo sprintf($strTables, number_format($num_tables, 0, $number_decimal_separator, $number_thousands_separator)); ?></b>&nbsp;
    </th>
    <th colspan="6" align="center">
        <b><?php echo $strSum; ?></b>
    </th>
    <th align="right" nowrap="nowrap">
        <b><?php echo number_format($sum_entries, 0, $number_decimal_separator, $number_thousands_separator); ?></b>
    </th>
    <th align="center">
        <b>--</b>
    </th>
    <?php
    if ($cfgShowStats) {
        echo "\n";
        ?>
    <th align="right" nowrap="nowrap">
        &nbsp;
        <b><?php echo $sum_formated . ' ' . $unit; ?></b>
    </th>
        <?php
    }
    echo "\n";
    ?>
</tr>

<tr>
    <td colspan="<?php echo (($cfgShowStats) ? '11' : '10'); ?>">
        <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
        <i><?php echo $strWithChecked; ?></i>&nbsp;&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strDrop; ?>" />
        &nbsp;<i><?php echo $strOr; ?></i>&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strEmpty; ?>" />
        &nbsp;<i><?php echo $strOr; ?></i>&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strPrintView; ?>" />
    </td>
</tr>
</table>

</form>
    <?php
} // end case mysql >= 3.23

// 3. Shows tables list mysql < 3.23
else {
    $i = 0;
    echo "\n";
    ?>
<form action="db_details.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />

<table border="<?php echo $cfgBorder; ?>">
<tr>
    <td></td>
    <th>&nbsp;<?php echo ucfirst($strTable); ?>&nbsp;</th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
</tr>
    <?php
    while ($i < $num_tables) {
        // Sets parameters for links
        $url_query = 'lang=' . $lang
                   . '&amp;server=' . $server
                   . '&amp;db=' . urlencode($db)
                   . '&amp;table=' . urlencode($tables[$i])
                   . '&amp;goto=db_details.php3';
        $bgcolor   = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_tbl[]" value="<?php echo urlencode($tables[$i]); ?>" />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" class="data">
        <b>&nbsp;<?php echo $tables[$i]; ?>&nbsp;</b>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($tables[$i])); ?>&amp;pos=0"><?php echo $strBrowse; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_select.php3?<?php echo $url_query; ?>"><?php echo $strSelect; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_change.php3?<?php echo $url_query; ?>"><?php echo $strInsert; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_properties.php3?<?php echo $url_query; ?>"><?php echo $strProperties; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($tables[$i])); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($tables[$i]))); ?>"><?php echo $strDrop; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('DELETE FROM ' . PMA_backquote($tables[$i])); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($tables[$i]))); ?>"><?php echo $strEmpty; ?></a>
    </td>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>">
        <?php PMA_countRecords($db, $tables[$i]); echo "\n"; ?>
    </td>
</tr>
        <?php
        $i++;
    } // end while
    echo "\n";
    ?>
<tr>
    <td colspan="9">
        <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
        <i><?php echo $strWithChecked; ?></i>&nbsp;&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strDrop; ?>" />
        &nbsp;<?php $strOr . "\n"; ?>&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strEmpty; ?>" />
    </td>
<tr>

</table>

</form>
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
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db)
           . '&amp;goto=db_details.php3';
if (isset($show_query) && $show_query == 'y') {
    // This script has been called by read_dump.php3
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
        <form method="post" action="read_dump.php3" enctype="multipart/form-data"
            onsubmit="return checkSqlQuery(this)">
            <input type="hidden" name="is_js_confirmed" value="0" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="goto" value="db_details.php3" />
            <input type="hidden" name="zero_rows" value="<?php echo htmlspecialchars($strSuccess); ?>" />
            <input type="hidden" name="prev_sql_query" value="<?php echo ((!empty($query_to_display)) ? urlencode($query_to_display) : ''); ?>" />
            <?php echo sprintf($strRunSQLQuery, $db) . ' ' . PMA_showDocu('manual_Reference.html#SELECT'); ?>&nbsp;:<br />
            <div style="margin-bottom: 5px">
<textarea name="sql_query" cols="<?php echo $cfgTextareaCols; ?>" rows="<?php echo $cfgTextareaRows; ?>" wrap="virtual">
<?php echo ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : ''); ?>
</textarea><br />
            <input type="checkbox" name="show_query" value="y" checked="checked" />&nbsp;
                <?php echo $strShowThisQuery; ?><br />
            </div>
<?php
// loic1: displays import dump feature only if file upload available
$is_upload = (PMA_PHP_INT_VERSION >= 40000 && function_exists('ini_get'))
           ? ((strtolower(ini_get('file_uploads')) == 'on' || ini_get('file_uploads') == 1) && intval(ini_get('upload_max_filesize')))
           : (intval(@get_cfg_var('upload_max_filesize')));
if ($is_upload) {
    echo '            <i>' . $strOr . '</i> ' . $strLocationTextfile . '&nbsp;:<br />' . "\n";
    ?>
            <div style="margin-bottom: 5px">
            <input type="file" name="sql_file" /><br />
            </div>
    <?php
} // end if
echo "\n";

// Bookmark Support
if ($cfgBookmark['db'] && $cfgBookmark['table']) {
    if (($bookmark_list = PMA_listBookmarks($db, $cfgBookmark)) && count($bookmark_list) > 0) {
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
    // loic1: already defined at the top of the script!
    // $tables     = mysql_list_tables($db);
    // $num_tables = @mysql_numrows($tables);
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
            $table = ((PMA_MYSQL_INT_VERSION >= 32300) ? $tables[$i]['Name'] : $tables[$i]);
            echo '                    <option value="' . $table . '">' . $table . '</option>' . "\n";
            $i++;
        }
        ?>
                </select>
            </td>
        <?php
    } // end if

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
    if (PMA_MYSQL_INT_VERSION >= 32306) {
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
    if (PMA_PHP_INT_VERSION >= 40004) {
        $is_zip  = (isset($cfgZipDump) && $cfgZipDump && @function_exists('gzcompress'));
        $is_gzip = (isset($cfgGZipDump) && $cfgGZipDump && @function_exists('gzencode'));
        $is_bzip = (isset($cfgBZipDump) && $cfgBZipDump && @function_exists('bzcompress'));
        if ($is_zip || $is_gzip || $is_bzip) {
            echo "\n" . '                (' . "\n";
            if ($is_zip) {
                ?>
                <input type="checkbox" name="zip" value="zip" onclick="return checkTransmitDump(this.form, 'zip')" /><?php echo $strZip . (($is_gzip || $is_bzip) ? '&nbsp;' : '') . "\n"; ?>
                <?php
            }
            if ($is_gzip) {
                echo "\n"
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
            onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1))">
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

<?php
// Check if the user is a Superuser
// TODO: set a global variable with this information
// loic1: optimized query
$result       = @mysql_query('USE mysql');
$is_superuser = (!mysql_error());
  
// Display the DROP DATABASE link only if allowed to do so
if ($cfgAllowUserDropDatabase || $is_superuser) {
    ?>
    <!-- Drop database -->
    <li>
        <a href="sql.php3?server=<?php echo $server; ?>&amp;lang=<?php echo $lang; ?>&amp;db=<?php echo urlencode($db); ?>&amp;sql_query=<?php echo urlencode('DROP DATABASE ' . PMA_backquote($db)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strDatabaseHasBeenDropped, htmlspecialchars(PMA_backquote($db)))); ?>&amp;goto=main.php3&amp;back=db_details.php3&amp;reload=1"
            onclick="return confirmLink(this, 'DROP DATABASE <?php echo PMA_jsFormat($db); ?>')">
            <?php echo $strDropDB . ' ' . htmlspecialchars($db); ?></a>
        <?php echo PMA_showDocu('manual_Reference.html#DROP_DATABASE') . "\n"; ?>
    </li>
    <?php
}
echo "\n";
?>

</ul>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
