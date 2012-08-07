<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

/**
 * Function implementations for this script
 */
require_once 'libraries/structure.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_structure.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('jquery/timepicker.js');
$common_functions = PMA_CommonFunctions::getInstance();

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'error',
    'is_info',
    'message',
    'mult_btn',
    'selected_tbl',
    'submit_mult'
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

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
            include 'libraries/mult_submits.inc.php';
        }
        if (empty($message)) {
            $message = PMA_Message::success();
        }
    }
    include 'libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    include 'libraries/db_info.inc.php';

    if (!PMA_DRIZZLE) {
        include_once 'libraries/replication.inc.php';
    } else {
        $server_slave_status = false;
    }
}

require_once 'libraries/bookmark.lib.php';

require_once 'libraries/mysql_charsets.lib.php';
$db_collation = PMA_getDbCollation($db);

// in a separate file to avoid redeclaration of functions in some code paths
require_once 'libraries/db_structure.lib.php';
$titles = $common_functions->buildActionTitles();

// 1. No tables

if ($num_tables == 0) {
    echo '<p>' . __('No tables found in database') . '</p>' . "\n";

    if (empty($db_is_information_schema)) {
        include 'libraries/display_create_table.lib.php';
    } // end if (Create Table dialog)
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

echo $common_functions->getListNavigator(
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
$create_time_all = '';
$update_time_all = '';
$check_time_all = '';
$checked        = !empty($checkall) ? ' checked="checked"' : '';
$num_columns    = $cfg['PropertiesNumColumns'] > 1
    ? ceil($num_tables / $cfg['PropertiesNumColumns']) + 1
    : 0;
$row_count      = 0;


$hidden_fields = array();
$odd_row       = true;
$sum_row_count_pre = '';

foreach ($tables as $keyname => $each_table) {
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
            $tblsize = doubleval($each_table['Data_length']) + doubleval($each_table['Index_length']);
            $sum_size += $tblsize;
            list($formatted_size, $unit) = $common_functions->formatByteDown(
                $tblsize, 3, ($tblsize > 0) ? 1 : 0
            );
            if (isset($each_table['Data_free']) && $each_table['Data_free'] > 0) {
                list($formatted_overhead, $overhead_unit)
                    = $common_functions->formatByteDown(
                        $each_table['Data_free'], 3,
                        ($each_table['Data_free'] > 0) ? 1 : 0
                    );
                $overhead_size += $each_table['Data_free'];
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
            $tblsize =  $each_table['Data_length'] + $each_table['Index_length'];
            $sum_size += $tblsize;
            list($formatted_size, $unit) = $common_functions->formatByteDown(
                $tblsize, 3, ($tblsize > 0) ? 1 : 0
            );
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
                "markAllRows('row_tbl_" . ($i + 1) . "');";
        } else {
            $overhead = '-';
        }
    } // end if

    unset($showtable);

    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        $showtable = PMA_Table::sGetStatusInfo($db, $each_table['TABLE_NAME'], null, true);
        $create_time = isset($showtable['Create_time']) ? $showtable['Create_time'] : false;

        // show oldest creation date in summary row
        if ($create_time && (!$create_time_all || $create_time < $create_time_all)) {
            $create_time_all = $create_time;
        }
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        // $showtable might already be set from ShowDbStructureCreation, see above
        if (! isset($showtable)) {
            $showtable = PMA_Table::sGetStatusInfo($db, $each_table['TABLE_NAME'], null, true);
        }
        $update_time = isset($showtable['Update_time']) ? $showtable['Update_time'] : false;

        // show newest update date in summary row
        if ($update_time && $update_time > $update_time_all) {
            $update_time_all = $update_time;
        }
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        // $showtable might already be set from ShowDbStructureCreation, see above
        if (! isset($showtable)) {
            $showtable = PMA_Table::sGetStatusInfo($db, $each_table['TABLE_NAME'], null, true);
        }
        $check_time = isset($showtable['Check_time']) ? $showtable['Check_time'] : false;

        // show newest check date in summary row
        if ($check_time && $check_time > $check_time_all) {
            $check_time_all = $check_time;
        }
    }

    $alias = (! empty($tooltip_aliasname) && isset($tooltip_aliasname[$each_table['TABLE_NAME']]))
               ? str_replace(' ', '&nbsp;', htmlspecialchars($tooltip_truename[$each_table['TABLE_NAME']]))
               : str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME']));
    $truename = (! empty($tooltip_truename) && isset($tooltip_truename[$each_table['TABLE_NAME']]))
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
    list($browse_table, $search_table, $browse_table_label, $empty_table,
        $tracking_icon
    ) = PMA_getHtmlForActionLinks(
            $each_table, $table_is_view, $tbl_url_query,
            $titles, $truename, $db_is_information_schema, $url_query
        );
    
    if (! $db_is_information_schema) {
        list($drop_query, $drop_message) 
            = PMA_getTableDropQueryAndMessage($table_is_view, $each_table);
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
<tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row;
    echo $table_is_view ? ' is_view' : '';
    ?>"
    id="row_tbl_<?php echo $i; ?>">
    <td class="center">
        <input type="checkbox" name="selected_tbl[]" class="checkall"
            value="<?php echo htmlspecialchars($each_table['TABLE_NAME']); ?>"
            id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> /></td>
    <th><?php echo $browse_table_label; ?>
        <?php echo (! empty($tracking_icon) ? $tracking_icon : ''); ?>
    </th>
    <?php
    if ($server_slave_status) {
       ?><td class="center"><?php
        echo $ignored
            ? $common_functions->getImage('s_cancel.png', 'NOT REPLICATED')
            : ''.
        $do
            ? $common_functions->getImage('s_success.png', 'REPLICATED')
            : ''; ?></td><?php
    }
    ?>
    <td class="center"><?php echo $browse_table; ?></td>
    <td class="center">
        <a href="tbl_structure.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Structure']; ?></a></td>
    <td class="center"><?php echo $search_table; ?></td>
    <?php
    if (! $db_is_information_schema) {
        ?>
    <td class="insert_table center">
        <a <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'class="ajax"' : ''); ?> href="tbl_change.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Insert']; ?></a></td>
    <td class="center"><?php echo $empty_table; ?></td>
    <td class="center">
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
    <?php
    } // end if (! $db_is_information_schema)

    // there is a null value in the ENGINE
    // - when the table needs to be repaired, or
    // - when it's a view
    //  so ensure that we'll display "in use" below for a table
    //  that needs to be repaired
    if (isset($each_table['TABLE_ROWS']) && ($each_table['ENGINE'] != null || $table_is_view)) {
        $row_count_pre = '';
        $show_superscript = '';
        if ($table_is_view) {
            // Drizzle views use FunctionEngine, and the only place where they are
            // available are I_S and D_D schemas, where we do exact counting
            if ($each_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']
                && $each_table['ENGINE'] != 'FunctionEngine'
            ) {
                $row_count_pre = '~';
                $sum_row_count_pre = '~';
                $show_superscript = $common_functions->showHint(
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
    <td class="value tbl_rows"><?php echo $row_count_pre . $common_functions->formatNumber($each_table['TABLE_ROWS'], 0) . $show_superscript; ?></td>
    <?php
    if (!($cfg['PropertiesNumColumns'] > 1)) {
    ?>
    <td class="nowrap"><?php echo ($table_is_view ? __('View') : $each_table['ENGINE']); ?></td>
        <?php
        if (isset($collation)) {
        ?>
    <td class="nowrap"><?php echo $collation ?></td>
    <?php
        }
    }
    if ($is_show_stats) {
    ?>
    <td class="value tbl_size"><a
        href="tbl_structure.php?<?php echo $tbl_url_query; ?>#showusage"
        ><?php echo '<span>' . $formatted_size . '</span> <span class="unit">' . $unit . '</span>'; ?></a></td>
    <td class="value tbl_overhead"><?php echo $overhead; ?></td>
    <?php
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
    ?>
    <td class="value tbl_creation"><?php echo $create_time ? $common_functions->localisedDate(strtotime($create_time)) : '-'; ?></td>
    <?php
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
    ?>
    <td class="value tbl_last_update"><?php echo $update_time ? $common_functions->localisedDate(strtotime($update_time)) : '-'; ?></td>
    <?php
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
    ?>
    <td class="value tbl_last_check"><?php echo $check_time ? $common_functions->localisedDate(strtotime($check_time)) : '-'; ?></td>
    <?php
    } // end if
    } elseif ($table_is_view) {
    ?>
    <td class="value">-</td>
    <td><?php echo __('View'); ?></td>
    <td>---</td>
        <?php
        if ($is_show_stats) {
            ?>
    <td class="value">-</td>
    <td class="value">-</td>
        <?php
        }
        ?>
    <?php
    } else {
        ?>
    <td colspan="<?php echo ($colspan_for_structure - ($db_is_information_schema ? 5 : 8)) ?>"
        class="center">
        <?php echo __('in use'); ?></td>
    <?php
    } // end if (isset($each_table['TABLE_ROWS'])) else
    ?>
</tr>
    <?php
} // end foreach

// Show Summary
echo '</tbody>';
echo PMA_getHtmlBodyForTableSummery(
    $num_tables, $server_slave_status, $db_is_information_schema, $sum_entries,
    $db_collation, $is_show_stats, $sum_size, $overhead_size, $create_time_all,
    $update_time_all, $check_time_all, $sum_row_count_pre
);
echo '</table>';
//check all
echo PMA_getHtmlForCheckAllTables($pmaThemeImage, $text_dir, 
    $overhead_check, $db_is_information_schema, $hidden_fields
);
echo '</form>';

// display again the table list navigator
echo $common_functions->getListNavigator(
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
echo $common_functions->getIcon('b_print.png', __('Print view'), true) . '</a>';

echo '<a href="db_datadict.php?' . $url_query . '">';
echo $common_functions->getIcon('b_tblanalyse.png', __('Data Dictionary'), true) . '</a>';
echo '</p>';

if (empty($db_is_information_schema)) {
    include 'libraries/display_create_table.lib.php';
} // end if (Create Table dialog)

?>
