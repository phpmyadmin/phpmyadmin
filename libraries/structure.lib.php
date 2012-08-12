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
 * @param array $current_table                 current table
 * @param boolean $table_is_view            Is table view or not
 * @param string $tbl_url_query             table url query
 * @param array $titles                     titles and icons for action links
 * @param string $truename                  table name
 * @param boolean $db_is_information_schema is database information schema or not
 * @param string $url_query                 url query
 * 
 * @return array ($browse_table, $search_table, $browse_table_label, $empty_table,
                    $tracking_icon)
 */
function PMA_getHtmlForActionLinks($current_table, $table_is_view, $tbl_url_query,
    $titles, $truename, $db_is_information_schema, $url_query
) {
    $common_functions = PMA_CommonFunctions::getInstance();
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
        $empty_table = '<a ';
        if ($GLOBALS['cfg']['AjaxEnable']) {
            $empty_table .= 'class="truncate_table_anchor"';
        }
        $empty_table .= ' href="sql.php?' . $tbl_url_query
            . '&amp;sql_query=';
        $empty_table .= urlencode(
            'TRUNCATE '
                . $common_functions->backquote($current_table['TABLE_NAME'])
            )
            . '&amp;message_to_show='
            . urlencode(
                sprintf(__('Table %s has been emptied'),
                htmlspecialchars($current_table['TABLE_NAME']))
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
                . $common_functions->getImage(
                    'eye.png', __('Tracking is active.')
                )
                . '</a>';
        } elseif (PMA_Tracker::getVersion($GLOBALS["db"], $truename) > 0) {
            $tracking_icon = '<a href="tbl_tracking.php?' . $url_query
                . '&amp;table=' . $truename . '">'
                . $common_functions->getImage(
                    'eye.png', __('Tracking is not active.')
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
 * @param boolean $table_is_view    Is table view or not
 * @param string $current_table        current table
 * 
 * @return array    ($drop_query, $drop_message)
 */
function PMA_getTableDropQueryAndMessage($table_is_view, $current_table)
{
    $drop_query = 'DROP '
        . (($table_is_view || $current_table['ENGINE'] == null) ? 'VIEW' : 'TABLE')
        . ' ' . PMA_CommonFunctions::getInstance()->backquote(
            $current_table['TABLE_NAME']
        );
    $drop_message = sprintf(
        ($table_is_view || $current_table['ENGINE'] == null)
            ? __('View %s has been dropped')
            : __('Table %s has been dropped'),
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
 * @param integer $num_tables                   number of tables
 * @param boolean $server_slave_status          server slave state
 * @param boolean $db_is_information_schema     whether database is information schema or not
 * @param integer $sum_entries                  sum entries
 * @param string $db_collation                  collation of given db
 * @param boolean $is_show_stats                whether stats is show or not
 * @param double $sum_size                      sum size
 * @param double $overhead_size                 overhead size
 * @param string $create_time_all               create time
 * @param string $update_time_all               update time
 * @param string $check_time_all                check time
 * @param integer $sum_row_count_pre            sum row count pre
 * 
 * @return string $html_output
 */
function PMA_getHtmlBodyForTableSummary($num_tables, $server_slave_status,
    $db_is_information_schema, $sum_entries, $db_collation, $is_show_stats,
    $sum_size, $overhead_size, $create_time_all, $update_time_all,
    $check_time_all, $sum_row_count_pre
) {
    $common_functions = PMA_CommonFunctions::getInstance();

    if ($is_show_stats) {
        list($sum_formatted, $unit) = $common_functions->formatByteDown(
            $sum_size, 3, 1
        );
        list($overhead_formatted, $overhead_unit)
            = $common_functions->formatByteDown($overhead_size, 3, 1);
    }

    $html_output = '<tbody id="tbl_summary_row">'
        . '<tr><th></th>';
    $html_output .= '<th class="tbl_num nowrap">';
    $html_output .= sprintf(
        _ngettext('%s table', '%s tables', $num_tables),
        $common_functions->formatNumber($num_tables, 0)
    );
    $html_output .= '</th>';

    if ($server_slave_status) {
        $html_output .= '<th>' . __('Replication') . '</th>' . "\n";
    }
    $html_output .= '<th colspan="'. ($db_is_information_schema ? 3 : 6) . '">'
        . __('Sum')
        . '</th>';
    $html_output .= '<th class="value tbl_rows">'
        . $sum_row_count_pre . $common_functions->formatNumber($sum_entries, 0)
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
                ? $common_functions->localisedDate(strtotime($create_time_all)) 
                : '-'
            )
            . '</th>';
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        $html_output .= '<th class="value tbl_last_update">' . "\n"
            . '        ' 
            . ($update_time_all
                ? $common_functions->localisedDate(strtotime($update_time_all)) 
                : '-'
            )
            . '</th>';
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        $html_output .= '<th class="value tbl_last_check">' . "\n"
            . '        ' 
            . ($check_time_all 
                ? $common_functions->localisedDate(strtotime($check_time_all)) 
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
 * @param string $pmaThemeImage             pma theme image url
 * @param string $text_dir                  url for text directory
 * @param string $overhead_check            overhead check
 * @param boolean $db_is_information_schema whether database is information schema or not
 * @param string $hidden_fields             hidden fields
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
        $html_output .= '/';
        $html_output .= '<a href="#" onclick="unMarkAllRows(\'tablesForm\');'
            . $overhead_check . 'return false;">'
            . __('Check tables having overhead')
            . '</a>';
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
 * Get HTML links for "Print view" and "Data Dictionary" options
 * 
 * @param string $url_query     url query
 * 
 * @return string $html_output
 */
function PMA_getHtmlForPrintViewAndDataDictionaryLinks($url_query)
{
    $common_functions = PMA_CommonFunctions::getInstance();
    $html_output = '<p>'
        . '<a href="db_printview.php?' . $url_query . '">'
        . $common_functions->getIcon(
            'b_print.png',
            __('Print view'),
            true
        ) . '</a>';

    $html_output .= '<a href="db_datadict.php?' . $url_query . '">'
        . $common_functions->getIcon(
            'b_tblanalyse.png',
            __('Data Dictionary'),
            true
        ) . '</a>'
        . '</p>';
    
    return $html_output;
}

/**
 * Get Time for Create time, update time and check time
 * 
 * @param array $current_table     current table
 * @param string $time_label    Create_time, Update_time, Check_time
 * @param integer $time_all     time
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
 * Get HTML for each table row of the database structure table
 * 
 * @param integer $curr                         current entry
 * @param boolean $odd_row                      whether row is odd or not
 * @param boolean $table_is_view                whether table is view or not
 * @param array $current_table                     current table
 * @param string $checked                       checked attribute
 * @param string $browse_table_label            browse table label action link
 * @param string $tracking_icon                 tracking icon
 * @param boolean $server_slave_status          server slave state
 * @param string $browse_table                  browse table action link
 * @param string $tbl_url_query                 table url query
 * @param string $search_table                  search table action link
 * @param boolean $db_is_information_schema     whether db is information schema or not
 * @param array $titles                         titles array
 * @param string $empty_table                   empty table action link
 * @param string $drop_query                    table dropt query
 * @param string $drop_message                  table drop message
 * @param string $collation                     collation
 * @param string $formatted_size                formatted size
 * @param string $unit                          unit
 * @param string $overhead                      overhead
 * @param string $create_time                   create time
 * @param string $update_time                   last update time
 * @param string $check_time                    last check time
 * @param boolean $is_show_stats                whether stats is show or not
 * @param boolean $ignored                      ignored
 * @param boolean $do                           do
 * @param intger $colspan_for_structure         colspan for structure
 * 
 * @return string $html_output
 */
function PMA_getHtmlForStructureTableRow($curr, $odd_row, $table_is_view, $current_table,
    $checked, $browse_table_label, $tracking_icon,$server_slave_status,
    $browse_table, $tbl_url_query, $search_table,
    $db_is_information_schema,$titles, $empty_table, $drop_query, $drop_message,
    $collation, $formatted_size, $unit, $overhead, $create_time, $update_time,
    $check_time,$is_show_stats, $ignored, $do, $colspan_for_structure
) {
    $common_functions = PMA_CommonFunctions::getInstance();
    
    $html_output = '<tr class="' . ($odd_row ? 'odd' : 'even');
    $odd_row = ! $odd_row;
    $html_output .= ($table_is_view ? ' is_view' : '')
    .'"'
    . 'id="row_tbl_' . $curr . '">';
        
    $html_output .= '<td class="center">'
        . '<input type="checkbox" name="selected_tbl[]" class="checkall"'
        . 'value="' . htmlspecialchars($current_table['TABLE_NAME']) . '"'
        . 'id="checkbox_tbl_' . $curr .'"' . $checked .' /></td>';
    
    $html_output .= '<th>'
        . $browse_table_label
        . (! empty($tracking_icon) ? $tracking_icon : '')
        . '</th>';
    
    if ($server_slave_status) {
        $html_output .= '<td class="center">'
            . ($ignored
                ? $common_functions->getImage('s_cancel.png', 'NOT REPLICATED')
                : '')
            . ($do
                ? $common_functions->getImage('s_success.png', 'REPLICATED')
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
    }  else {
        $html_output .= PMA_getHtmlForRepairtable($colspan_for_structure,
            $db_is_information_schema
        );
    } // end if (isset($current_table['TABLE_ROWS'])) else
    $html_output .= '</tr>';
    
    return $html_output;
}

/**
 * Get HTML for Insert/Empty/Drop action links
 * 
 * @param string $tbl_url_query     table url query
 * @param boolean $table_is_view    whether table is view or not
 * @param array $titles             titles array
 * @param string $empty_table       HTML link for empty table
 * @param array $current_table         current table
 * @param string $drop_query        query for drop table
 * @param string $drop_message      table drop message
 * 
 * @return string $html_output
 */
function PMA_getHtmlForInsertEmptyDropActionLinks($tbl_url_query, $table_is_view,
    $titles, $empty_table, $current_table, $drop_query, $drop_message
) {
    $html_output = '<td class="insert_table center">'
        . '<a ' 
        . ($GLOBALS['cfg']['AjaxEnable'] ? 'class="ajax"' : '') 
        . ' href="tbl_change.php?' . $tbl_url_query . '">'
        . $titles['Insert']
        . '</a></td>';
    $html_output .= '<td class="center">' . $empty_table . '</td>';
    $html_output .= '<td class="center">';
    $html_output .= '<a ';
    if ($GLOBALS['cfg']['AjaxEnable']) {
        $html_output .= 'class="drop_table_anchor';
        if ($table_is_view || $current_table['ENGINE'] == null) {
            // this class is used in db_structure.js to display the
            // correct confirmation message
            $html_output .= ' view';
        }
        $html_output .= '"';
    }
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
 * @param string $tbl_url_query     tabel url query
 * @param string $formatted_size    formatted size
 * @param string $unit              unit
 * @param string $overhead          overhead
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
 * @param string $create_time   create time 
 * @param string $update_time   last update time    
 * @param string $check_time    last check time
 * 
 * @return string $html_output
 */
function PMA_getHtmlForStructureTimes($create_time, $update_time, $check_time)
{
    $common_functions = PMA_CommonFunctions::getInstance();
    $html_output = '';
    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        $html_output .= '<td class="value tbl_creation">' 
            . ($create_time 
                ? $common_functions->localisedDate(strtotime($create_time)) 
                : '-' )
            . '</td>';
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        $html_output .= '<td class="value tbl_last_update">'
            . ($update_time 
                ? $common_functions->localisedDate(strtotime($update_time)) 
                : '-' )
            . '</td>';
    } // end if
    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        $html_output .= '<td class="value tbl_last_check">'
            . ($check_time 
                ? $common_functions->localisedDate(strtotime($check_time)) 
                : '-' )
            . '</td>';
    }
    return $html_output;
}

/**
 * Get HTML for ENGINE value not null or view tables that are not empty tables
 * 
 * @param boolean $table_is_view    whether table is view
 * @param array $current_table         current table
 * @param string $collation         collation
 * @param boolean $is_show_stats    whether atats show or not
 * @param string $tbl_url_query     table url query
 * @param string $formatted_size    formatted size
 * @param string $unit              unit
 * @param string $overhead          overhead
 * @param string $create_time       create time
 * @param string $update_time       update time
 * @param string $check_time        check time
 * 
 * @return string $html_output 
 */
function PMA_getHtmlForNotNullEngineViewTable($table_is_view, $current_table,
    $collation, $is_show_stats, $tbl_url_query, $formatted_size, $unit,
    $overhead, $create_time, $update_time, $check_time
) {
    $common_functions = PMA_CommonFunctions::getInstance();
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
    } elseif ($current_table['ENGINE'] == 'InnoDB' 
        && (! $current_table['COUNTED'])
    ) {
        // InnoDB table: we did not get an accurate row count
        $row_count_pre = '~';
        $sum_row_count_pre = '~';
        $show_superscript = '';
    }

    $html_output .= '<td class="value tbl_rows">'
        . $row_count_pre . $common_functions->formatNumber(
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
 * @param type $is_show_stats   whether stats show or not
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
 * @param integer $colspan_for_structure        colspan for structure
 * @param boolean $db_is_information_schema     whether db is information schema or not
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
 * void PMA_TableHeader([bool $db_is_information_schema = false])
 * display table header (<table><thead>...</thead><tbody>)
 *
 * @param boolean $db_is_information_schema
 * @param boolean $replication
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
        . PMA_SortableTableHeader(__('Table'), 'table') 
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
        .'<th>' . PMA_SortableTableHeader(__('Rows'), 'records', 'DESC')
        . PMA_CommonFunctions::getInstance()->showHint(
            PMA_sanitize(
                __('May be approximate. See [a@./Documentation.html#faq3_11@Documentation]FAQ 3.11[/a]')
            )
        ) . "\n"
        .'</th>' . "\n";
    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        $html_output .= '<th>' . PMA_SortableTableHeader(__('Type'), 'type') 
            . '</th>' . "\n";
        $cnt++;
        $html_output .= '<th>' 
            . PMA_SortableTableHeader(__('Collation'), 'collation') 
            . '</th>' . "\n";
        $cnt++;
    }
    if ($GLOBALS['is_show_stats']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>' 
            . PMA_SortableTableHeader(__('Size'), 'size', 'DESC')
            . '</th>' . "\n"
        // larger values are more interesting so default sort order is DESC
            . '<th>' 
            . PMA_SortableTableHeader(__('Overhead'), 'overhead', 'DESC') 
            . '</th>' . "\n";
        $cnt += 2;
    }
    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>' 
            . PMA_SortableTableHeader(__('Creation'), 'creation', 'DESC')
            . '</th>' . "\n";
        $cnt += 2;
    }
    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>' 
            . PMA_SortableTableHeader(__('Last update'), 'last_update', 'DESC') 
            . '</th>' . "\n";
        $cnt += 2;
    }
    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        // larger values are more interesting so default sort order is DESC
        $html_output .= '<th>' 
            . PMA_SortableTableHeader(__('Last check'), 'last_check', 'DESC')
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
function PMA_SortableTableHeader($title, $sort, $initial_sort_order = 'ASC')
{
    $common_functions = PMA_CommonFunctions::getInstance();
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
            $order_img  = ' ' . $common_functions->getImage(
                's_asc.png',
                __('Ascending'),
                array('class' => 'sort_arrow', 'title' => '')
            );
            $order_img .= ' ' . $common_functions->getImage(
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
            $order_img  = ' ' . $common_functions->getImage(
                's_asc.png',
                __('Ascending'),
                array('class' => 'sort_arrow hide', 'title' => '')
            );
            $order_img .= ' ' . $common_functions->getImage(
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

    return PMA_CommonFunctions::getInstance()->linkOrButton(
        $url, $title . $order_img, $order_link_params
    );
}

/**
 * Get the alias ant truname
 * 
 * @param string $tooltip_aliasname tooltip alias name
 * @param array $current_table         current table
 * @param string $tooltip_truename  tooltip true name
 * 
 * @return array ($alias, $truename) 
 */
function PMA_getAliasAndTruename($tooltip_aliasname, $current_table,
    $tooltip_truename
) {
    $alias = (! empty($tooltip_aliasname) 
            && isset($tooltip_aliasname[$current_table['TABLE_NAME']])
        )
        ? str_replace(' ', '&nbsp;',
            htmlspecialchars($tooltip_truename[$current_table['TABLE_NAME']])
        )
        : str_replace(' ', '&nbsp;', 
            htmlspecialchars($current_table['TABLE_NAME'])
        );
    $truename = (! empty($tooltip_truename) 
            && isset($tooltip_truename[$current_table['TABLE_NAME']])
        )
        ? str_replace(' ', '&nbsp;',
            htmlspecialchars($tooltip_truename[$current_table['TABLE_NAME']])
        )
        : str_replace(' ', '&nbsp;',
            htmlspecialchars($current_table['TABLE_NAME'])
        );
    
    return array($alias, $truename);
}

/**
 * Get the server slave state
 * 
 * @param boolean $server_slave_status  server slave state
 * @param string $truename              true name
 * 
 * @return array ($do, $ignored)
 */
function PMA_getServerSlaveStatus($server_slave_status, $truename) {
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
                && (preg_match(
                    "@^" . substr($table_part, 0, strlen($table_part) - 1) . "@",
                    $truename)
                )
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
                && (preg_match(
                    "@^" . substr($table_part, 0, strlen($table_part) - 1) . "@",
                    $truename)
                )
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
 * @param array $current_table              current table
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param boolean $is_show_stats            whether stats show or not
 * @param boolean $table_is_view            whether table is view or not
 * @param double $sum_size                  totla table size
 * @param double $overhead_size             overhead size
 * 
 * @return array 
 */
function PMA_getStuffForEnginetable($current_table, $db_is_information_schema,
    $is_show_stats, $table_is_view, $sum_size, $overhead_size
) {
    $common_functions = PMA_CommonFunctions::getInstance();
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
 * @param array $current_table              current table
 * @param boolean $is_show_stats            whether stats show or not
 * @param double $sum_size                  sum size
 * @param double $overhead_size             overhead size
 * 
 * @return array 
 */
function PMA_getValuesForAriaTable($db_is_information_schema, $current_table,
    $is_show_stats, $sum_size, $overhead_size, $formatted_size, $unit,
    $formatted_overhead, $overhead_unit
) {
    $common_functions = PMA_CommonFunctions::getInstance();
    if ($db_is_information_schema) {
            $current_table['Rows'] = PMA_Table::countRecords(
                $GLOBALS['db'], $current_table['Name']
            );
        }

    if ($is_show_stats) {
        $tblsize = doubleval($current_table['Data_length']) 
            + doubleval($current_table['Index_length']);
        $sum_size += $tblsize;
        list($formatted_size, $unit) = $common_functions->formatByteDown(
            $tblsize, 3, ($tblsize > 0) ? 1 : 0
        );
        if (isset($current_table['Data_free']) && $current_table['Data_free'] > 0) {
            list($formatted_overhead, $overhead_unit)
                = $common_functions->formatByteDown(
                    $current_table['Data_free'], 3,
                    ($current_table['Data_free'] > 0) ? 1 : 0
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
 * @param array $current_table         current table
 * @param boolean $is_show_stats    whether stats show or not
 * @param double $sum_size          sum size
 * 
 * @return array 
 */
function PMA_getValuesForPbmsTable($current_table, $is_show_stats, $sum_size)
{
    $common_functions = PMA_CommonFunctions::getInstance();
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
        list($formatted_size, $unit) = $common_functions->formatByteDown(
            $tblsize, 3, ($tblsize > 0) ? 1 : 0
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
 * @param type $db_is_information_schema    whether db is information schema or not
 * @param type $tbl_is_view                 whether table is view or nt
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
    }  else { /* see tbl_structure.js, function moreOptsMenuResize() */
        $colspan = 9;
        if (PMA_DRIZZLE) {
            $colspan -= 2;
        }
        if ($GLOBALS['cfg']['PropertiesIconic']) {
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
 * For "Action" Coulumn, this function contains only HTML code for "Change" and "Drop"
 * 
 * @param array $row                        current row
 * @param boolean $odd_row                  whether current row is odd or even
 * @param string $rownum                    row number
 * @param string $checked                   checked
 * @param string $displayed_field_name      displayed field name
 * @param string $type_nowrap               type nowrap
 * @param array $extracted_columnspec       associative array containing type, 
 *                                          spec_in_brackets
 *                                          and possibly enum_set_values (another array)
 * @param string $type_mime                 mime type
 * @param string $field_charset             field charset
 * @param string $attribute                 attribute (BINARY, UNSIGNED, 
 *                                          UNSIGNED ZEROFILL, on update CURRENT_TIMESTAMP)
 * @param boolean $tbl_is_view              whether tables is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string $url_query                 url query
 * @param string $field_encoded             field encoded
 * @param array $titles                     tittles array
 * @param string $table                     table
 * 
 * @return array ($html_output, $odd_row)
 */
function PMA_getHtmlTableStructureRow($row, $odd_row, $rownum, $checked,
    $displayed_field_name, $type_nowrap, $extracted_columnspec, $type_mime,
    $field_charset, $attribute, $tbl_is_view, $db_is_information_schema,
    $url_query, $field_encoded, $titles, $table
) {
    $common_functions = PMA_CommonFunctions::getInstance();
    
    $html_output = '<tr class="' . ($odd_row ? 'odd': 'even') . '">';
    $odd_row = !$odd_row; 
    $html_output .= '<td class="center">'
        . '<input type="checkbox" class="checkall" name="selected_fld[]" '
        . 'value="' . htmlspecialchars($row['Field']) . '" '
        . 'id="checkbox_row_' . $rownum . '"' . $checked . '/>'
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
            $html_output .= $common_functions->convertBitDefaultValue($row['Default']);
        } else {
            $html_output .= $row['Default'];
        }
    } else {
        $html_output .= '<i>' . _pgettext('None for default', 'None') . '</i>';
    }
    $html_output .= '</td>';
    
    $html_output .= '<td class="nowrap">' . strtoupper($row['Extra']) . '</td>';
    
    $html_output .= PMA_getHtmlForDropColumn($tbl_is_view,
        $db_is_information_schema, $url_query, $field_encoded,
        $titles, $table, $row
    );

    return array($html_output, $odd_row);
}

/**
 * Get HTML code for "Drop" Action link
 * 
 * @param boolean $tbl_is_view              whether tables is view or not
 * @param boolean $db_is_information_schema whether db is information schema or not
 * @param string $url_query                 url query
 * @param string $field_encoded             field encoded
 * @param array $titles                     tittles array
 * @param string $table                     table
 * @param array $row                        current row
 * 
 * @return string $html_output
 */
function PMA_getHtmlForDropColumn($tbl_is_view, $db_is_information_schema,
    $url_query, $field_encoded, $titles, $table, $row
) {
    $common_functions = PMA_CommonFunctions::getInstance();
    $html_output = '';
    
    if (! $tbl_is_view && ! $db_is_information_schema) {
        $html_output .= '<td class="edit center">'
            . '<a href="tbl_alter.php?' . $url_query . '&amp;field=' . $field_encoded . '">'
            . $titles['Change'] . '</a>' . '</td>';
        $html_output .= '<td class="drop center">'
            . '<a ' 
            . ($GLOBALS['cfg']['AjaxEnable'] ? ' class="drop_column_anchor"' : '') 
            . ' href="sql.php?' . $url_query . '&amp;sql_query=' 
            . urlencode('ALTER TABLE ' . $common_functions->backquote($table) 
                . ' DROP ' . $common_functions->backquote($row['Field']) . ';'
            ) 
            . '&amp;dropped_column=' . urlencode($row['Field']) 
            . '&amp;message_to_show=' . urlencode(sprintf(
                __('Column %s has been dropped'),
                htmlspecialchars($row['Field']))
            ) . '" >'
            . $titles['Drop'] . '</a>'
            . '</td>';
    }
    
    return $html_output;
}

?>
 