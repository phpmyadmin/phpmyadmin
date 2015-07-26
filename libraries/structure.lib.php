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
require_once 'libraries/util.lib.php';
require_once 'libraries/display_create_table.lib.php';
require_once 'libraries/transformations.lib.php';

use PMA\Util;

/**
 * Creates a clickable column header for table information
 *
 * @todo Remove and make it a template
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
    $order_link_params = array(
        'title' => __('Sort')
    );

    // If this column was requested to be sorted.
    if ($requested_sort == $sort) {
        $ascending = $requested_sort_order == 'ASC';
        $future_sort_order = $ascending ? 'DESC' : 'ASC';
        // current sort order is ASC
        $order_img = PMA_Util::getImage(
            's_asc.png',
            __('Ascending'),
            array(
                'class' => 'sort_arrow' . $future_sort_order ? '' : ' hide'
            )
        ) . ' ' . PMA_Util::getImage(
            's_desc.png',
            __('Descending'),
            array(
                'class' => 'sort_arrow' . $future_sort_order ? ' hide' : ''
            )
        );
        $order_link_params = array_merge($order_link_params, array(
            'onmouseover' => "$('.sort_arrow').toggle();",
            'onmouseout' => "$('.sort_arrow').toggle();"
        ));
    }

    // We set the position back to 0 every time they sort.
    $_url_params = array(
        'db' => $_REQUEST['db'],
        'pos' => 0,
        'sort' => $sort,
        'sort_order' => $future_sort_order
    );

    if (! empty($_REQUEST['tbl_type'])) {
        $_url_params['tbl_type'] = $_REQUEST['tbl_type'];
    }
    if (! empty($_REQUEST['tbl_group'])) {
        $_url_params['tbl_group'] = $_REQUEST['tbl_group'];
    }

    return PMA_Util::linkOrButton(
        'db_structure.php' . PMA_URL_getCommon($_url_params),
        $title . $order_img, $order_link_params
    );
}

/**
 * Get the value set for ENGINE table,
 *
 * @todo Remove
 *
 * @param array $current_table current table
 * @param boolean $db_is_system_schema whether db is information schema or not
 * @param boolean $is_show_stats whether stats show or not
 * @param double $sum_size total table size
 * @param double $overhead_size overhead size
 * @return array
 * @internal param bool $table_is_view whether table is view or not
 */
function PMA_getStuffForEngineTypeTable($current_table, $db_is_system_schema,
                                        $is_show_stats, $sum_size, $overhead_size
) {
    $formatted_size = '-';
    $unit = '';
    $formatted_overhead = '';
    $overhead_unit = '';
    $table_is_view = false;

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

    if ($current_table['TABLE_TYPE'] == 'VIEW' || $current_table['TABLE_TYPE'] == 'SYSTEM VIEW') {
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
 *
 * @todo Remove
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
function PMA_getValuesForAriaTable(
    $db_is_system_schema, $current_table, $is_show_stats,
    $sum_size, $overhead_size, $formatted_size, $unit,
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
 *
 * @todo Remove
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
 * Get HTML snippet for display table statistics
 *
 * @todo Move to StructureController::getTableStats
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
function PMA_getTableStats(
    $showtable, $table_info_num_rows, $tbl_is_view,
    $db_is_system_schema, $tbl_storage_engine,
    $url_query, $tbl_collation
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
    if (! $is_innodb && isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
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
            ($showtable['Data_length'] + $showtable['Index_length'])
            / $showtable['Rows'],
            6, 1
        );
    } else {
        $avg_size = $avg_unit = '';
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
            'free_size' => isset($free_size) ? $free_size : null,
            'free_unit' => isset($free_unit) ? $free_unit : null,
            'effect_size' => $effect_size,
            'effect_unit' => $effect_unit,
            'tot_size' => $tot_size,
            'tot_unit' => $tot_unit
        )
    );
}

