<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Checks if the left frame has to be reloaded
 */
require_once('./libraries/grab_globals.lib.php');


/**
 * Does the common work
 */
$js_to_run = 'functions.js';
require('./server_common.inc.php');

?>
<script type="text/javascript" language="javascript1.2">
<!--
function reload_window(db) {
    if (typeof(window.parent) != 'undefined'
        && typeof(window.parent.frames['nav']) != 'undefined') {
        window.parent.frames['nav'].location.replace('./left.php?<?php echo PMA_generate_common_url('','','&');?>&db=' + db + '&hash=' + <?php echo (($cfg['QueryFrame'] && $cfg['QueryFrameJS']) ? 'window.parent.frames[\'queryframe\'].document.hashform.hash.value' : "'" . md5($cfg['PmaAbsoluteUri']) . "'"); ?>);
    }
}
//-->
</script>

<?php

/**
 * Sorts the databases array according to the user's choice
 *
 * @param   array    a record associated to a database
 * @param   array    a record associated to a database
 *
 * @return  integer  a value representing whether $a should be before $b in the
 *                   sorted array or not
 *
 * @global  string   the column the array shall be sorted by
 * @global  string   the sorting order ('asc' or 'desc')
 *
 * @access  private
 */
function PMA_dbCmp($a, $b)
{
    global $sort_by, $sort_order;
    if ($sort_by == 'db_name') {
        return ($sort_order == 'asc' ? 1 : -1) * strcasecmp($a['db_name'], $b['db_name']);
    } else if ($a[$sort_by] == $b[$sort_by]) {
        return strcasecmp($a['db_name'], $b['db_name']);
    } else {
        return ($sort_order == 'asc' ? 1 : -1) * ((int)$a[$sort_by] > (int)$b[$sort_by] ? 1 : -1);
    }
} // end of the 'PMA_dbCmp()' function


/**
 * Gets the databases list - if it has not been built yet
 */
if ($server > 0 && empty($dblist)) {
    PMA_availableDatabases();
}


/**
 * Drops multiple databases
 */
if ((!empty($drop_selected_dbs) || isset($query_type)) && ($is_superuser || $cfg['AllowUserDropDatabase'])) {
    if (empty($selected_db) && ! (isset($query_type) && !empty($selected))) {
        $message = $strNoDatabasesSelected;
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db' ;
        $err_url = 'server_databases.php?' . PMA_generate_common_url();
        require('./mult_submits.inc.php');
        $message = sprintf($strDatabasesDropped, count($selected));
        // we need to reload the database list now.
        PMA_availableDatabases();
        $reload = 1;
    }
}


/**
 * Displays the links
 */
require('./server_links.inc.php');


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . '    ' . (empty($dbstats) ? $strDatabases : $strDatabasesStats) . "\n"
   . '</h2>' . "\n";


/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!empty($dbstats) && !$is_superuser) {
    echo $strNoPrivileges . "\n";
    require_once('./footer.inc.php');
}


/**
 * Prepares the statistics
 */
$statistics = array();
foreach($dblist AS $current_db) {
    $tmp_array = array(
        'db_name' => $current_db,
        'tbl_cnt' => 0,
        'data_sz' => 0,
        'idx_sz' => 0,
        'tot_sz' => 0
    );
    if (!empty($dbstats)) {
        $res = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($current_db) . ';', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW TABLE STATUS FROM ' . PMA_backquote($current_db) . ';');
        while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
            $tmp_array['tbl_cnt']++;
            $tmp_array['data_sz'] += $row['Data_length'];
            $tmp_array['idx_sz'] += $row['Index_length'];
        }
    }
    $tmp_array['tot_sz'] = $tmp_array['data_sz'] + $tmp_array['idx_sz'];
    $statistics[] = $tmp_array;
}

// avoids 'undefined index' errors
if (empty($sort_by)) {
    $sort_by = 'db_name';
}
if (empty($sort_order)) {
    if ($sort_by == 'db_name') {
        $sort_order = 'asc';
    } else {
        $sort_order = 'desc';
    }
}

// sorts the array
usort($statistics, 'PMA_dbCmp');


/**
 * Displays the page
 */
if (count($statistics) > 0) {
    echo '<form action="./server_databases.php" method="post" name="dbStatsForm">' . "\n"
       . PMA_generate_common_hidden_inputs('', '', 1)
       . '    <input type="hidden" name="dbstats" value="' . (empty($dbstats) ? '0' : '1') . '" />' . "\n"
       . '    <input type="hidden" name="sort_by" value="' . $sort_by . '" />' . "\n"
       . '    <input type="hidden" name="sort_order" value="' . $sort_order . '" />' . "\n"
       . '    <table border="0">' . "\n"
       . '        <tr>' . "\n"
       . ($is_superuser || $cfg['AllowUserDropDatabase'] ? '            <th>&nbsp;</th>' . "\n" : '')
       . '            <th>' . "\n"
       . '                &nbsp;';
    if (empty($dbstats)) {
        echo $strDatabase . "\n"
           . '                <img src="./images/asc_order.png" border="0" width="7" height="7"  alt="' . $strAscending . '" />' . "\n"
           . '                &nbsp;' . "\n"
           . '            </th>' . "\n";
    } else {
        echo "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=db_name&amp;sort_order=' . (($sort_by == 'db_name' && $sort_order == 'asc') ? 'desc' : 'asc') . '">' . "\n"
           . '                    ' . $strDatabase . "\n"
           . ($sort_by == 'db_name' ? '                    <img src="./images/' . $sort_order . '_order.png" border="0" width="7" height="7"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '                &nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th>' . "\n"
           . '                &nbsp;' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=tbl_cnt&amp;sort_order=' . (($sort_by == 'tbl_cnt' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strNumTables . "\n"
           . ($sort_by == 'tbl_cnt' ? '                    <img src="./images/' . $sort_order . '_order.png" border="0" width="7" height="7"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '                &nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th colspan="2">' . "\n"
           . '                &nbsp;' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=data_sz&amp;sort_order=' . (($sort_by == 'data_sz' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strData . "\n"
           . ($sort_by == 'data_sz' ? '                    <img src="./images/' . $sort_order . '_order.png" border="0" width="7" height="7"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '                &nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th colspan="2">' . "\n"
           . '                &nbsp;' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=idx_sz&amp;sort_order=' . (($sort_by == 'idx_sz' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strIndexes . "\n"
           . ($sort_by == 'idx_sz' ? '                    <img src="./images/' . $sort_order . '_order.png" border="0" width="7" height="7"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '                &nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th colspan="2">' . "\n"
           . '                &nbsp;' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=tot_sz&amp;sort_order=' . (($sort_by == 'tot_sz' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strTotalUC . "\n"
           . ($sort_by == 'tot_sz' ? '                    <img src="./images/' . $sort_order . '_order.png" border="0" width="7" height="7"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '                &nbsp;' . "\n"
           . '            </th>' . "\n";
    }
    if ($is_superuser) {
        echo '            <th>' . "\n"
           . '                &nbsp;' . $strAction . '&nbsp;' . "\n"
           . '            </th>' . "\n";
    }
    echo '        </tr>' . "\n";
    $useBgcolorOne = TRUE;
    $total_calc = array(
        'db_cnt' => 0,
        'tbl_cnt' => 0,
        'data_sz' => 0,
        'idx_sz' => 0,
        'tot_sz' => 0
    );
    foreach ($statistics as $current) {
        list($data_size, $data_unit) = PMA_formatByteDown($current['data_sz'], 3, 1);
        list($idx_size, $idx_unit)   = PMA_formatByteDown($current['idx_sz'], 3, 1);
        list($tot_size, $tot_unit)   = PMA_formatByteDown($current['tot_sz'], 3, 1);
        $total_calc['db_cnt']++;
        $total_calc['tbl_cnt'] += $current['tbl_cnt'];
        $total_calc['data_sz'] += $current['data_sz'];
        $total_calc['idx_sz']  += $current['idx_sz'];
        $total_calc['tot_sz']  += $current['tot_sz'];
        echo '        <tr>' . "\n";
        if ($is_superuser || $cfg['AllowUserDropDatabase']) {
            echo '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '                <input type="checkbox" name="selected_db[]" title="' . htmlspecialchars($current['db_name']) . '" value="' . htmlspecialchars($current['db_name']) . '" ' . (empty($checkall) ? '' : 'checked="checked" ') . '/>' . "\n"
               . '            </td>' . "\n";
        }
        echo '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
           . '                <a onclick="reload_window(\'' . urlencode($current['db_name']) . '\'); return true;" href="' . $cfg['DefaultTabDatabase'] . '?' . $url_query . '&amp;db=' . urlencode($current['db_name']) . '" title="' . sprintf($strJumpToDB, htmlspecialchars($current['db_name'])) . '">' . "\n"
           . '                    ' . htmlspecialchars($current['db_name']) . "\n"
           . '                </a>' . "\n"
           . '            </td>' . "\n";
        if (!empty($dbstats)) {
            echo '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '" align="right">' . "\n"
               . '                ' . $current['tbl_cnt'] . "\n"
               . '            </td>' . "\n"
               . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '" align="right">' . "\n"
               . '                ' . $data_size . "\n"
               . '            </td>' . "\n"
               . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '                ' . $data_unit . "\n"
               . '            </td>' . "\n"
               . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '" align="right">' . "\n"
               . '                ' . $idx_size . "\n"
               . '            </td>' . "\n"
               . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '                ' . $idx_unit . "\n"
               . '            </td>' . "\n"
               . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '" align="right">' . "\n"
               . '                <b>' . "\n"
               . '                    ' . $tot_size . "\n"
               . '                </b>' . "\n"
               . '            </td>' . "\n"
               . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '                <b>' . "\n"
               . '                    ' . $tot_unit . "\n"
               . '                </b>' . "\n"
               . '            </td>' . "\n";
        }
        if ($is_superuser) {
            echo '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '                <a onclick="reload_window(\'' . urlencode($current['db_name']) . '\'); return true;" href="./server_privileges.php?' . $url_query . '&amp;checkprivs=' . urlencode($current['db_name']) . '" title="' . sprintf($strCheckPrivsLong, htmlspecialchars($current['db_name'])) . '">'. "\n"
               . '                    ' . $strCheckPrivs . "\n"
               . '                </a>' . "\n"
               . '            </td>' . "\n";
        }
        echo '        </tr>' . "\n";
        $useBgcolorOne = !$useBgcolorOne;
    } // end while
    if (!empty($dbstats)) {
        list($data_size, $data_unit) = PMA_formatByteDown($total_calc['data_sz'], 3, 1);
        list($idx_size, $idx_unit)   = PMA_formatByteDown($total_calc['idx_sz'], 3, 1);
        list($tot_size, $tot_unit)   = PMA_formatByteDown($total_calc['tot_sz'], 3, 1);
        echo '        <tr>' . "\n"
           . '            <th>&nbsp;</th>' . "\n"
           . '            <th>' . "\n"
           . '                &nbsp;' . $strTotalUC . ':&nbsp;' . $total_calc['db_cnt'] . '&nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th align="right">' . "\n"
           . '                &nbsp;' . $total_calc['tbl_cnt'] . '&nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th align="right">' . "\n"
           . '                &nbsp;' . $data_size . "\n"
           . '            </th>' . "\n"
           . '            <th align="left">' . "\n"
           . '                ' . $data_unit . '&nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th align="right">' . "\n"
           . '                &nbsp;' . $idx_size . "\n"
           . '            </th>' . "\n"
           . '            <th align="left">' . "\n"
           . '                ' . $idx_unit . '&nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th align="right">' . "\n"
           . '                &nbsp;' . $tot_size . "\n"
           . '            </th>' . "\n"
           . '            <th align="left">' . "\n"
           . '                ' . $tot_unit . '&nbsp;' . "\n"
           . '            </th>' . "\n"
           . '            <th>&nbsp;</th>' . "\n"
           . '        </tr>' . "\n";
    }
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $common_url_query = PMA_generate_common_url() . '&amp;sort_by=' . $sort_by . '&amp;sort_order=' . $sort_order . '&amp;dbstats=' . (empty($dbstats) ? '10' : '3');
        echo '    <tr>' . "\n"
           . '        <td colspan="' . (empty($dbstats) ? '10' : '3') . '">' . "\n"
           . '            <img src="./images/arrow_' . $text_dir . '.gif" border="0" width="38" height="22" alt="' . $strWithChecked . '" />' . "\n"
           . '            <a href="./server_databases.php?' . $common_url_query . '&amp;checkall=1" onclick="setCheckboxes(\'dbStatsForm\', true); return false;">' . "\n"
           . '                ' . $strCheckAll
           . '            </a>' . "\n"
           . '            &nbsp;/&nbsp;' . "\n"
           . '            <a href="./server_databases.php?' . $common_url_query . '" onclick="setCheckboxes(\'dbStatsForm\', false); return false;">' . "\n"
           . '                ' . $strUncheckAll
           . '            </a>' . "\n"
           . '        </td>' . "\n"
           . '    </tr>' . "\n";
    }
    echo '    </table>' . "\n";
    unset($data_size);
    unset($data_unit);
    unset($idx_size);
    unset($idx_unit);
    unset($tot_size);
    unset($tot_unit);
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        echo '    <ul>' . "\n";
    }
    if ($is_superuser && empty($dbstats)) {
        echo '        <li>' . "\n"
           . '            <b>' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1" title="' . $strDatabasesStatsEnable . '">' . "\n"
           . '                    ' . $strDatabasesStatsEnable . "\n"
           . '                </a>' . "\n"
           . '            </b>' . "\n"
           . '            <br />' . "\n"
           . '            ' . $strDatabasesStatsHeavyTraffic . "\n"
           . '        </li><br /><br />' . "\n";
    } else if ($is_superuser && !empty($dbstats)) {
        echo '        <li>' . "\n"
           . '            <b>' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '" title="' . $strDatabasesStatsDisable . '">'. "\n"
           . '                    ' . $strDatabasesStatsDisable . "\n"
           . '                </a>' . "\n"
           . '            </b>' . "\n"
           . '        </li><br /><br />' . "\n";
    }
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        echo '        <li>' . "\n"
           . '            <b>' . "\n"
           . '                ' . $strDropSelectedDatabases . "\n"
           . '            </b><br />' . "\n"
           . '            <input type="submit" name="drop_selected_dbs" value="' . $strGo . '" />' . "\n"
           . '        </li>' . "\n"
           . '    </ul>' . "\n";
    }
    echo '</form>' . "\n";
} else {
    echo $strNoDatabases . "\n";
}


/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
