<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// Display function
/**
 * void PMA_TableHeader([bool $db_is_information_schema = false])
 * display table header (<table><thead>...</thead><tbody>)
 *
 * @uses    PMA_showHint()
 * @uses    $GLOBALS['cfg']['PropertiesNumColumns']
 * @uses    $GLOBALS['is_show_stats']
 * @uses    $GLOBALS['strTable']
 * @uses    $GLOBALS['strAction']
 * @uses    $GLOBALS['strRecords']
 * @uses    $GLOBALS['strApproximateCount']
 * @uses    $GLOBALS['strType']
 * @uses    $GLOBALS['strCollation']
 * @uses    $GLOBALS['strSize']
 * @uses    $GLOBALS['strOverhead']
 * @uses    $GLOBALS['structure_tbl_col_cnt']
 * @uses    PMA_SortableTableHeader()
 * @param   boolean $db_is_information_schema
 * @param   boolean $replication
 */
function PMA_TableHeader($db_is_information_schema = false, $replication = false)
{
    $cnt = 0; // Let's count the columns...

    if ($db_is_information_schema) {
        $action_colspan = 3;
    } else {
        $action_colspan = 6;
    }

    echo '<table class="data" style="float: left;">' . "\n"
        .'<thead>' . "\n"
        .'<tr><th></th>' . "\n"
        .'    <th>' . PMA_SortableTableHeader($GLOBALS['strTable'], 'table') . '</th>' . "\n";
    if ($replication) {
     echo '    <th>' . "\n"
         .'        ' . $GLOBALS['strReplication'] . "\n"
         .'    </th>';
    }
    echo '    <th colspan="' . $action_colspan . '">' . "\n"
        .'        ' . $GLOBALS['strAction'] . "\n"
        .'    </th>'
        // larger values are more interesting so default sort order is DESC
        .'    <th>' . PMA_SortableTableHeader($GLOBALS['strRecords'], 'records', 'DESC')
        .PMA_showHint(PMA_sanitize($GLOBALS['strApproximateCount'])) . "\n"
        .'    </th>' . "\n";
    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        echo '    <th>' . PMA_SortableTableHeader($GLOBALS['strType'], 'type') . '</th>' . "\n";
        $cnt++;
        echo '    <th>' . PMA_SortableTableHeader($GLOBALS['strCollation'], 'collation') . '</th>' . "\n";
        $cnt++;
    }
    if ($GLOBALS['is_show_stats']) {
        // larger values are more interesting so default sort order is DESC
        echo '    <th>' . PMA_SortableTableHeader($GLOBALS['strSize'], 'size', 'DESC') . '</th>' . "\n"
        // larger values are more interesting so default sort order is DESC
           . '    <th>' . PMA_SortableTableHeader($GLOBALS['strOverhead'], 'overhead', 'DESC') . '</th>' . "\n";
        $cnt += 2;
    }
    echo '</tr>' . "\n";
    echo '</thead>' . "\n";
    echo '<tbody>' . "\n";
    $GLOBALS['structure_tbl_col_cnt'] = $cnt + $action_colspan + 3;
} // end function PMA_TableHeader()


/**
 * Creates a clickable column header for table information
 *
 * @param   string  title to use for the link
 * @param   string  corresponds to sortable data name mapped in libraries/db_info.inc.php  
 * @param   string  initial sort order
 * @returns string  link to be displayed in the table header
 */
function PMA_SortableTableHeader($title, $sort, $initial_sort_order = 'ASC')
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
    $order_link_params['title'] = $GLOBALS['strSort'];

    // If this column was requested to be sorted.
    if ($requested_sort == $sort) {
        if ($requested_sort_order == 'ASC') {
            $future_sort_order = 'DESC';
            // current sort order is ASC
            $order_img  = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_asc.png" width="11" height="9" alt="'. $GLOBALS['strAscending'] . '" title="'. $GLOBALS['strAscending'] . '" id="sort_arrow" />';
            // but on mouse over, show the reverse order (DESC)
            $order_link_params['onmouseover'] = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
            // on mouse out, show current sort order (ASC)
            $order_link_params['onmouseout']  = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
        } else {
            $future_sort_order = 'ASC';
            // current sort order is DESC
            $order_img  = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_desc.png" width="11" height="9" alt="'. $GLOBALS['strDescending'] . '" title="'. $GLOBALS['strDescending'] . '" id="sort_arrow" />';
            // but on mouse over, show the reverse order (ASC)
            $order_link_params['onmouseover'] = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
            // on mouse out, show current sort order (DESC)
            $order_link_params['onmouseout']  = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
        }
    }

    $_url_params = array(
        'db' => $_REQUEST['db'],
    );

    $url = 'db_structure.php'.PMA_generate_common_url($_url_params);
    // We set the position back to 0 every time they sort.
    $url .= "&amp;pos=0&amp;sort=$sort&amp;sort_order=$future_sort_order";

    return PMA_linkOrButton($url, $title . $order_img, $order_link_params);
} // end function PMA_SortableTableHeader()
?>
