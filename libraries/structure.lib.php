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

require_once 'libraries/Template.class.php';

/**
 * Get the HTML links for action links
 * Actions are, Browse, Search, Browse table label, empty table
 *
 * @param array   $current_table       current table
 * @param boolean $table_is_view       Is table view or not
 * @param string  $tbl_url_query       table url query
 * @param array   $titles              titles and icons for action links
 * @param string  $truename            table name
 * @param boolean $db_is_system_schema is database information schema or not
 * @param string  $url_query           url query
 *
 * @return array ($browse_table, $search_table, $browse_table_label, $empty_table,
 *                $tracking_icon)
 */
function PMA_getHtmlForActionLinks($current_table, $table_is_view, $tbl_url_query,
    $titles, $truename, $db_is_system_schema, $url_query
) {
    $empty_table = '';

    if ($current_table['TABLE_ROWS'] > 0 || $table_is_view) {
        $may_have_rows = true;
    } else {
        $may_have_rows = false;
    }

    $browse_table = '<a href="sql.php' . $tbl_url_query . '&amp;pos=0">';
    if ($may_have_rows) {
        $browse_table .= $titles['Browse'];
    } else {
        $browse_table .= $titles['NoBrowse'];
    }
    $browse_table .= '</a>';

    $search_table = '<a href="tbl_select.php' . $tbl_url_query . '">';
    if ($may_have_rows) {
        $search_table .= $titles['Search'];
    } else {
        $search_table .= $titles['NoSearch'];
    }
    $search_table .= '</a>';

    $browse_table_label = '<a href="sql.php' . $tbl_url_query
        . '&amp;pos=0" title="'
        . htmlspecialchars($current_table['TABLE_COMMENT']) . '">'
        . $truename . '</a>';

    if (!$db_is_system_schema) {
        $empty_table = '<a class="truncate_table_anchor ajax"';
        $empty_table .= ' href="sql.php' . $tbl_url_query
            . '&amp;sql_query=';
        $empty_table .= urlencode(
            'TRUNCATE ' . PMA_Util::backquote($current_table['TABLE_NAME'])
        );
        $empty_table .= '&amp;message_to_show='
            . urlencode(
                sprintf(
                    __('Table %s has been emptied.'),
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
            $tracking_icon = '<a href="tbl_tracking.php' . $url_query
                . '&amp;table=' . $truename . '">'
                . PMA_Util::getImage(
                    'eye.png', __('Tracking is active.')
                )
                . '</a>';
        } elseif (PMA_Tracker::getVersion($GLOBALS["db"], $truename) > 0) {
            $tracking_icon = '<a href="tbl_tracking.php' . $url_query
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
            ? __('View %s has been dropped.')
            : __('Table %s has been dropped.')),
        str_replace(
            ' ',
            '&nbsp;',
            htmlspecialchars($current_table['TABLE_NAME'])
        )
    );
    return array($drop_query, $drop_message);
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
    $showtable = $GLOBALS['dbi']->getTable(
        $GLOBALS['db'],
        $current_table['TABLE_NAME']
    )->sGetStatusInfo(null, true);
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
 * @param integer $curr                  current entry
 * @param boolean $odd_row               whether row is odd or not
 * @param boolean $table_is_view         whether table is view or not
 * @param array   $current_table         current table
 * @param string  $browse_table_label    browse table label action link
 * @param string  $tracking_icon         tracking icon
 * @param boolean $server_slave_status   server slave state
 * @param string  $browse_table          browse table action link
 * @param string  $tbl_url_query         table url query
 * @param string  $search_table          search table action link
 * @param boolean $db_is_system_schema   whether db is information schema or not
 * @param array   $titles                titles array
 * @param string  $empty_table           empty table action link
 * @param string  $drop_query            table drop query
 * @param string  $drop_message          table drop message
 * @param string  $collation             collation
 * @param string  $formatted_size        formatted size
 * @param string  $unit                  unit
 * @param string  $overhead              overhead
 * @param string  $create_time           create time
 * @param string  $update_time           last update time
 * @param string  $check_time            last check time
 * @param boolean $is_show_stats         whether stats is show or not
 * @param boolean $ignored               ignored
 * @param boolean $do                    do
 * @param integer $colspan_for_structure colspan for structure
 *
 * @return array $html_output, $odd_row, $approx_rows
 */
function PMA_getHtmlForStructureTableRow(
    $curr, $odd_row, $table_is_view, $current_table,
    $browse_table_label, $tracking_icon,$server_slave_status,
    $browse_table, $tbl_url_query, $search_table,
    $db_is_system_schema,$titles, $empty_table, $drop_query, $drop_message,
    $collation, $formatted_size, $unit, $overhead, $create_time, $update_time,
    $check_time,$is_show_stats, $ignored, $do, $colspan_for_structure
) {
    global $db;

    $show_superscript = '';

    // there is a null value in the ENGINE
    // - when the table needs to be repaired, or
    // - when it's a view
    //  so ensure that we'll display "in use" below for a table
    //  that needs to be repaired
    $approx_rows = false;
    if (isset($current_table['TABLE_ROWS']) && ($current_table['ENGINE'] != null || $table_is_view)) {
        // InnoDB table: we did not get an accurate row count
        $approx_rows = !$table_is_view && $current_table['ENGINE'] == 'InnoDB' && !$current_table['COUNTED'];

        // Drizzle views use FunctionEngine, and the only place where they are
        // available are I_S and D_D schemas, where we do exact counting
        if ($table_is_view && $current_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews'] && $current_table['ENGINE'] != 'FunctionEngine') {
            $approx_rows = true;
            $show_superscript = PMA_Util::showHint(PMA_sanitize(sprintf(__('This view has at least this number of rows. Please refer to %sdocumentation%s.'), '[doc@cfg_MaxExactCountViews]', '[/doc]')));
        }
    }

    $html_output = PMA\Template::get('structure/structure_table_row', array(
        'db' => $db,
        'curr' => $curr,
        'odd_row' => $odd_row,
        'table_is_view' => $table_is_view,
        'current_table' => $current_table,
        'browse_table_label' => $browse_table_label,
        'tracking_icon' => $tracking_icon,
        'server_slave_status' => $server_slave_status,
        'browse_table' => $browse_table,
        'tbl_url_query' => $tbl_url_query,
        'search_table' => $search_table,
        'db_is_system_schema' => $db_is_system_schema,
        'titles' => $titles,
        'empty_table' => $empty_table,
        'drop_query' => $drop_query,
        'drop_message' => $drop_message,
        'collation' => $collation,
        'formatted_size' => $formatted_size,
        'unit' => $unit,
        'overhead' => $overhead,
        'create_time' => $create_time,
        'update_time' => $update_time,
        'check_time' => $check_time,
        'is_show_stats' => $is_show_stats,
        'ignored' => $ignored,
        'do' => $do,
        'colspan_for_structure' => $colspan_for_structure,
        'approx_rows' => $approx_rows,
        'show_superscript' => $show_superscript
    ));

    $odd_row = ! $odd_row;

    return array($html_output, $odd_row, $approx_rows);
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

    $url = 'db_structure.php' . PMA_URL_getCommon($_url_params);
    // We set the position back to 0 every time they sort.
    $url .= "&amp;pos=0&amp;sort=$sort&amp;sort_order=$future_sort_order";
    if (! empty($_REQUEST['tbl_type'])) {
        $url .= "&amp;tbl_type=" . $_REQUEST['tbl_type'];
    }
    if (! empty($_REQUEST['tbl_group'])) {
        $url .= "&amp;tbl_group=" . $_REQUEST['tbl_group'];
    }

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

    if (!$server_slave_status) {
        return array($do, $ignored);
    }

    $nbServSlaveDoDb = count($GLOBALS['replication_info']['slave']['Do_DB']);
    $nbServSlaveIgnoreDb
        = count($GLOBALS['replication_info']['slave']['Ignore_DB']);
    $searchDoDBInTruename = array_search(
        $truename, $GLOBALS['replication_info']['slave']['Do_DB']
    );
    $searchDoDBInDB = array_search(
        $GLOBALS['db'], $GLOBALS['replication_info']['slave']['Do_DB']
    );
    if (strlen($searchDoDBInTruename) > 0
        || strlen($searchDoDBInDB) > 0
        || ($nbServSlaveDoDb == 1 && $nbServSlaveIgnoreDb == 1)
    ) {
        $do = true;
    }
    foreach ($GLOBALS['replication_info']['slave']['Wild_Do_Table'] as $db_table) {
        $table_part = PMA_extractDbOrTable($db_table, 'table');
        $pattern = "@^"
            . /*overload*/mb_substr($table_part, 0, -1)
            . "@";
        if (($GLOBALS['db'] == PMA_extractDbOrTable($db_table, 'db'))
            && (preg_match($pattern, $truename))
        ) {
            $do = true;
        }
    }

    $searchDb = array_search(
        $GLOBALS['db'],
        $GLOBALS['replication_info']['slave']['Ignore_DB']
    );
    $searchTable = array_search(
        $truename,
        $GLOBALS['replication_info']['slave']['Ignore_Table']
    );
    if ((strlen($searchTable) > 0) || strlen($searchDb) > 0) {
        $ignored = true;
    }
    foreach (
        $GLOBALS['replication_info']['slave']['Wild_Ignore_Table'] as $db_table
        ) {
        $table_part = PMA_extractDbOrTable($db_table, 'table');
        $pattern = "@^"
            . /*overload*/mb_substr($table_part, 0, -1)
            . "@";
        if (($GLOBALS['db'] == PMA_extractDbOrTable($db_table))
            && (preg_match($pattern, $truename))
        ) {
            $ignored = true;
        }
    }

    return array($do, $ignored);
}

/**
 * Get the value set for ENGINE table,
 * $current_table, $formatted_size, $unit, $formatted_overhead,
 * $overhead_unit, $overhead_size, $table_is_view
 *
 * @param array   $current_table       current table
 * @param boolean $db_is_system_schema whether db is information schema or not
 * @param boolean $is_show_stats       whether stats show or not
 * @param boolean $table_is_view       whether table is view or not
 * @param double  $sum_size            total table size
 * @param double  $overhead_size       overhead size
 *
 * @return array
 */
function PMA_getStuffForEngineTypeTable($current_table, $db_is_system_schema,
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
            $db_is_system_schema, $current_table, $is_show_stats,
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
            = PMA_getValuesForInnodbTable($current_table, $is_show_stats, $sum_size);
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
        // possibly a view, do nothing
        break;
    default :
        // Unknown table type.
        if ($is_show_stats) {
            $formatted_size =  __('unknown');
            $unit          =  '';
        }
    } // end switch

    if ($current_table['TABLE_TYPE'] == 'VIEW'
        || $current_table['TABLE_TYPE'] == 'SYSTEM VIEW'
    ) {
        // countRecords() takes care of $cfg['MaxExactCountViews']
        $current_table['TABLE_ROWS'] = $GLOBALS['dbi']
            ->getTable($GLOBALS['db'], $current_table['TABLE_NAME'])
            ->countRecords(true);
        $table_is_view = true;
    }

    return array($current_table, $formatted_size, $unit, $formatted_overhead,
        $overhead_unit, $overhead_size, $table_is_view, $sum_size
    );
}

/**
 * Get values for ARIA/MARIA tables
 * $current_table, $formatted_size, $unit, $formatted_overhead,
 * $overhead_unit, $overhead_size
 *
 * @param boolean $db_is_system_schema whether db is information schema or not
 * @param array   $current_table       current table
 * @param boolean $is_show_stats       whether stats show or not
 * @param double  $sum_size            sum size
 * @param double  $overhead_size       overhead size
 * @param number  $formatted_size      formatted size
 * @param string  $unit                unit
 * @param number  $formatted_overhead  overhead formatted
 * @param string  $overhead_unit       overhead unit
 *
 * @return array
 */
function PMA_getValuesForAriaTable($db_is_system_schema, $current_table,
    $is_show_stats, $sum_size, $overhead_size, $formatted_size, $unit,
    $formatted_overhead, $overhead_unit
) {
    if ($db_is_system_schema) {
        $current_table['Rows'] = $GLOBALS['dbi']
            ->getTable($GLOBALS['db'], $current_table['Name'])
            ->countRecords();
    }

    if ($is_show_stats) {
        $tblsize = doubleval($current_table['Data_length'])
            + doubleval($current_table['Index_length']);
        $sum_size += $tblsize;
        list($formatted_size, $unit) = PMA_Util::formatByteDown(
            $tblsize, 3, ($tblsize > 0) ? 1 : 0
        );
        if (isset($current_table['Data_free']) && $current_table['Data_free'] > 0) {
            // here, the value 4 as the second parameter
            // would transform 6.1MiB into 6,224.6KiB
            list($formatted_overhead, $overhead_unit)
                = PMA_Util::formatByteDown(
                    $current_table['Data_free'], 4,
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
 * Get values for InnoDB table
 * $current_table, $formatted_size, $unit, $sum_size
 *
 * @param array   $current_table current table
 * @param boolean $is_show_stats whether stats show or not
 * @param double  $sum_size      sum size
 *
 * @return array
 */
function PMA_getValuesForInnodbTable($current_table, $is_show_stats, $sum_size)
{
    $formatted_size = $unit = '';

    if (($current_table['ENGINE'] == 'InnoDB'
        && $current_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
        || !isset($current_table['TABLE_ROWS'])
    ) {
        $current_table['COUNTED'] = true;
        $current_table['TABLE_ROWS'] = $GLOBALS['dbi']
            ->getTable($GLOBALS['db'], $current_table['TABLE_NAME'])
            ->countRecords(true);
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
 * Get HTML for edit views'
 *
 * @param string $url_params URL parameters
 *
 * @return string $html_output
 */
function PMA_getHtmlForEditView($url_params)
{
    $query = "SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`, `SECURITY_TYPE`"
        . " FROM `INFORMATION_SCHEMA`.`VIEWS`"
        . " WHERE TABLE_SCHEMA='" . PMA_Util::sqlAddSlashes($GLOBALS['db']) . "'"
        . " AND TABLE_NAME='" . PMA_Util::sqlAddSlashes($GLOBALS['table']) . "';";
    $item = $GLOBALS['dbi']->fetchSingleRow($query);

    $tableObj = new PMA_Table($GLOBALS['table'], $GLOBALS['db']);
    $createView = $tableObj->showCreate();
    // get algorithm from $createView of the form CREATE ALGORITHM=<ALGORITHM> DE...
    $parts = explode(" ", substr($createView, 17));
    $item['ALGORITHM'] = $parts[0];

    $view = array(
        'operation' => 'alter',
        'definer' => $item['DEFINER'],
        'sql_security' => $item['SECURITY_TYPE'],
        'name' => $GLOBALS['table'],
        'as' => $item['VIEW_DEFINITION'],
        'with' => $item['CHECK_OPTION'],
        'algorithm' => $item['ALGORITHM'],
    );
    $url  = 'view_create.php' . PMA_URL_getCommon($url_params) . '&amp;';
    $url .= implode(
        '&amp;',
        array_map(
            function ($key, $val) {
                return 'view[' . urlencode($key) . ']=' . urlencode($val);
            },
            array_keys($view),
            $view
        )
    );
    $html_output = PMA_Util::linkOrButton(
        $url,
        PMA_Util::getIcon('b_edit.png', __('Edit view'), true)
    );
    return $html_output;
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
 * @param array   $showtable           full table status info
 * @param integer $table_info_num_rows table info number of rows
 * @param boolean $tbl_is_view         whether table is view or not
 * @param boolean $db_is_system_schema whether db is information schema or not
 * @param string  $tbl_storage_engine  table storage engine
 * @param string  $url_query           url query
 * @param string  $tbl_collation       table collation
 *
 * @return string $html_output
 */
function PMA_getHtmlForDisplayTableStats($showtable, $table_info_num_rows,
    $tbl_is_view, $db_is_system_schema, $tbl_storage_engine, $url_query,
    $tbl_collation
) {
    if (empty($showtable)) {
        $showtable = $GLOBALS['dbi']->getTable(
            $GLOBALS['db'], $GLOBALS['table']
        )->sGetStatusInfo(null, true);
    }

    if (empty($showtable['Data_length'])) {
        $showtable['Data_length'] = 0;
    }
    if (empty($showtable['Index_length'])) {
        $showtable['Index_length'] = 0;
    }

    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');

    // Gets some sizes

    $table = new PMA_Table($GLOBALS['table'], $GLOBALS['db']);
    $mergetable = $table->isMerge();

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
            $showtable['Data_length'] + $showtable['Index_length']
            - $showtable['Data_free'],
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
            ($showtable['Data_length'] + $showtable['Index_length'])
            / $showtable['Rows'],
            6, 1
        );
    }

    return PMA\Template::get('structure/display_table_stats')->render(
      array(
          'showtable' => $showtable,
          'table_info_num_rows' => $table_info_num_rows,
          'tbl_is_view' => $tbl_is_view,
          'db_is_system_schema' => $db_is_system_schema,
          'tbl_storage_engine' => $tbl_storage_engine,
          'url_query' => $url_query,
          'tbl_collation' => $tbl_collation,
          'is_innodb' => $is_innodb,
          'mergetable' => $mergetable,
          'avg_size' => $avg_size,
          'avg_unit' => $avg_unit,
          'data_size' => $data_size,
          'data_unit' => $data_unit,
          'index_size' => $index_size,
          'index_unit' => $index_unit,
          'free_size' => $free_size,
          'free_unit' => $free_unit,
          'effect_size' => $effect_size,
          'effect_unit' => $effect_unit,
          'tot_size' => $tot_size,
          'tot_unit' => $tot_unit
      )
    );
}

/**
 * Displays HTML for changing one or more columns
 *
 * @param string $db       database name
 * @param string $table    table name
 * @param array  $selected the selected columns
 * @param string $action   target script to call
 *
 * @return boolean $regenerate true if error occurred
 *
 */
function PMA_displayHtmlForColumnChange($db, $table, $selected, $action)
{
    // $selected comes from mult_submits.inc.php
    if (empty($selected)) {
        $selected[]   = $_REQUEST['field'];
        $selected_cnt = 1;
    } else { // from a multiple submit
        $selected_cnt = count($selected);
    }

    /**
     * @todo optimize in case of multiple fields to modify
     */
    $fields_meta = array();
    for ($i = 0; $i < $selected_cnt; $i++) {
        $fields_meta[] = $GLOBALS['dbi']->getColumns(
            $db, $table, $selected[$i], true
        );
    }
    $num_fields  = count($fields_meta);
    // set these globals because tbl_columns_definition_form.inc.php
    // verifies them
    // @todo: refactor tbl_columns_definition_form.inc.php so that it uses
    // function params
    $GLOBALS['action'] = $action;
    $GLOBALS['num_fields'] = $num_fields;

    /**
     * Form for changing properties.
     */
    include_once 'libraries/check_user_privileges.lib.php';
    include 'libraries/tbl_columns_definition_form.inc.php';
}

/**
 * Verifies if some elements of a column have changed
 *
 * @param integer $i column index in the request
 *
 * @return boolean $alterTableNeeded true if we need to generate ALTER TABLE
 *
 */
function PMA_columnNeedsAlterTable($i)
{
    // these two fields are checkboxes so might not be part of the
    // request; therefore we define them to avoid notices below
    if (! isset($_REQUEST['field_null'][$i])) {
        $_REQUEST['field_null'][$i] = 'NO';
    }
    if (! isset($_REQUEST['field_extra'][$i])) {
        $_REQUEST['field_extra'][$i] = '';
    }

    // field_name does not follow the convention (corresponds to field_orig)
    if ($_REQUEST['field_attribute'][$i] != $_REQUEST['field_attribute_orig'][$i]
        || $_REQUEST['field_collation'][$i] != $_REQUEST['field_collation_orig'][$i]
        || $_REQUEST['field_comments'][$i] != $_REQUEST['field_comments_orig'][$i]
        || $_REQUEST['field_default_value'][$i] != $_REQUEST['field_default_value_orig'][$i]
        || $_REQUEST['field_default_type'][$i] != $_REQUEST['field_default_type_orig'][$i]
        || $_REQUEST['field_extra'][$i] != $_REQUEST['field_extra_orig'][$i]
        || $_REQUEST['field_length'][$i] != $_REQUEST['field_length_orig'][$i]
        || $_REQUEST['field_name'][$i] != $_REQUEST['field_orig'][$i]
        || $_REQUEST['field_null'][$i] != $_REQUEST['field_null_orig'][$i]
        || $_REQUEST['field_type'][$i] != $_REQUEST['field_type_orig'][$i]
        || ! empty($_REQUEST['field_move_to'][$i])
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Update the table's structure based on $_REQUEST
 *
 * @param string $db    database name
 * @param string $table table name
 *
 * @return boolean $regenerate              true if error occurred
 *
 */
function PMA_updateColumns($db, $table)
{
    $err_url = 'tbl_structure.php' . PMA_URL_getCommon(
        array(
            'db' => $db, 'table' => $table
        )
    );
    $regenerate = false;
    $field_cnt = count($_REQUEST['field_name']);
    $changes = array();
    $pmatable = new PMA_Table($table, $db);
    $adjust_privileges = array();

    for ($i = 0; $i < $field_cnt; $i++) {
        if (PMA_columnNeedsAlterTable($i)) {
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
                isset($_REQUEST['field_virtuality'][$i])
                ? $_REQUEST['field_virtuality'][$i]
                : '',
                isset($_REQUEST['field_expression'][$i])
                ? $_REQUEST['field_expression'][$i]
                : '',
                isset($_REQUEST['field_move_to'][$i])
                ? $_REQUEST['field_move_to'][$i]
                : ''
            );

            // find the remembered sort expression
            $sorted_col = $pmatable->getUiProp(PMA_Table::PROP_SORTED_COLUMN);
            // if the old column name is part of the remembered sort expression
            if (/*overload*/mb_strpos(
                $sorted_col,
                PMA_Util::backquote($_REQUEST['field_orig'][$i])
            ) !== false) {
                // delete the whole remembered sort expression
                $pmatable->removeUiProp(PMA_Table::PROP_SORTED_COLUMN);
            }

            if (isset($_REQUEST['field_adjust_privileges'][$i])
                && ! empty($_REQUEST['field_adjust_privileges'][$i])
                && $_REQUEST['field_orig'][$i] != $_REQUEST['field_name'][$i]
            ) {
                $adjust_privileges[$_REQUEST['field_orig'][$i]]
                    = $_REQUEST['field_name'][$i];
            }
        }
    } // end for

    $response = PMA_Response::getInstance();

    if (count($changes) > 0 || isset($_REQUEST['preview_sql'])) {
        // Builds the primary keys statements and updates the table
        $key_query = '';
        /**
         * this is a little bit more complex
         *
         * @todo if someone selects A_I when altering a column we need to check:
         *  - no other column with A_I
         *  - the column has an index, if not create one
         *
         */

        // To allow replication, we first select the db to use
        // and then run queries on this db.
        if (! $GLOBALS['dbi']->selectDb($db)) {
            PMA_Util::mysqlDie(
                $GLOBALS['dbi']->getError(),
                'USE ' . PMA_Util::backquote($db) . ';',
                false,
                $err_url
            );
        }
        $sql_query = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ';
        $sql_query .= implode(', ', $changes) . $key_query;
        $sql_query .= ';';

        // If there is a request for SQL previewing.
        if (isset($_REQUEST['preview_sql'])) {
            PMA_previewSQL(count($changes) > 0 ? $sql_query : '');
        }

        $changedToBlob = array();
        // While changing the Column Collation
        // First change to BLOB
        for ($i = 0; $i < $field_cnt; $i++ ) {
            if (isset($_REQUEST['field_collation'][$i])
                && isset($_REQUEST['field_collation_orig'][$i])
                && $_REQUEST['field_collation'][$i] !== $_REQUEST['field_collation_orig'][$i]
            ) {
                $secondary_query = 'ALTER TABLE ' . PMA_Util::backquote($table)
                    . ' CHANGE ' . PMA_Util::backquote($_REQUEST['field_orig'][$i])
                    . ' ' . PMA_Util::backquote($_REQUEST['field_orig'][$i])
                    . ' BLOB;';
                $GLOBALS['dbi']->query($secondary_query);
                $changedToBlob[$i] = true;
            } else {
                $changedToBlob[$i] = false;
            }
        }

        // Then make the requested changes
        $result = $GLOBALS['dbi']->tryQuery($sql_query);

        if ($result !== false) {
            $changed_privileges = PMA_adjustColumnPrivileges(
                $db, $table, $adjust_privileges
            );

            if ($changed_privileges) {
                $message = PMA_Message::success(
                    __(
                        'Table %1$s has been altered successfully. Privileges ' .
                        'have been adjusted.'
                    )
                );
            } else {
                $message = PMA_Message::success(
                    __('Table %1$s has been altered successfully.')
                );
            }
            $message->addParam($table);

            $response->addHTML(
                PMA_Util::getMessage($message, $sql_query, 'success')
            );
        } else {
            // An error happened while inserting/updating a table definition

            // Save the Original Error
            $orig_error = $GLOBALS['dbi']->getError();
            $changes_revert = array();

            // Change back to Orignal Collation and data type
            for ($i = 0; $i < $field_cnt; $i++) {
                if ($changedToBlob[$i]) {
                    $changes_revert[] = 'CHANGE ' . PMA_Table::generateAlter(
                        isset($_REQUEST['field_orig'][$i])
                        ? $_REQUEST['field_orig'][$i]
                        : '',
                        $_REQUEST['field_name'][$i],
                        $_REQUEST['field_type_orig'][$i],
                        $_REQUEST['field_length_orig'][$i],
                        $_REQUEST['field_attribute_orig'][$i],
                        isset($_REQUEST['field_collation_orig'][$i])
                        ? $_REQUEST['field_collation_orig'][$i]
                        : '',
                        isset($_REQUEST['field_null_orig'][$i])
                        ? $_REQUEST['field_null_orig'][$i]
                        : 'NOT NULL',
                        $_REQUEST['field_default_type_orig'][$i],
                        $_REQUEST['field_default_value_orig'][$i],
                        isset($_REQUEST['field_extra_orig'][$i])
                        ? $_REQUEST['field_extra_orig'][$i]
                        : false,
                        isset($_REQUEST['field_comments_orig'][$i])
                        ? $_REQUEST['field_comments_orig'][$i]
                        : '',
                        isset($_REQUEST['field_move_to_orig'][$i])
                        ? $_REQUEST['field_move_to_orig'][$i]
                        : ''
                    );
                }
            }

            $revert_query = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ';
            $revert_query .= implode(', ', $changes_revert) . '';
            $revert_query .= ';';

            // Column reverted back to original
            $GLOBALS['dbi']->query($revert_query);

            $response->isSuccess(false);
            $response->addJSON(
                'message',
                PMA_Message::rawError(
                    __('Query error') . ':<br />' . $orig_error
                )
            );
            $regenerate = true;
        }
    }

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
                && /*overload*/mb_strlen(
                    $_REQUEST['field_name'][$fieldindex]
                )
            ) {
                PMA_setMIME(
                    $db, $table, $_REQUEST['field_name'][$fieldindex],
                    $mimetype,
                    $_REQUEST['field_transformation'][$fieldindex],
                    $_REQUEST['field_transformation_options'][$fieldindex],
                    $_REQUEST['field_input_transformation'][$fieldindex],
                    $_REQUEST['field_input_transformation_options'][$fieldindex]
                );
            }
        }
    }
    return $regenerate;
}

/**
 * Adjusts the Privileges for all the columns whose names have changed
 *
 * @param string $db                database name
 * @param string $table             table name
 * @param array  $adjust_privileges assoc array of old col names mapped to new cols
 *
 * @return boolean $changed  boolean whether atleast one column privileges adjusted
 */
function PMA_adjustColumnPrivileges($db, $table, $adjust_privileges)
{
    $changed = false;

    if (! defined('PMA_DRIZZLE') || ! PMA_DRIZZLE) {
        if (isset($GLOBALS['col_priv']) && $GLOBALS['col_priv']
            && isset($GLOBALS['flush_priv']) && $GLOBALS['flush_priv']
        ) {

            $GLOBALS['dbi']->selectDb('mysql');

            // For Column specific privileges
            foreach ($adjust_privileges as $oldCol => $newCol) {
                $query_adjust_col_privileges = 'UPDATE '
                    . PMA_Util::backquote('columns_priv') . ' '
                    . 'SET Column_name = "' . $newCol . '" '
                    . 'WHERE Db = "' . $db . '" AND Table_name = "' . $table
                    . '" AND Column_name = "' . $oldCol . '";';

                $GLOBALS['dbi']->query($query_adjust_col_privileges);

                // i.e. if atleast one column privileges adjusted
                $changed = true;
            }

            if ($changed) {
                // Finally FLUSH the new privileges
                $flushPrivQuery = "FLUSH PRIVILEGES;";
                $GLOBALS['dbi']->query($flushPrivQuery);
            }
        }
    }

    return $changed;
}

/**
 * Moves columns in the table's structure based on $_REQUEST
 *
 * @param string $db    database name
 * @param string $table table name
 *
 * @return void
 */
function PMA_moveColumns($db, $table)
{
    $GLOBALS['dbi']->selectDb($db);

    /*
     * load the definitions for all columns
     */
    $columns = $GLOBALS['dbi']->getColumnsFull($db, $table);
    $column_names = array_keys($columns);
    $changes = array();

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
        if (isset($data['Extra'])
            && $data['Extra'] == 'on update CURRENT_TIMESTAMP'
        ) {
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

        $virtual = array(
            'VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'
        );
        $data['Virtuality'] = '';
        $data['Expression'] = '';
        if (isset($data['Extra']) && in_array($data['Extra'], $virtual)) {
            $data['Virtuality'] = str_replace(' GENERATED', '', $data['Extra']);
            $table = new PMA_Table($GLOBALS['table'], $GLOBALS['db']);
            $expressions = $table->getColumnGenerationExpression($column);
            $data['Expression'] = $expressions[$column];
        }

        $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
            $column,
            $column,
            /*overload*/mb_strtoupper($extracted_columnspec['type']),
            $extracted_columnspec['spec_in_brackets'],
            $extracted_columnspec['attribute'],
            isset($data['Collation']) ? $data['Collation'] : '',
            $data['Null'] === 'YES' ? 'NULL' : 'NOT NULL',
            $default_type,
            $current_timestamp ? '' : $data['Default'],
            isset($data['Extra']) && $data['Extra'] !== '' ? $data['Extra'] : false,
            isset($data['COLUMN_COMMENT']) && $data['COLUMN_COMMENT'] !== ''
            ? $data['COLUMN_COMMENT'] : false,
            $data['Virtuality'],
            $data['Expression'],
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
    $GLOBALS['dbi']->tryQuery($move_query);
    $tmp_error = $GLOBALS['dbi']->getError();
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

/**
 * Get columns with indexes
 *
 * @param string $db    database name
 * @param string $table tablename
 * @param int    $types types bitmask
 *
 * @return array an array of columns
 */
function PMA_getColumnsWithIndex($db, $table, $types)
{
    $columns_with_index = array();
    foreach (PMA_Index::getFromTableByChoice($table, $db, $types) as $index) {
        $columns = $index->getColumns();
        foreach ($columns as $column_name => $dummy) {
            $columns_with_index[$column_name] = 1;
        }
    }
    return array_keys($columns_with_index);
}

/**
 * Function to get the type of command for multiple field handling
 *
 * @return string
 */
function PMA_getMultipleFieldCommandType()
{
    $submit_mult = null;

    if (isset($_REQUEST['submit_mult_change_x'])) {
        $submit_mult = 'change';
    } elseif (isset($_REQUEST['submit_mult_drop_x'])) {
        $submit_mult = 'drop';
    } elseif (isset($_REQUEST['submit_mult_primary_x'])) {
        $submit_mult = 'primary';
    } elseif (isset($_REQUEST['submit_mult_index_x'])) {
        $submit_mult = 'index';
    } elseif (isset($_REQUEST['submit_mult_unique_x'])) {
        $submit_mult = 'unique';
    } elseif (isset($_REQUEST['submit_mult_spatial_x'])) {
        $submit_mult = 'spatial';
    } elseif (isset($_REQUEST['submit_mult_fulltext_x'])) {
        $submit_mult = 'ftext';
    } elseif (isset($_REQUEST['submit_mult_browse_x'])) {
        $submit_mult = 'browse';
    } elseif (isset($_REQUEST['submit_mult'])) {
        $submit_mult = $_REQUEST['submit_mult'];
    } elseif (isset($_REQUEST['mult_btn']) && $_REQUEST['mult_btn'] == __('Yes')) {
        $submit_mult = 'row_delete';
        if (isset($_REQUEST['selected'])) {
            $_REQUEST['selected_fld'] = $_REQUEST['selected'];
        }
    }

    return $submit_mult;
}

/**
 * Function to display table browse for selected columns
 *
 * @param string $db            current database
 * @param string $table         current table
 * @param string $goto          goto page url
 * @param string $pmaThemeImage URI of the pma theme image
 *
 * @return void
 */
function PMA_displayTableBrowseForSelectedColumns($db, $table, $goto,
    $pmaThemeImage
) {
    $GLOBALS['active_page'] = 'sql.php';
    $sql_query = '';
    foreach ($_REQUEST['selected_fld'] as $sval) {
        if ($sql_query == '') {
            $sql_query .= 'SELECT ' . PMA_Util::backquote($sval);
        } else {
            $sql_query .=  ', ' . PMA_Util::backquote($sval);
        }
    }
    $sql_query .= ' FROM ' . PMA_Util::backquote($db)
    . '.' . PMA_Util::backquote($table);

    // Parse and analyze the query
    include_once 'libraries/parse_analyze.inc.php';

    include_once 'libraries/sql.lib.php';

    PMA_executeQueryAndSendQueryResponse(
        $analyzed_sql_results, // analyzed_sql_results
        false, // is_gotofile
        $db, // db
        $table, // table
        null, // find_real_end
        null, // sql_query_for_bookmark
        null, // extra_data
        null, // message_to_show
        null, // message
        null, // sql_data
        $goto, // goto
        $pmaThemeImage, // pmaThemeImage
        null, // disp_query
        null, // disp_message
        null, // query_type
        $sql_query, // sql_query
        null, // selectedTables
        null // complete_query
    );
}

/**
 * Function to check if a table is already in favorite list.
 *
 * @param string $db            current database
 * @param string $current_table current table
 *
 * @return true|false
 */
function PMA_checkFavoriteTable($db, $current_table)
{
    foreach ($_SESSION['tmpval']['favorite_tables'][$GLOBALS['server']] as $value) {
        if ($value['db'] == $db && $value['table'] == $current_table) {
            return true;
        }
    }
    return false;
}

/**
 * Add or remove favorite tables
 *
 * @param string $db current database
 *
 * @return void
 */
function PMA_addRemoveFavoriteTables($db)
{
    $fav_instance = PMA_RecentFavoriteTable::getInstance('favorite');
    if (isset($_REQUEST['favorite_tables'])) {
        $favorite_tables = json_decode($_REQUEST['favorite_tables'], true);
    } else {
        $favorite_tables = array();
    }
    // Required to keep each user's preferences separate.
    $user = sha1($GLOBALS['cfg']['Server']['user']);

    // Request for Synchronization of favorite tables.
    if (isset($_REQUEST['sync_favorite_tables'])) {
        PMA_synchronizeFavoriteTables($fav_instance, $user, $favorite_tables);
        exit;
    }
    $changes = true;
    $msg = '';
    $titles = PMA_Util::buildActionTitles();
    $favorite_table = $_REQUEST['favorite_table'];
    $already_favorite = PMA_checkFavoriteTable($db, $favorite_table);

    if (isset($_REQUEST['remove_favorite'])) {
        if ($already_favorite) {
            // If already in favorite list, remove it.
            $fav_instance->remove($db, $favorite_table);
        }
    } elseif (isset($_REQUEST['add_favorite'])) {
        if (!$already_favorite) {
            if (count($fav_instance->getTables()) == $GLOBALS['cfg']['NumFavoriteTables']) {
                $changes = false;
                $msg = '<div class="error"><img src="themes/dot.gif" '
                    . 'title="" alt="" class="icon ic_s_error" />'
                    . __("Favorite List is full!")
                    . '</div>';
            } else {
                // Otherwise add to favorite list.
                $fav_instance->add($db, $favorite_table);
            }
        }
    }

    $favorite_tables[$user] = $fav_instance->getTables();
    $ajax_response = PMA_Response::getInstance();
    $ajax_response->addJSON(
        'changes',
        $changes
    );
    if ($changes) {
        $ajax_response->addJSON(
            'user',
            $user
        );
        $ajax_response->addJSON(
            'favorite_tables',
            json_encode($favorite_tables)
        );
        $ajax_response->addJSON(
            'list',
            $fav_instance->getHtmlList()
        );
        $ajax_response->addJSON(
            'anchor',
            PMA\Template::get('structure/favorite_anchor')->render(
                array(
                    'db' => $db,
                    'current_table' => array('TABLE_NAME' => $favorite_table),
                    'titles' => $titles
                )
            )
        );
    } else {
        $ajax_response->addJSON(
            'message',
            $msg
        );
    }
}

/**
 * Synchronize favorite tables
 *
 * @param PMA_RecentFavoriteTable $fav_instance    Instance of this class
 * @param string                  $user            The user hash
 * @param array                   $favorite_tables Existing favorites
 *
 * @return void
 */
function PMA_synchronizeFavoriteTables($fav_instance, $user, $favorite_tables)
{
    $fav_instance_tables = $fav_instance->getTables();

    if (empty($fav_instance_tables)
        && isset($favorite_tables[$user])
    ) {
        foreach ($favorite_tables[$user] as $key => $value) {
            $fav_instance->add($value['db'], $value['table']);
        }
    }
    $favorite_tables[$user] = $fav_instance->getTables();

    $ajax_response = PMA_Response::getInstance();
    $ajax_response->addJSON(
        'favorite_tables',
        json_encode($favorite_tables)
    );
    $ajax_response->addJSON(
        'list',
        $fav_instance->getHtmlList()
    );
    $server_id = $GLOBALS['server'];
    // Set flag when localStorage and pmadb(if present) are in sync.
    $_SESSION['tmpval']['favorites_synced'][$server_id] = true;
}

/**
 * Returns the real row count for a table
 *
 * @param string $db    Database name
 * @param string $table Table name
 *
 * @return number
 */
function PMA_getRealRowCountTable($db, $table)
{
    // SQL query to get row count for a table.
    $sql_query = 'SELECT COUNT(*) AS ' . PMA_Util::backquote('row_count')
        . ' FROM ' . PMA_Util::backquote($db) . '.'
        . PMA_Util::backquote($table);
    $result = $GLOBALS['dbi']->fetchSingleRow($sql_query);
    $row_count = $result['row_count'];

    return $row_count;
}

/**
 * Returns the real row count for all tables of a DB
 *
 * @param string $db     Database name
 * @param array  $tables Array containing table names.
 *
 * @return array
 */
function PMA_getRealRowCountDb($db, $tables)
{
    // Array to store the results.
    $row_count_all = array();
    // Iterate over each table and fetch real row count.
    foreach ($tables as $table) {
        $row_count = PMA_getRealRowCountTable($db, $table['TABLE_NAME']);
        array_push(
            $row_count_all,
            array('table' => $table['TABLE_NAME'], 'row_count' => $row_count)
        );
    }

    return $row_count_all;
}

/**
 * Handles request for real row count on database level view page.
 *
 * @return boolean true
 */
function PMA_handleRealRowCountRequest()
{
    $ajax_response = PMA_Response::getInstance();
    // If there is a request to update all table's row count.
    if (isset($_REQUEST['real_row_count_all'])) {
        $real_row_count_all = PMA_getRealRowCountDb(
            $GLOBALS['db'],
            $GLOBALS['tables']
        );
        $ajax_response->addJSON(
            'real_row_count_all',
            json_encode($real_row_count_all)
        );
        return true;
    }
    // Get the real row count for the table.
    $real_row_count = PMA_getRealRowCountTable(
        $GLOBALS['db'],
        $_REQUEST['table']
    );
    // Format the number.
    $real_row_count = PMA_Util::formatNumber($real_row_count, 0);
    $ajax_response->addJSON('real_row_count', $real_row_count);
    return true;
}

/**
 * Possibly show the table creation dialog
 *
 * @param string       $db                  Current database name
 * @param bool         $db_is_system_schema Whether this db is a system schema
 * @param PMA_Response $response            PMA_Response instance
 *
 * @return void
 */
function PMA_possiblyShowCreateTableDialog($db, $db_is_system_schema, $response)
{
    if (empty($db_is_system_schema)) {
        ob_start();
        include 'libraries/display_create_table.lib.php';
        $content = ob_get_contents();
        ob_end_clean();
        $response->addHTML($content);
    } // end if (Create Table dialog)
}

/**
 * Returns the HTML for secondary levels tabs of the table structure page
 *
 * @param string $tbl_storage_engine storage engine of the table
 *
 * @return string HTML for secondary levels tabs
 */
function PMA_getStructureSecondaryTabs($tbl_storage_engine)
{
    $html_output = '';

    $cfgRelation = PMA_getRelationsParam();
    if ($cfgRelation['relwork']
        || PMA_Util::isForeignKeySupported(strtoupper($tbl_storage_engine))
    ) {
        $url_params = array();
        $url_params['db'] = $GLOBALS['db'];
        $url_params['table'] = $GLOBALS['table'];

        $html_output .= '<ul id="topmenu2">';
        foreach (PMA_getStructureSubTabs() as $tab) {
            $html_output .= PMA_Util::getHtmlTab($tab, $url_params);
        }
        $html_output .= '</ul>';
        $html_output .= '<div class="clearfloat"></div>';
    }
    return $html_output;
}

/**
 * Returns an array with necessary configurations to create
 * sub-tabs in the Structure page at table level
 *
 * @return array Array containing configuration (icon, text, link, id)
 * of sub-tabs
 */
function PMA_getStructureSubTabs()
{
    $subtabs = array();

    $subtabs['structure']['icon'] = 'b_props';
    $subtabs['structure']['link'] = 'tbl_structure.php';
    $subtabs['structure']['text'] = __('Table structure');
    $subtabs['structure']['id'] = 'table_strucuture_id';

    $subtabs['relation']['icon'] = 'b_relations';
    $subtabs['relation']['link'] = 'tbl_relation.php';
    $subtabs['relation']['text'] = __('Relation view');
    $subtabs['relation']['id'] = 'table_relation_id';

    return $subtabs;
}
