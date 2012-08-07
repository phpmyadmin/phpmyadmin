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

?>
