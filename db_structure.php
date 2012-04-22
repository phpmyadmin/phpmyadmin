<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'db_structure.js';
$GLOBALS['js_include'][] = 'tbl_change.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'jquery/jquery.sprintf.js';

/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is true)
 */
if (empty($is_info)) {
    // Drops/deletes/etc. multiple tables if required
    if ((!empty($submit_mult) && isset($selected_tbl))
        || isset($mult_btn)
    ) {
        $action = 'db_structure.php';
        $err_url = 'db_structure.php?'. PMA_generate_common_url($db);

        // see bug #2794840; in this case, code path is:
        // db_structure.php -> libraries/mult_submits.inc.php -> sql.php
        // -> db_structure.php and if we got an error on the multi submit,
        // we must display it here and not call again mult_submits.inc.php
        if (! isset($error) || false === $error) {
            include './libraries/mult_submits.inc.php';
        }
        if (empty($message)) {
            $message = PMA_Message::success();
        }
    }
    include './libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    include './libraries/db_info.inc.php';

    if (!PMA_DRIZZLE) {
        include_once './libraries/replication.inc.php';
    } else {
        $server_slave_status = false;
    }
}

require_once './libraries/bookmark.lib.php';

require_once './libraries/mysql_charsets.lib.php';
$db_collation = PMA_getDbCollation($db);

// in a separate file to avoid redeclaration of functions in some code paths
require_once './libraries/db_structure.lib.php';
$titles = PMA_buildActionTitles();

// 1. No tables

if ($num_tables == 0) {
    echo '<p>' . __('No tables found in database') . '</p>' . "\n";

    if (empty($db_is_information_schema)) {
        include './libraries/display_create_table.lib.php';
    } // end if (Create Table dialog)

    /**
     * Displays the footer
     */
    include_once './libraries/footer.inc.php';
    exit;
}

// else
// 2. Shows table informations

/**
 * Displays the tables list
 */
echo '<div id="tableslistcontainer">';
$_url_params = array(
    'pos' => $pos,
    'db'  => $db);

// Add the sort options if they exists
if (isset($_REQUEST['sort'])) {
    $_url_params['sort'] = $_REQUEST['sort'];
}

if (isset($_REQUEST['sort_order'])) {
    $_url_params['sort_order'] = $_REQUEST['sort_order'];
}

PMA_listNavigator(
    $total_num_tables, $pos, $_url_params, 'db_structure.php',
    'frame_content', $GLOBALS['cfg']['MaxTableList']
);

?>
<form method="post" action="db_structure.php" name="tablesForm" id="tablesForm">
<?php
echo PMA_generate_common_hidden_inputs($db);

PMA_TableHeader($db_is_information_schema, $server_slave_status);

$i = $sum_entries = 0;
$sum_size       = (double) 0;
$overhead_size  = (double) 0;
$overhead_check = '';
$checked        = !empty($checkall) ? ' checked="checked"' : '';
$num_columns    = $cfg['PropertiesNumColumns'] > 1
    ? ceil($num_tables / $cfg['PropertiesNumColumns']) + 1
    : 0;
$row_count      = 0;


$hidden_fields = array();
$odd_row       = true;
$sum_row_count_pre = '';

$tableReductionCount = 0;   // the amount to reduce the table count by

foreach ($tables as $keyname => $each_table) {
    if (PMA_BS_IsHiddenTable($keyname)) {
        $tableReductionCount++;
        continue;
    }

    // Get valid statistics whatever is the table type

    $table_is_view = false;
    $table_encoded = urlencode($each_table['TABLE_NAME']);
    // Sets parameters for links
    $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
    // do not list the previous table's size info for a view
    $formatted_size = '-';
    $unit = '';

    switch ( $each_table['ENGINE']) {
        // MyISAM, ISAM or Heap table: Row count, data size and index size
        // are accurate; data size is accurate for ARCHIVE
    case 'MyISAM' :
    case 'ISAM' :
    case 'HEAP' :
    case 'MEMORY' :
    case 'ARCHIVE' :
    case 'Aria' :
    case 'Maria' :
        if ($db_is_information_schema) {
            $each_table['Rows'] = PMA_Table::countRecords(
                $db, $each_table['Name']
            );
        }

        if ($is_show_stats) {
            $tblsize                    =  doubleval($each_table['Data_length']) + doubleval($each_table['Index_length']);
            $sum_size                   += $tblsize;
            list($formatted_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
            if (isset($each_table['Data_free']) && $each_table['Data_free'] > 0) {
                list($formatted_overhead, $overhead_unit)     = PMA_formatByteDown($each_table['Data_free'], 3, ($each_table['Data_free'] > 0) ? 1 : 0);
                $overhead_size           += $each_table['Data_free'];
            }
        }
        break;
    case 'InnoDB' :
    case 'PBMS' :
        // InnoDB table: Row count is not accurate but data and index sizes are.
        // PBMS table in Drizzle: TABLE_ROWS is taken from table cache, so it may be unavailable

        if (($each_table['ENGINE'] == 'InnoDB'
            && $each_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
            || !isset($each_table['TABLE_ROWS'])
        ) {
            $each_table['COUNTED'] = true;
            $each_table['TABLE_ROWS'] = PMA_Table::countRecords(
                $db, $each_table['TABLE_NAME'],
                $force_exact = true, $is_view = false
            );
        } else {
            $each_table['COUNTED'] = false;
        }

        // Drizzle doesn't provide data and index length, check for null
        if ($is_show_stats && $each_table['Data_length'] !== null) {
            $tblsize                    =  $each_table['Data_length'] + $each_table['Index_length'];
            $sum_size                   += $tblsize;
            list($formatted_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
        }
        //$display_rows                   =  ' - ';
        break;
    // Mysql 5.0.x (and lower) uses MRG_MyISAM and MySQL 5.1.x (and higher) uses MRG_MYISAM
    // Both are aliases for MERGE
    case 'MRG_MyISAM' :
    case 'MRG_MYISAM' :
    case 'MERGE' :
    case 'BerkeleyDB' :
        // Merge or BerkleyDB table: Only row count is accurate.
        if ($is_show_stats) {
            $formatted_size =  ' - ';
            $unit          =  '';
        }
        break;
        // for a view, the ENGINE is sometimes reported as null,
        // or on some servers it's reported as "SYSTEM VIEW"
    case null :
    case 'SYSTEM VIEW' :
    case 'FunctionEngine' :
        // if table is broken, Engine is reported as null, so one more test
        if ($each_table['TABLE_TYPE'] == 'VIEW') {
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $each_table['TABLE_ROWS'] = PMA_Table::countRecords(
                $db, $each_table['TABLE_NAME'],
                $force_exact = true, $is_view = true
            );
            $table_is_view = true;
        }
        break;
    default :
        // Unknown table type.
        if ($is_show_stats) {
            $formatted_size =  __('unknown');
            $unit          =  '';
        }
    } // end switch

    if (! PMA_Table::isMerge($db, $each_table['TABLE_NAME'])) {
        $sum_entries += $each_table['TABLE_ROWS'];
    }

    if (isset($each_table['Collation'])) {
        $collation = '<dfn title="'
            . PMA_getCollationDescr($each_table['Collation']) . '">'
            . $each_table['Collation'] . '</dfn>';
    } else {
        $collation = '---';
    }

    if ($is_show_stats) {
        if (isset($formatted_overhead)) {
            $overhead = '<a href="tbl_structure.php?'
                . $tbl_url_query . '#showusage"><span>' . $formatted_overhead
                . '</span> <span class="unit">' . $overhead_unit . '</span></a>' . "\n";
            unset($formatted_overhead);
            $overhead_check .=
                "document.getElementById('checkbox_tbl_" . ($i + 1) . "').checked = true;";
        } else {
            $overhead = '-';
        }
    } // end if

    $alias = (!empty($tooltip_aliasname) && isset($tooltip_aliasname[$each_table['TABLE_NAME']]))
               ? str_replace(' ', '&nbsp;', htmlspecialchars($tooltip_truename[$each_table['TABLE_NAME']]))
               : str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME']));
    $truename = (!empty($tooltip_truename) && isset($tooltip_truename[$each_table['TABLE_NAME']]))
               ? str_replace(' ', '&nbsp;', htmlspecialchars($tooltip_truename[$each_table['TABLE_NAME']]))
               : str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME']));

    $i++;

    $row_count++;
    if ($table_is_view) {
        $hidden_fields[] = '<input type="hidden" name="views[]" value="'
            .  htmlspecialchars($each_table['TABLE_NAME']) . '" />';
    }

    /*
     * Always activate links for Browse, Search and Empty, even if
     * the icons are greyed, because
     * 1. for views, we don't know the number of rows at this point
     * 2. for tables, another source could have populated them since the
     *    page was generated
     *
     * I could have used the PHP ternary conditional operator but I find
     * the code easier to read without this operator.
     */
    if ($each_table['TABLE_ROWS'] > 0 || $table_is_view) {
        $may_have_rows = true;
    } else {
        $may_have_rows = false;
    }
    $browse_table = '<a href="sql.php?' . $tbl_url_query . '&amp;pos=0">';
    if ($may_have_rows) {
        $browse_table .= $titles['Browse'];
    } else {
        $browse_table .= $titles['NoBrowse'];
    }
    $browse_table .= '</a>';

    $search_table = '<a href="tbl_select.php?' . $tbl_url_query . '">';
    if ($may_have_rows) {
        $search_table .= $titles['Search'];
    } else {
        $search_table .= $titles['NoSearch'];
    }
    $search_table .= '</a>';

    $browse_table_label = '<a href="sql.php?' . $tbl_url_query . '&amp;pos=0">' . $truename . '</a>';

    if (! $db_is_information_schema) {
        $empty_table = '<a ';
        if ($GLOBALS['cfg']['AjaxEnable']) {
            $empty_table .= 'class="truncate_table_anchor"';
        }
        $empty_table .= ' href="sql.php?' . $tbl_url_query
             . '&amp;sql_query=';
        $empty_table .= urlencode('TRUNCATE ' . PMA_backquote($each_table['TABLE_NAME']))
             . '&amp;message_to_show='
             . urlencode(sprintf(__('Table %s has been emptied'), htmlspecialchars($each_table['TABLE_NAME'])))
             .'">';
        if ($may_have_rows) {
            $empty_table .= $titles['Empty'];
        } else {
            $empty_table .= $titles['NoEmpty'];
        }
        $empty_table .= '</a>';

        $drop_query = 'DROP '
            . (($table_is_view || $each_table['ENGINE'] == null) ? 'VIEW' : 'TABLE')
            . ' ' . PMA_backquote($each_table['TABLE_NAME']);
        $drop_message = sprintf(
            ($table_is_view || $each_table['ENGINE'] == null)? __('View %s has been dropped') : __('Table %s has been dropped'),
            str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME']))
        );
    }

    $tracking_icon = '';
    if (PMA_Tracker::isActive()) {
        if (PMA_Tracker::isTracked($GLOBALS["db"], $truename)) {
            $tracking_icon = '<a href="tbl_tracking.php?' . $url_query
                . '&amp;table=' . $truename . '">'
                . PMA_getImage('eye.png', __('Tracking is active.'))
                . '</a>';
        } elseif (PMA_Tracker::getVersion($GLOBALS["db"], $truename) > 0) {
            $tracking_icon = '<a href="tbl_tracking.php?' . $url_query
                . '&amp;table=' . $truename . '">'
                . PMA_getImage('eye.png', __('Tracking is not active.'))
                . '</a>';
        }
    }

    if ($num_columns > 0
        && $num_tables > $num_columns
        && ($row_count % $num_columns) == 0
    ) {
        $row_count = 1;
        $odd_row = true;
        ?>
    </tr>
</tbody>
</table>
        <?php
        PMA_TableHeader(false, $server_slave_status);
    }

    $ignored = false;
    $do = false;

    if ($server_slave_status) {
        ////////////////////////////////////////////////////////////////

        if ((strlen(array_search($truename, $server_slave_Do_Table)) > 0)
            || (strlen(array_search($db, $server_slave_Do_DB)) > 0)
            || (count($server_slave_Do_DB) == 1 && count($server_slave_Ignore_DB) == 1)
        ) {
            $do = true;
        }
        foreach ($server_slave_Wild_Do_Table as $db_table) {
            $table_part = PMA_extract_db_or_table($db_table, 'table');
            if (($db == PMA_extract_db_or_table($db_table, 'db'))
                && (preg_match("@^" . substr($table_part, 0, strlen($table_part) - 1) . "@", $truename))
            ) {
                $do = true;
            }
        }
        ////////////////////////////////////////////////////////////////////
        if ((strlen(array_search($truename, $server_slave_Ignore_Table)) > 0)
            || (strlen(array_search($db, $server_slave_Ignore_DB)) > 0)
        ) {
            $ignored = true;
        }
        foreach ($server_slave_Wild_Ignore_Table as $db_table) {
            $table_part = PMA_extract_db_or_table($db_table, 'table');
            if (($db == PMA_extract_db_or_table($db_table))
                && (preg_match("@^" . substr($table_part, 0, strlen($table_part) - 1) . "@", $truename))
            ) {
                $ignored = true;
            }
        }
        unset($table_part);
    }
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
    <td align="center">
        <input type="checkbox" name="selected_tbl[]"
            value="<?php echo htmlspecialchars($each_table['TABLE_NAME']); ?>"
            id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> /></td>
    <th><?php echo $browse_table_label; ?>
        <?php echo (! empty($tracking_icon) ? $tracking_icon : ''); ?>
    </th>
   <?php if ($server_slave_status) { ?><td align="center"><?php
        echo $ignored
            ? PMA_getImage('s_cancel.png', 'NOT REPLICATED')
            : ''.
        $do
            ? PMA_getImage('s_success.png', 'REPLICATED')
            : ''; ?></td><?php } ?>
    <td align="center"><?php echo $browse_table; ?></td>
    <td align="center">
        <a href="tbl_structure.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Structure']; ?></a></td>
    <td align="center"><?php echo $search_table; ?></td>
    <?php if (! $db_is_information_schema) { ?>
    <td align="center" class="insert_table">
        <a <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'class="ajax"' : ''); ?> href="tbl_change.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Insert']; ?></a></td>
    <td align="center"><?php echo $empty_table; ?></td>
    <td align="center">
    <a 
    <?php if ($GLOBALS['cfg']['AjaxEnable']) {
            echo 'class="drop_table_anchor';
            if ($table_is_view || $each_table['ENGINE'] == null) {
                // this class is used in db_structure.js to display the
                // correct confirmation message
                echo ' view';
            }
            echo '"';
          }
    ?> href="sql.php?<?php echo $tbl_url_query;
            ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php
            echo urlencode($drop_query); ?>&amp;message_to_show=<?php
            echo urlencode($drop_message); ?>" >
            <?php echo $titles['Drop']; ?></a></td>
    <?php } // end if (! $db_is_information_schema)

    // there is a null value in the ENGINE
    // - when the table needs to be repaired, or
    // - when it's a view
    //  so ensure that we'll display "in use" below for a table
    //  that needs to be repaired
    if (isset($each_table['TABLE_ROWS']) && ($each_table['ENGINE'] != null || $table_is_view)) {
        $row_count_pre = '';
        $show_superscript = '';
        if ($table_is_view) {
            // Drizzle views use FunctionEngine, and the only place where they are available are I_S and D_D
            // schemas, where we do exact counting
            if ($each_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']
                && $each_table['ENGINE'] != 'FunctionEngine'
            ) {
                $row_count_pre = '~';
                $sum_row_count_pre = '~';
                $show_superscript = PMA_showHint(
                    PMA_sanitize(
                        sprintf(
                            __('This view has at least this number of rows. Please refer to %sdocumentation%s.'),
                            '[a@./Documentation.html#cfg_MaxExactCountViews@_blank]',
                            '[/a]'
                        )
                    )
                );
            }
        } elseif ($each_table['ENGINE'] == 'InnoDB' && (! $each_table['COUNTED'])) {
            // InnoDB table: we did not get an accurate row count
            $row_count_pre = '~';
            $sum_row_count_pre = '~';
            $show_superscript = '';
        }
    ?>
    <td class="value tbl_rows"><?php echo $row_count_pre . PMA_formatNumber($each_table['TABLE_ROWS'], 0) . $show_superscript; ?></td>
        <?php if (!($cfg['PropertiesNumColumns'] > 1)) { ?>
    <td nowrap="nowrap"><?php echo ($table_is_view ? __('View') : $each_table['ENGINE']); ?></td>
            <?php if (isset($collation)) { ?>
    <td nowrap="nowrap"><?php echo $collation ?></td>
            <?php } ?>
        <?php } ?>

        <?php if ($is_show_stats) { ?>
    <td class="value tbl_size"><a
        href="tbl_structure.php?<?php echo $tbl_url_query; ?>#showusage"
        ><?php echo '<span>' . $formatted_size . '</span> <span class="unit">' . $unit . '</span>'; ?></a></td>
    <td class="value tbl_overhead"><?php echo $overhead; ?></td>
        <?php } // end if ?>
    <?php } elseif ($table_is_view) { ?>
    <td class="value">-</td>
    <td><?php echo __('View'); ?></td>
    <td>---</td>
        <?php if ($is_show_stats) { ?>
    <td class="value">-</td>
    <td class="value">-</td>
        <?php } ?>
    <?php } else { ?>
    <td colspan="<?php echo ($colspan_for_structure - ($db_is_information_schema ? 5 : 8)) ?>"
        align="center">
        <?php echo __('in use'); ?></td>
    <?php } // end if (isset($each_table['TABLE_ROWS'])) else ?>
</tr>
    <?php
} // end foreach

// Show Summary
if ($is_show_stats) {
    list($sum_formatted, $unit) = PMA_formatByteDown($sum_size, 3, 1);
    list($overhead_formatted, $overhead_unit)
        = PMA_formatByteDown($overhead_size, 3, 1);
}
?>
</tbody>
<tbody id="tbl_summary_row">
<tr><th></th>
    <th align="center" nowrap="nowrap" class="tbl_num">
        <?php
            // for blobstreaming - if the number of tables is 0, set tableReductionCount to 0
            // (we don't want negative numbers here)
            if ($num_tables == 0) {
                $tableReductionCount = 0;
            }
            echo sprintf(
                _ngettext('%s table', '%s tables', $num_tables - $tableReductionCount),
                PMA_formatNumber($num_tables - $tableReductionCount, 0)
            );
        ?>
    </th>
    <?php
        if ($server_slave_status) {
            echo '    <th>' . __('Replication') . '</th>' . "\n";
        }
    ?>
    <th colspan="<?php echo ($db_is_information_schema ? 3 : 6) ?>" align="center">
        <?php echo __('Sum'); ?></th>
    <th class="value tbl_rows"><?php echo $sum_row_count_pre . PMA_formatNumber($sum_entries, 0); ?></th>
<?php
if (!($cfg['PropertiesNumColumns'] > 1)) {
    $default_engine = PMA_DBI_fetch_value('SHOW VARIABLES LIKE \'storage_engine\';', 0, 1);
    echo '    <th align="center">' . "\n"
       . '        <dfn title="'
       . sprintf(__('%s is the default storage engine on this MySQL server.'), $default_engine)
       . '">' .$default_engine . '</dfn></th>' . "\n";
    // we got a case where $db_collation was empty
    echo '    <th align="center">' . "\n";
    if (! empty($db_collation)) {
        echo '        <dfn title="'
            . PMA_getCollationDescr($db_collation) . ' (' . __('Default') . ')">' . $db_collation
            . '</dfn>';
    }
    echo '</th>';
}

if ($is_show_stats) {
    ?>
    <th class="value tbl_size"><?php echo $sum_formatted . ' ' . $unit; ?></th>
    <th class="value tbl_overhead"><?php echo $overhead_formatted . ' ' . $overhead_unit; ?></th>
    <?php
}
?>
</tr>
</tbody>
</table>

<div class="clearfloat">
<?php
// Check all tables url
$checkall_url = 'db_structure.php?' . PMA_generate_common_url($db);
?>
<img class="selectallarrow" src="<?php echo $pmaThemeImage .'arrow_'.$text_dir.'.png'; ?>"
    width="38" height="22" alt="<?php echo __('With selected:'); ?>" />
<a href="<?php echo $checkall_url; ?>&amp;checkall=1"
    onclick="if (markAllRows('tablesForm')) return false;">
    <?php echo __('Check All'); ?></a>
/
<a href="<?php echo $checkall_url; ?>"
    onclick="if (unMarkAllRows('tablesForm')) return false;">
    <?php echo __('Uncheck All'); ?></a>
<?php if ($overhead_check != '') { ?>
/
<a href="#" onclick="unMarkAllRows('tablesForm');
    <?php echo $overhead_check; ?> return false;">
    <?php echo __('Check tables having overhead'); ?></a>
<?php } ?>

<select name="submit_mult" class="autosubmit" style="margin: 0 3em 0 3em;">
<?php
echo '    <option value="' . __('With selected:') . '" selected="selected">'
     . __('With selected:') . '</option>' . "\n";
echo '    <option value="export" >'
     . __('Export') . '</option>' . "\n";
echo '    <option value="print" >'
    . __('Print view') . '</option>' . "\n";

if (!$db_is_information_schema && !$cfg['DisableMultiTableMaintenance']) {
    echo '    <option value="empty_tbl" >'
         . __('Empty') . '</option>' . "\n";
    echo '    <option value="drop_tbl" >'
         . __('Drop') . '</option>' . "\n";
    echo '    <option value="check_tbl" >'
         . __('Check table') . '</option>' . "\n";
    if (!PMA_DRIZZLE) {
        echo '    <option value="optimize_tbl" >'
             . __('Optimize table') . '</option>' . "\n";
        echo '    <option value="repair_tbl" >'
             . __('Repair table') . '</option>' . "\n";
    }
    echo '    <option value="analyze_tbl" >'
         . __('Analyze table') . '</option>' . "\n";
    echo '    <option value="add_prefix_tbl" >'
         . __('Add prefix to table') . '</option>' . "\n";
    echo '    <option value="replace_prefix_tbl" >'
         . __('Replace table prefix') . '</option>' . "\n";
    echo '    <option value="copy_tbl_change_prefix" >'
         . __('Copy table with prefix') . '</option>' . "\n";
}
?>
</select>
<script type="text/javascript">
<!--
// Fake js to allow the use of the <noscript> tag
//-->
</script>
<noscript>
    <input type="submit" value="<?php echo __('Go'); ?>" />
</noscript>
<?php echo implode("\n", $hidden_fields) . "\n"; ?>
</div>
</form>
<?php
// display again the table list navigator
PMA_listNavigator(
    $total_num_tables, $pos, $_url_params, 'db_structure.php',
    'frame_content', $GLOBALS['cfg']['MaxTableList']
);
?>
</div>
<hr />

<?php

/**
 * Work on the database
 */
/* DATABASE WORK */
/* Printable view of a table */
echo '<p>';
echo '<a href="db_printview.php?' . $url_query . '">';
echo PMA_getIcon('b_print.png', __('Print view'), true) . '</a>';

echo '<a href="./db_datadict.php?' . $url_query . '">';
echo PMA_getIcon('b_tblanalyse.png', __('Data Dictionary'), true) . '</a>';
echo '</p>';

if (empty($db_is_information_schema)) {
    include './libraries/display_create_table.lib.php';
} // end if (Create Table dialog)

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
