<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');

/**
 * Rename database
 */
if (isset($db) && isset($db_rename) && $db_rename == 'true') {
    if (!isset($newname) || empty($newname)) {
        $message = $strDatabaseEmpty;
    } else {
        $local_query = 'CREATE DATABASE ' . PMA_backquote($newname) . ';';
        $sql_query = $local_query;
        PMA_DBI_query($local_query);
        $tables = PMA_DBI_get_tables($db);
        foreach ($tables as $table) {
            $local_query = 'RENAME TABLE '
                . PMA_backquote($db) . '.' . PMA_backquote($table)
                . ' TO '
                . PMA_backquote($newname) . '.' . PMA_backquote($table)
                . ';';
            $sql_query .= "\n" . $local_query;
            PMA_DBI_query($local_query);
        }
        $local_query = 'DROP DATABASE ' . PMA_backquote($db) . ';';
        $sql_query .= "\n" . $local_query;
        PMA_DBI_query($local_query);
        $reload     = TRUE;
        $message    = sprintf($strRenameDatabaseOK, htmlspecialchars($db), htmlspecialchars($newname));

        /* Update relations */
        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();

        if ($cfgRelation['commwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['column_info'])
                          . ' SET db_name    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['bookmarkwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['bookmark'])
                          . ' SET dbase    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE dbase  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['displaywork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                          . ' SET db_name    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'');
        }

        if ($cfgRelation['relwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['relation'])
                          . ' SET foreign_db    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\'');
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['relation'])
                          . ' SET master_db    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['historywork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['history'])
                          . ' SET db    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['pdfwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                          . ' SET db_name    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'');
        }

        /* Change database to be used */
        $db         = $newname;
    }
}
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
    }
    require('./db_details_common.php');
    $url_query .= '&amp;goto=db_details_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    require('./db_details_db_info.php');
    echo "\n";

    /**
     * Show result of multi submit operation
     */
    if ((!empty($submit_mult) && isset($selected_tbl))
       || isset($mult_btn)) {
        PMA_showMessage($strSuccess);
    }
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
       . '                    &nbsp;' .  $GLOBALS['strRecords'] . '&nbsp;' . "\n"
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
        $iconic_spacer = '<div class="nowrap">';
    } else {
        $iconic_spacer = '';
    }

    $titles['Browse']     = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/b_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" border="0" />';
    $titles['Search']     = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/b_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" border="0" />';
    $titles['NoBrowse']   = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/bd_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" border="0" />';
    $titles['NoSearch']   = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/bd_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" border="0" />';
    $titles['Insert']     = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/b_insrow.png" alt="' . $strInsert . '" title="' . $strInsert . '" border="0" />';
    $titles['Properties'] = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/b_props.png" alt="' . $strProperties . '" title="' . $strProperties . '" border="0" />';
    $titles['Drop']       = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/b_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" border="0" />';
    $titles['Empty']      = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/b_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" border="0" />';
    $titles['NoEmpty']    = $iconic_spacer . '<img hspace="2" width="16" height="16" src="images/bd_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" border="0" />';

    if ($propicon == 'both') {
        $titles['Browse']     .= '&nbsp;' . $strBrowse . '</div>';
        $titles['Search']     .= '&nbsp;' . $strSearch . '</div>';
        $titles['NoBrowse']   .= '&nbsp;' . $strBrowse . '</div>';
        $titles['NoSearch']   .= '&nbsp;' . $strSearch . '</div>';
        $titles['Insert']     .= '&nbsp;' . $strInsert . '</div>';
        $titles['Properties'] .= '&nbsp;' . $strProperties . '</div>';
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
    $titles['Properties'] = $strProperties;
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
        <p><i>
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

    foreach($tables AS $keyname => $sts_data) {
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

        if ($GLOBALS['cfg']['BrowsePointerColor'] != '') {
            $on_mouse = ' onmouseover="setPointer(this, ' . $i . ', \'over\', \'' . $bgcolor . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"'
                      . ' onmouseout="setPointer(this, ' . $i . ', \'out\', \'' . $bgcolor . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"';
        }
        if ($GLOBALS['cfg']['BrowseMarkerColor'] != '') {
            $on_mouse .= ' onmousedown="setPointer(this, ' . $i . ', \'click\', \'' . $bgcolor . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"';
        }

        $click_mouse = ' onmousedown="document.getElementById(\'checkbox_tbl_' . $i . '\').checked = (document.getElementById(\'checkbox_tbl_' . $i . '\').checked ? false : true);" ';

        $row_count++;
        if($num_columns > 0 && $num_tables > $num_columns && (($row_count % ($num_columns)) == 0)) {
            $bgcolor       = $cfg['BgcolorTwo'];
            $row_count = 1;
        ?>
            </tr>
        </table>
    </td>
    <td><img src="./images/spacer.png" border="0" width="10" height="1" alt="" /></td>
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
                        <?php echo $titles['Properties']; ?></a>
                            </td>
                            <td align="center" bgcolor="<?php echo $bgcolor; ?>">
                    <a href="sql.php?<?php echo $tbl_url_query; ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
                        onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
                        <?php echo $titles['Drop']; ?></a>
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
                <td colspan="3" align="center" bgcolor="<?php echo $bgcolor; ?>" <?php echo $click_mouse; ?>>
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
        if (PMA_MYSQL_INT_VERSION >= 40100) {
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
                    <img src="./images/arrow_<?php echo $text_dir; ?>.png" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
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
                    <img src="./images/spacer.png" border="0" width="38" height="1" alt="" />
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
      if($cfg['PropertiesIconic']){
                          echo '<img src="./images/b_print.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
                        }
      echo $strPrintView . '</a>';
    ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php
                  echo '<a href="./db_datadict.php?' . $url_query . '">';
                        if($cfg['PropertiesIconic']){
                          echo '<img src="./images/b_tblanalyse.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
      }
      echo $strDataDict . '</a>';
?></td></tr>
<tr><td colspan="3"><img src="images/spacer.png" width="1" height="1" border="0" alt="" /></td></tr></table>
    <?php
} // end if
?>
<table border="0" cellpadding="2" cellspacing="0">
    <!-- Create a new table -->
        <form method="post" action="tbl_create.php" onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1))">
     <tr>
     <td class="tblHeaders" colspan="3" nowrap="nowrap"><?php
        echo PMA_generate_common_hidden_inputs($db);
        if($cfg['PropertiesIconic']){ echo '<img src="images/b_newtbl.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />'; }
        // if you want navigation:
        $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . $header_url_qry . '&amp;db=' . urlencode($GLOBALS['db']) . '">'
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
?>
     </td> </tr>
        </form>



<?php
if ($cfgRelation['commwork']) {
?>
    <!-- Alter/Enter db-comment -->
        <tr><td colspan="3"><img src="images/spacer.png" width="1" height="1" border="0" alt="" /></td></tr>

        <tr>
        <td colspan="3" class="tblHeaders"><?php
          if($cfg['PropertiesIconic']){
                                          echo '<img src="images/b_comment.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
                                        }
          echo $strDBComment;
        ?></td></tr>
                                <form method="post" action="db_details_structure.php">
        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                                  <td colspan="2" nowrap="nowrap">
            <input type="hidden" name="db_comment" value="true" />
            <?php echo PMA_generate_common_hidden_inputs($db); ?>
            <input type="text" name="comment" class="textfield" size="30" value="<?php echo (isset($comment) && is_array($comment) ? htmlspecialchars(implode(' ', $comment)) : ''); ?>" /></td><td align="right">
            <input type="submit" value="<?php echo $strGo; ?>" />
         </td></tr>
        </form>
<?php
}
?>
    <!-- Rename database -->
        <tr><td colspan="3"><img src="images/spacer.png" width="1" height="1" border="0" alt="" /></td></tr>
        <tr><td colspan="3" class="tblHeaders"><?php
          if($cfg['PropertiesIconic']){
                                          echo '<img src="images/b_edit.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
                                        }
          echo $strDBRename.':&nbsp;';
          ?></td></tr>
        <form method="post" action="db_details_structure.php"
            onsubmit="return emptyFormElements(this, 'newname')">
                                        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="2"><?php
          echo '<input type="hidden" name="db_rename" value="true" />'
             . PMA_generate_common_hidden_inputs($db);
          ?><input type="text" name="newname" size="30" class="textfield" value="" /></td>
            <td align="right"><input type="submit" value="<?php echo $strGo; ?>" /></td>
        </form></tr>

<?php

if (PMA_MYSQL_INT_VERSION >= 40101) {
    // MySQL supports setting default charsets / collations for databases since
    // version 4.1.1.
    echo '    <!-- Change database charset -->' . "\n"
       . '    <tr><td colspan="3"><img src="images/spacer.png" width="1" height="1" border="0" alt="" /></td></tr>' . "\n"
       . '    <tr><td colspan="3" class="tblHeaders">';
       if($cfg['PropertiesIconic']){
         echo '<img src="./images/s_asci.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
       }
    echo '      <label for="select_db_collation">' . $strCollation . '</label>:&nbsp;' . "\n"
       . '    </td></tr>' . "\n"
       . '        <form method="post" action="./db_details_structure.php">' . "\n"
       . '    <tr bgcolor="' . $cfg['BgcolorOne'] . '"><td colspan="2" nowrap="nowrap">'
       . PMA_generate_common_hidden_inputs($db, $table, 3)
       . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'db_collation', 'select_db_collation', $db_collation, FALSE, 3)
       . '    </td><td align="right">'
       . '            <input type="submit" name="submitcollation" value="' . $strGo . '" style="vertical-align: middle" />&nbsp;' . "\n"
       . '    </td></tr>' . "\n"
       . '        </form>' . "\n"
       . '         ' . "\n\n";
}

if ($num_tables > 0
    && !$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == FALSE) {
    echo '    <tr><td colspan="3"><img src="images/spacer.png" width="1" height="1" border="0" alt="" /></td></tr>' . "\n"
       . '    <tr><td colspan="3" class="tblHeadError">';
       if($cfg['PropertiesIconic']){
         echo '<img src="./images/s_error.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
       }
    echo '    ' . $strError . '' . "\n";
    echo '</td><tr>';
    echo '<td colspan="3" class="tblError">';
    $url_to_goto = '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">';
    echo '        ' . sprintf(wordwrap($strRelationNotWorking,60,'<br />'), $url_to_goto, '</a>') . "\n";
    echo '    </td></tr>' . "\n";
} // end if
?>
</table>
<?php
// is this OK to check for 'class' support?
if ($num_tables > 0) {
    $takeaway = $url_query . '&amp;table=' . urlencode($table);
}
if (($cfgRelation['pdfwork'] && $num_tables > 0) ||
($num_tables > 0
    && $cfgRelation['relwork'] && $cfgRelation['commwork']
    && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    )
) { ?><hr /><table border="0" cellpadding="2" cellspacing="0"><?php }

if ($cfgRelation['pdfwork'] && $num_tables > 0) {
    ?>
    <!-- Work on PDF Pages -->
      <tr><td colspan="3" class="tblHeaders">
      <?php
        if($cfg['PropertiesIconic']){
        echo '<img src="./images/b_pdfdoc.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
        }
?>PDF</td></tr><tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
        <td colspan="3"><?php
        echo '<a href="pdf_pages.php?' . $takeaway . '">';
        if($cfg['PropertiesIconic']){
        echo '<img src="./images/b_edit.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
        }
        echo ''. $strEditPDFPages . '</a>';
     ?></td></tr>

    <!-- PDF schema -->
    <?php
    // We only show this if we find something in the new pdf_pages table

    $test_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $test_rs    = PMA_query_as_cu($test_query);
    if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
        echo "\n";
        ?>
        <form method="post" action="pdf_schema.php">
         <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><td colspan="3">
            <?php
                  echo PMA_generate_common_hidden_inputs($db);
                  if($cfg['PropertiesIconic']){
                   echo '<img src="./images/b_view.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
                  }
            ?>
            <?php echo $strDisplayPDF; ?>:&nbsp;</td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><td width="20">&nbsp;</td><td colspan="2">
            <?php echo $strPageNumber; ?>&nbsp;
            <select name="pdf_page_number">
        <?php
        while ($pages = @PMA_DBI_fetch_assoc($test_rs)) {
            echo "\n" . '                '
                 . '<option value="' . $pages['page_nr'] . '">' . $pages['page_nr'] . ': ' . $pages['page_descr'] . '</option>';
        } // end while
        PMA_DBI_free_result($test_rs);
        unset($test_rs);
        echo "\n";
        ?>
            </select></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td width="20" valign="top">
            <input type="checkbox" name="show_grid" id="show_grid_opt" /></td><td>
            <label for="show_grid_opt"><?php echo $strShowGrid; ?></label></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td width="20" valign="top">
            <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" /></td><td>
            <label for="show_color_opt"><?php echo $strShowColor; ?></label></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td width="20" valign="top">
            <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" /></td><td>
            <label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?></label></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td width="20" valign="top">
            <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" /></td><td>
            <label for="all_tab_same_wide"><?php echo wordwrap($strAllTableSameWidth,55,'<br />'); ?></label></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td width="20" valign="top">
            <input type="checkbox" name="with_doc" id="with_doc" checked="checked" /></td><td>
            <label for="with_doc"><?php echo $strDataDict; ?></label></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td colspan="2">
            <?php echo $strShowDatadictAs; ?>
            <select name="orientation">
                <option value="L"><?php echo $strLandscape;?></option>
                <option value="P"><?php echo $strPortrait;?></option>
            </select></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td colspan="2">
            <?php echo $strPaperSize; ?>
            <select name="paper">
            <?php
                foreach($cfg['PDFPageSizes'] AS $key => $val) {
                    echo '<option value="' . $val . '"';
                    if ($val == $cfg['PDFDefaultPageSize']) {
                        echo ' selected="selected"';
                    }
                    echo ' >' . $val . '</option>' . "\n";
                }
            ?>
                </select></td></tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                                                  <td width="20">&nbsp;</td><td colspan="3" align="right">
                &nbsp;&nbsp;<input type="submit" value="<?php echo $strGo; ?>" /></td>
            </form></tr>
            <tr><td colspan="3"><img src="images/spacer.png" width="1" height="1" border="0" alt="" /></td></tr>
        <?php
    }   // end if
?>

<?php
} // end if

if ($num_tables > 0
    && $cfgRelation['relwork'] && $cfgRelation['commwork']
    && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    ) {
?>
    <!-- import docSQL files -->
    <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="3"><?php
                   echo '<a href="db_details_importdocsql.php?' . $takeaway . '">';
       if($cfg['PropertiesIconic']){
         echo '<img src="./images/b_docsql.png" border="0" width="16" height="16" hspace="2" align="absmiddle" />';
       }
       echo $strImportDocSQL . '</a>';
    ?>
    </td></tr>
    <?php
}
echo "\n";
if (($cfgRelation['pdfwork'] && $num_tables > 0) ||
($num_tables > 0
    && $cfgRelation['relwork'] && $cfgRelation['commwork']
    && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    )
) { ?></table><?php }

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
