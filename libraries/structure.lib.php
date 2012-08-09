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
 * @param array $each_table                 current table
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
function PMA_getHtmlForActionLinks($each_table, $table_is_view, $tbl_url_query,
    $titles, $truename, $db_is_information_schema, $url_query
) {
    $common_functions = PMA_CommonFunctions::getInstance();

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
                . $common_functions->backquote($each_table['TABLE_NAME'])
            )
            . '&amp;message_to_show='
            . urlencode(
                sprintf(__('Table %s has been emptied'),
                htmlspecialchars($each_table['TABLE_NAME']))
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
 * @param string $each_table        current table
 * 
 * @return array    ($drop_query, $drop_message)
 */
function PMA_getTableDropQueryAndMessage($table_is_view, $each_table)
{
    $drop_query = 'DROP '
        . (($table_is_view || $each_table['ENGINE'] == null) ? 'VIEW' : 'TABLE')
        . ' ' . PMA_CommonFunctions::getInstance()->backquote(
            $each_table['TABLE_NAME']
        );
    $drop_message = sprintf(
        ($table_is_view || $each_table['ENGINE'] == null)
            ? __('View %s has been dropped')
            : __('Table %s has been dropped'),
        str_replace(
            ' ',
            '&nbsp;',
            htmlspecialchars($each_table['TABLE_NAME'])
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
function PMA_getHtmlBodyForTableSummery($num_tables, $server_slave_status,
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
 * @param array $each_table     current table
 * @param string $time_label    Create_time, Update_time, Check_time
 * @param integer $time_all     time
 * 
 * @return array ($time, $time_all) 
 */
function PMA_getTimeForCreateUpdateCheck($each_table, $time_label, $time_all)
{
    $showtable = PMA_Table::sGetStatusInfo(
        $GLOBALS['db'],
        $each_table['TABLE_NAME'],
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
 * @param array $each_table                     current table
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
function PMA_getHtmlForStructureTableRow($curr, $odd_row, $table_is_view, $each_table,
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
        . 'value="' . htmlspecialchars($each_table['TABLE_NAME']) . '"'
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
            $titles, $empty_table, $each_table, $drop_query, $drop_message
        );
    } // end if (! $db_is_information_schema)
    
    // there is a null value in the ENGINE
    // - when the table needs to be repaired, or
    // - when it's a view
    //  so ensure that we'll display "in use" below for a table
    //  that needs to be repaired
    if (isset($each_table['TABLE_ROWS']) 
        && ($each_table['ENGINE'] != null 
        || $table_is_view)
    ) {
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
        } elseif ($each_table['ENGINE'] == 'InnoDB' 
            && (! $each_table['COUNTED'])
        ) {
            // InnoDB table: we did not get an accurate row count
            $row_count_pre = '~';
            $sum_row_count_pre = '~';
            $show_superscript = '';
        }
        
        $html_output .= '<td class="value tbl_rows">'
            . $row_count_pre . $common_functions->formatNumber(
                $each_table['TABLE_ROWS'], 0
            )
            . $show_superscript . '</td>';
        
        if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
            $html_output .= '<td class="nowrap">'
                . ($table_is_view ? __('View') : $each_table['ENGINE'])
                . '</td>';
            if (isset($collation)) {
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
    } elseif ($table_is_view) {
        $html_output .= '<td class="value">-</td>'
            . '<td>' . __('View') . '</td>'
            . '<td>---</td>';
        if ($is_show_stats) {
            $html_output .= '<td class="value">-</td>'
                . '<td class="value">-</td>';
        }
    }  else {
        $html_output .= '<td colspan="'
            . ($colspan_for_structure - ($db_is_information_schema ? 5 : 8)) . '"'
            . 'class="center">'
            . __('in use')
            . '</td>';       
    } // end if (isset($each_table['TABLE_ROWS'])) else
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
 * @param array $each_table         current table
 * @param string $drop_query        query for drop table
 * @param string $drop_message      table drop message
 * 
 * @return string $html_output
 */
function PMA_getHtmlForInsertEmptyDropActionLinks($tbl_url_query, $table_is_view,
    $titles, $empty_table, $each_table, $drop_query, $drop_message
) {
    $html_output = '<td class="insert_table center">'
        . '<a ' 
        . ($GLOBALS['cfg']['AjaxEnable'] ? 'class="ajax"' : '') 
        . ' href="tbl_change.php' . $tbl_url_query . '">'
        . $titles['Insert']
        . '</a></td>';
    $html_output .= '<td class="center">' . $empty_table . '</td>';
    $html_output .= '<td class="center">';
    $html_output .= '<a ';
    if ($GLOBALS['cfg']['AjaxEnable']) {
        $html_output .= 'class="drop_table_anchor';
        if ($table_is_view || $each_table['ENGINE'] == null) {
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

?>
