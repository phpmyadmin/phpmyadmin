<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if (empty($is_info)) {
    if (!defined('PMA_GRAB_GLOBALS_INCLUDED')) {
        include('./libraries/grab_globals.lib.php3');
    }
    if (!defined('PMA_COMMON_LIB_INCLUDED')) {
        include('./libraries/common.lib.php3');
    }

    // Drops/deletes/etc. multiple tables if required
    if ((!empty($submit_mult) && isset($selected_tbl))
       || isset($mult_btn)) {
        $action = 'db_details_structure.php3';
        $err_url = 'db_details_structure.php3?'. PMA_generate_common_url($db);
        include('./mult_submits.inc.php3');
    }
    include('./db_details_common.php3');
    $url_query .= '&amp;goto=db_details_structure.php3';

    // Gets the database structure
    $sub_part = '_structure';
    include('./db_details_db_info.php3');
    echo "\n";

    /**
     * Show result of multi submit operation
     */
    if ((!empty($submit_mult) && isset($selected_tbl))
       || isset($mult_btn)) {
        PMA_showMessage($strSuccess);
    }
}

// Display function
function pma_TableHeader($alternate = FALSE) {
    if ($alternate) {
?>
            <table border="<?php echo $GLOBALS['cfg']['Border']; ?>">
            <tr>
                <td></td>
                <th>&nbsp;<?php echo $GLOBALS['strTable']; ?>&nbsp;</th>
                <th colspan="6"><?php echo $GLOBALS['strAction']; ?></th>
                <th><?php echo $GLOBALS['strRecords']; ?></th>
            </tr>
<?php
    } else {
?>
            <table border="<?php echo $GLOBALS['cfg']['Border']; ?>">
            <tr>
                <td></td>
                <th>&nbsp;<?php echo $GLOBALS['strTable']; ?>&nbsp;</th>
                <th colspan="6"><?php echo $GLOBALS['strAction']; ?></th>
                <th><?php echo $GLOBALS['strRecords']; ?></th>
                <?php
                if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
                ?>
                <th><?php echo $GLOBALS['strType']; ?></th>
                <?php
                }
                ?>
                <?php
                if ($GLOBALS['cfg']['ShowStats']) {
                    echo '<th>' . $GLOBALS['strSize'] . '</th>';
                    echo '<th>' . $GLOBALS['strOverhead'] . '</th>';
                }
                echo "\n";
                ?>
            </tr>
<?php
    }
}


/**
 * Settings for relations stuff
 */
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();

/**
 * Check if comments were updated
 */
if ($cfgRelation['commwork'] && isset($db_comment) && $db_comment == 'true') {
    PMA_SetComment($db, '', '(db_comment)', $comment);
}

/**
 * Displays the tables list
 */
?>

<!-- TABLE LIST -->

<?php
$titles = array();
if ($cfg['PropertiesIconic'] == true) {
    // We need to copy the value or else the == 'both' check will always return true
    $propicon = (string)$cfg['PropertiesIconic'];

    if ($propicon == 'both') {
        $iconic_spacer = '<nobr>';
    } else {
        $iconic_spacer = '';
    }

    $titles['Browse']     = $iconic_spacer . '<img hspace="7" width="12" height="13" src="images/button_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" border="0" />';
    $titles['Search']     = $iconic_spacer . '<img hspace="7" width="14" height="13" src="images/button_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" border="0" />';
    $titles['NoBrowse']   = $iconic_spacer . '<img hspace="7" width="12" height="13" src="images/button_nobrowse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" border="0" />';
    $titles['NoSearch']   = $iconic_spacer . '<img hspace="7" width="14" height="13" src="images/button_noselect.png" alt="' . $strSearch . '" title="' . $strSearch . '" border="0" />';
    $titles['Insert']     = $iconic_spacer . '<img hspace="7" width="13" height="13" src="images/button_insert.png" alt="' . $strInsert . '" title="' . $strInsert . '" border="0" />';
    $titles['Properties'] = $iconic_spacer . '<img hspace="7" width="18" height="13" src="images/button_properties.png" alt="' . $strProperties . '" title="' . $strProperties . '" border="0" />';
    $titles['Drop']       = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" border="0" />';
    $titles['Empty']      = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" border="0" />';
    $titles['NoEmpty']    = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_noempty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" border="0" />';

    if ($propicon == 'both') {
        $titles['Browse']     .= '&nbsp;' . $strBrowse . '</nobr>';
        $titles['Search']     .= '&nbsp;' . $strSearch . '</nobr>';
        $titles['NoBrowse']   .= '&nbsp;' . $strBrowse . '</nobr>';
        $titles['NoSearch']   .= '&nbsp;' . $strSearch . '</nobr>';
        $titles['Insert']     .= '&nbsp;' . $strInsert . '</nobr>';
        $titles['Properties'] .= '&nbsp;' . $strProperties . '</nobr>';
        $titles['Drop']       .= '&nbsp;' . $strDrop . '</nobr>';
        $titles['Empty']      .= '&nbsp;' . $strEmpty . '</nobr>';
        $titles['NoEmpty']    .= '&nbsp;' . $strEmpty . '</nobr>';
    }
} else {
    $titles['Browse']     = $strBrowse;
    $titles['Search']     = $strSearch;
    $titles['NoBrowse']   = $strBrowse;
    $titles['NoSearch']   = $strSearch;
    $titles['Insert']     = $strInsert;
    $titles['Properties'] = $strProperties;
    $titles['Drop']       = $strDrop;
    $titles['Empty']      = $strEmpty;
    $titles['NoEmpty']    = $strEmpty;
}

// 1. No tables
if ($num_tables == 0) {
    echo $strNoTablesFound . "\n";
}
// 2. Shows table informations on mysql >= 3.23.03 - staybyte - 11 June 2001
else if (PMA_MYSQL_INT_VERSION >= 32303) {
    // Get additional information about tables for tooltip
    if ($cfg['ShowTooltip']) {
        $tooltip_truename = array();
        $tooltip_aliasname = array();

        $result  = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
        while ($tmp = PMA_mysql_fetch_array($result)) {
            $tooltip_truename[$tmp['Name']] = ($cfg['ShowTooltipAliasTB'] ? (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : $tmp['Name']) : $tmp['Name']);
            $tooltip_aliasname[$tmp['Name']] = ($cfg['ShowTooltipAliasTB'] ? $tmp['Name'] : (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : ''));
            if (isset($tmp['Create_time']) && !empty($tmp['Create_time'])) {
                $tooltip_aliasname[$tmp['Name']] .= ', ' . $strStatCreateTime . ': ' . PMA_localisedDate(strtotime($tmp['Create_time']));
            }

            if (isset($tmp['Update_time']) && !empty($tmp['Update_time'])) {
                $tooltip_aliasname[$tmp['Name']] .= ', ' . $strStatUpdateTime . ': ' . PMA_localisedDate(strtotime($tmp['Update_time']));
            }

            if (isset($tmp['Check_time']) && !empty($tmp['Check_time'])) {
                $tooltip_aliasname[$tmp['Name']] .= ', ' . $strStatCheckTime . ': ' . PMA_localisedDate(strtotime($tmp['Check_time']));
            }

        } // end while
    } // end if

    if ($cfgRelation['commwork']) {
        $comment = PMA_getComments($db);

        /**
         * Displays table comment
         */
        if (is_array($comment)) {
            ?>
        <!-- DB comment -->
        <p><i>
            <?php echo htmlspecialchars(implode(' ', $comment)) . "\n"; ?>
        </i></p>
            <?php
        } // end if
    }
    ?>
<form method="post" action="db_details_structure.php3" name="tablesForm">
    <?php echo PMA_generate_common_hidden_inputs($db); ?>

<?php
    if ($cfg['PropertiesNumColumns'] > 1) {
?>
<table cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td valign="top">
<?php
    }
    
    pma_TableHeader();

    $i = $sum_entries = 0;
    (double) $sum_size = 0;
    (double) $overhead_size = 0;
    $overhead_check = '';
    $checked   = (!empty($checkall) ? ' checked="checked"' : '');
    $num_columns = ($cfg['PropertiesNumColumns'] > 1 ? (ceil($num_tables / $cfg['PropertiesNumColumns']) + 1) : 0);
    $row_count = 0;
    while (list($keyname, $sts_data) = each($tables)) {
        $table         = $sts_data['Name'];
        $table_encoded = urlencode($table);
        $table_name    = htmlspecialchars($table);

        $alias = (!empty($tooltip_aliasname) && isset($tooltip_aliasname[$table]))
                   ? htmlspecialchars($tooltip_aliasname[$table])
                   : htmlspecialchars($sts_data['Name']);
        $truename = (!empty($tooltip_truename) && isset($tooltip_truename[$table]))
                   ? htmlspecialchars($tooltip_truename[$table])
                   : htmlspecialchars($sts_data['Name']);

        // Sets parameters for links
        $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
        $bgcolor       = ($i++ % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        echo "\n";

        $row_count++;
        if($num_columns > 0 && $num_tables > $num_columns && (($row_count % ($num_columns)) == 0)) {
            $bgcolor       = $cfg['BgcolorTwo'];
            $row_count = 1;
        ?>
            </tr>
        </table>
    </td>
    <td><img src="./images/spacer.gif" border="0" width="10" height="1" alt="" /></td>
    <td valign="top">
        <?php
            pma_TableHeader();
        }
        ?>
            <tr>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <input type="checkbox" name="selected_tbl[]" value="<?php echo $table_encoded; ?>" id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> />
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                    &nbsp;<b><label for="checkbox_tbl_<?php echo $i; ?>" title="<?php echo $alias; ?>"><?php echo $truename; ?></label>&nbsp;</b>&nbsp;
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        include('./libraries/bookmark.lib.php3');
        $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');
        
        if (!empty($sts_data['Rows'])) {
            echo '<a href="sql.php3?' . $tbl_url_query . '&amp;sql_query='
                 . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table)))
                 . '&amp;pos=0">' . $titles['Browse'] . '</a>';
        } else {
            echo $titles['NoBrowse'];
        }
        ?>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if (!empty($sts_data['Rows'])) {
            echo '<a href="tbl_select.php3?' . $tbl_url_query . '">'
                 . $titles['Search'] . '</a>';
        } else {
            echo $titles['NoSearch'];
        }
        ?>
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_change.php3?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Insert']; ?></a>
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_properties_structure.php3?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Properties']; ?></a>
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
            onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
            <?php echo $titles['Drop']; ?></a>
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if (!empty($sts_data['Rows'])) {
            echo '<a href="sql.php3?' . $tbl_url_query
                 . '&amp;sql_query=';
            if (PMA_MYSQL_INT_VERSION >= 40000) {
                echo urlencode('TRUNCATE ' . PMA_backquote($table))
                     . '&amp;zero_rows='
                     . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
                     . '" onclick="return confirmLink(this, \'TRUNCATE ';
            } else {
                echo urlencode('DELETE FROM ' . PMA_backquote($table))
                     . '&amp;zero_rows='
                     . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
                     . '" onclick="return confirmLink(this, \'DELETE FROM ';
            }
            echo PMA_jsFormat($table) . '\')">' . $titles['Empty'] . '</a>';
        } else {
             echo $titles['NoEmpty'];
        }
        ?>
                </td>
        <?php
        echo "\n";

        // loic1: Patch from Joshua Nye <josh at boxcarmedia.com> to get valid
        //        statistics whatever is the table type
        if (isset($sts_data['Rows'])) {
            // MyISAM, ISAM or Heap table: Row count, data size and index size
            // is accurate.
            if (isset($sts_data['Type']) && ereg('^(MyISAM|ISAM|HEAP)$', $sts_data['Type'])) {
                if ($cfg['ShowStats']) {
                    $tblsize                    =  doubleval($sts_data['Data_length']) + doubleval($sts_data['Index_length']);
                    $sum_size                   += $tblsize;
                    list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                    if (isset($sts_data['Data_free']) && $sts_data['Data_free'] > 0) {
                        list($formated_overhead, $overhead_unit)     = PMA_formatByteDown($sts_data['Data_free']);      
                        $overhead_size           += $sts_data['Data_free'];
                    }
                }
                $sum_entries                    += $sts_data['Rows'];
                $display_rows                   =  number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator);
            }

            // InnoDB table: Row count is not accurate but data and index
            // sizes are.
            else if (isset($sts_data['Type']) && $sts_data['Type'] == 'InnoDB') {
                if ($cfg['ShowStats']) {
                    $tblsize                    =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                   += $tblsize;
                    list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                }
                //$display_rows                   =  '&nbsp;-&nbsp;';
                // get row count with another method
                if ($sts_data['Rows'] < $cfg['MaxExactCount']) {
                    $local_query         = 'SELECT COUNT(*) AS count FROM '
                                         . PMA_backquote($db) . '.'
                                         . PMA_backquote($table);
                    $table_info_result   = PMA_mysql_query($local_query)
                                         or PMA_mysqlDie('', $local_query, '', $err_url_0);
                    $row_count           = PMA_mysql_result($table_info_result, 0, 'count');
                    $sum_entries         += $row_count;
                } else {
                    $row_count           = $sts_data['Rows'];
                    $sum_entries         += $sts_data['Rows'];
                }
                $display_rows        = number_format($row_count, 0, $number_decimal_separator, $number_thousands_separator);
            }

            // Merge or BerkleyDB table: Only row count is accurate.
            else if (isset($sts_data['Type']) && ereg('^(MRG_MyISAM|BerkeleyDB)$', $sts_data['Type'])) {
                if ($cfg['ShowStats']) {
                    $formated_size              =  '&nbsp;-&nbsp;';
                    $unit                       =  '';
                }
                print_r($sts_data);
                $sum_entries                    += $sts_data['Rows'];
                $display_rows                   =  number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator);
            }

            // Unknown table type.
            else {
                if ($cfg['ShowStats']) {
                    $formated_size              =  'unknown';
                    $unit                       =  '';
                }
                $display_rows                   =  'unknown';
            }
            ?>
                <td align="right" bgcolor="<?php echo $bgcolor; ?>">
            <?php
            echo "\n" . '        ' . $display_rows . "\n";
            ?>
                </td>
            <?php
            if (!($cfg['PropertiesNumColumns'] > 1)) {
            ?>
                <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                    &nbsp;<?php echo (isset($sts_data['Type']) ? $sts_data['Type'] : '&nbsp;'); ?>&nbsp;
                </td>
            <?php
            }
            
            if ($cfg['ShowStats']) {
                echo "\n";
                ?>
                <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                    &nbsp;&nbsp;
                    <a href="tbl_properties_structure.php3?<?php echo $tbl_url_query; ?>#showusage"><?php echo $formated_size . ' ' . $unit; ?></a>
                </td>
                <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                    &nbsp;&nbsp;
                    <?php 
                    if (isset($formated_overhead)) {
                        echo '<a href="tbl_properties_structure.php3?' . $tbl_url_query . '#showusage">' . $formated_overhead . ' ' . $overhead_unit . '</a>' . "\n";
                        unset($formated_overhead);
                        $overhead_check .= "document.getElementById('checkbox_tbl_$i').checked = true;";
                    } else {
                        echo "&nbsp;-&nbsp;\n";
                    }
                    ?>
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
    if ($cfg['ShowStats']) {
        list($sum_formated, $unit) = PMA_formatByteDown($sum_size, 3, 1);
        list($overhead_formated, $overhead_unit) = PMA_formatByteDown($overhead_size, 3, 1);
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
    <?php
    if (!($cfg['PropertiesNumColumns'] > 1)) {
    ?>
                <th align="center">
                    <b>--</b>
                </th>
    <?php
    }

    if ($cfg['ShowStats']) {
        echo "\n";
        ?>
                <th align="right" nowrap="nowrap">
                    &nbsp;
                    <b><?php echo $sum_formated . ' ' . $unit; ?></b>
                </th>
                <th align="right" nowrap="nowrap">
                    &nbsp;
                    <b><?php echo $overhead_formated . ' ' . $overhead_unit; ?></b>
                </th>
        <?php
    }
    echo "\n";
    ?>
            </tr>

    <?php
    // Check all tables url
    $checkall_url = 'db_details_structure.php3?' . PMA_generate_common_url($db);
    echo "\n";
    ?>
            <tr>
                <td colspan="<?php echo (($cfg['ShowStats']) ? '11' : '10'); ?>" valign="bottom">
                    <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
                    <a href="<?php echo $checkall_url; ?>&amp;checkall=1" onclick="setCheckboxes('tablesForm', true); return false;">
                        <?php echo $strCheckAll; ?></a>
                    &nbsp;/&nbsp;
                    <a href="<?php echo $checkall_url; ?>" onclick="setCheckboxes('tablesForm', false); return false;">
                        <?php echo $strUncheckAll; ?></a>
                    <?php if ($overhead_check != '') { ?>
                    &nbsp;/&nbsp;
                    <a href="#" onclick="setCheckboxes('tablesForm', false); <?php echo $overhead_check; ?> return false;">
                        <?php echo $strCheckOverhead; ?></a>
                    <? } ?>
                    &nbsp;&nbsp;&nbsp;
                    <img src="./images/spacer.gif" border="0" width="38" height="1" alt="" />
                    <select name="submit_mult" dir="ltr" onchange="this.form.submit();">
    <?php
    echo "\n";
    echo '            <option value="' . $strWithChecked . '" selected="selected">'
         . $strWithChecked . '</option>' . "\n";
    echo '            <option value="' . $strDrop . '" >'
         . $strDrop . '</option>' . "\n";
    echo '            <option value="' . $strEmpty . '" >'
         . $strEmpty . '</option>' . "\n";
    echo '            <option value="' . $strPrintView . '" >'
         . $strPrintView . '</option>' . "\n";
    echo '            <option value="' . $strCheckTable . '" >'
         . $strCheckTable . '</option>' . "\n";
    echo '            <option value="' . $strOptimizeTable . '" >'
         . $strOptimizeTable . '</option>' . "\n";
    echo '            <option value="' . $strRepairTable . '" >'
         . $strRepairTable . '</option>' . "\n";
    echo '            <option value="' . $strAnalyzeTable . '" >'
         . $strAnalyzeTable . '</option>' . "\n";
    ?>
                    </select>
                    <script type="text/javascript" language="javascript">
                    <!--
                    // Fake js to allow the use of the <noscript> tag
                    //-->
                    </script>
                    <noscript>
                        <input type="submit" value="<?php echo $strGo; ?>" />
                    </noscript>
                </td>
            </tr>
            </table>
<?php
if ($cfg['PropertiesNumColumns'] > 1) {
?>
        </td>
    </tr>
</table>
<?php
}
?>
</form>
    <?php
} // end case mysql >= 3.23.03

// 3. Shows tables list mysql < 3.23.03
else {
    if ($cfgRelation['commwork']) {
        $comment = PMA_getComments($db);

        /**
         * Displays table comment
         */
        if (is_array($comment)) {
            ?>
        <!-- DB comment -->
        <p><i>
            <?php echo htmlspecialchars(implode(' ', $comment)) . "\n"; ?>
        </i></p>
            <?php
        } // end if
    }

    $i = 0;
    echo "\n";
    ?>
<form action="db_details_structure.php3">
    <?php PMA_generate_common_hidden_inputs($db); ?>

<?php
    if ($cfg['PropertiesNumColumns'] > 1) {
?>
<table cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td valign="top">
<?php
    }

    pma_TableHeader(true);
    
    $checked = (!empty($checkall) ? ' checked="checked"' : '');
    $num_columns = ($cfg['PropertiesNumColumns'] > 1 ? (ceil($num_tables / $cfg['PropertiesNumColumns']) + 1) : 0);
    $row_count = 0;
    while ($i < $num_tables) {
        $table         = $tables[$i];
        $table_encoded = urlencode($table);
        $table_name    = htmlspecialchars($table);

        // Sets parameters for links
        $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
        $bgcolor       = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        echo "\n";
        $row_count++;
        if($num_columns > 0 && $num_tables > $num_columns && (($row_count % ($num_columns)) == 0)) {
            $bgcolor       = $cfg['BgcolorTwo'];
            $row_count = 1;
        ?>
            </tr>
        </table>
    </td>
    <td><img src="./images/spacer.gif" border="0" width="10" height="1" alt="" /></td>
    <td valign="top">
        <?php
            pma_TableHeader(true);
        }
        ?>
            <tr>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <input type="checkbox" name="selected_tbl[]" value="<?php echo $table_encoded; ?>" id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> />
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>" class="data">
                    <b>&nbsp;<label for="checkbox_tbl_<?php echo $i; ?>"><?php echo $table_name; ?></label>&nbsp;</b>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
                    <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0"><?php echo $strBrowse; ?></a>
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <a href="tbl_select.php3?<?php echo $tbl_url_query; ?>"><?php echo $titles['Search']; ?></a>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
                    <a href="tbl_change.php3?<?php echo $tbl_url_query; ?>"><?php echo $titles['Insert']; ?></a>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
                    <a href="tbl_properties.php3?<?php echo $tbl_url_query; ?>"><?php echo $titles['Properties']; ?></a>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
                    <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, $table_name)); ?>"><?php echo $titles['Drop']; ?></a>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
                    <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;sql_query=<?php echo urlencode('DELETE FROM ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenEmptied, $table_name)); ?>"><?php echo $titles['Empty']; ?></a>
                </td>
                <td align="right" bgcolor="<?php echo $bgcolor; ?>">
                    <?php PMA_countRecords($db, $table); echo "\n"; ?>
                </td>
            </tr>
        <?php
        $i++;
    } // end while
    echo "\n";

    // Check all tables url
    $checkall_url = 'db_details_structure.php3?' . PMA_generate_common_url($db);
    ?>
            <tr>
                <td colspan="9">
                    <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
                    <a href="<?php echo $checkall_url; ?>&amp;checkall=1" onclick="setCheckboxes('tablesForm', true); return false;">
                        <?php echo $strCheckAll; ?></a>
                    &nbsp;/&nbsp;
                    <a href="<?php echo $checkall_url; ?>" onclick="setCheckboxes('tablesForm', false); return false;">
                        <?php echo $strUncheckAll; ?></a>
                </td>
            </tr>
            
            <tr>
                <td colspan="9">
                    <img src="./images/spacer.gif" border="0" width="38" height="1" alt="" />
                    <i><?php echo $strWithChecked; ?></i>&nbsp;&nbsp;
                    <input type="submit" name="submit_mult" value="<?php echo $strDrop; ?>" />
                    &nbsp;<?php $strOr . "\n"; ?>&nbsp;
                    <input type="submit" name="submit_mult" value="<?php echo $strEmpty; ?>" />
                </td>
            </tr>
            </table>
<?php
    if ($cfg['PropertiesNumColumns'] > 1) {
?>
        </td>
    </tr>
</table>
<?php
    }
?>
</form>
    <?php
} // end case mysql < 3.23.03

echo "\n";
?>
<hr />


<?php
/**
 * Work on the database
 */
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
    <li>
         <div style="margin-bottom: 10px"><a href="./db_datadict.php3?<?php echo $url_query; ?>"><?php echo $strDataDict; ?></a></div>
    </li>
    <?php
} // end if
?>

<?php
if ($cfgRelation['commwork']) {
?>
    <!-- Alter/Enter db-comment -->
    <li>
        <form method="post" action="db_details_structure.php3">
            <?php echo $strDBComment; ?>
            <input type="hidden" name="db_comment" value="true" />
            <?php echo PMA_generate_common_hidden_inputs($db); ?>
            <input type="text" name="comment" class="textfield" value="<?php echo (isset($comment) && is_array($comment) ? htmlspecialchars(implode(' ', $comment)) : ''); ?>" />
            <input type="submit" value="<?php echo $strGo; ?>" />
        </form>
    </li>
<?php
}
?>

    <!-- Create a new table -->
    <li>
        <form method="post" action="tbl_create.php3"
            onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1))">
        <?php echo PMA_generate_common_hidden_inputs($db); ?>
<?php
echo '        ' . sprintf($strCreateNewTable, htmlspecialchars($db)) . '&nbsp;:<br />' . "\n";
echo '        ' . $strName . '&nbsp;:&nbsp;' . "\n";
echo '        ' . '<input type="text" name="table" maxlength="64" class="textfield" />' . "\n";
echo '        ' . '<br />' . "\n";
echo '        ' . $strFields . '&nbsp;:&nbsp;' . "\n";
echo '        ' . '<input type="text" name="num_fields" size="2" class="textfield" />' . "\n";
echo '        ' . '&nbsp;<input type="submit" value="' . $strGo . '" />' . "\n";
?>
        </form>
    </li>

<?php
if ($num_tables > 0
    && !$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == FALSE) {
    echo '    <li>' . "\n";
    echo '        <div style="margin-bottom: 10px">' . "\n";
    echo '        <font color="red">' . $strError . '</font><br />' . "\n";
    $url_to_goto = '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php3?' . $url_query . '">';
    echo '        ' . sprintf($strRelationNotWorking, $url_to_goto, '</a>') . "\n";
    echo '        </div>' . "\n";
    echo '    </li>' . "\n";
} // end if

// is this OK to check for 'class' support?
if ($num_tables > 0) {
    $takeaway = $url_query . '&amp;table=' . urlencode($table);
}

if ($cfgRelation['pdfwork'] && $num_tables > 0) {
    ?>
    <!-- Work on PDF Pages -->
    <li>
        <div style="margin-bottom: 10px"><a href="pdf_pages.php3?<?php echo $takeaway; ?>"><?php echo $strEditPDFPages; ?></a></div>
    </li>

    <!-- PDF schema -->
    <?php
    // We only show this if we find something in the new pdf_pages table

    $test_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $test_rs    = PMA_query_as_cu($test_query);
    if ($test_rs && mysql_num_rows($test_rs) > 0) {
        echo "\n";
        ?>
    <li>
        <form method="post" action="pdf_schema.php3">
            <?php echo PMA_generate_common_hidden_inputs($db); ?>
            <?php echo $strDisplayPDF; ?>&nbsp;:<br />
            <?php echo $strPageNumber; ?>&nbsp;
            <select name="pdf_page_number">
        <?php
        while ($pages = @PMA_mysql_fetch_array($test_rs)) {
            echo "\n" . '                '
                 . '<option value="' . $pages['page_nr'] . '">' . $pages['page_nr'] . ': ' . $pages['page_descr'] . '</option>';
        } // end while
        echo "\n";
        ?>
            </select><br />
            <input type="checkbox" name="show_grid" id="show_grid_opt" />
            <label for="show_grid_opt"><?php echo $strShowGrid; ?></label><br />
            <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" />
            <label for="show_color_opt"><?php echo $strShowColor; ?></label><br />
            <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
            <label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?></label><br />
            <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" />
            <label for="all_tab_same_wide"><?php echo $strAllTableSameWidth; ?></label> <br />
            <input type="checkbox" name="with_doc" id="with_doc" checked="checked" />
            <label for="with_doc"><?php echo $strDataDict; ?></label> <br />
            <?php echo $strShowDatadictAs; ?>
            <select name="orientation">
                <option value="L"><?php echo $strLandscape;?></option>
                <option value="P"><?php echo $strPortrait;?></option>
            </select><br />
            <?php echo $strPaperSize; ?>
            <select name="paper">
            <?php
                while (list($key,$val) = each($cfg['PDFPageSizes'])) {
                    echo '<option value="' . $val . '"';
                    if ($val == $cfg['PDFDefaultPageSize']) {
                        echo ' selected="selected"';
                    }
                    echo ' >' . $val . '</option>' . "\n";
                }
            ?>
                </select><br />
                &nbsp;&nbsp;<input type="submit" value="<?php echo $strGo; ?>" />
            </form>
    </li>
        <?php
    }   // end if
} // end if

if ($num_tables > 0
    && $cfgRelation['relwork'] && $cfgRelation['commwork']
    && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    ) {
    ?>
    <!-- import docSQL files -->
    <li>
        <div style="margin-bottom: 10px"><a href="db_details_importdocsql.php3?<?php echo $takeaway . '">' . $strImportDocSQL; ?></a></div>
    </li>
    <?php
}
echo "\n" . '</ul>';


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
