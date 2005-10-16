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
    if ($GLOBALS['cfg']['NaturalOrder']) {
        $sorter = 'strnatcmp';
    } else {
        $sorter = 'strcasecmp';
    }
    if ($sort_by == 'db_name') {
        return ($sort_order == 'asc' ? 1 : -1) * $sorter($a['db_name'], $b['db_name']);
    } else if ($a[$sort_by] == $b[$sort_by]) {
        return $sorter($a['db_name'], $b['db_name']);
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
        if ($mult_btn == $strYes) {
            $message = sprintf($strDatabasesDropped, count($selected));
        } else {
            $message = sprintf($strDatabasesDropped, 0);
        }
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
   . ( $GLOBALS['cfg']['MainPageIconic']
      ? '<img class="icon" src="' . $pmaThemeImage . 's_db.png" width="16"'
        .' height="16" alt="" />' 
      : '' )
   . ( empty($dbstats) ? $strDatabases : $strDatabasesStats ) . "\n"
   .'</h2>' . "\n";


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
foreach ($dblist AS $current_db) {
    $tmp_array = array(
        'db_name' => $current_db,
        'tbl_cnt' => 0,
        'data_sz' => 0,
        'idx_sz' => 0,
        'tot_sz' => 0
    );
    if (!empty($dbstats)) {
        $res = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($current_db) . ';');
        while ($row = PMA_DBI_fetch_assoc($res)) {
            $tmp_array['tbl_cnt']++;
            $tmp_array['data_sz'] += $row['Data_length'];
            $tmp_array['idx_sz'] += $row['Index_length'];
        }
        PMA_DBI_free_result($res);
        unset($res);
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
       . '    <table id="tabledatabases" class="data">' . "\n"
       . '    <thead>' . "\n"
       . '        <tr>' . "\n"
       . ($is_superuser || $cfg['AllowUserDropDatabase'] ? '            <th>&nbsp;</th>' . "\n" : '')
       . '            <th>' . "\n"
       . '                <a href="./server_databases.php?' . $url_query . (!empty($dbstats) ? '&amp;dbstats=1' : '') . '&amp;sort_by=db_name&amp;sort_order=' . (($sort_by == 'db_name' && $sort_order == 'asc') ? 'desc' : 'asc') . '">' . "\n"
       . '                    ' . $strDatabase . "\n"
       . ($sort_by == 'db_name' ? '                    <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
       . '                </a>' . "\n"
       . '            </th>' . "\n";
    if (!empty($dbstats)) {
        if (PMA_MYSQL_INT_VERSION >= 40101) {
            echo '            <th>' . $strCollation . '</th>' . "\n";
        }
        echo '            <th><a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=tbl_cnt&amp;sort_order=' . (($sort_by == 'tbl_cnt' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strNumTables . "\n"
           . ($sort_by == 'tbl_cnt' ? '                    <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '            </th>' . "\n"
           . '            <th colspan="2">' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=data_sz&amp;sort_order=' . (($sort_by == 'data_sz' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strData . "\n"
           . ($sort_by == 'data_sz' ? '                    <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '            </th>' . "\n"
           . '            <th colspan="2">' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=idx_sz&amp;sort_order=' . (($sort_by == 'idx_sz' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strIndexes . "\n"
           . ($sort_by == 'idx_sz' ? '                    <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '            </th>' . "\n"
           . '            <th colspan="2">' . "\n"
           . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1&amp;sort_by=tot_sz&amp;sort_order=' . (($sort_by == 'tot_sz' && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
           . '                    ' . $strTotalUC . "\n"
           . ($sort_by == 'tot_sz' ? '                    <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
           . '                </a>' . "\n"
           . '            </th>' . "\n";
    }
    if ($is_superuser) {
        echo '            <th>' . ($cfg['PropertiesIconic'] ? '&nbsp;' : $strAction ) . "\n"
           . '            </th>' . "\n";
    }
    echo '        </tr>' . "\n"
       . '    </thead>' . "\n"
       . '    <tbody>' . "\n";
    $total_calc = array(
        'db_cnt'    => 0,
        'tbl_cnt'   => 0,
        'data_sz'   => 0,
        'idx_sz'    => 0,
        'tot_sz'    => 0
    );
    $odd_row = false;
    foreach ( $statistics as $current ) {
        $odd_row = !$odd_row;
        list($data_size, $data_unit) = PMA_formatByteDown($current['data_sz'], 3, 1);
        list($idx_size, $idx_unit)   = PMA_formatByteDown($current['idx_sz'], 3, 1);
        list($tot_size, $tot_unit)   = PMA_formatByteDown($current['tot_sz'], 3, 1);
        $total_calc['db_cnt']++;
        $total_calc['tbl_cnt'] += $current['tbl_cnt'];
        $total_calc['data_sz'] += $current['data_sz'];
        $total_calc['idx_sz']  += $current['idx_sz'];
        $total_calc['tot_sz']  += $current['tot_sz'];
        echo '        <tr class="' . ( $odd_row ? 'odd' : 'even' ) . '">' . "\n";
        if ($is_superuser || $cfg['AllowUserDropDatabase']) {
            echo '            <td class="tool">' . "\n";
            if ($current['db_name'] != 'mysql' && (PMA_MYSQL_INT_VERSION < 50002 || $current['db_name'] != 'information_schema')) {
                echo '                <input type="checkbox" name="selected_db[]" title="' . htmlspecialchars($current['db_name']) . '" value="' . htmlspecialchars($current['db_name']) . '" ' . (empty($checkall) ? '' : 'checked="checked" ') . '/>' . "\n";
            } else {
                echo '                <input type="checkbox" name="selected_db[]" title="' . htmlspecialchars($current['db_name']) . '" value="' . htmlspecialchars($current['db_name']) . '" disabled="disabled"/>' . "\n";
            }
            echo '            </td>' . "\n";
        }
        echo '            <td class="name">' . "\n"
           . '                <a onclick="if ( window.parent.frames[0].openDb(\'' . urlencode($current['db_name']) . '\') ) return false;" href="index.php?' . $url_query . '&amp;db=' . urlencode($current['db_name']) . '" title="' . sprintf($strJumpToDB, htmlspecialchars($current['db_name'])) . '" target="_parent">' . "\n"
           . '                    ' . htmlspecialchars($current['db_name']) . "\n"
           . '                </a>' . "\n"
           . '            </td>' . "\n";
        if (!empty($dbstats)) {
            if (PMA_MYSQL_INT_VERSION >= 40101) {
                $current_collation = PMA_getDbCollation($current['db_name']);
                echo '            <td><dfn title="' . htmlspecialchars(PMA_getCollationDescr($current_collation)) . '">' . "\n"
                   . '                    ' . htmlspecialchars($current_collation) . "\n"
                   . '                </dfn>' . "\n"
                   . '            </td>' . "\n";
            }
            echo '            <td class="value">' . $current['tbl_cnt'] . '</td>' . "\n"
               . '            <td class="value">' . $data_size . '</td>' . "\n"
               . '            <td class="unit">' . $data_unit . '</td>' . "\n"
               . '            <td class="value">' . $idx_size . '</td>' . "\n"
               . '            <td class="unit">' . $idx_unit . '</td>' . "\n"
               . '            <td class="value"><strong>' . $tot_size . '</strong></td>' . "\n"
               . '            <td class="unit"><strong>' . $tot_unit . '</strong></td>' . "\n";
        }
        if ($is_superuser) {
            echo '            <td class="tool">' . "\n"
               . '                <a onclick="window.parent.frames[0].openDb(\'' . urlencode($current['db_name']) . '\', true);" href="./server_privileges.php?' . $url_query . '&amp;checkprivs=' . urlencode($current['db_name']) . '" title="' . sprintf($strCheckPrivsLong, htmlspecialchars($current['db_name'])) . '">'. "\n"
               . '                    ' .($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 's_rights.png" width="16" height="16" alt=" ' .$strCheckPrivs . '" /> ' : $strCheckPrivs ). "\n"
               . '                </a>' . "\n"
               . '            </td>' . "\n";
        }
        echo '        </tr>' . "\n";
    } // end foreach ( $statistics as $current )
    if (!empty($dbstats)) {
        list($data_size, $data_unit) = PMA_formatByteDown($total_calc['data_sz'], 3, 1);
        list($idx_size, $idx_unit)   = PMA_formatByteDown($total_calc['idx_sz'], 3, 1);
        list($tot_size, $tot_unit)   = PMA_formatByteDown($total_calc['tot_sz'], 3, 1);
        echo '        <tr><th>&nbsp;</th>' . "\n"
           . '            <th>' . $strTotalUC . ': ' . $total_calc['db_cnt']
           . '            </th>' . "\n";
        if (PMA_MYSQL_INT_VERSION >= 40101) {
            echo '            <th>&nbsp;</th>' . "\n";
        }
        echo '            <th class="value">' . $total_calc['tbl_cnt'] . '</th>' . "\n"
           . '            <th class="value">' . $data_size . '</th>' . "\n"
           . '            <th class="unit">' . $data_unit . '</th>' . "\n"
           . '            <th class="value">' . $idx_size . '</th>' . "\n"
           . '            <th class="unit">' . $idx_unit . '</th>' . "\n"
           . '            <th class="value">' . $tot_size . '</th>' . "\n"
           . '            <th class="unit">' . $tot_unit . '</th>' . "\n"
           . '            <th>&nbsp;</th>' . "\n"
           . '        </tr>' . "\n";
    }
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $common_url_query = PMA_generate_common_url() . '&amp;sort_by=' . $sort_by . '&amp;sort_order=' . $sort_order . '&amp;dbstats=' . (empty($dbstats) ? '0' : '1');
        echo '    <tr><td colspan="' . (!empty($dbstats) ? '10' : '3') . '">' . "\n"
           . '            <img class="icon" src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png" width="38" height="22" alt="' . $strWithChecked . '" />' . "\n"
           . '            <a href="./server_databases.php?' . $common_url_query . '&amp;checkall=1" onclick="setCheckboxes(\'dbStatsForm\', true); return false;">' . "\n"
           . '                ' . $strCheckAll
           . '            </a>' . "\n"
           . '             / ' . "\n"
           . '            <a href="./server_databases.php?' . $common_url_query . '" onclick="setCheckboxes(\'dbStatsForm\', false); return false;">' . "\n"
           . '                ' . $strUncheckAll
           . '            </a>' . "\n"
           . '        </td>' . "\n"
           . '    </tr>' . "\n";
    }
    echo '    </tbody>' . "\n"
        .'    </table>' . "\n";
    unset($data_size);
    unset($data_unit);
    unset($idx_size);
    unset($idx_unit);
    unset($tot_size);
    unset($tot_unit);
    if ( $GLOBALS['cfg']['PropertiesIconic'] )
    {
        // iconic view
        if ($is_superuser || $cfg['AllowUserDropDatabase']) {
            echo '       <br /><table cellpadding="2" cellspacing="0">' . "\n";
            if ($is_superuser && empty($dbstats)) {
                echo '        <tr><td><a href="./server_databases.php?' . $url_query . '&amp;dbstats=1" title="' . $strDatabasesStatsEnable . '">' . "\n"
                   . '                    <img class="icon" src="' .$pmaThemeImage . 'b_dbstatistics.png" width="16" height="16" alt="" />' . "\n"
                   . '                </a>' . "\n"
                   . '            </td><td>' . "\n"
                   . '            <strong>' . "\n"
                   . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1" title="' . $strDatabasesStatsEnable . '">' . "\n"
                   . '                    ' . $strDatabasesStatsEnable . "\n"
                   . '                </a>' . "\n"
                   . '            </strong>' . "\n"
                   . '            </td></tr><tr><td></td><td>' . "\n"
                   . '            ' . $strDatabasesStatsHeavyTraffic . "\n"
                   . '            <br />&nbsp;</td></tr>' . "\n";
            } else if ($is_superuser && !empty($dbstats)) {
                echo '        <tr><td><a href="./server_databases.php?' . $url_query . '" title="' . $strDatabasesStatsDisable . '">' . "\n"
                   . '                    <img class="icon" src="' .$pmaThemeImage . 'b_dbstatistics.png" width="16" height="16" alt="" />' . "\n"
                   . '                </a>' . "\n"
                   . '            </td><td>' . "\n"
                   . '            <strong>' . "\n"
                   . '                <a href="./server_databases.php?' . $url_query . '" title="' . $strDatabasesStatsDisable . '">' . "\n"
                   . '                    ' . $strDatabasesStatsDisable . "\n"
                   . '                </a>' . "\n"
                   . '            </strong>' . "\n"
                   . '            </td></tr><tr><td colspan="2">&nbsp;</td></tr>' . "\n";
            }
            echo '        <tr><td><img class="icon" src="' .$pmaThemeImage . 'b_deltbl.png" width="16" height="16" alt="" />' . "\n"
               . '            </td>' . "\n"
               . '            <td><strong>'
               . '                ' . $strDropSelectedDatabases
               . '            </strong>' . "\n"
               . '            </td></tr><tr><td >&nbsp;</td><td>' . "\n"
               . '            <input type="submit" name="drop_selected_dbs" value="' . $strDrop . '" id="buttonNo" />' . "\n"
               . '            <br />&nbsp;</td></tr>' . "\n"
               . '        </table>' . "\n";
        }
    }
    else
    {
        // classic view
        if ($is_superuser || $cfg['AllowUserDropDatabase']) {
            echo '       <br /><ul>' . "\n";
            if ($is_superuser && empty($dbstats)) {
                echo '        <li><strong>' . "\n"
                   . '                <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1" title="' . $strDatabasesStatsEnable . '">' . "\n"
                   . '                    ' . $strDatabasesStatsEnable . "\n"
                   . '                </a>' . "\n"
                   . '            </strong>' . "\n"
                   . '            <br />' . "\n"
                   . '            ' . $strDatabasesStatsHeavyTraffic . "\n"
                   . '        </li>' . "\n";
            } else if ($is_superuser && !empty($dbstats)) {
                echo '        <li><strong>' . "\n"
                   . '                <a href="./server_databases.php?' . $url_query . '" title="' . $strDatabasesStatsDisable . '">' . "\n"
                   . '                    ' . $strDatabasesStatsDisable . "\n"
                   . '                </a>' . "\n"
                   . '            </strong>' . "\n"
                   . '            <br /></li>' . "\n";
            }
            echo '        <li><strong>' . $strDropSelectedDatabases . "\n"
               . '            </strong>' . "\n"
               . '            <br />' . "\n"
               . '            <input type="submit" name="drop_selected_dbs" value="' . $strDrop . '" id="buttonNo" />' . "\n"
               . '        </li>' . "\n"
               . '    </ul>' . "\n";
        }
    }
    echo '</form>' . "\n";
} else {
    echo $strNoDatabases . "\n";
}

/**
 * Create new database.
 */
if ( $GLOBALS['cfg']['PropertiesIconic'] )
{
    echo '<table cellpadding="2" cellspacing="0">' . "\n";
    echo '<tr>' . "\n"
       . '    <td style="vertical-align: baseline;">' . "\n"
       . '        <img class="icon" src="' .$pmaThemeImage . 'b_newdb.png" width="16" height="16" alt="" />' . "\n"
       . '    </td>' . "\n"
       . '    <td>' . "\n";
}
else
{
    echo '<ul>' . "\n";
    echo '    <li>' . "\n";
}

require('./libraries/display_create_database.lib.php');

if ( $GLOBALS['cfg']['PropertiesIconic'] )
{
    echo '    </td></tr></table>' . "\n";
}
else
{
    echo '    </li>' . "\n";
    echo '</ul>' . "\n";
}

/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
