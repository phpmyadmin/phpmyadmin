<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');

/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if (empty($is_info)) {
    // Drops/deletes/etc. multiple tables if required
    if ((!empty($submit_mult) && isset($selected_tbl))
       || isset($mult_btn)) {
        $action = 'db_details_structure.php';
        $err_url = 'db_details_structure.php?'. PMA_generate_common_url($db);
        require('./mult_submits.inc.php');
        $message = $strSuccess;
    }
    require('./db_details_common.php');
    $url_query .= '&amp;goto=db_details_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    require('./db_details_db_info.php');
    echo "\n";
}

if (PMA_MYSQL_INT_VERSION >= 40101) {
    $db_collation = PMA_getDbCollation($db);
}


// Display function
function pma_TableHeader($alternate = FALSE) {
    echo '            <table border="' . $GLOBALS['cfg']['Border'] . '" cellpadding="2" cellspacing="1">' . "\n"
       . '            <tr>' . "\n"
       . '                <td></td>' . "\n"
       . '                <th>' . "\n"
       . '                    &nbsp;' . $GLOBALS['strTable'] . '&nbsp;' . "\n"
       . '                </th>' . "\n"
       . '                <th colspan="6">' . "\n"
       . '                    &nbsp;' . $GLOBALS['strAction'] . '&nbsp;' . "\n"
       . '                </th>' . "\n"
       . '                <th>' . "\n"
       . '                    &nbsp;' .  $GLOBALS['strRecords'] . PMA_showHint($GLOBALS['strApproximateCount']) . '&nbsp;' . "\n"
       . '                </th>' . "\n";
    if (!$alternate) {
        if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
            echo '                <th>' . "\n"
               . '                    &nbsp;' . $GLOBALS['strType'] . '&nbsp;' . "\n"
               . '                </th>' . "\n";
            if (PMA_MYSQL_INT_VERSION >= 40100) {
                echo '                <th>' . "\n"
                   . '                    &nbsp;' . $GLOBALS['strCollation'] . '&nbsp;' . "\n"
                   . '                </th>' . "\n";
            }
        }
        if ($GLOBALS['cfg']['ShowStats']) {
            echo '                <th>' . "\n"
               . '                    &nbsp;' . $GLOBALS['strSize'] . '&nbsp;' . "\n"
               . '                </th>' . "\n"
               . '                <th>' . "\n"
               . '                    &nbsp;' . $GLOBALS['strOverhead'] . '&nbsp;' . "\n"
               . '                </th>' . "\n";
        }
        echo "\n";
    }
    echo '            </tr>' . "\n";
}


/**
 * Settings for relations stuff
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

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
        $iconic_spacer = '<div class="nowrap">';
    } else {
        $iconic_spacer = '';
    }

    $titles['Browse']     = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'b_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" border="0" />';
    $titles['Search']     = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'b_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" border="0" />';
    $titles['NoBrowse']   = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'bd_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" border="0" />';
    $titles['NoSearch']   = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'bd_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" border="0" />';
    $titles['Insert']     = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'b_insrow.png" alt="' . $strInsert . '" title="' . $strInsert . '" border="0" />';
    $titles['Structure']  = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'b_props.png" alt="' . $strStructure . '" title="' . $strStructure . '" border="0" />';
    $titles['Drop']       = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'b_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" border="0" />';
    $titles['Empty']      = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'b_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" border="0" />';
    $titles['NoEmpty']    = $iconic_spacer . '<img hspace="2" width="16" height="16" src="' .$pmaThemeImage . 'bd_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" border="0" />';

    if ($propicon == 'both') {
        $titles['Browse']     .= '&nbsp;' . $strBrowse . '</div>';
        $titles['Search']     .= '&nbsp;' . $strSearch . '</div>';
        $titles['NoBrowse']   .= '&nbsp;' . $strBrowse . '</div>';
        $titles['NoSearch']   .= '&nbsp;' . $strSearch . '</div>';
        $titles['Insert']     .= '&nbsp;' . $strInsert . '</div>';
        $titles['Structure']  .= '&nbsp;' . $strStructure . '</div>';
        $titles['Drop']       .= '&nbsp;' . $strDrop . '</div>';
        $titles['Empty']      .= '&nbsp;' . $strEmpty . '</div>';
        $titles['NoEmpty']    .= '&nbsp;' . $strEmpty . '</div>';
    }
} else {
    $titles['Browse']     = $strBrowse;
    $titles['Search']     = $strSearch;
    $titles['NoBrowse']   = $strBrowse;
    $titles['NoSearch']   = $strSearch;
    $titles['Insert']     = $strInsert;
    $titles['Structure']  = $strStructure;
    $titles['Drop']       = $strDrop;
    $titles['Empty']      = $strEmpty;
    $titles['NoEmpty']    = $strEmpty;
}

// 1. No tables
if ($num_tables == 0) {
    echo $strNoTablesFound . "\n";
}
// 2. Shows table informations - staybyte - 11 June 2001
else {
    // Get additional information about tables for tooltip is done in db_details_db_info.php only once
    if ($cfgRelation['commwork']) {
        $comment = PMA_getComments($db);

        /**
         * Displays table comment
         */
        if (is_array($comment)) {
            ?>
        <!-- DB comment -->
        <p id="dbComment"><i>
            <?php echo htmlspecialchars(implode(' ', $comment)) . "\n"; ?>
        </i></p>
            <?php
        } // end if
    }
    ?>
<form method="post" action="db_details_structure.php" name="tablesForm">
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

    if ($cfg['NaturalOrder']) {
        $tables_temp = $tables;
        foreach (array_keys($tables_temp) as $each) {
            $tables_sort[$each] = $tables_temp[$each]['Name'];
        }
        natsort($tables_sort);
        $sort_i = 0;
        foreach (array_keys($tables_sort) as $each) {
            $tables_temp[$sort_i] = $tables[$each];
            $sort_i++;
        }
        $tables = $tables_temp;
    }

    foreach ($tables AS $keyname => $sts_data) {
        $table         = $sts_data['Name'];
        $table_encoded = urlencode($table);
        $table_name    = htmlspecialchars($table);

        $alias = (!empty($tooltip_aliasname) && isset($tooltip_aliasname[$table]))
                   ? htmlspecialchars($tooltip_aliasname[$table])
                   :  htmlspecialchars($sts_data['Name']);
        $truename = (!empty($tooltip_truename) && isset($tooltip_truename[$table]))
                   ? htmlspecialchars($tooltip_truename[$table])
                   : htmlspecialchars($sts_data['Name']);

        // Sets parameters for links
        $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
        $bgcolor       = ($i++ % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        echo "\n";

        if ($GLOBALS['cfg']['BrowsePointerEnable'] == TRUE) {
            $on_mouse = ' onmouseover="setPointer(this, ' . $i . ', \'over\', \'' . $bgcolor . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"'
                      . ' onmouseout="setPointer(this, ' . $i . ', \'out\', \'' . $bgcolor . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"';
        } else {
           $on_mouse = '';
        }
        if ($GLOBALS['cfg']['BrowseMarkerEnable'] == TRUE) {
            $on_mouse .= ' onmousedown="setPointer(this, ' . $i . ', \'click\', \'' . $bgcolor . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"';
        }

        $click_mouse = ' onmousedown="document.getElementById(\'checkbox_tbl_' . $i . '\').checked = (document.getElementById(\'checkbox_tbl_' . $i . '\').checked ? false : true);" ';

        $row_count++;
        if ($num_columns > 0 && $num_tables > $num_columns && (($row_count % ($num_columns)) == 0)) {
            $bgcolor       = $cfg['BgcolorTwo'];
            $row_count = 1;
        ?>
            </tr>
        </table>
    </td>
    <td><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" border="0" width="10" height="1" alt="" /></td>
    <td valign="top">
        <?php
            pma_TableHeader();
        }
        ?>
            <tr <?php echo $on_mouse; ?>>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <input type="checkbox" name="selected_tbl[]" value="<?php echo $table_encoded; ?>" id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> />
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap" <?php echo $click_mouse; ?>>
                    &nbsp;<b><label onclick="javascript: return (document.getElementById('checkbox_tbl_<?php echo $i; ?>') ? false : true)" for="checkbox_tbl_<?php echo $i; ?>" title="<?php echo $alias; ?>"><?php echo $truename; ?></label>&nbsp;</b>&nbsp;
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        require_once('./libraries/bookmark.lib.php');
        $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

        if (!empty($sts_data['Rows'])) {
            echo '<a href="sql.php?' . $tbl_url_query . '&amp;sql_query='
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
            echo '<a href="tbl_select.php?' . $tbl_url_query . '">'
                 . $titles['Search'] . '</a>';
        } else {
            echo $titles['NoSearch'];
        }
        ?>
                </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <a href="tbl_change.php?<?php echo $tbl_url_query; ?>">
                        <?php echo $titles['Insert']; ?></a>
                            </td>
                            <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <a href="tbl_properties_structure.php?<?php echo $tbl_url_query; ?>">
                        <?php echo $titles['Structure']; ?></a>
                            </td>
                <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if (!empty($sts_data['Rows'])) {
            echo '<a href="sql.php?' . $tbl_url_query
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
                            <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <a href="sql.php?<?php echo $tbl_url_query; ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
                        onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
                        <?php echo $titles['Drop']; ?></a>
                </td>
        <?php
        echo "\n";

        // loic1: Patch from Joshua Nye <josh at boxcarmedia.com> to get valid
        //        statistics whatever is the table type
        if (isset($sts_data['Rows'])) {
            // MyISAM, ISAM or Heap table: Row count, data size and index size
            // is accurate.
            if (isset($sts_data['Type']) && preg_match('@^(MyISAM|ISAM|HEAP)$@', $sts_data['Type'])) {
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
                    $table_info_result   = PMA_DBI_query($local_query);
                    list($row_count)     = PMA_DBI_fetch_row($table_info_result);
                    PMA_DBI_free_result($table_info_result);
                    unset($table_info_result);
                    $sum_entries         += $row_count;
                } else {
                    $row_count           = $sts_data['Rows'];
                    $sum_entries         += $sts_data['Rows'];
                }
                $display_rows        = number_format($row_count, 0, $number_decimal_separator, $number_thousands_separator);
            }

            // Merge or BerkleyDB table: Only row count is accurate.
            else if (isset($sts_data['Type']) && preg_match('@^(MRG_MyISAM|BerkeleyDB)$@', $sts_data['Type'])) {
                if ($cfg['ShowStats']) {
                    $formated_size              =  '&nbsp;-&nbsp;';
                    $unit                       =  '';
                }
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
                <td align="right" bgcolor="<?php echo $bgcolor; ?>" <?php echo $click_mouse; ?>>
            <?php
            echo "\n" . '        ' . $display_rows . "\n";
            ?>
                </td>
            <?php
            if (!($cfg['PropertiesNumColumns'] > 1)) {
                echo '                <td bgcolor="' . $bgcolor . '" nowrap="nowrap" ' . $click_mouse . '>' . "\n"
                   . '                    &nbsp;' . (isset($sts_data['Type']) ? $sts_data['Type'] : '&nbsp;') . '&nbsp;' . "\n"
                   . '                </td>' . "\n";
                if (PMA_MYSQL_INT_VERSION >= 40100) {
                    echo '                <td bgcolor="' . $bgcolor . '" nowrap="nowrap" ' . $click_mouse . '>' . "\n"
                       . '                    &nbsp;' . (isset($sts_data['Collation']) ? '<dfn title="' . PMA_getCollationDescr($sts_data['Collation']) . '">' . $sts_data['Collation'] . '</dfn>' : '---') . '&nbsp;' . "\n"
                       . '                </td>' . "\n";
                }
            }

            if ($cfg['ShowStats']) {
                echo "\n";
                ?>
                <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap" <?php echo $click_mouse; ?>>
                    &nbsp;&nbsp;
                    <a href="tbl_properties_structure.php?<?php echo $tbl_url_query; ?>#showusage"><?php echo $formated_size . ' ' . $unit; ?></a>
                </td>
                <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap" <?php echo $click_mouse; ?>>
                    &nbsp;&nbsp;
                    <?php
                    if (isset($formated_overhead)) {
                        echo '<a href="tbl_properties_structure.php?' . $tbl_url_query . '#showusage">' . $formated_overhead . ' ' . $overhead_unit . '</a>' . "\n";
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
                <td colspan="4" align="center" bgcolor="<?php echo $bgcolor; ?>" <?php echo $click_mouse; ?>>
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
        echo '                <th align="center">' . "\n"
           . '                    <b>--</b>' . "\n"
           . '                </th>' . "\n";
        if (PMA_MYSQL_INT_VERSION >= 40101) {
            echo '                <th align="center">' . "\n"
               . '                    &nbsp;<b><dfn title="' . PMA_getCollationDescr($db_collation) . '">' . $db_collation . '</dfn></b>&nbsp;' . "\n"
               . '                </th>' . "\n";
        }
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
    $checkall_url = 'db_details_structure.php?' . PMA_generate_common_url($db);
    echo "\n";

    $basecolspan = 9;
    if (!($cfg['PropertiesNumColumns'] > 1)) {
        $basecolspan++;
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            $basecolspan++;
        }
    }

    if ($cfg['ShowStats']) {
        $basecolspan += 2;
    }
    ?>
            <tr>
                <td colspan="<?php echo $basecolspan; ?>" valign="bottom">
                    <img src="<?php echo $pmaThemeImage .'arrow_'.$text_dir.'.png'; ?>" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
                    <a href="<?php echo $checkall_url; ?>&amp;checkall=1" onclick="setCheckboxes('tablesForm', true); return false;">
                        <?php echo $strCheckAll; ?></a>
                    &nbsp;/&nbsp;
                    <a href="<?php echo $checkall_url; ?>" onclick="setCheckboxes('tablesForm', false); return false;">
                        <?php echo $strUncheckAll; ?></a>
                    <?php if ($overhead_check != '') { ?>
                    &nbsp;/&nbsp;
                    <a href="#" onclick="setCheckboxes('tablesForm', false); <?php echo $overhead_check; ?> return false;">
                        <?php echo $strCheckOverhead; ?></a>
                    <?php } ?>
                    &nbsp;&nbsp;&nbsp;
                    <img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" border="0" width="38" height="1" alt="" />
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
} // end if more than one table

echo "\n";
?>
<hr />

<?php
/**
 * Work on the database
    * redesigned 2004-05-08 by mkkeck
 */
?>
<!-- DATABASE WORK -->

<?php
if ($num_tables > 0) {
    ?>
    <!-- Printable view of a table -->
<table border="0" cellpadding="2" cellspacing="0">
                <tr><td nowrap="nowrap" colspan="3"><?php
    echo '<a href="db_printview.php?' . $url_query . '">';
    if ($cfg['PropertiesIconic']) {
         echo '<img src="' . $pmaThemeImage . 'b_print.png" border="0" width="16" height="16" hspace="2" align="middle" />';
     }
      echo $strPrintView . '</a>';
    ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
     <?php
      echo '<a href="./db_datadict.php?' . $url_query . '">';
      if($cfg['PropertiesIconic']){
          echo '<img src="' . $pmaThemeImage . 'b_tblanalyse.png" border="0" width="16" height="16" hspace="2" align="middle" />';
      }
      echo $strDataDict . '</a>';
?></td></tr>
<tr><td colspan="3"><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td></tr></table>
    <?php
} // end if
?>
<table border="0" cellpadding="2" cellspacing="0">
    <!-- Create a new table -->
        <form method="post" action="tbl_create.php" onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1))">
     <tr>
     <td class="tblHeaders" colspan="3" nowrap="nowrap"><?php
        echo PMA_generate_common_hidden_inputs($db);
        if($cfg['PropertiesIconic']){ echo '<img src="' . $pmaThemeImage . 'b_newtbl.png" border="0" width="16" height="16" hspace="2" align="middle" />'; }
        // if you want navigation:
        $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url() . '&amp;db=' . urlencode($GLOBALS['db']) . '">'
                   . htmlspecialchars($GLOBALS['db']) . '</a>';
        // else use
        // $strDBLink = htmlspecialchars($db);
echo '             ' . sprintf($strCreateNewTable, $strDBLink) . ':&nbsp;' . "\n";
echo '     </td></tr>';
echo '     <tr bgcolor="'.$cfg['BgcolorOne'].'"><td nowrap="nowrap">';
echo '             ' . $strName . ':&nbsp;' . "\n";
echo '     </td>';
echo '     <td nowrap="nowrap">';
echo '             ' . '<input type="text" name="table" maxlength="64" size="30" class="textfield" />';
echo '     </td><td>&nbsp;</td></tr>';
echo '     <tr bgcolor="'.$cfg['BgcolorOne'].'"><td nowrap="nowrap">';
echo '             ' . $strFields . ':&nbsp;' . "\n";
echo '     </td>';
echo '     <td nowrap="nowrap">';
echo '             ' . '<input type="text" name="num_fields" size="2" class="textfield" />' . "\n";
echo '     </td>';
echo '     <td align="right">';
echo '             ' . '&nbsp;<input type="submit" value="' . $strGo . '" />' . "\n";
echo '     </td> </tr>';
echo '        </form>';

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
