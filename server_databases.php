<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Does the common work
 */
require_once './libraries/common.inc.php';


$js_to_run = 'functions.js';
require './libraries/server_common.inc.php';

/**
 * avoids 'undefined index' errors
 */
if (empty($_REQUEST['sort_by'])) {
    $sort_by = 'SCHEMA_NAME';
} else {
    $sort_by = PMA_sanitize($_REQUEST['sort_by']);
}

if (isset($_REQUEST['sort_order'])
 && strtolower($_REQUEST['sort_order']) == 'desc') {
    $sort_order = 'desc';
} else {
    $sort_order = 'asc';
}

$dbstats    = empty($_REQUEST['dbstats']) ? 0 : 1;
$pos        = empty($_REQUEST['pos']) ? 0 : (int) $_REQUEST['pos'];


/**
 * Drops multiple databases
 */

// workaround for IE behavior (it returns some coordinates based on where
// the mouse was on the Drop image):
if (isset($_REQUEST['drop_selected_dbs_x'])) {
    $_REQUEST['drop_selected_dbs'] = true;
}

if ((isset($_REQUEST['drop_selected_dbs']) || isset($_REQUEST['query_type']))
  && ($is_superuser || $cfg['AllowUserDropDatabase'])) {
    if (! isset($_REQUEST['selected_dbs']) && ! isset($_REQUEST['query_type'])) {
        $message = $strNoDatabasesSelected;
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db' ;
        $err_url = 'server_databases.php?' . PMA_generate_common_url();
        if (isset($_REQUEST['selected_dbs'])) {
            $selected_db = $_REQUEST['selected_dbs'];
        }
        require './libraries/mult_submits.inc.php';
        unset($action, $submit_mult, $err_url, $selected_db);
        if ($mult_btn == $strYes) {
            $message = sprintf($strDatabasesDropped, count($selected));
        } else {
            $message = sprintf($strDatabasesDropped, 0);
        }
    }
}

/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic']
      ? '<img class="icon" src="' . $pmaThemeImage . 's_db.png" width="16"'
        .' height="16" alt="" />'
      : '')
   . ($dbstats ? $strDatabasesStats : $strDatabases) . "\n"
   .'</h2>' . "\n";

/**
 * Gets the databases list
 */
if ($server > 0) {
    $databases = PMA_DBI_get_databases_full(null, $dbstats, null, $sort_by,
        $sort_order, $pos, true);
    $databases_count = $PMA_List_Database->count();
} else {
    $databases_count = 0;
}


/**
 * Displays the page
 */
if ($databases_count > 0) {
    reset($databases);
    $first_database = current($databases);
    // table col order
    // there is no db specific collation or charset prior 4.1.0
    if (PMA_MYSQL_INT_VERSION >= 40100) {
        $column_order['DEFAULT_COLLATION_NAME'] = array(
                'disp_name' => $strCollation,
                'description_function' => 'PMA_getCollationDescr',
                'format'    => 'string',
                'footer'    => PMA_getServerCollation(),
            );
    }
    $column_order['SCHEMA_TABLES'] = array(
        'disp_name' => $strNumTables,
        'format'    => 'number',
        'footer'    => 0,
    );
    $column_order['SCHEMA_TABLE_ROWS'] = array(
        'disp_name' => $strRows,
        'format'    => 'number',
        'footer'    => 0,
    );
    $column_order['SCHEMA_DATA_LENGTH'] = array(
        'disp_name' => $strData,
        'format'    => 'byte',
        'footer'    => 0,
    );
    $column_order['SCHEMA_INDEX_LENGTH'] = array(
        'disp_name' => $strIndexes,
        'format'    => 'byte',
        'footer'    => 0,
    );
    $column_order['SCHEMA_LENGTH'] = array(
        'disp_name' => $strTotalUC,
        'format'    => 'byte',
        'footer'    => 0,
    );
    $column_order['SCHEMA_DATA_FREE'] = array(
        'disp_name' => $strOverhead,
        'format'    => 'byte',
        'footer'    => 0,
    );

    $_url_params = array(
        'pos' => $pos,
        'dbstats' => $dbstats,
        'sort_by' => $sort_by,
        'sort_order' => $sort_order,
    );

    PMA_listNavigator($databases_count, $pos, $_url_params, 'server_databases.php', 'frame_content', $GLOBALS['cfg']['MaxDbList']);

    $_url_params['pos'] = $pos;

    echo '<form action="./server_databases.php" method="post" name="dbStatsForm" id="dbStatsForm">' . "\n"
       . PMA_generate_common_hidden_inputs($_url_params);

    $_url_params['sort_by'] = 'SCHEMA_NAME';
    $_url_params['sort_order'] = ($sort_by == 'SCHEMA_NAME' && $sort_order == 'asc') ? 'desc' : 'asc';

    echo '<table id="tabledatabases" class="data">' . "\n"
       . '<thead>' . "\n"
       . '<tr>' . "\n"
       . ($is_superuser || $cfg['AllowUserDropDatabase'] ? '        <th>&nbsp;</th>' . "\n" : '')
       . '    <th><a href="./server_databases.php' . PMA_generate_common_url($_url_params) . '">' . "\n"
       . '            ' . $strDatabase . "\n"
       . ($sort_by == 'SCHEMA_NAME' ? '                <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
       . '        </a></th>' . "\n";
    $table_columns = 3;
    foreach ($column_order as $stat_name => $stat) {
        if (array_key_exists($stat_name, $first_database)) {
            if ($stat['format'] === 'byte') {
                $table_columns += 2;
                $colspan = ' colspan="2"';
            } else {
                $table_columns++;
                $colspan = '';
            }
            $_url_params['sort_by'] = $stat_name;
            $_url_params['sort_order'] = ($sort_by == $stat_name && $sort_order == 'desc') ? 'asc' : 'desc';
            echo '    <th' . $colspan . '>'
                .'<a href="./server_databases.php' . PMA_generate_common_url($_url_params) . '">' . "\n"
                .'            ' . $stat['disp_name'] . "\n"
                .($sort_by == $stat_name ? '            <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
                .'        </a></th>' . "\n";
        }
    }
    if ($is_superuser) {
        echo '    <th>' . ($cfg['PropertiesIconic'] ? '&nbsp;' : $strAction) . "\n"
           . '    </th>' . "\n";
    }
    echo '</tr>' . "\n"
       . '</thead>' . "\n"
       . '<tbody>' . "\n";

    $odd_row = true;
    foreach ($databases as $current) {
        echo '<tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n";
        $odd_row = ! $odd_row;

        if ($is_superuser || $cfg['AllowUserDropDatabase']) {
            echo '    <td class="tool">' . "\n";
            if ($current['SCHEMA_NAME'] != 'mysql' && (PMA_MYSQL_INT_VERSION < 50002 || $current['SCHEMA_NAME'] != 'information_schema')) {
                echo '        <input type="checkbox" name="selected_dbs[]" title="' . htmlspecialchars($current['SCHEMA_NAME']) . '" value="' . htmlspecialchars($current['SCHEMA_NAME']) . '" ' . (empty($checkall) ? '' : 'checked="checked" ') . '/>' . "\n";
            } else {
                echo '        <input type="checkbox" name="selected_dbs[]" title="' . htmlspecialchars($current['SCHEMA_NAME']) . '" value="' . htmlspecialchars($current['SCHEMA_NAME']) . '" disabled="disabled"/>' . "\n";
            }
            echo '    </td>' . "\n";
        }
        echo '    <td class="name">' . "\n"
           . '        <a onclick="if (window.parent.openDb(\'' . urlencode($current['SCHEMA_NAME']) . '\')) return false;" href="index.php?' . $url_query . '&amp;db=' . urlencode($current['SCHEMA_NAME']) . '" title="' . sprintf($strJumpToDB, htmlspecialchars($current['SCHEMA_NAME'])) . '" target="_parent">' . "\n"
           . '            ' . htmlspecialchars($current['SCHEMA_NAME']) . "\n"
           . '        </a>' . "\n"
           . '    </td>' . "\n";

        foreach ($column_order as $stat_name => $stat) {
            if (array_key_exists($stat_name, $current)) {
                if (is_numeric($stat['footer'])) {
                    $column_order[$stat_name]['footer'] += $current[$stat_name];
                }
                if ($stat['format'] === 'byte') {
                    list($value, $unit) = PMA_formatByteDown($current[$stat_name], 3, 1);
                } elseif ($stat['format'] === 'number') {
                    $value = PMA_formatNumber($current[$stat_name], 0);
                } else {
                    $value = htmlentities($current[$stat_name], 0);
                }
                echo '    <td class="value">';
                if (isset($stat['description_function'])) {
                    echo '<dfn title="' . $stat['description_function']($current[$stat_name]) . '">';
                }
                echo $value;
                if (isset($stat['description_function'])) {
                    echo '</dfn>';
                }
                echo '</td>' . "\n";
                if ($stat['format'] === 'byte') {
                    echo '    <td class="unit">' . $unit . '</td>' . "\n";
                }
            }
        }

        if ($is_superuser) {
            echo '    <td class="tool">' . "\n"
               . '        <a onclick="window.parent.setDb(\'' . urlencode($current['SCHEMA_NAME']) . '\');" href="./server_privileges.php?' . $url_query . '&amp;checkprivs=' . urlencode($current['SCHEMA_NAME']) . '" title="' . sprintf($strCheckPrivsLong, htmlspecialchars($current['SCHEMA_NAME'])) . '">'. "\n"
               . '            ' .($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 's_rights.png" width="16" height="16" alt=" ' .$strCheckPrivs . '" /> ' : $strCheckPrivs). "\n"
               . '        </a></td>' . "\n";
        }
        echo '</tr>' . "\n";
    } // end foreach ($databases as $key => $current)
    unset($current, $odd_row);

    echo '<tr>' . "\n";
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        echo '    <th>&nbsp;</th>' . "\n";
    }
    echo '    <th>' . $strTotalUC . ': ' . $databases_count . '</th>' . "\n";
    foreach ($column_order as $stat_name => $stat) {
        if (array_key_exists($stat_name, $first_database)) {
            if ($stat['format'] === 'byte') {
                list($value, $unit) = PMA_formatByteDown($stat['footer'], 3, 1);
            } elseif ($stat['format'] === 'number') {
                $value = PMA_formatNumber($stat['footer'], 0);
            } else {
                $value = htmlentities($stat['footer'], 0);
            }
            echo '    <th class="value">';
            if (isset($stat['description_function'])) {
                echo '<dfn title="' . $stat['description_function']($stat['footer']) . '">';
            }
            echo $value;
            if (isset($stat['description_function'])) {
                echo '</dfn>';
            }
            echo '</th>' . "\n";
            if ($stat['format'] === 'byte') {
                echo '    <th class="unit">' . $unit . '</th>' . "\n";
            }
        }
    }
    if ($is_superuser) {
        echo '    <th>&nbsp;</th>' . "\n";
    }
    echo '</tr>' . "\n";
    echo '</tbody>' . "\n"
        .'</table>' . "\n";
    unset($column_order, $stat_name, $stat, $databases, $table_columns);

    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $common_url_query = PMA_generate_common_url() . '&amp;sort_by=' . $sort_by . '&amp;sort_order=' . $sort_order . '&amp;dbstats=' . $dbstats;
        echo '<img class="selectallarrow" src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png" width="38" height="22" alt="' . $strWithChecked . '" />' . "\n"
           . '<a href="./server_databases.php?' . $common_url_query . '&amp;checkall=1" onclick="if (markAllRows(\'tabledatabases\')) return false;">' . "\n"
           . '    ' . $strCheckAll . '</a> / ' . "\n"
           . '<a href="./server_databases.php?' . $common_url_query . '" onclick="if (unMarkAllRows(\'tabledatabases\')) return false;">' . "\n"
           . '    ' . $strUncheckAll . '</a>' . "\n"
           . '<i>' . $strWithChecked . '</i>' . "\n";
        PMA_buttonOrImage('drop_selected_dbs', 'mult_submit', 'drop_selected_dbs', $strDrop, 'b_deltbl.png');
    }

    echo '<ul><li id="li_switch_dbstats"><strong>' . "\n";
    if (empty($dbstats)) {
        echo '        <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1"'
            .' title="' . $strDatabasesStatsEnable . '">' . "\n"
            .'            ' . $strDatabasesStatsEnable;
    } else {
        echo '        <a href="./server_databases.php?' . $url_query . '"'
            .' title="' . $strDatabasesStatsDisable . '">' . "\n"
            .'            ' . $strDatabasesStatsDisable;
    }
    echo '</a></strong><br />' . "\n"
        .'        <div class="warning">'
        . $strDatabasesStatsHeavyTraffic . '</div></li>' . "\n"
        .'</ul>' . "\n";
    echo '</form>';
} else {
    echo $strNoDatabases;
}
unset($databases_count);

/**
 * Create new database.
 */
if ($cfg['ShowCreateDb']) {
    echo '<ul><li id="li_create_database">' . "\n";
    require './libraries/display_create_database.lib.php';
    echo '    </li>' . "\n";
    echo '</ul>' . "\n";
}

/**
 * Sends the footer
 */
require_once './libraries/footer.inc.php';

?>
