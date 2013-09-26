<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions for structure section in pma
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get the HTML links for action links
 * Actions are, Browse, Search, Browse table label, empty table
 *
 * @param array   $current_table            current table
 * @param boolean $table_is_view            Is table view or not
 * @param string  $tbl_url_query            table url query
 * @param array   $titles                   titles and icons for action links
 * @param string  $truename                 table name
 * @param boolean $db_is_information_schema is database information schema or not
 * @param string  $url_query                url query
 *
 * @return array ($browse_table, $search_table, $browse_table_label, $empty_table,
 *                $tracking_icon)
 */
function PMA_getHtmlForActionLinks($current_table, $table_is_view, $tbl_url_query,
    $titles, $truename, $db_is_information_schema, $url_query
) {
    $empty_table = '';

    if ($current_table['TABLE_ROWS'] > 0 || $table_is_view) {
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

    $browse_table_label = '<a href="sql.php?' . $tbl_url_query . '&amp;pos=0">'
        . $truename . '</a>';

    if (!$db_is_information_schema) {
        $empty_table = '<a class="truncate_table_anchor ajax"';
        $empty_table .= ' href="sql.php?' . $tbl_url_query
            . '&amp;sql_query=';
        $empty_table .= urlencode(
            'TRUNCATE ' . PMA_Util::backquote($current_table['TABLE_NAME'])
        );
        $empty_table .= '&amp;message_to_show='
            . urlencode(
                sprintf(
                    __('Table %s has been emptied'),
                    htmlspecialchars($current_table['TABLE_NAME'])
                )
            )
            . '">';
        if ($may_have_rows) {
            $empty_table .= $titles['Empty'];
        } else {
            $empty_table .= $titles['NoEmpty'];
        }
        $empty_table .= '</a>';
        // truncating views doesn't work
        if ($table_is_view) {
            $empty_table = '&nbsp;';
        }
    }

    $tracking_icon = '';
    if (PMA_Tracker::isActive()) {
        if (PMA_Tracker::isTracked($GLOBALS["db"], $truename)) {
            $tracking_icon = '<a href="tbl_tracking.php?' . $url_query
                . '&amp;table=' . $truename . '">'
                . PMA_Util::getImage(
                    'eye.png', __('Tracking is active.')
                )
                . '</a>';
        } elseif (PMA_Tracker::getVersion($GLOBALS["db"], $truename) > 0) {
            $tracking_icon = '<a href="tbl_tracking.php?' . $url_query
                . '&amp;table=' . $truename . '">'
                . PMA_Util::getImage(
                    'eye_grey.png', __('Tracking is not active.')
                )
                . '</a>';
        }
    }

    return array($browse_table,
        $search_table,
        $browse_table_label,
        $empty_table,
        $tracking_icon
    );
}

/**
 * Get table drop query and drop message
 *
 * @param boolean $table_is_view Is table view or not
 * @param string  $current_table current table
 *
 * @return array    ($drop_query, $drop_message)
 */
function PMA_getTableDropQueryAndMessage($table_is_view, $current_table)
{
    $drop_query = 'DROP '
        . (($table_is_view || $current_table['ENGINE'] == null) ? 'VIEW' : 'TABLE')
        . ' ' . PMA_Util::backquote(
            $current_table['TABLE_NAME']
        );
    $drop_message = sprintf(
        (($table_is_view || $current_table['ENGINE'] == null)
            ? __('View %s has been dropped')
            : __('Table %s has been dropped')),
        str_replace(
            ' ',
            '&nbsp;',
            htmlspecialchars($current_table['TABLE_NAME'])
        )
    );
    return array($drop_query, $drop_message);
}

/**
 * Get HTML body for table summery
 *
 * @param integer $num_tables               number of tables
 * @param boolean $server_slave_status      server slave state
 * @param boolean $db_is_information_schema whether database is information
 *                                          schema or not
 * @param integer $sum_entries              sum entries
 * @param string  $db_collation             collation of given db
 * @param boolean $is_show_stats            whether stats is show or not
 * @param double  $sum_size                 sum size
 * @param double  $overhead_size            overhead size
 * @param string  $create_time_all          create time
 * @param string  $update_time_all          update time
 * @param string  $check_time_all           check time
 * @param integer $sum_row_count_pre        sum row count pre
 *
 * @return string $html_output
 */
function PMA_getHtmlBodyForTableSummary($num_tables, $server_slave_status,
    $db_is_information_schema, $sum_entries, $db_collation, $is_show_stats,
    $sum_size, $overhead_size, $create_time_all, $update_time_all,
    $check_time_all, $sum_row_count_pre
) {
    if ($is_show_stats) {
        list($sum_formatted, $unit) = PMA_Util::formatByteDown(
            $sum_size, 3, 1
        );
        list($overhead_formatted, $overhead_unit)
            = PMA_Util::formatByteDown($overhead_size, 3, 1);
    }

    $html_output = '<tbody id="tbl_summary_row">'
        . '<tr><th></th>';
    $html_output .= '<th class="tbl_num nowrap">';
    $html_output .= sprintf(
        _ngettext('%s table', '%s tables', $num_tables),
        PMA_Util::formatNumber($num_tables, 0)
    );
    $html_output .= '</th>';

    if ($server_slave_status) {
        $html_output .= '<th>' . __('Replication') . '</th>' . "\n";
    }
    $html_output .= '<th colspan="'. ($db_is_information_schema ? 3 : 6) . '">'
        . __('Sum')
        . '</th>';
    $html_output .= '<th class="value tbl_rows">'
        . $sum_row_count_pre . PMA_Util::formatNumber($sum_entries, 0)
        . '</th>';

    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        $default_engine = PMA_DBI_fetch_value(
            'SHOW VARIABLES LIKE \'storage_engine\';',
            0,
            1
        );
        $html_output .=  '<th class="center">' . "\n"
            . '<dfn title="'
            . sprintf(
                __('%s is the default storage engine on this MySQL server.'),
                $default_engine
            )
            . '">' .$default_engine . '</dfn></th>' . "\n";
        // we got a case where $db_collation was empty
        $html_output .= '<th>' . "\n";

        if (! empty($db_collation)) {
            $html_output .= '<dfn title="'
                . PMA_getCollationDescr($db_collation)
                . ' (' . __('Default') . ')">'
                . $db_collation
                . '</dfn>';
        }
        $html_output .= '</th>';
    }
    if ($is_show_stats) {
        $html_output .= '<th class="value tbl_size">'
            . $sum_formatted . ' ' . $unit
            . '</th>';
        $html_output .= '<th class="value tbl_overhead">'
            . $overhead_formatted . ' ' . $overhead_unit
            . '</th>';
    }

    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        $html_output .= '<th class="value tbl_creation">' . "\n"
            . '        '
            . ($create_time_all
                ? PMA_Util::localisedDate(strtotime($create_time_all))
                : '-'
            )
            . '</th>';
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        $html_output .= '<th class="value tbl_last_update">' . "\n"
            . '        '
            . ($update_time_all
                ? PMA_Util::localisedDate(strtotime($update_time_all))
                : '-'
            )
            . '</th>';
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        $html_output .= '<th class="value tbl_last_check">' . "\n"
            . '        '
            . ($check_time_all
                ? PMA_Util::localisedDate(strtotime($check_time_all))
                : '-'
            )
            . '</th>';
    }
    $html_output .= '</tr>'
        . '</tbody>';

    return $html_output;
}

/**
 * Get HTML for "check all" check box with "with selected" dropdown
 *
 * @param string  $pmaThemeImage            pma theme image url
 * @param string  $text_dir                 url for text directory
 * @param string  $overhead_check           overhead check
 * @param boolean $db_is_information_schema whether database is information
 *                                          schema or not
 * @param string  $hidden_fields            hidden fields
 *
 * @return string $html_output
 */
function PMA_getHtmlForCheckAllTables($pmaThemeImage, $text_dir,
    $overhead_check, $db_is_information_schema, $hidden_fields
) {
    $html_output = '<div class="clearfloat">';
    $html_output .= '<img class="selectallarrow" '
        . 'src="' .$pmaThemeImage .'arrow_'.$text_dir.'.png' . '"'
        . 'width="38" height="22" alt="' . __('With selected:') . '" />';

    $html_output .= '<input type="checkbox" id="checkall" '
        . 'title="' . __('Check All') .'" />';
    $html_output .= '<label for="checkall">' .__('Check All') . '</label>';

    if ($overhead_check != '') {
        $html_output .= PMA_getHtmlForCheckTablesHavingOverheadlink(
            $overhead_check
        );
    }

    $html_output .= '<select name="submit_mult" class="autosubmit" '
        . 'style="margin: 0 3em 0 3em;">';

    $html_output .= '<option value="' . __('With selected:')
        . '" selected="selected">'
        . __('With selected:') . '</option>' . "\n";
    $html_output .= '<option value="export" >'
        . __('Export') . '</option>' . "\n";
    $html_output .= '<option value="print" >'
        . __('Print view') . '</option>' . "\n";

    if (!$db_is_information_schema
        && !$GLOBALS['cfg']['DisableMultiTableMaintenance']
    ) {
        $html_output .= '<option value="empty_tbl" >'
            . __('Empty') . '</option>' . "\n";
        $html_output .= '<option value="drop_tbl" >'
            . __('Drop') . '</option>' . "\n";
        $html_output .= '<option value="check_tbl" >'
            . __('Check table') . '</option>' . "\n";
        if (!PMA_DRIZZLE) {
            $html_output .= '<option value="optimize_tbl" >'
                . __('Optimize table') . '</option>' . "\n";
            $html_output .= '<option value="repair_tbl" >'
                . __('Repair table') . '</option>' . "\n";
        }
        $html_output .= '<option value="analyze_tbl" >'
            . __('Analyze table') . '</option>' . "\n";
        $html_output .= '<option value="add_prefix_tbl" >'
            . __('Add prefix to table') . '</option>' . "\n";
        $html_output .= '<option value="replace_prefix_tbl" >'
            . __('Replace table prefix') . '</option>' . "\n";
        $html_output .= '<option value="copy_tbl_change_prefix" >'
            . __('Copy table with prefix') . '</option>' . "\n";
    }
    $html_output .= '</select>'
        . implode("\n", $hidden_fields) . "\n";
    $html_output .= '</div>';

    return $html_output;
}

/**
 * Get HTML code for "Check tables having overhead" link
 *
 * @param string $overhead_check overhead check
 *
 * @return string $html_output
 */
function PMA_getHtmlForCheckTablesHavingOverheadlink($overhead_check)
{
    return ' / '
        . '<a href="#" onclick="unMarkAllRows(\'tablesForm\');'
        . $overhead_check . 'return false;">'
        . __('Check tables having overhead')
        . '</a>';
}


/**
 * Get HTML links for "Print view" options
 *
 * @param string $url_query url query
 *
 * @return string $html_output
 */
function PMA_getHtmlForTablePrintViewLink($url_query)
{
    return '<p>'
        . '<a href="db_printview.php?' . $url_query . '" target="print_view">'
        . PMA_Util::getIcon(
            'b_print.png',
            __('Print view'),
            true
        ) . '</a>';
}

/**
 * Get HTML links "Data Dictionary" options
 *
 * @param string $url_query url query
 *
 * @return string $html_output
 */
function PMA_getHtmlForDataDictionaryLink($url_query)
{
    return '<a href="db_datadict.php?' . $url_query . '" target="print_view">'
        . PMA_Util::getIcon(
            'b_tblanalyse.png',
            __('Data Dictionary'),
            true
        ) . '</a>'
        . '</p>';
}

/**
 * Get Time for Create time, update time and check time
 *
 * @param array   $current_table current table
 * @param string  $time_label    Create_time, Update_time, Check_time
 * @param integer $time_all      time
 *
 * @return array ($time, $time_all)
 */
function PMA_getTimeForCreateUpdateCheck($current_table, $time_label, $time_all)
{
    $showtable = PMA_Table::sGetStatusInfo(
        $GLOBALS['db'],
        $current_table['TABLE_NAME'],
        null,
        true
    );
    $time = isset($showtable[$time_label])
        ? $showtable[$time_label]
        : false;

    // show oldest creation date in summary row
    if ($time && (!$time_all || $time < $time_all)) {
        $time_all = $time;
    }
    return array($time, $time_all);
}

/**
 * Get HTML for each table row of the database structure table,
 * And this function returns $odd_row param also
 *
 * @param integer $curr                     current entry
 * @param boolean $odd_row                  whether row is odd or not
 * @param boolean $table_is_view            whether table is view or not
 * @param array   $current_table            current table
 * @param string  $browse_table_label       browse table label action link
 * @param string  $tracking_icon            tracking icon
 * @param boolean $server_slave_status      server slave state
 * @param string  $browse_table             browse table action link
 * @param string  $tbl_url_query            table url query
 * @param string  $search_table             search table action link
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param array   $titles                   titles array
 * @param string  $empty_table              empty table action link
 * @param string  $drop_query               table dropt query
 * @param string  $drop_message             table drop message
 * @param string  $collation                collation
 * @param string  $formatted_size           formatted size
 * @param string  $unit                     unit
 * @param string  $overhead                 overhead
 * @param string  $create_time              create time
 * @param string  $update_time              last update time
 * @param string  $check_time               last check time
 * @param boolean $is_show_stats            whether stats is show or not
 * @param boolean $ignored                  ignored
 * @param boolean $do                       do
 * @param intger  $colspan_for_structure    colspan for structure
 *
 * @return array $html_output, $odd_row
 */
function PMA_getHtmlForStructureTableRow(
    $curr, $odd_row, $table_is_view, $current_table,
    $browse_table_label, $tracking_icon,$server_slave_status,
    $browse_table, $tbl_url_query, $search_table,
    $db_is_information_schema,$titles, $empty_table, $drop_query, $drop_message,
    $collation, $formatted_size, $unit, $overhead, $create_time, $update_time,
    $check_time,$is_show_stats, $ignored, $do, $colspan_for_structure
) {
    $html_output = '<tr class="' . ($odd_row ? 'odd' : 'even');
    $odd_row = ! $odd_row;
    $html_output .= ($table_is_view ? ' is_view' : '')
    .'" id="row_tbl_' . $curr . '">';

    $html_output .= '<td class="center">'
        . '<input type="checkbox" name="selected_tbl[]" class="checkall" '
        . 'value="' . htmlspecialchars($current_table['TABLE_NAME']) . '" '
        . 'id="checkbox_tbl_' . $curr .'" /></td>';

    $html_output .= '<th>'
        . $browse_table_label
        . (! empty($tracking_icon) ? $tracking_icon : '')
        . '</th>';

    if ($server_slave_status) {
        $html_output .= '<td class="center">'
            . ($ignored
                ? PMA_Util::getImage('s_cancel.png', 'NOT REPLICATED')
                : '')
            . ($do
                ? PMA_Util::getImage('s_success.png', 'REPLICATED')
                : '')
            . '</td>';
    }

    $html_output .= '<td class="center">' . $browse_table . '</td>';
    $html_output .= '<td class="center">'
        . '<a href="tbl_structure.php?' . $tbl_url_query . '">'
        . $titles['Structure'] . '</a></td>';
    $html_output .= '<td class="center">' . $search_table . '</td>';

    if (! $db_is_information_schema) {
        $html_output .= PMA_getHtmlForInsertEmptyDropActionLinks(
            $tbl_url_query, $table_is_view,
            $titles, $empty_table, $current_table, $drop_query, $drop_message
        );
    } // end if (! $db_is_information_schema)

    // there is a null value in the ENGINE
    // - when the table needs to be repaired, or
    // - when it's a view
    //  so ensure that we'll display "in use" below for a table
    //  that needs to be repaired
    if (isset($current_table['TABLE_ROWS'])
        && ($current_table['ENGINE'] != null
        || $table_is_view)
    ) {
        $html_output .= PMA_getHtmlForNotNullEngineViewTable(
            $table_is_view, $current_table, $collation, $is_show_stats,
            $tbl_url_query, $formatted_size, $unit, $overhead, $create_time,
            $update_time, $check_time
        );
    } elseif ($table_is_view) {
        $html_output .= PMA_getHtmlForViewTable($is_show_stats);
    } else {
        $html_output .= PMA_getHtmlForRepairtable(
            $colspan_for_structure,
            $db_is_information_schema
        );
    } // end if (isset($current_table['TABLE_ROWS'])) else
    $html_output .= '</tr>';

    return array($html_output, $odd_row);
}

/**
 * Get HTML for Insert/Empty/Drop action links
 *
 * @param string  $tbl_url_query table url query
 * @param boolean $table_is_view whether table is view or not
 * @param array   $titles        titles array
 * @param string  $empty_table   HTML link for empty table
 * @param array   $current_table current table
 * @param string  $drop_query    query for drop table
 * @param string  $drop_message  table drop message
 *
 * @return string $html_output
 */
function PMA_getHtmlForInsertEmptyDropActionLinks($tbl_url_query, $table_is_view,
    $titles, $empty_table, $current_table, $drop_query, $drop_message
) {
    $html_output = '<td class="insert_table center">'
        . '<a href="tbl_change.php?' . $tbl_url_query . '">'
        . $titles['Insert']
        . '</a></td>';
    $html_output .= '<td class="center">' . $empty_table . '</td>';
    $html_output .= '<td class="center">';
    $html_output .= '<a ';
    $html_output .= 'class="ajax drop_table_anchor';
    if ($table_is_view || $current_table['ENGINE'] == null) {
        // this class is used in db_structure.js to display the
        // correct confirmation message
        $html_output .= ' view';
    }
    $html_output .= '"';
    $html_output .= 'href="sql.php?' . $tbl_url_query
        . '&amp;reload=1&amp;purge=1&amp;sql_query='
        . urlencode($drop_query) . '&amp;message_to_show='
        . urlencode($drop_message) . '" >'
        . $titles['Drop'] . '</a></td>';

    return $html_output;
}

/**
 * Get HTML for show stats
 *
 * @param string $tbl_url_query  tabel url query
 * @param string $formatted_size formatted size
 * @param string $unit           unit
 * @param string $overhead       overhead
 *
 * @return string $html_output
 */
function PMA_getHtmlForShowStats($tbl_url_query, $formatted_size,
    $unit, $overhead
) {
     $html_output = '<td class="value tbl_size"><a'
        . 'href="tbl_structure.php?' . $tbl_url_query . '#showusage" >'
        . '<span>' . $formatted_size . '</span> '
        . '<span class="unit">' . $unit . '</span>'
        . '</a></td>';
    $html_output .= '<td class="value tbl_overhead">' . $overhead . '</td>';

    return $html_output;
}

/**
 * Get HTML to show database structure creation, last update and last checkx time
 *
 * @param string $create_time create time
 * @param string $update_time last update time
 * @param string $check_time  last check time
 *
 * @return string $html_output
 */
function PMA_getHtmlForStructureTimes($create_time, $update_time, $check_time)
{
    $html_output = '';
    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        $html_output .= '<td class="value tbl_creation">'
            . ($create_time
                ? PMA_Util::localisedDate(strtotime($create_time))
                : '-' )
            . '</td>';
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        $html_output .= '<td class="value tbl_last_update">'
            . ($update_time
                ? PMA_Util::localisedDate(strtotime($update_time))
                : '-' )
            . '</td>';
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        $html_output .= '<td class="value tbl_last_check">'
            . ($check_time
                ? PMA_Util::localisedDate(strtotime($check_time))
                : '-' )
            . '</td>';
    }
    return $html_output;
}

/**
 * Get HTML for ENGINE value not null or view tables that are not empty tables
 *
 * @param boolean $table_is_view  whether table is view
 * @param array   $current_table  current table
 * @param string  $collation      collation
 * @param boolean $is_show_stats  whether atats show or not
 * @param string  $tbl_url_query  table url query
 * @param string  $formatted_size formatted size
 * @param string  $unit           unit
 * @param string  $overhead       overhead
 * @param string  $create_time    create time
 * @param string  $update_time    update time
 * @param string  $check_time     check time
 *
 * @return string $html_output
 */
function PMA_getHtmlForNotNullEngineViewTable($table_is_view, $current_table,
    $collation, $is_show_stats, $tbl_url_query, $formatted_size, $unit,
    $overhead, $create_time, $update_time, $check_time
) {
    $html_output = '';
    $row_count_pre = '';
    $show_superscript = '';
    if ($table_is_view) {
        // Drizzle views use FunctionEngine, and the only place where they are
        // available are I_S and D_D schemas, where we do exact counting
        if ($current_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']
            && $current_table['ENGINE'] != 'FunctionEngine'
        ) {
            $row_count_pre = '~';
            $sum_row_count_pre = '~';
            $show_superscript = PMA_Util::showHint(
                PMA_sanitize(
                    sprintf(
                        __('This view has at least this number of rows. Please refer to %sdocumentation%s.'),
                        '[doc@cfg_MaxExactCountViews]',
                        '[/doc]'
                    )
                )
            );
        }
    } elseif ($current_table['ENGINE'] == 'InnoDB'
        && (! $current_table['COUNTED'])
    ) {
        // InnoDB table: we did not get an accurate row count
        $row_count_pre = '~';
        $sum_row_count_pre = '~';
        $show_superscript = '';
    }

    $html_output .= '<td class="value tbl_rows">'
        . $row_count_pre . PMA_Util::formatNumber(
            $current_table['TABLE_ROWS'], 0
        )
        . $show_superscript . '</td>';

    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        $html_output .= '<td class="nowrap">'
            . ($table_is_view ? __('View') : $current_table['ENGINE'])
            . '</td>';
        if (strlen($collation)) {
            $html_output .= '<td class="nowrap">' . $collation . '</td>';
        }
    }

    if ($is_show_stats) {
        $html_output .= PMA_getHtmlForShowStats(
            $tbl_url_query, $formatted_size, $unit, $overhead
        );
    }

    $html_output .= PMA_getHtmlForStructureTimes(
        $create_time, $update_time, $check_time
    );

    return $html_output;
}

/**
 * Get HTML snippet view table
 *
 * @param type $is_show_stats whether stats show or not
 *
 * @return string $html_output
 */
function PMA_getHtmlForViewTable($is_show_stats)
{
    $html_output = '<td class="value">-</td>'
        . '<td>' . __('View') . '</td>'
        . '<td>---</td>';
    if ($is_show_stats) {
        $html_output .= '<td class="value">-</td>'
            . '<td class="value">-</td>';
    }
    return $html_output;
}

/**
 * display "in use" below for a table that needs to be repaired
 *
 * @param integer $colspan_for_structure    colspan for structure
 * @param boolean $db_is_information_schema whether db is information schema or not
 *
 * @return string HTML snippet
 */
function PMA_getHtmlForRepairtable(
    $colspan_for_structure,
    $db_is_information_schema
) {
    return '<td colspan="'
        . ($colspan_for_structure - ($db_is_information_schema ? 5 : 8)) . '"'
        . 'class="center">'
        . __('in use')
        . '</td>';
}

/**
 * display table header (<table><thead>...</thead><tbody>)
 *
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param boolean $replication              whether to sho replication status
 *
 * @return html data
 */
function PMA_TableHeader($db_is_information_schema = false, $replication = false)
{
    $cnt = 0; // Let's count the columns...

    if ($db_is_information_schema) {
        $action_colspan = 3;
    } else {
        $action_colspan = 6;
    }

    $html_output = '<table class="data">' . "\n"
        .'<thead>' . "\n"
        .'<tr><th></th>' . "\n"
        .'<th>'
        . PMA_sortableTableHeader(__('Table'), 'table')
        . '</th>' . "\n";
    if ($replication) {
        $html_output .= '<th>' . "\n"
            .'        ' . __('Replication') . "\n"
            .'</th>';
    }
    $html_output .= '<th colspan="' . $action_colspan . '">' . "\n"
        .'        ' . __('Action') . "\n"
        .'</th>'
        // larger values are more interesting so default sort order is DESC
        .'<th>' . PMA_sortableTableHeader(__('Rows'), 'records', 'DESC')
        . PMA_Util::showHint(
            PMA_sanitize(
                __('May be approximate. See [doc@faq3-11]FAQ 3.11[/doc]')
            )
        ) . "\n"
        .'</th>' . "\n";
    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        $html_output .= '<th>' . PMA_sortableTableHeader(__('Type'), 'type')
            . '</th>' . "\n";
        $cnt++;
        $html_output .= '<th>'
            . PMA_sortableTableHeader(__('Collation'), 'collation')
            . '</th>' . "\n";
        $cnt++;
    }
    if ($GLOBALS['is_show_stats']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>'
            . PMA_sortableTableHeader(__('Size'), 'size', 'DESC')
            . '</th>' . "\n"
        // larger values are more interesting so default sort order is DESC
            . '<th>'
            . PMA_sortableTableHeader(__('Overhead'), 'overhead', 'DESC')
            . '</th>' . "\n";
        $cnt += 2;
    }
    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>'
            . PMA_sortableTableHeader(__('Creation'), 'creation', 'DESC')
            . '</th>' . "\n";
        $cnt += 2;
    }
    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>'
            . PMA_sortableTableHeader(__('Last update'), 'last_update', 'DESC')
            . '</th>' . "\n";
        $cnt += 2;
    }
    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>'
            . PMA_sortableTableHeader(__('Last check'), 'last_check', 'DESC')
            . '</th>' . "\n";
        $cnt += 2;
    }
    $html_output .= '</tr>' . "\n";
    $html_output .= '</thead>' . "\n";
    $html_output .= '<tbody>' . "\n";
    $GLOBALS['colspan_for_structure'] = $cnt + $action_colspan + 3;

    return $html_output;
}

/**
 * Creates a clickable column header for table information
 *
 * @param string $title              title to use for the link
 * @param string $sort               corresponds to sortable data name mapped in
 *                                   libraries/db_info.inc.php
 * @param string $initial_sort_order initial sort order
 *
 * @return string link to be displayed in the table header
 */
function PMA_sortableTableHeader($title, $sort, $initial_sort_order = 'ASC')
{
    // Set some defaults
    $requested_sort = 'table';
    $requested_sort_order = $future_sort_order = $initial_sort_order;

    // If the user requested a sort
    if (isset($_REQUEST['sort'])) {
        $requested_sort = $_REQUEST['sort'];

        if (isset($_REQUEST['sort_order'])) {
            $requested_sort_order = $_REQUEST['sort_order'];
        }
    }

    $order_img = '';
    $order_link_params = array();
    $order_link_params['title'] = __('Sort');

    // If this column was requested to be sorted.
    if ($requested_sort == $sort) {
        if ($requested_sort_order == 'ASC') {
            $future_sort_order = 'DESC';
            // current sort order is ASC
            $order_img  = ' ' . PMA_Util::getImage(
                's_asc.png',
                __('Ascending'),
                array('class' => 'sort_arrow', 'title' => '')
            );
            $order_img .= ' ' . PMA_Util::getImage(
                's_desc.png',
                __('Descending'),
                array('class' => 'sort_arrow hide', 'title' => '')
            );
            // but on mouse over, show the reverse order (DESC)
            $order_link_params['onmouseover'] = "$('.sort_arrow').toggle();";
            // on mouse out, show current sort order (ASC)
            $order_link_params['onmouseout'] = "$('.sort_arrow').toggle();";
        } else {
            $future_sort_order = 'ASC';
            // current sort order is DESC
            $order_img  = ' ' . PMA_Util::getImage(
                's_asc.png',
                __('Ascending'),
                array('class' => 'sort_arrow hide', 'title' => '')
            );
            $order_img .= ' ' . PMA_Util::getImage(
                's_desc.png',
                __('Descending'),
                array('class' => 'sort_arrow', 'title' => '')
            );
            // but on mouse over, show the reverse order (ASC)
            $order_link_params['onmouseover'] = "$('.sort_arrow').toggle();";
            // on mouse out, show current sort order (DESC)
            $order_link_params['onmouseout'] = "$('.sort_arrow').toggle();";
        }
    }

    $_url_params = array(
        'db' => $_REQUEST['db'],
    );

    $url = 'db_structure.php'.PMA_generate_common_url($_url_params);
    // We set the position back to 0 every time they sort.
    $url .= "&amp;pos=0&amp;sort=$sort&amp;sort_order=$future_sort_order";

    return PMA_Util::linkOrButton(
        $url, $title . $order_img, $order_link_params
    );
}

/**
 * Get the alias ant truname
 *
 * @param string $tooltip_aliasname tooltip alias name
 * @param array  $current_table     current table
 * @param string $tooltip_truename  tooltip true name
 *
 * @return array ($alias, $truename)
 */
function PMA_getAliasAndTrueName($tooltip_aliasname, $current_table,
    $tooltip_truename
) {
    $alias = (! empty($tooltip_aliasname)
            && isset($tooltip_aliasname[$current_table['TABLE_NAME']])
        )
        ? str_replace(
            ' ', '&nbsp;',
            htmlspecialchars($tooltip_truename[$current_table['TABLE_NAME']])
        )
        : str_replace(
            ' ', '&nbsp;',
            htmlspecialchars($current_table['TABLE_NAME'])
        );
    $truename = (! empty($tooltip_truename)
            && isset($tooltip_truename[$current_table['TABLE_NAME']])
        )
        ? str_replace(
            ' ', '&nbsp;',
            htmlspecialchars($tooltip_truename[$current_table['TABLE_NAME']])
        )
        : str_replace(
            ' ', '&nbsp;',
            htmlspecialchars($current_table['TABLE_NAME'])
        );

    return array($alias, $truename);
}

/**
 * Get the server slave state
 *
 * @param boolean $server_slave_status server slave state
 * @param string  $truename            true name
 *
 * @return array ($do, $ignored)
 */
function PMA_getServerSlaveStatus($server_slave_status, $truename)
{
    $ignored = false;
    $do = false;
    include_once 'libraries/replication.inc.php';
    if ($server_slave_status) {
        if ((strlen(array_search($truename, $server_slave_Do_Table)) > 0)
            || (strlen(array_search($GLOBALS['db'], $server_slave_Do_DB)) > 0)
            || (count($server_slave_Do_DB) == 1 && count($server_slave_Ignore_DB) == 1)
        ) {
            $do = true;
        }
        foreach ($server_slave_Wild_Do_Table as $db_table) {
            $table_part = PMA_extract_db_or_table($db_table, 'table');
            if (($GLOBALS['db'] == PMA_extract_db_or_table($db_table, 'db'))
                && (preg_match("@^" . substr($table_part, 0, strlen($table_part) - 1) . "@", $truename))
            ) {
                $do = true;
            }
        }

        if ((strlen(array_search($truename, $server_slave_Ignore_Table)) > 0)
            || (strlen(array_search($GLOBALS['db'], $server_slave_Ignore_DB)) > 0)
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
    }
    return array($do, $ignored);
}

/**
 * Get the value set for ENGINE table,
 * $current_table, $formatted_size, $unit, $formatted_overhead,
 * $overhead_unit, $overhead_size, $table_is_view
 *
 * @param array   $current_table            current table
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param boolean $is_show_stats            whether stats show or not
 * @param boolean $table_is_view            whether table is view or not
 * @param double  $sum_size                 totle table size
 * @param double  $overhead_size            overhead size
 *
 * @return array
 */
function PMA_getStuffForEngineTypeTable($current_table, $db_is_information_schema,
    $is_show_stats, $table_is_view, $sum_size, $overhead_size
) {
    $formatted_size = '-';
    $unit = '';
    $formatted_overhead = '';
    $overhead_unit = '';

    switch ( $current_table['ENGINE']) {
        // MyISAM, ISAM or Heap table: Row count, data size and index size
        // are accurate; data size is accurate for ARCHIVE
    case 'MyISAM' :
    case 'ISAM' :
    case 'HEAP' :
    case 'MEMORY' :
    case 'ARCHIVE' :
    case 'Aria' :
    case 'Maria' :
        list($current_table, $formatted_size, $unit, $formatted_overhead,
        $overhead_unit, $overhead_size, $sum_size) = PMA_getValuesForAriaTable(
            $db_is_information_schema, $current_table, $is_show_stats,
            $sum_size, $overhead_size, $formatted_size, $unit,
            $formatted_overhead, $overhead_unit
        );
        break;
    case 'InnoDB' :
    case 'PBMS' :
        // InnoDB table: Row count is not accurate but data and index sizes are.
        // PBMS table in Drizzle: TABLE_ROWS is taken from table cache,
        // so it may be unavailable
        list($current_table, $formatted_size, $unit, $sum_size)
            = PMA_getValuesForPbmsTable($current_table, $is_show_stats, $sum_size);
        //$display_rows                   =  ' - ';
        break;
    // Mysql 5.0.x (and lower) uses MRG_MyISAM
    // and MySQL 5.1.x (and higher) uses MRG_MYISAM
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
        if ($current_table['TABLE_TYPE'] == 'VIEW') {
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $current_table['TABLE_ROWS'] = PMA_Table::countRecords(
                $GLOBALS['db'], $current_table['TABLE_NAME'],
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

    return array($current_table, $formatted_size, $unit, $formatted_overhead,
        $overhead_unit, $overhead_size, $table_is_view, $sum_size
    );
}

/**
 * Get values for ARIA/MARIA tables
 * $current_table, $formatted_size, $unit, $formatted_overhead,
 * $overhead_unit, $overhead_size
 *
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param array   $current_table            current table
 * @param boolean $is_show_stats            whether stats show or not
 * @param double  $sum_size                 sum size
 * @param double  $overhead_size            overhead size
 *
 * @return array
 */
function PMA_getValuesForAriaTable($db_is_information_schema, $current_table,
    $is_show_stats, $sum_size, $overhead_size, $formatted_size, $unit,
    $formatted_overhead, $overhead_unit
) {
    if ($db_is_information_schema) {
        $current_table['Rows'] = PMA_Table::countRecords(
            $GLOBALS['db'], $current_table['Name']
        );
    }

    if ($is_show_stats) {
        $tblsize = doubleval($current_table['Data_length'])
            + doubleval($current_table['Index_length']);
        $sum_size += $tblsize;
        list($formatted_size, $unit) = PMA_Util::formatByteDown(
            $tblsize, 3, ($tblsize > 0) ? 1 : 0
        );
        if (isset($current_table['Data_free']) && $current_table['Data_free'] > 0) {
            list($formatted_overhead, $overhead_unit)
                = PMA_Util::formatByteDown(
                    $current_table['Data_free'], 3,
                    (($current_table['Data_free'] > 0) ? 1 : 0)
                );
            $overhead_size += $current_table['Data_free'];
        }
    }
    return array($current_table, $formatted_size, $unit, $formatted_overhead,
        $overhead_unit, $overhead_size, $sum_size
    );
}

/**
 * Get valuse for PBMS table
 * $current_table, $formatted_size, $unit, $sum_size
 *
 * @param array   $current_table current table
 * @param boolean $is_show_stats whether stats show or not
 * @param double  $sum_size      sum size
 *
 * @return array
 */
function PMA_getValuesForPbmsTable($current_table, $is_show_stats, $sum_size)
{
    $formatted_size = $unit = '';

    if (($current_table['ENGINE'] == 'InnoDB'
        && $current_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
        || !isset($current_table['TABLE_ROWS'])
    ) {
        $current_table['COUNTED'] = true;
        $current_table['TABLE_ROWS'] = PMA_Table::countRecords(
            $GLOBALS['db'], $current_table['TABLE_NAME'],
            $force_exact = true, $is_view = false
        );
    } else {
        $current_table['COUNTED'] = false;
    }

    // Drizzle doesn't provide data and index length, check for null
    if ($is_show_stats && $current_table['Data_length'] !== null) {
        $tblsize =  $current_table['Data_length'] + $current_table['Index_length'];
        $sum_size += $tblsize;
        list($formatted_size, $unit) = PMA_Util::formatByteDown(
            $tblsize, 3, (($tblsize > 0) ? 1 : 0)
        );
    }

    return array($current_table, $formatted_size, $unit, $sum_size);
}

/**
 * table structure
 */

/**
 * Get the HTML snippet for structure table table header
 *
 * @param type $db_is_information_schema whether db is information schema or not
 * @param type $tbl_is_view              whether table is view or nt
 *
 * @return string $html_output
 */
function PMA_getHtmlForTableStructureHeader(
    $db_is_information_schema,
    $tbl_is_view
) {
    $html_output = '<thead>';
    $html_output .= '<tr>';
    $html_output .= '<th></th>'
        . '<th>#</th>'
        . '<th>' . __('Name') . '</th>'
        . '<th>' . __('Type'). '</th>'
        . '<th>' . __('Collation') . '</th>'
        . '<th>' . __('Attributes') . '</th>'
        . '<th>' . __('Null') . '</th>'
        . '<th>' . __('Default') . '</th>'
        . '<th>' . __('Extra') . '</th>';

    if ($db_is_information_schema || $tbl_is_view) {
        $html_output .= '<th>' . __('View') . '</th>';
    } else { /* see tbl_structure.js, function moreOptsMenuResize() */
        $colspan = 9;
        if (PMA_DRIZZLE) {
            $colspan -= 2;
        }
        if (in_array(
            $GLOBALS['cfg']['ActionLinksMode'],
            array('icons', 'both')
            )
        ) {
            $colspan--;
        }
        $html_output .= '<th colspan="' . $colspan . '" '
            . 'class="action">' . __('Action') . '</th>';
    }
    $html_output .= '</tr>'
        . '</thead>';

    return $html_output;
}

/**
 * Get HTML for structure table's rows and return $odd_row parameter also
 * For "Action" Column, this function contains only HTML code for "Change"
 * and "Drop"
 *
 * @param array   $row                      current row
 * @param string  $rownum                   row number
 * @param string  $displayed_field_name     displayed field name
 * @param string  $type_nowrap              type nowrap
 * @param array   $extracted_columnspec     associative array containing type,
 *                                          spec_in_brackets and possibly
 *                                          enum_set_values (another array)
 * @param string  $type_mime                mime type
 * @param string  $field_charset            field charset
 * @param string  $attribute                attribute (BINARY, UNSIGNED,
 *                                          UNSIGNED ZEROFILL,
 *                                          on update CURRENT_TIMESTAMP)
 * @param boolean $tbl_is_view              whether tables is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string  $url_query                url query
 * @param string  $field_encoded            field encoded
 * @param array   $titles                   tittles array
 * @param string  $table                    table
 *
 * @return array ($html_output, $odd_row)
 */
function PMA_getHtmlTableStructureRow($row, $rownum,
    $displayed_field_name, $type_nowrap, $extracted_columnspec, $type_mime,
    $field_charset, $attribute, $tbl_is_view, $db_is_information_schema,
    $url_query, $field_encoded, $titles, $table
) {
    $html_output = '<td class="center">'
        . '<input type="checkbox" class="checkall" name="selected_fld[]" '
        . 'value="' . htmlspecialchars($row['Field']) . '" '
        . 'id="checkbox_row_' . $rownum . '"/>'
        . '</td>';

    $html_output .= '<td class="right">'
        . $rownum
        . '</td>';

    $html_output .= '<th class="nowrap">'
        . '<label for="checkbox_row_' . $rownum . '">'
        . $displayed_field_name . '</label>'
        . '</th>';

    $html_output .= '<td' . $type_nowrap . '>'
        .'<bdo dir="ltr" lang="en">'
        . $extracted_columnspec['displayed_type'] . $type_mime
        . '</bdo></td>';

    $html_output .= '<td>' .
        (empty($field_charset)
            ? ''
            : '<dfn title="' . PMA_getCollationDescr($field_charset) . '">'
                . $field_charset . '</dfn>'
        )
        . '</td>';

    $html_output .= '<td class="column_attribute nowrap">'
        . $attribute . '</td>';
    $html_output .= '<td>'
        . (($row['Null'] == 'YES') ? __('Yes') : __('No')) . '  </td>';

    $html_output .= '<td class="nowrap">';
    if (isset($row['Default'])) {
        if ($extracted_columnspec['type'] == 'bit') {
            // here, $row['Default'] contains something like b'010'
            $html_output .= PMA_Util::convertBitDefaultValue($row['Default']);
        } else {
            $html_output .= $row['Default'];
        }
    } else {
        $html_output .= '<i>' . _pgettext('None for default', 'None') . '</i>';
    }
    $html_output .= '</td>';

    $html_output .= '<td class="nowrap">' . strtoupper($row['Extra']) . '</td>';

    $html_output .= PMA_getHtmlForDropColumn(
        $tbl_is_view, $db_is_information_schema,
        $url_query, $field_encoded,
        $titles, $table, $row
    );

    return $html_output;
}

/**
 * Get HTML code for "Drop" Action link
 *
 * @param boolean $tbl_is_view              whether tables is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string  $url_query                url query
 * @param string  $field_encoded            field encoded
 * @param array   $titles                   tittles array
 * @param string  $table                    table
 * @param array   $row                      current row
 *
 * @return string $html_output
 */
function PMA_getHtmlForDropColumn($tbl_is_view, $db_is_information_schema,
    $url_query, $field_encoded, $titles, $table, $row
) {
    $html_output = '';

    if (! $tbl_is_view && ! $db_is_information_schema) {
        $html_output .= '<td class="edit center">'
            . '<a class="change_column_anchor ajax"'
            . ' href="tbl_structure.php?' 
            . $url_query . '&amp;field=' . $field_encoded 
            . '&amp;change_column=1">'
            . $titles['Change'] . '</a>' . '</td>';
        $html_output .= '<td class="drop center">'
            . '<a class="drop_column_anchor ajax"'
            . ' href="sql.php?' . $url_query . '&amp;sql_query='
            . urlencode(
                'ALTER TABLE ' . PMA_Util::backquote($table)
                . ' DROP ' . PMA_Util::backquote($row['Field']) . ';'
            )
            . '&amp;dropped_column=' . urlencode($row['Field'])
            . '&amp;message_to_show=' . urlencode(
                sprintf(
                    __('Column %s has been dropped'),
                    htmlspecialchars($row['Field'])
                )
            ) . '" >'
            . $titles['Drop'] . '</a>'
            . '</td>';
    }

    return $html_output;
}

/**
 * Get HTML for "check all" check box with "with selected" actions in table
 * structure
 *
 * @param string  $pmaThemeImage            pma theme image url
 * @param string  $text_dir                 test directory
 * @param boolean $tbl_is_view              whether table is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string  $tbl_storage_engine       table storage engine
 *
 * @return string $html_output
 */
function PMA_getHtmlForCheckAllTableColumn($pmaThemeImage, $text_dir,
    $tbl_is_view, $db_is_information_schema, $tbl_storage_engine
) {
    $html_output = '<img class="selectallarrow" '
        . 'src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png' . '"'
        . 'width="38" height="22" alt="' . __('With selected:') . '" />';

    $html_output .= '<input type="checkbox" id="checkall" '
        . 'title="' . __('Check All') . '" />'
        . '<label for="checkall">' . __('Check All') . '</label>';

    $html_output .= '<i style="margin-left: 2em">'
        . __('With selected:') . '</i>';

    $html_output .= PMA_Util::getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_browse',
        __('Browse'), 'b_browse.png', 'browse'
    );

    if (! $tbl_is_view && ! $db_is_information_schema) {
        $html_output .= PMA_Util::getButtonOrImage(
            'submit_mult', 'mult_submit change_columns_anchor ajax',
            'submit_mult_change', __('Change'), 'b_edit.png', 'change'
        );
        $html_output .= PMA_Util::getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_drop',
            __('Drop'), 'b_drop.png', 'drop'
        );
        if ('ARCHIVE' != $tbl_storage_engine) {
            $html_output .= PMA_Util::getButtonOrImage(
                'submit_mult', 'mult_submit', 'submit_mult_primary',
                __('Primary'), 'b_primary.png', 'primary'
            );
            $html_output .= PMA_Util::getButtonOrImage(
                'submit_mult', 'mult_submit', 'submit_mult_unique',
                __('Unique'), 'b_unique.png', 'unique'
            );
            $html_output .= PMA_Util::getButtonOrImage(
                'submit_mult', 'mult_submit', 'submit_mult_index',
                __('Index'), 'b_index.png', 'index'
            );
        }

        if (! empty($tbl_storage_engine) && $tbl_storage_engine == 'MYISAM') {
            $html_output .= PMA_Util::getButtonOrImage(
                'submit_mult', 'mult_submit', 'submit_mult_spatial',
                __('Spatial'), 'b_spatial.png', 'spatial'
            );
        }
        if (! empty($tbl_storage_engine)
            && ($tbl_storage_engine == 'MYISAM'
            || $tbl_storage_engine == 'ARIA'
            || $tbl_storage_engine == 'MARIA')
        ) {
            $html_output .= PMA_Util::getButtonOrImage(
                'submit_mult', 'mult_submit', 'submit_mult_fulltext',
                __('Fulltext'), 'b_ftext.png', 'ftext'
            );
        }
    }
    return $html_output;
}

/**
 * Get HTML for move columns dialog
 *
 * @return string $html_output
 */
function PMA_getHtmlDivForMoveColumnsDialog()
{
    $html_output = '<div id="move_columns_dialog" '
        . 'title="' . __('Move columns') . '" style="display: none">';

    $html_output .= '<p>'
        . __('Move the columns by dragging them up and down.') . '</p>';

    $html_output .= '<form action="tbl_structure.php">'
        . '<div>'
        . PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table'])
        . '<ul></ul>'
        . '</div>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML for edit views'
 *
 * @param string $url_params URL parameters
 *
 * @return string $html_output
 */
function PMA_getHtmlForEditView($url_params)
{
    $create_view = PMA_DBI_get_definition(
        $GLOBALS['db'], 'VIEW', $GLOBALS['table']
    );
    $create_view = preg_replace('@^CREATE@', 'ALTER', $create_view);
    $html_output = PMA_Util::linkOrButton(
        'tbl_sql.php' . PMA_generate_common_url(
            $url_params +
            array(
                'sql_query' => $create_view,
                'show_query' => '1',
            )
        ),
        PMA_Util::getIcon('b_edit.png', __('Edit view'), true)
    );
    return $html_output;
}

/**
 * Get HTML links for 'Print view', 'Relation view', 'Propose table structure',
 * 'Track table' and 'Move columns'
 *
 * @param string  $url_query                url query
 * @param boolean $tbl_is_view              whether table is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string  $tbl_storage_engine       table storage engine
 * @param array   $cfgRelation              current relation parameters
 *
 * @return string $html_output
 */
function PMA_getHtmlForOptionalActionLinks($url_query, $tbl_is_view,
    $db_is_information_schema, $tbl_storage_engine, $cfgRelation
) {
    $html_output = '<a href="tbl_printview.php?' . $url_query . '" target="print_view">'
        . PMA_Util::getIcon('b_print.png', __('Print view'), true)
        . '</a>';

    if (! $tbl_is_view && ! $db_is_information_schema) {
        // if internal relations are available, or foreign keys are supported
        // ($tbl_storage_engine comes from libraries/tbl_info.inc.php

        if ($cfgRelation['relwork']
            || PMA_Util::isForeignKeySupported($tbl_storage_engine)
        ) {
            $html_output .= '<a href="tbl_relation.php?' . $url_query . '">'
                . PMA_Util::getIcon(
                    'b_relations.png', __('Relation view'), true
                )
                . '</a>';
        }
        if (!PMA_DRIZZLE) {
            $html_output .= '<a href="sql.php?' . $url_query
                . '&amp;session_max_rows=all&amp;sql_query=' . urlencode(
                    'SELECT * FROM ' . PMA_Util::backquote($GLOBALS['table'])
                    . ' PROCEDURE ANALYSE()'
                ) . '">'
                . PMA_Util::getIcon(
                    'b_tblanalyse.png',
                    __('Propose table structure'),
                    true
                )
                . '</a>';
            $html_output .= PMA_Util::showMySQLDocu(
                'Extending_MySQL', 'procedure_analyse'
            ) . "\n";
        }
        if (PMA_Tracker::isActive()) {
            $html_output .= '<a href="tbl_tracking.php?' . $url_query . '">'
                . PMA_Util::getIcon('eye.png', __('Track table'), true)
                . '</a>';
        }
        $html_output .= '<a href="#" id="move_columns_anchor">'
            . PMA_Util::getIcon('b_move.png', __('Move columns'), true)
            . '</a>';
    }

    return $html_output;
}

/**
 * Get HTML snippet for "Add column" feature in structure table
 *
 * @param array $columns_list column list array
 *
 * @return string $html_output
 */
function PMA_getHtmlForAddColumn($columns_list)
{
    $html_output = '<form method="post" action="tbl_addfield.php" '
        . 'id="addColumns" name="addColumns" '
        . 'onsubmit="return checkFormElementInRange('
            . 'this, \'num_fields\', \'' . str_replace(
                '\'',
                '\\\'',
                __('You have to add at least one column.')
            ) . '\', 1)'
        . '">';

    $html_output .= PMA_generate_common_hidden_inputs(
        $GLOBALS['db'],
        $GLOBALS['table']
    );
    if (in_array(
        $GLOBALS['cfg']['ActionLinksMode'],
        array('icons', 'both')
        )
    ) {
        $html_output .=PMA_Util::getImage(
            'b_insrow.png',
            __('Add column')
        );
    }
    $num_fields = '<input type="text" name="num_fields" size="2" '
        . 'maxlength="2" value="1" onfocus="this.select()" />';
    $html_output .= sprintf(__('Add %s column(s)'), $num_fields);

    // I tried displaying the drop-down inside the label but with Firefox
    // the drop-down was blinking
    $column_selector = '<select name="after_field" '
        . 'onclick="this.form.field_where[2].checked=true" '
        . 'onchange="this.form.field_where[2].checked=true">';

    foreach ($columns_list as $one_column_name) {
        $column_selector .= '<option '
            . 'value="' . htmlspecialchars($one_column_name) . '">'
            . htmlspecialchars($one_column_name)
            . '</option>';
    }
    $column_selector .= '</select>';

    $choices = array(
        'last'  => __('At End of Table'),
        'first' => __('At Beginning of Table'),
        'after' => sprintf(__('After %s'), '')
    );
    $html_output .= PMA_Util::getRadioFields(
        'field_where', $choices, 'last', false
    );
    $html_output .= $column_selector;
    $html_output .= '<input type="submit" value="' . __('Go') . '" />'
        . '</form>';

    return $html_output;
}

/**
 * Get HTML snippet for table rows in the Information ->Space usage table
 *
 * @param boolean $odd_row whether current row is odd or even
 * @param string  $name    type of usage
 * @param string  $value   value of usage
 * @param string  $unit    unit
 *
 * @return string $html_output
 */
function PMA_getHtmlForSpaceUsageTableRow($odd_row, $name, $value, $unit)
{
    $html_output = '<tr class="' . (($odd_row = !$odd_row) ? 'odd' : 'even') . '">';
    $html_output .= '<th class="name">' . $name . '</th>';
    $html_output .= '<td class="value">' . $value . '</td>';
    $html_output .= '<td class="unit">' . $unit . '</td>';
    $html_output .= '</tr>';

    return $html_output;
}

/**
 * Get HTML for Optimize link if overhead in Information fieldset
 *
 * @param type $url_query URL query
 *
 * @return string $html_output
 */
function PMA_getHtmlForOptimizeLink($url_query)
{
    $html_output = '<tr class="tblFooters">';
    $html_output .= '<td colspan="3" class="center">';
    $html_output .= '<a href="sql.php?' . $url_query
        . '&pos=0&amp;sql_query=' . urlencode(
            'OPTIMIZE TABLE ' . PMA_Util::backquote($GLOBALS['table'])
        )
        . '">'
        . PMA_Util::getIcon('b_tbloptimize.png', __('Optimize table'))
        . '</a>';
    $html_output .= '</td>';
    $html_output .= '</tr>';

    return $html_output;
}

/**
 * Get HTML for 'Row statistics' table row
 *
 * @param type $odd_row whether current row is odd or even
 * @param type $name    statement name
 * @param type $value   value
 *
 * @return string $html_output
 */
function PMA_getHtmlForRowStatsTableRow($odd_row, $name, $value)
{
    $html_output = '<tr class="' . (($odd_row = !$odd_row) ? 'odd' : 'even') . '">';
    $html_output .= '<th class="name">' . $name . '</th>';
    $html_output .= '<td class="value">' . $value . '</td>';
    $html_output .= '</tr>';

    return $html_output;
}

/**
 * Get HTML snippet for display Row statistics table
 *
 * @param array   $showtable     show table array
 * @param string  $tbl_collation table collation
 * @param boolean $is_innodb     whether table is innob or not
 * @param boolean $mergetable    Checks if current table is a merge table
 * @param integer $avg_size      average size
 * @param string  $avg_unit      average unit
 *
 * @return string $html_output
 */
function getHtmlForRowStatsTable($showtable, $tbl_collation,
    $is_innodb, $mergetable, $avg_size, $avg_unit
) {
    $odd_row = false;
    $html_output = '<table id="tablerowstats" class="data">';
    $html_output .= '<caption class="tblHeaders">'
        . __('Row statistics') . '</caption>';
    $html_output .= '<tbody>';

    if (isset($showtable['Row_format'])) {
        if ($showtable['Row_format'] == 'Fixed') {
            $value = __('static');
        } elseif ($showtable['Row_format'] == 'Dynamic') {
            $value = __('dynamic');
        } else {
            $value = $showtable['Row_format'];
        }
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row, __('Format'), $value
        );
        $odd_row = !$odd_row;
    }
    if (! empty($showtable['Create_options'])) {
        if ($showtable['Create_options'] == 'partitioned') {
            $value = __('partitioned');
        } else {
            $value = $showtable['Create_options'];
        }
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row, __('Options'), $value
        );
        $odd_row = !$odd_row;
    }
    if (!empty($tbl_collation)) {
        $value = '<dfn title="' . PMA_getCollationDescr($tbl_collation) . '">'
            . $tbl_collation . '</dfn>';
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row, __('Collation'), $value
        );
        $odd_row = !$odd_row;
    }
    if (!$is_innodb && isset($showtable['Rows'])) {
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Rows'),
            PMA_Util::formatNumber($showtable['Rows'], 0)
        );
        $odd_row = !$odd_row;
    }
    if (!$is_innodb
        && isset($showtable['Avg_row_length'])
        && $showtable['Avg_row_length'] > 0
    ) {
        list($avg_row_length_value, $avg_row_length_unit) 
            = PMA_Util::formatByteDown(
                $showtable['Avg_row_length'],
                6, 
                1
            );
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Row length'),
            ($avg_row_length_value . ' ' . $avg_row_length_unit)
        );
        unset($avg_row_length_value, $avg_row_length_unit);
        $odd_row = !$odd_row;
    }
    if (!$is_innodb
        && isset($showtable['Data_length'])
        && $showtable['Rows'] > 0
        && $mergetable == false
    ) {
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Row size'),
            ($avg_size . ' ' . $avg_unit)
        );
        $odd_row = !$odd_row;
    }
    if (isset($showtable['Auto_increment'])) {
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Next autoindex'),
            PMA_Util::formatNumber($showtable['Auto_increment'], 0)
        );
        $odd_row = !$odd_row;
    }
    if (isset($showtable['Create_time'])) {
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Creation'),
            PMA_Util::localisedDate(strtotime($showtable['Create_time']))
        );
        $odd_row = !$odd_row;
    }
    if (isset($showtable['Update_time'])) {
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Last update'),
            PMA_Util::localisedDate(strtotime($showtable['Update_time']))
        );
        $odd_row = !$odd_row;
    }
    if (isset($showtable['Check_time'])) {
        $html_output .= PMA_getHtmlForRowStatsTableRow(
            $odd_row,
            __('Last check'),
            PMA_Util::localisedDate(strtotime($showtable['Check_time']))
        );
    }
    $html_output .= '</tbody>'
    . '</table>'
    . '</fieldset>'
    . '</div>';

    return $html_output;
}

/**
 * Get HTML snippet for action row in structure table,
 * This function returns common HTML <td> for Primary, Unique, Index,
 * Spatial actions
 *
 * @param array   $type               column type
 * @param array   $tbl_storage_engine table storage engine
 * @param string  $class              class attribute for <td>
 * @param boolean $hasField           has field
 * @param boolean $hasLinkClass       has <a> the class attribute
 * @param string  $url_query          url query
 * @param boolean $primary            primary if set, false otherwise
 * @param string  $syntax             Sql syntax
 * @param string  $message            message to show
 * @param string  $action             action
 * @param array   $titles             titles array
 * @param array   $row                current row
 * @param boolean $isPrimary          is primary action
 *
 * @return array $html_output, $action_enabled
 */
function PMA_getHtmlForActionRowInStructureTable($type, $tbl_storage_engine,
    $class, $hasField, $hasLinkClass, $url_query, $primary, $syntax,
    $message, $action, $titles, $row, $isPrimary
) {
    $html_output = '<li class="'. $class .'">';

    if ($type == 'text'
        || $type == 'blob'
        || 'ARCHIVE' == $tbl_storage_engine
        || $hasField
    ) {
        $html_output .= $titles['No' . $action];
        $action_enabled = false;
    } else {
        $html_output .= '<a rel="samepage" '
            . ($hasLinkClass ? 'class="ajax add_primary_key_anchor" ' : '')
            . 'href="sql.php?' . $url_query . '&amp;sql_query='
            . urlencode(
                'ALTER TABLE ' . PMA_Util::backquote($GLOBALS['table'])
                . ($isPrimary ? ($primary ? ' DROP PRIMARY KEY,' : '') : '')
                . ' ' . $syntax . '('
                . PMA_Util::backquote($row['Field']) . ');'
            )
            . '&amp;message_to_show=' . urlencode(
                sprintf(
                    $message,
                    htmlspecialchars($row['Field'])
                )
            ) . '" >'
            . $titles[$action] . '</a>';
        $action_enabled = true;
    }
    $html_output .= '</li>';

    return array($html_output, $action_enabled);
}

/**
 * Get HTML for fulltext action,
 * and this function returns $fulltext_enabled boolean value also
 *
 * @param string $tbl_storage_engine table storage engine
 * @param string $type               column type
 * @param string $url_query          url query
 * @param array  $row                current row
 * @param array  $titles             titles array
 *
 * @return type array $html_output, $fulltext_enabled
 */
function PMA_getHtmlForFullTextAction($tbl_storage_engine, $type, $url_query,
    $row, $titles
) {
    $html_output = '<li class="fulltext nowrap">';
    if (! empty($tbl_storage_engine)
        && ($tbl_storage_engine == 'MYISAM'
        || $tbl_storage_engine == 'ARIA'
        || $tbl_storage_engine == 'MARIA'
        || ($tbl_storage_engine == 'INNODB' && PMA_MYSQL_INT_VERSION >= 50604))
        && (strpos(' ' . $type, 'text') || strpos(' ' . $type, 'char'))
    ) {
        $html_output .= '<a rel="samepage" href="sql.php?' . $url_query . '&amp;sql_query='
            . urlencode(
                'ALTER TABLE ' . PMA_Util::backquote($GLOBALS['table'])
                . ' ADD FULLTEXT(' . PMA_Util::backquote($row['Field'])
                . ');'
            )
            . '&amp;message_to_show='
            . urlencode(
                sprintf(
                    __('An index has been added on %s'),
                    htmlspecialchars($row['Field'])
                )
            )
            . '">';
        $html_output .= $titles['IdxFulltext'] . '</a>';
        $fulltext_enabled = true;
    } else {
        $html_output .= $titles['NoIdxFulltext'];
        $fulltext_enabled = false;
    }
    $html_output .= '</li>';
    return array($html_output, $fulltext_enabled);
}

/**
 * Get HTML snippet for "Distinc Value" action
 *
 * @param string $url_query url query
 * @param array  $row       current row
 * @param array  $titles    titles array
 *
 * @return string $html_output
 */
function PMA_getHtmlForDistinctValueAction($url_query, $row, $titles)
{
    $html_output = '<li class="browse nowrap">';
    $html_output .= '<a href="sql.php?' . $url_query . '&amp;sql_query='
        . urlencode(
            'SELECT COUNT(*) AS ' . PMA_Util::backquote(__('Rows'))
            . ', ' . PMA_Util::backquote($row['Field'])
            . ' FROM ' . PMA_Util::backquote($GLOBALS['table'])
            . ' GROUP BY ' . PMA_Util::backquote($row['Field'])
            . ' ORDER BY ' . PMA_Util::backquote($row['Field'])
        )
        . '">'
        . $titles['DistinctValues']
        . '</a>';
    $html_output .= '</li>';

    return $html_output;
}

/**
 * Get HTML snippet for Actions in table structure
 *
 * @param string  $type                      column type
 * @param string  $tbl_storage_engine        table storage engine
 * @param boolean $primary                   primary if set, false otherwise
 * @param string  $field_name                column name
 * @param string  $url_query                 url query
 * @param array   $titles                    titles array
 * @param array   $row                       current row
 * @param string  $rownum                    row number
 * @param array   $hidden_titles             hidden titles
 * @param array   $columns_with_unique_index columns with unique index
 *
 * @return string $html_output;
 */
function PMA_getHtmlForActionsInTableStructure($type, $tbl_storage_engine,
    $primary, $field_name, $url_query, $titles, $row, $rownum, $hidden_titles,
    $columns_with_unique_index
) {
    $html_output = '<td><ul class="table-structure-actions resizable-menu">';
    list($primary, $primary_enabled)
        = PMA_getHtmlForActionRowInStructureTable(
            $type, $tbl_storage_engine,
            'primary nowrap',
            ($primary && $primary->hasColumn($field_name)),
            true, $url_query, $primary,
            'ADD PRIMARY KEY',
            __('A primary key has been added on %s'),
            'Primary', $titles, $row, true
        );
    $html_output .= $primary;
    list($unique, $unique_enabled)
        = PMA_getHtmlForActionRowInStructureTable(
            $type, $tbl_storage_engine,
            'unique nowrap',
            isset($columns_with_unique_index[$field_name]),
            false, $url_query, $primary, 'ADD UNIQUE',
            __('An index has been added on %s'),
            'Unique', $titles, $row, false
        );
    $html_output .= $unique;
    list($index, $index_enabled)
        = PMA_getHtmlForActionRowInStructureTable(
            $type, $tbl_storage_engine,
            'index nowrap', false, false, $url_query,
            $primary, 'ADD INDEX', __('An index has been added on %s'),
            'Index', $titles, $row, false
        );
    $html_output .= $index;
    if (!PMA_DRIZZLE) {
        $spatial_types = array(
            'geometry', 'point', 'linestring', 'polygon', 'multipoint',
            'multilinestring', 'multipolygon', 'geomtrycollection'
        );
        list($spatial, $spatial_enabled)
            = PMA_getHtmlForActionRowInStructureTable(
                $type, $tbl_storage_engine,
                'spatial nowrap',
                (! in_array($type, $spatial_types)
                    || 'MYISAM' != $tbl_storage_engine
                ),
                false, $url_query, $primary, 'ADD SPATIAL',
                __('An index has been added on %s'), 'Spatial',
                $titles, $row, false
            );
        $html_output .= $spatial;

        // FULLTEXT is possible on TEXT, CHAR and VARCHAR
        list ($fulltext, $fulltext_enabled) = PMA_getHtmlForFullTextAction(
            $tbl_storage_engine, $type, $url_query, $row, $titles
        );
        $html_output .= $fulltext;
    }
    $html_output .= PMA_getHtmlForDistinctValueAction($url_query, $row, $titles);
    $html_output .= '</ul></td>';
    return $html_output;
}

/**
 * Get hidden action titles (image and string)
 *
 * @return array $hidden_titles
 */
function PMA_getHiddenTitlesArray()
{
    $hidden_titles = array();
    $hidden_titles['DistinctValues'] = PMA_Util::getIcon(
        'b_browse.png', __('Distinct values'), true
    );
    $hidden_titles['Primary'] = PMA_Util::getIcon(
        'b_primary.png', __('Add primary key'), true
    );
    $hidden_titles['NoPrimary'] = PMA_Util::getIcon(
        'bd_primary.png', __('Add primary key'), true
    );
    $hidden_titles['Index'] = PMA_Util::getIcon(
        'b_index.png', __('Add index'), true
    );
    $hidden_titles['NoIndex'] = PMA_Util::getIcon(
        'bd_index.png', __('Add index'), true
    );
    $hidden_titles['Unique'] = PMA_Util::getIcon(
        'b_unique.png', __('Add unique index'), true
    );
    $hidden_titles['NoUnique'] = PMA_Util::getIcon(
        'bd_unique.png', __('Add unique index'), true
    );
    $hidden_titles['Spatial'] = PMA_Util::getIcon(
        'b_spatial.png', __('Add SPATIAL index'), true
    );
    $hidden_titles['NoSpatial'] = PMA_Util::getIcon(
        'bd_spatial.png', __('Add SPATIAL index'), true
    );
    $hidden_titles['IdxFulltext'] = PMA_Util::getIcon(
        'b_ftext.png', __('Add FULLTEXT index'), true
    );
    $hidden_titles['NoIdxFulltext'] = PMA_Util::getIcon(
        'bd_ftext.png', __('Add FULLTEXT index'), true
    );

    return $hidden_titles;
}

/**
 * Get action titles (image or string array
 *
 * @return array  $titles
 */
function PMA_getActionTitlesArray()
{
    $titles = array();
    $titles['Change']
        = PMA_Util::getIcon('b_edit.png', __('Change'));
    $titles['Drop']
        = PMA_Util::getIcon('b_drop.png', __('Drop'));
    $titles['NoDrop']
        = PMA_Util::getIcon('b_drop.png', __('Drop'));
    $titles['Primary']
        = PMA_Util::getIcon('b_primary.png', __('Primary'));
    $titles['Index']
        = PMA_Util::getIcon('b_index.png', __('Index'));
    $titles['Unique']
        = PMA_Util::getIcon('b_unique.png', __('Unique'));
    $titles['Spatial']
        = PMA_Util::getIcon('b_spatial.png', __('Spatial'));
    $titles['IdxFulltext']
        = PMA_Util::getIcon('b_ftext.png', __('Fulltext'));
    $titles['NoPrimary']
        = PMA_Util::getIcon('bd_primary.png', __('Primary'));
    $titles['NoIndex']
        = PMA_Util::getIcon('bd_index.png', __('Index'));
    $titles['NoUnique']
        = PMA_Util::getIcon('bd_unique.png', __('Unique'));
    $titles['NoSpatial']
        = PMA_Util::getIcon('bd_spatial.png', __('Spatial'));
    $titles['NoIdxFulltext']
        = PMA_Util::getIcon('bd_ftext.png', __('Fulltext'));
    $titles['DistinctValues']
        = PMA_Util::getIcon('b_browse.png', __('Distinct values'));

    return $titles;
}

/**
 * Get HTML snippet for display table statistics
 *
 * @param array   $showtable                full table status info
 * @param integer $table_info_num_rows      table info number of rows
 * @param boolean $tbl_is_view              whether table is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string  $tbl_storage_engine       table storage engine
 * @param string  $url_query                url query
 * @param string  $tbl_collation            table collation
 *
 * @return string $html_output
 */
function PMA_getHtmlForDisplayTableStats($showtable, $table_info_num_rows,
    $tbl_is_view, $db_is_information_schema, $tbl_storage_engine, $url_query,
    $tbl_collation
) {
    $html_output = '<div id="tablestatistics">';
    if (empty($showtable)) {
        $showtable = PMA_Table::sGetStatusInfo(
            $GLOBALS['db'], $GLOBALS['table'], null, true
        );
    }

    $nonisam     = false;
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type'])
        && ! preg_match('@ISAM|HEAP@i', $showtable['Type'])
    ) {
        $nonisam = true;
    }

    // Gets some sizes

    $mergetable = PMA_Table::isMerge($GLOBALS['db'], $GLOBALS['table']);

    // this is to display for example 261.2 MiB instead of 268k KiB
    $max_digits = 3;
    $decimals = 1;
    list($data_size, $data_unit) = PMA_Util::formatByteDown(
        $showtable['Data_length'], $max_digits, $decimals
    );
    if ($mergetable == false) {
        list($index_size, $index_unit) = PMA_Util::formatByteDown(
            $showtable['Index_length'], $max_digits, $decimals
        );
    }
    // InnoDB returns a huge value in Data_free, do not use it
    if (! $is_innodb
        && isset($showtable['Data_free'])
        && $showtable['Data_free'] > 0
    ) {
        list($free_size, $free_unit) = PMA_Util::formatByteDown(
            $showtable['Data_free'], $max_digits, $decimals
        );
        list($effect_size, $effect_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free'],
            $max_digits, $decimals
        );
    } else {
        list($effect_size, $effect_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'],
            $max_digits, $decimals
        );
    }
    list($tot_size, $tot_unit) = PMA_Util::formatByteDown(
        $showtable['Data_length'] + $showtable['Index_length'],
        $max_digits, $decimals
    );
    if ($table_info_num_rows > 0) {
        list($avg_size, $avg_unit) = PMA_Util::formatByteDown(
            ($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'],
            6, 1
        );
    }

    // Displays them
    $odd_row = false;

    $html_output .=  '<fieldset>'
        . '<legend>' . __('Information') . '</legend>'
        . '<a id="showusage"></a>';

    if (! $tbl_is_view && ! $db_is_information_schema) {
        $html_output .= '<table id="tablespaceusage" class="data">'
            . '<caption class="tblHeaders">' . __('Space usage') . '</caption>'
            . '<tbody>';

        $html_output .= PMA_getHtmlForSpaceUsageTableRow(
            $odd_row, __('Data'), $data_size, $data_unit
        );
        $odd_row = !$odd_row;

        if (isset($index_size)) {
            $html_output .= PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Index'), $index_size, $index_unit
            );
            $odd_row = !$odd_row;
        }

        if (isset($free_size)) {
            $html_output .= PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Overhead'), $free_size, $free_unit
            );
            $html_output .= PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Effective'), $effect_size, $effect_unit
            );
            $odd_row = !$odd_row;
        }
        if (isset($tot_size) && $mergetable == false) {
            $html_output .= PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Total'), $tot_size, $tot_unit
            );
            $odd_row = !$odd_row;
        }
        // Optimize link if overhead
        if (isset($free_size) && !PMA_DRIZZLE
            && ($tbl_storage_engine == 'MYISAM'
            || $tbl_storage_engine == 'ARIA'
            || $tbl_storage_engine == 'MARIA'
            || $tbl_storage_engine == 'BDB')
        ) {
            $html_output .= PMA_getHtmlForOptimizeLink($url_query);
        }
        $html_output .= '</tbody>'
            . '</table>';
    }

    $html_output .= getHtmlForRowStatsTable(
        $showtable, $tbl_collation,
        $is_innodb, $mergetable,
        (isset ($avg_size) ? $avg_size : ''),
        (isset ($avg_unit) ? $avg_unit : '')
    );

    return $html_output;
}

/**
 * Displays HTML for changing one or more columns
 *
 * @param string  $db                       database name
 * @param string  $table                    table name
 * @param array   $selected                 the selected columns
 * @param string  $action                   target script to call 
 *
 * @return boolean $regenerate              true if error occurred
 * 
 */
function PMA_displayHtmlForColumnChange($db, $table, $selected, $action) 
{
    // $selected comes from multi_submits.inc.php
    if (empty($selected)) {
        $selected[]   = $_REQUEST['field'];
        $selected_cnt = 1;
    } else { // from a multiple submit
        $selected_cnt = count($selected);
    }

    /**
     * @todo optimize in case of multiple fields to modify
     */
    for ($i = 0; $i < $selected_cnt; $i++) {
        $fields_meta[] = PMA_DBI_get_columns($db, $table, $selected[$i], true);
    }
    $num_fields  = count($fields_meta);
    // set these globals because tbl_columns_definition_form.inc.php 
    // verifies them
    // @todo: refactor tbl_columns_definition_form.inc.php so that it uses 
    // function params
    $GLOBALS['action'] = 'tbl_structure.php';
    $GLOBALS['num_fields'] = $num_fields; 

    // Get more complete field information.
    // For now, this is done to obtain MySQL 4.1.2+ new TIMESTAMP options
    // and to know when there is an empty DEFAULT value.
    // Later, if the analyser returns more information, it
    // could be executed to replace the info given by SHOW FULL COLUMNS FROM.
    /**
     * @todo put this code into a require()
     * or maybe make it part of PMA_DBI_get_columns();
     */

    // We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
    // SHOW FULL COLUMNS says NULL and SHOW CREATE TABLE says NOT NULL (tested
    // in MySQL 4.0.25).

    $show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.' . PMA_Util::backquote($table),
        0, 1
    );
    $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
    unset($show_create_table);
    /**
     * Form for changing properties.
     */
    include 'libraries/tbl_columns_definition_form.inc.php';
}


/**
 * Update the table's structure based on $_REQUEST
 *
 * @param string  $db                       database name
 * @param string  $table                    table name
 *
 * @return boolean $regenerate              true if error occurred
 *
 */
function PMA_updateColumns($db, $table)
{
    $err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
    $regenerate = false;
    $field_cnt = count($_REQUEST['field_name']);
    $key_fields = array();
    $changes = array();

    for ($i = 0; $i < $field_cnt; $i++) {
        $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
            isset($_REQUEST['field_orig'][$i])
                ? $_REQUEST['field_orig'][$i]
                : '',
            $_REQUEST['field_name'][$i],
            $_REQUEST['field_type'][$i],
            $_REQUEST['field_length'][$i],
            $_REQUEST['field_attribute'][$i],
            isset($_REQUEST['field_collation'][$i])
                ? $_REQUEST['field_collation'][$i]
                : '',
            isset($_REQUEST['field_null'][$i])
                ? $_REQUEST['field_null'][$i]
                : 'NOT NULL',
            $_REQUEST['field_default_type'][$i],
            $_REQUEST['field_default_value'][$i],
            isset($_REQUEST['field_extra'][$i])
                ? $_REQUEST['field_extra'][$i]
                : false,
            isset($_REQUEST['field_comments'][$i])
                ? $_REQUEST['field_comments'][$i]
                : '',
            $key_fields,
            $i,
            isset($_REQUEST['field_move_to'][$i])
                ? $_REQUEST['field_move_to'][$i]
                : ''
        );
    } // end for

    // Builds the primary keys statements and updates the table
    $key_query = '';
    /**
     * this is a little bit more complex
     *
     * @todo if someone selects A_I when altering a column we need to check:
     *  - no other column with A_I
     *  - the column has an index, if not create one
     *
    if (count($key_fields)) {
        $fields = array();
        foreach ($key_fields as $each_field) {
            if (isset($_REQUEST['field_name'][$each_field]) && strlen($_REQUEST['field_name'][$each_field])) {
                $fields[] = PMA_Util::backquote($_REQUEST['field_name'][$each_field]);
            }
        } // end for
        $key_query = ', ADD KEY (' . implode(', ', $fields) . ') ';
    }
     */

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    if (! PMA_DBI_select_db($db)) {
        PMA_Util::mysqlDie(
            PMA_DBI_getError(),
            'USE ' . PMA_Util::backquote($db) . ';',
            '',
            $err_url
        );
    }
    $sql_query = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ';
    $sql_query .= implode(', ', $changes) . $key_query;
    $sql_query .= ';';
    $result    = PMA_DBI_try_query($sql_query);

    $response = PMA_Response::getInstance();
    if ($result !== false) {
        $message = PMA_Message::success(
            __('Table %1$s has been altered successfully')
        );
        $message->addParam($table);

        /**
         * If comments were sent, enable relation stuff
         */
        include_once 'libraries/transformations.lib.php';

        // update field names in relation
        if (isset($_REQUEST['field_orig']) && is_array($_REQUEST['field_orig'])) {
            foreach ($_REQUEST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_REQUEST['field_name'][$fieldindex] != $fieldcontent) {
                    PMA_REL_renameField(
                        $db, $table, $fieldcontent,
                        $_REQUEST['field_name'][$fieldindex]
                    );
                }
            }
        }

        // update mime types
        if (isset($_REQUEST['field_mimetype'])
            && is_array($_REQUEST['field_mimetype'])
            && $GLOBALS['cfg']['BrowseMIME']
        ) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && strlen($_REQUEST['field_name'][$fieldindex])
                ) {
                    PMA_setMIME(
                        $db, $table, $_REQUEST['field_name'][$fieldindex],
                        $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex]
                    );
                }
            }
        }

        $response->addHTML(
            PMA_Util::getMessage($message, $sql_query, 'success')
        );
    } else {
        // An error happened while inserting/updating a table definition
        $response->isSuccess(false);
        $response->addJSON('message',
            PMA_Message::rawError(__('Query error') . ':<br />'.PMA_DBI_getError())
        );
        $regenerate = true;
    }
    return $regenerate;
}

/**
 * Moves columns in the table's structure based on $_REQUEST
 *
 * @param string  $db                       database name
 * @param string  $table                    table name
 */
function PMA_moveColumns($db, $table)
{
    PMA_DBI_select_db($db);

    /*
     * load the definitions for all columns
     */
    $columns = PMA_DBI_get_columns_full($db, $table);
    $column_names = array_keys($columns);
    $changes = array();
    $we_dont_change_keys = array();

    // move columns from first to last
    for ($i = 0, $l = count($_REQUEST['move_columns']); $i < $l; $i++) {
        $column = $_REQUEST['move_columns'][$i];
        // is this column already correctly placed?
        if ($column_names[$i] == $column) {
            continue;
        }

        // it is not, let's move it to index $i
        $data = $columns[$column];
        $extracted_columnspec = PMA_Util::extractColumnSpec($data['Type']);
        if (isset($data['Extra']) && $data['Extra'] == 'on update CURRENT_TIMESTAMP') {
            $extracted_columnspec['attribute'] = $data['Extra'];
            unset($data['Extra']);
        }
        $current_timestamp = false;
        if (($data['Type'] == 'timestamp' || $data['Type'] == 'datetime')
            && $data['Default'] == 'CURRENT_TIMESTAMP'
        ) {
            $current_timestamp = true;
        }
        $default_type
            = $data['Null'] === 'YES' && $data['Default'] === null
                ? 'NULL'
                : ($current_timestamp
                    ? 'CURRENT_TIMESTAMP'
                    : ($data['Default'] === null
                        ? 'NONE'
                        : 'USER_DEFINED'));

        $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
            $column,
            $column,
            strtoupper($extracted_columnspec['type']),
            $extracted_columnspec['spec_in_brackets'],
            $extracted_columnspec['attribute'],
            isset($data['Collation']) ? $data['Collation'] : '',
            $data['Null'] === 'YES' ? 'NULL' : 'NOT NULL',
            $default_type,
            $current_timestamp ? '' : $data['Default'],
            isset($data['Extra']) && $data['Extra'] !== '' ? $data['Extra'] : false,
            isset($data['Comments']) && $data['Comments'] !== ''
            ? $data['Comments'] : false,
            $we_dont_change_keys,
            $i,
            $i === 0 ? '-first' : $column_names[$i - 1]
        );
        // update current column_names array, first delete old position
        for ($j = 0, $ll = count($column_names); $j < $ll; $j++) {
            if ($column_names[$j] == $column) {
                unset($column_names[$j]);
            }
        }
        // insert moved column
        array_splice($column_names, $i, 0, $column);
    }
    $response = PMA_Response::getInstance();
    if (empty($changes)) { // should never happen
        $response->isSuccess(false);
        exit;
    }
    $move_query = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ';
    $move_query .= implode(', ', $changes);
    // move columns
    $result = PMA_DBI_try_query($move_query);
    $tmp_error = PMA_DBI_getError();
    if ($tmp_error) {
        $response->isSuccess(false);
        $response->addJSON('message', PMA_Message::error($tmp_error));
    } else {
        $message = PMA_Message::success(
            __('The columns have been moved successfully.')
        );
        $response->addJSON('message', $message);
        $response->addJSON('columns', $column_names);
    }
    exit;
}
?>
