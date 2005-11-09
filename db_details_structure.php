<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if ( empty( $is_info ) ) {
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
}

// 1. No tables
if ( $num_tables == 0 ) {
    echo '<p>' . $strNoTablesFound . '</p>' . "\n";

    if ( empty( $db_is_information_schema ) ) {
        require('./libraries/display_create_table.lib.php');
    } // end if (Create Table dialog)

    /**
     * Displays the footer
     */
    require_once('./footer.inc.php');
    exit;
}

// else
// 2. Shows table informations - staybyte - 11 June 2001

require_once('./libraries/bookmark.lib.php');

if ( PMA_MYSQL_INT_VERSION >= 40101 ) {
    require_once('./libraries/mysql_charsets.lib.php');
    $db_collation = PMA_getDbCollation( $db );
}

// Display function
function PMA_TableHeader( $db_is_information_schema = false ) {
    $cnt = 0; // Let's count the columns...

    if ( $db_is_information_schema ) {
        $action_colspan = 3;
    } else {
        $action_colspan = 6;
    }

    echo '<table class="data" style="float: left;">' . "\n"
        .'<thead>' . "\n"
        .'<tr><td></td>' . "\n"
        .'    <th>' . $GLOBALS['strTable'] . '</th>' . "\n"
        .'    <th colspan="' . $action_colspan . '">' . "\n"
        .'        ' . $GLOBALS['strAction'] . "\n"
        .'    </th>'
        .'    <th>' . $GLOBALS['strRecords']
        .PMA_showHint( $GLOBALS['strApproximateCount'] ) . "\n"
        .'    </th>' . "\n";
    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        echo '    <th>' . $GLOBALS['strType'] . '</th>' . "\n";
        $cnt++;
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            echo '    <th>' . $GLOBALS['strCollation'] . '</th>' . "\n";
            $cnt++;
        }
    }
    if ($GLOBALS['cfg']['ShowStats']) {
        echo '    <th>' . $GLOBALS['strSize'] . '</th>' . "\n"
           . '    <th>' . $GLOBALS['strOverhead'] . '</th>' . "\n";
        $cnt += 2;
    }
    echo '</tr>' . "\n";
    echo '</thead>' . "\n";
    echo '<tbody>' . "\n";
    $GLOBALS['structure_tbl_col_cnt'] = $cnt + $action_colspan + 3;
}

$titles = array();
if ( true == $cfg['PropertiesIconic'] ) {
    $titles['Browse']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" />';
    $titles['NoBrowse']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" />';
    $titles['Search']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" />';
    $titles['NoSearch']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" />';
    $titles['Insert']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_insrow.png" alt="' . $strInsert . '" title="' . $strInsert . '" />';
    $titles['NoInsert']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_insrow.png" alt="' . $strInsert . '" title="' . $strInsert . '" />';
    $titles['Structure']  = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_props.png" alt="' . $strStructure . '" title="' . $strStructure . '" />';
    $titles['Drop']       = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" />';
    $titles['NoDrop']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" />';
    $titles['Empty']      = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" />';
    $titles['NoEmpty']    = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" />';

    if ( 'both' === $cfg['PropertiesIconic'] ) {
        $titles['Browse']     .= $strBrowse;
        $titles['Search']     .= $strSearch;
        $titles['NoBrowse']   .= $strBrowse;
        $titles['NoSearch']   .= $strSearch;
        $titles['Insert']     .= $strInsert;
        $titles['NoInsert']   .= $strInsert;
        $titles['Structure']  .= $strStructure;
        $titles['Drop']       .= $strDrop;
        $titles['NoDrop']     .= $strDrop;
        $titles['Empty']      .= $strEmpty;
        $titles['NoEmpty']    .= $strEmpty;
    }
} else {
    $titles['Browse']     = $strBrowse;
    $titles['Search']     = $strSearch;
    $titles['NoBrowse']   = $strBrowse;
    $titles['NoSearch']   = $strSearch;
    $titles['Insert']     = $strInsert;
    $titles['NoInsert']   = $strInsert;
    $titles['Structure']  = $strStructure;
    $titles['Drop']       = $strDrop;
    $titles['NoDrop']     = $strDrop;
    $titles['Empty']      = $strEmpty;
    $titles['NoEmpty']    = $strEmpty;
}

/**
 * Displays the tables list
 */
?>
<form method="post" action="db_details_structure.php" name="tablesForm" id="tablesForm">
<?php
echo PMA_generate_common_hidden_inputs( $db );

PMA_TableHeader( $db_is_information_schema );

$i = $sum_entries = 0;
$sum_size       = (double) 0;
$overhead_size  = (double) 0;
$overhead_check = '';
$checked        = !empty($checkall) ? ' checked="checked"' : '';
$num_columns    = $cfg['PropertiesNumColumns'] > 1 ? ceil($num_tables / $cfg['PropertiesNumColumns']) + 1 : 0;
$row_count      = 0;


$hidden_fields = array();
$odd_row       = true;
foreach ( $tables as $keyname => $each_table ) {
    if ( $each_table['TABLE_ROWS'] === NULL || $each_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount']) {
        $each_table['TABLE_ROWS'] = PMA_countRecords( $db,
            $each_table['TABLE_NAME'], $return = true, $force_exact = true );
    }

    $table_encoded = urlencode($each_table['TABLE_NAME']);
    // MySQL < 5.0.13 returns "view", >= 5.0.13 returns "VIEW"
    $table_is_view = ( $each_table['TABLE_TYPE'] === 'VIEW'
                       || $each_table['TABLE_TYPE'] === 'SYSTEM VIEW' );

    $alias = (!empty($tooltip_aliasname) && isset($tooltip_aliasname[$each_table['TABLE_NAME']]))
               ? htmlspecialchars($tooltip_aliasname[$each_table['TABLE_NAME']])
               :  htmlspecialchars($each_table['TABLE_NAME']);
    $truename = (!empty($tooltip_truename) && isset($tooltip_truename[$each_table['TABLE_NAME']]))
               ? htmlspecialchars($tooltip_truename[$each_table['TABLE_NAME']])
               : htmlspecialchars($each_table['TABLE_NAME']);

    // Sets parameters for links
    $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
    $i++;

    $row_count++;
    if ( $table_is_view ) {
        $hidden_fields[] = '<input type="hidden" name="views[]" value="' .  $table_encoded . '" />';
    }

    if ( $each_table['TABLE_ROWS'] > 0 ) {
        $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($each_table['TABLE_NAME']) . '\'', 'label');
        $browse_table = '<a href="sql.php?' . $tbl_url_query . '&amp;sql_query='
             . ( $book_sql_query ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($each_table['TABLE_NAME'])))
             . '&amp;pos=0">' . $titles['Browse'] . '</a>';
        $search_table = '<a href="tbl_select.php?' . $tbl_url_query . '">'
             . $titles['Search'] . '</a>';
    } else {
        $browse_table = $titles['NoBrowse'];
        $search_table = $titles['NoSearch'];
    }

    if ( ! $db_is_information_schema ) {
        if ( ! empty($each_table['TABLE_ROWS']) ) {
            $empty_table = '<a href="sql.php?' . $tbl_url_query
                 . '&amp;sql_query=';
            if (PMA_MYSQL_INT_VERSION >= 40000) {
                $empty_table .= urlencode('TRUNCATE ' . PMA_backquote($each_table['TABLE_NAME']))
                     . '&amp;zero_rows='
                     . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($each_table['TABLE_NAME'])))
                     . '" onclick="return confirmLink(this, \'TRUNCATE ';
            } else {
                $empty_table .= urlencode('DELETE FROM ' . PMA_backquote($each_table['TABLE_NAME']))
                     . '&amp;zero_rows='
                     . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($each_table['TABLE_NAME'])))
                     . '" onclick="return confirmLink(this, \'DELETE FROM ';
            }
            $empty_table .= PMA_jsFormat($each_table['TABLE_NAME']) . '\')">' . $titles['Empty'] . '</a>';
        } else {
            $empty_table = $titles['NoEmpty'];
        }
        $drop_query = 'DROP '
            . ( $table_is_view ? 'VIEW' : 'TABLE' )
            . ' ' . PMA_backquote($each_table['TABLE_NAME']);
        $drop_message = sprintf(
            $table_is_view ? $strViewHasBeenDropped : $strTableHasBeenDropped,
            htmlspecialchars( $each_table['TABLE_NAME'] ) );
    }

    // loic1: Patch from Joshua Nye <josh at boxcarmedia.com> to get valid
    //        statistics whatever is the table type
    if ( isset( $each_table['TABLE_ROWS'] ) ) {
        // MyISAM, ISAM or Heap table: Row count, data size and index size
        // is accurate.
        if ( preg_match('@^(MyISAM|ISAM|HEAP|MEMORY)$@', $each_table['ENGINE']) ) {
            if ($cfg['ShowStats']) {
                $tblsize                    =  doubleval($each_table['Data_length']) + doubleval($each_table['Index_length']);
                $sum_size                   += $tblsize;
                list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                if (isset($each_table['Data_free']) && $each_table['Data_free'] > 0) {
                    list($formated_overhead, $overhead_unit)     = PMA_formatByteDown($each_table['Data_free']);
                    $overhead_size           += $each_table['Data_free'];
                }
            }
            $sum_entries                    += $each_table['TABLE_ROWS'];
        } elseif ( $each_table['ENGINE'] == 'InnoDB' ) {
            // InnoDB table: Row count is not accurate but data and index
            // sizes are.
            if ($cfg['ShowStats']) {
                $tblsize                    =  $each_table['Data_length'] + $each_table['Index_length'];
                $sum_size                   += $tblsize;
                list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
            }
            //$display_rows                   =  ' - ';
            $sum_entries       += $each_table['TABLE_ROWS'];
        } elseif ( preg_match('@^(MRG_MyISAM|BerkeleyDB)$@', $each_table['ENGINE']) ) {
            // Merge or BerkleyDB table: Only row count is accurate.
            if ($cfg['ShowStats']) {
                $formated_size =  ' - ';
                $unit          =  '';
            }
            $sum_entries       += $each_table['TABLE_ROWS'];
        } else {
            // Unknown table type.
            if ($cfg['ShowStats']) {
                $formated_size =  'unknown';
                $unit          =  '';
            }
        }

        if (PMA_MYSQL_INT_VERSION >= 40100) {
            if ( isset( $each_table['Collation'] ) ) {
                $collation = '<dfn title="'
                    . PMA_getCollationDescr($each_table['Collation']) . '">'
                    . $each_table['Collation'] . '</dfn>';
            } else {
                $collation = '---';
            }
        }

        if ( $cfg['ShowStats']) {
            if (isset($formated_overhead)) {
                $overhead = '<a href="tbl_properties_structure.php?'
                    . $tbl_url_query . '#showusage">' . $formated_overhead
                    . ' ' . $overhead_unit . '</a>' . "\n";
                unset($formated_overhead);
                $overhead_check .=
                    "document.getElementById('checkbox_tbl_$i').checked = true;";
            } else {
                $overhead = '-';
            }
        } // end if
    }

    if ( $num_columns > 0 && $num_tables > $num_columns
      && ( ($row_count % $num_columns) == 0 )) {
        $row_count = 1;
        $odd_row = true;
    ?>
    </tr>
</tbody>
</table>
        <?php
        PMA_TableHeader();
    }
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
    <td align="center">
        <input type="checkbox" name="selected_tbl[]"
            value="<?php echo $table_encoded; ?>"
            id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> /></td>
    <th><label for="checkbox_tbl_<?php echo $i; ?>"
            title="<?php echo $alias; ?>"><?php echo $truename; ?></label>
    </th>
    <td align="center"><?php echo $browse_table; ?></td>
    <td align="center">
        <a href="tbl_properties_structure.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Structure']; ?></a></td>
    <td align="center"><?php echo $search_table; ?></td>
    <?php if ( ! $db_is_information_schema ) { ?>
    <td align="center">
        <a href="tbl_change.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Insert']; ?></a></td>
    <td align="center"><?php echo $empty_table; ?></td>
    <td align="center">
        <a href="sql.php?<?php echo $tbl_url_query;
            ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php
            echo urlencode($drop_query); ?>&amp;zero_rows=<?php
            echo urlencode($drop_message); ?>"
            onclick="return confirmLink(this, '<?php echo PMA_jsFormat($drop_query, FALSE); ?>')">
            <?php echo $titles['Drop']; ?></a></td>
    <?php } // end if ( ! $db_is_information_schema ) ?>
    <?php if ( isset( $each_table['TABLE_ROWS'] ) ) { ?>
    <td class="value"><?php echo PMA_formatNumber( $each_table['TABLE_ROWS'], 0 ); ?></td>
        <?php if (!($cfg['PropertiesNumColumns'] > 1)) { ?>
    <td nowrap="nowrap"><?php echo $each_table['ENGINE']; ?></td>
            <?php if ( isset( $collation ) ) { ?>
    <td nowrap="nowrap"><?php echo $collation ?></td>
            <?php } ?>
        <?php } ?>

        <?php if ( $cfg['ShowStats']) { ?>
    <td class="value"><a
        href="tbl_properties_structure.php?<?php echo $tbl_url_query; ?>#showusage"
        ><?php echo $formated_size . ' ' . $unit; ?></a></td>
    <td class="value"><?php echo $overhead; ?></td>
        <?php } // end if ?>
    <?php } elseif ( $table_is_view ) { ?>
    <td class="value">-</td>
    <td><?php echo $strView; ?></td>
    <td>---</td>
        <?php if ($cfg['ShowStats']) { ?>
    <td class="value">-</td>
    <td class="value">-</td>
        <?php } ?>
    <?php } else { ?>
    <td colspan="<?php echo ($structure_tbl_col_cnt - ($db_is_information_schema ? 5 : 8)) ?>"
        align="center">
        <?php echo $strInUse; ?></td>
    <?php } // end if ( isset( $each_table['TABLE_ROWS'] ) ) else ?>
</tr>
    <?php
} // end foreach

// Show Summary
if ($cfg['ShowStats']) {
    list($sum_formated, $unit) = PMA_formatByteDown($sum_size, 3, 1);
    list($overhead_formated, $overhead_unit) =
        PMA_formatByteDown($overhead_size, 3, 1);
}
?>
</tbody>
<tbody>
<tr><td></td>
    <th align="center" nowrap="nowrap">
        <?php echo sprintf( $strTables, PMA_formatNumber( $num_tables, 0 ) ); ?>
    </th>
    <th colspan="<?php echo ( $db_is_information_schema ? 3 : 6 ) ?>" align="center">
        <?php echo $strSum; ?></th>
    <th class="value"><?php echo PMA_formatNumber( $sum_entries, 0 ); ?></th>
<?php
if (!($cfg['PropertiesNumColumns'] > 1)) {
    echo '    <th align="center">'
        .PMA_DBI_get_default_engine() . '</th>' . "\n";
    if ( ! empty( $db_collation ) ) {
        echo '    <th align="center">' . "\n"
           . '        <dfn title="'
           . PMA_getCollationDescr($db_collation) . '">' . $db_collation
           . '</dfn></th>';
    }
}

if ($cfg['ShowStats']) {
    ?>
    <th class="value"><?php echo $sum_formated . ' ' . $unit; ?></th>
    <th class="value"><?php echo $overhead_formated . ' ' . $overhead_unit; ?></th>
    <?php
}
?>
</tr>
</tbody>
</table>

<div class="clearfloat">
<?php
// Check all tables url
$checkall_url = 'db_details_structure.php?' . PMA_generate_common_url($db);
?>
<img class="selectallarrow" src="<?php echo $pmaThemeImage .'arrow_'.$text_dir.'.png'; ?>"
    width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
<a href="<?php echo $checkall_url; ?>&amp;checkall=1"
    onclick="if ( markAllRows('tablesForm') ) return false;">
    <?php echo $strCheckAll; ?></a>
/
<a href="<?php echo $checkall_url; ?>"
    onclick="if ( unMarkAllRows('tablesForm') ) return false;">
    <?php echo $strUncheckAll; ?></a>
<?php if ($overhead_check != '') { ?>
/
<a href="#" onclick="setCheckboxes('tablesForm', false);
    <?php echo $overhead_check; ?> return false;">
    <?php echo $strCheckOverhead; ?></a>
<?php } ?>

<img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>"
    width="38" height="1" alt="" />
<select name="submit_mult" onchange="this.form.submit();">
<?php
echo '    <option value="' . $strWithChecked . '" selected="selected">'
     . $strWithChecked . '</option>' . "\n";
echo '    <option value="' . $strDrop . '" >'
     . $strDrop . '</option>' . "\n";
echo '    <option value="' . $strEmpty . '" >'
     . $strEmpty . '</option>' . "\n";
echo '    <option value="' . $strPrintView . '" >'
     . $strPrintView . '</option>' . "\n";
echo '    <option value="' . $strCheckTable . '" >'
     . $strCheckTable . '</option>' . "\n";
echo '    <option value="' . $strOptimizeTable . '" >'
     . $strOptimizeTable . '</option>' . "\n";
echo '    <option value="' . $strRepairTable . '" >'
     . $strRepairTable . '</option>' . "\n";
echo '    <option value="' . $strAnalyzeTable . '" >'
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
<?php echo implode( "\n", $hidden_fields ) . "\n"; ?>
</div>
</form>
<hr />

<?php
/**
 * Work on the database
 * redesigned 2004-05-08 by mkkeck
 */
/* DATABASE WORK */
/* Printable view of a table */
echo '<p>';
echo '<a href="db_printview.php?' . $url_query . '">';
if ( $cfg['PropertiesIconic'] ) {
     echo '<img class="icon" src="' . $pmaThemeImage
        .'b_print.png" width="16" height="16" alt="" />';
}
echo $strPrintView . '</a> ';

echo '<a href="./db_datadict.php?' . $url_query . '">';
if($cfg['PropertiesIconic']){
    echo '<img class="icon" src="' . $pmaThemeImage
        .'b_tblanalyse.png" width="16" height="16" alt="" />';
}
echo $strDataDict . '</a>';
echo '</p>';

if ( empty( $db_is_information_schema ) ) {
    require('./libraries/display_create_table.lib.php');
} // end if (Create Table dialog)

/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
