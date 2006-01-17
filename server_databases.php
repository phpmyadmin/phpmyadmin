<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Does the common work
 */
require_once('./libraries/common.lib.php');


$js_to_run = 'functions.js';
require('./libraries/server_common.inc.php');

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
function PMA_dbCmp($a, $b) {
    global $sort_by, $sort_order;
    if ($GLOBALS['cfg']['NaturalOrder']) {
        $sorter = 'strnatcmp';
    } else {
        $sorter = 'strcasecmp';
    }
    if ($sort_by == 'SCHEMA_NAME') {
        return ($sort_order == 'asc' ? 1 : -1) * $sorter($a['SCHEMA_NAME'], $b['SCHEMA_NAME']);
    } elseif ($a[$sort_by] == $b[$sort_by]) {
        return $sorter($a['SCHEMA_NAME'], $b['SCHEMA_NAME']);
    } else {
        return ($sort_order == 'asc' ? 1 : -1) * ((int)$a[$sort_by] > (int)$b[$sort_by] ? 1 : -1);
    }
} // end of the 'PMA_dbCmp()' function


/**
 * avoids 'undefined index' errors
 */
if (empty($sort_by)) {
    $sort_by = 'SCHEMA_NAME';
} else {
    $sort_by = PMA_sanitize($sort_by);
}
if (empty($sort_order)) {
    if ($sort_by == 'SCHEMA_NAME') {
        $sort_order = 'asc';
    } else {
        $sort_order = 'desc';
    }
} else {
    $sort_order = PMA_sanitize($sort_order);
}

$dbstats = empty( $dbstats ) ? 0 : 1;


/**
 * Drops multiple databases
 */

// workaround for IE behavior (it returns some coordinates based on where
// the mouse was on the Drop image):

if (isset($drop_selected_dbs_x)) {
    $drop_selected_dbs = 'Drop';
}

if ((!empty($drop_selected_dbs) || isset($query_type)) && ($is_superuser || $cfg['AllowUserDropDatabase'])) {
    if ((! isset($selected_db) || ! strlen($selected_db)) && ! (isset($query_type) && !empty($selected))) {
        $message = $strNoDatabasesSelected;
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db' ;
        $err_url = 'server_databases.php?' . PMA_generate_common_url();
        require('./libraries/mult_submits.inc.php');
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
require('./libraries/server_links.inc.php');


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ( $GLOBALS['cfg']['MainPageIconic']
      ? '<img class="icon" src="' . $pmaThemeImage . 's_db.png" width="16"'
        .' height="16" alt="" />'
      : '' )
   . ( $dbstats ? $strDatabasesStats : $strDatabases ) . "\n"
   .'</h2>' . "\n";

/**
 * Gets the databases list
 */
if ($server > 0) {
    $databases = PMA_DBI_get_databases_full(null, $dbstats);
} else {
    $databases = array();
}


/**
 * Displays the page
 */
if (count($databases) > 0) {
    // sorts the array
    usort( $databases, 'PMA_dbCmp' );

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

    echo '<form action="./server_databases.php" method="post" name="dbStatsForm" id="dbStatsForm">' . "\n"
       . PMA_generate_common_hidden_inputs('', '', 1)
       . '<input type="hidden" name="dbstats" value="' . $dbstats . '" />' . "\n"
       . '<input type="hidden" name="sort_by" value="' . $sort_by . '" />' . "\n"
       . '<input type="hidden" name="sort_order" value="' . $sort_order . '" />' . "\n"
       . '<table id="tabledatabases" class="data">' . "\n"
       . '<thead>' . "\n"
       . '<tr>' . "\n"
       . ($is_superuser || $cfg['AllowUserDropDatabase'] ? '        <th>&nbsp;</th>' . "\n" : '')
       . '    <th><a href="./server_databases.php?' . $url_query . '&amp;dbstats=' . $dbstats . '&amp;sort_by=SCHEMA_NAME&amp;sort_order=' . (($sort_by == 'SCHEMA_NAME' && $sort_order == 'asc') ? 'desc' : 'asc') . '">' . "\n"
       . '            ' . $strDatabase . "\n"
       . ($sort_by == 'SCHEMA_NAME' ? '                <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
       . '        </a></th>' . "\n";
    $table_columns = 3;
    foreach ( $column_order as $stat_name => $stat ) {
        if ( array_key_exists( $stat_name, $databases[0] ) ) {
            if ( $stat['format'] === 'byte' ) {
                $table_columns += 2;
                $colspan = ' colspan="2"';
            } else {
                $table_columns++;
                $colspan = '';
            }
            echo '    <th' . $colspan . '>'
                .'<a href="./server_databases.php?' . $url_query . '&amp;dbstats=' . (int) $dbstats . '&amp;sort_by=' . urlencode( $stat_name ) . '&amp;sort_order=' . (($sort_by == $stat_name && $sort_order == 'desc') ? 'asc' : 'desc') . '">' . "\n"
                .'            ' . $stat['disp_name'] . "\n"
                .($sort_by == $stat_name ? '            <img class="icon" src="' . $pmaThemeImage . 's_' . $sort_order . '.png" width="11" height="9"  alt="' . ($sort_order == 'asc' ? $strAscending : $strDescending) . '" />' . "\n" : '')
                .'        </a></th>' . "\n";
        }
    }
    if ($is_superuser) {
        echo '    <th>' . ($cfg['PropertiesIconic'] ? '&nbsp;' : $strAction ) . "\n"
           . '    </th>' . "\n";
    }
    echo '</tr>' . "\n"
       . '</thead>' . "\n"
       . '<tbody>' . "\n";

    $odd_row = true;
    foreach ( $databases as $key => $current ) {

        echo '<tr class="' . ( $odd_row ? 'odd' : 'even' ) . '">' . "\n";
        $odd_row = ! $odd_row;

        if ( $is_superuser || $cfg['AllowUserDropDatabase'] ) {
            echo '    <td class="tool">' . "\n";
            if ($current['SCHEMA_NAME'] != 'mysql' && (PMA_MYSQL_INT_VERSION < 50002 || $current['SCHEMA_NAME'] != 'information_schema')) {
                echo '        <input type="checkbox" name="selected_db[]" title="' . htmlspecialchars($current['SCHEMA_NAME']) . '" value="' . htmlspecialchars($current['SCHEMA_NAME']) . '" ' . (empty($checkall) ? '' : 'checked="checked" ') . '/>' . "\n";
            } else {
                echo '        <input type="checkbox" name="selected_db[]" title="' . htmlspecialchars($current['SCHEMA_NAME']) . '" value="' . htmlspecialchars($current['SCHEMA_NAME']) . '" disabled="disabled"/>' . "\n";
            }
            echo '    </td>' . "\n";
        }
        echo '    <td class="name">' . "\n"
           . '        <a onclick="if ( window.parent.openDb(\'' . urlencode($current['SCHEMA_NAME']) . '\') ) return false;" href="index.php?' . $url_query . '&amp;db=' . urlencode($current['SCHEMA_NAME']) . '" title="' . sprintf($strJumpToDB, htmlspecialchars($current['SCHEMA_NAME'])) . '" target="_parent">' . "\n"
           . '            ' . htmlspecialchars($current['SCHEMA_NAME']) . "\n"
           . '        </a>' . "\n"
           . '    </td>' . "\n";

        foreach ( $column_order as $stat_name => $stat ) {
            if ( array_key_exists( $stat_name, $current ) ) {
                if ( is_numeric( $stat['footer'] ) ) {
                    $column_order[$stat_name]['footer'] += $current[$stat_name];
                }
                if ( $stat['format'] === 'byte' ) {
                    list( $value, $unit ) = PMA_formatByteDown( $current[$stat_name], 3, 1 );
                } elseif ( $stat['format'] === 'number' ) {
                    $value = PMA_formatNumber( $current[$stat_name], 0 );
                } else {
                    $value = htmlentities( $current[$stat_name], 0 );
                }
                echo '    <td class="value">';
                if ( isset( $stat['description_function'] ) ) {
                    echo '<dfn title="' . $stat['description_function']( $current[$stat_name] ) . '">';
                }
                echo $value;
                if ( isset( $stat['description_function'] ) ) {
                    echo '</dfn>';
                }
                echo '</td>' . "\n";
                if ( $stat['format'] === 'byte' ) {
                    echo '    <td class="unit">' . $unit . '</td>' . "\n";
                }
            }
        }

        if ($is_superuser) {
            echo '    <td class="tool">' . "\n"
               . '        <a onclick="window.parent.setDb(\'' . urlencode($current['SCHEMA_NAME']) . '\');" href="./server_privileges.php?' . $url_query . '&amp;checkprivs=' . urlencode($current['SCHEMA_NAME']) . '" title="' . sprintf($strCheckPrivsLong, htmlspecialchars($current['SCHEMA_NAME'])) . '">'. "\n"
               . '            ' .($cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 's_rights.png" width="16" height="16" alt=" ' .$strCheckPrivs . '" /> ' : $strCheckPrivs ). "\n"
               . '        </a></td>' . "\n";
        }
        echo '</tr>' . "\n";
    } // end foreach ( $databases as $key => $current )
    unset( $key, $current, $odd_row );

    echo '<tr>' . "\n";
    if ( $is_superuser || $cfg['AllowUserDropDatabase'] ) {
        echo '    <th>&nbsp;</th>' . "\n";
    }
    echo '    <th>' . $strTotalUC . ': ' . count( $databases ) . '</th>' . "\n";
    foreach ( $column_order as $stat_name => $stat ) {
        if ( array_key_exists( $stat_name, $databases[0] ) ) {
            if ( $stat['format'] === 'byte' ) {
                list( $value, $unit ) = PMA_formatByteDown( $stat['footer'], 3, 1 );
            } elseif ( $stat['format'] === 'number' ) {
                $value = PMA_formatNumber( $stat['footer'], 0 );
            } else {
                $value = htmlentities( $stat['footer'], 0 );
            }
            echo '    <th class="value">';
            if ( isset( $stat['description_function'] ) ) {
                echo '<dfn title="' . $stat['description_function']( $stat['footer'] ) . '">';
            }
            echo $value;
            if ( isset( $stat['description_function'] ) ) {
                echo '</dfn>';
            }
            echo '</th>' . "\n";
            if ( $stat['format'] === 'byte' ) {
                echo '    <th class="unit">' . $unit . '</th>' . "\n";
            }
        }
    }
    if ( $is_superuser ) {
        echo '    <th>&nbsp;</th>' . "\n";
    }
    echo '</tr>' . "\n";
    echo '</tbody>' . "\n"
        .'</table>' . "\n";
    unset( $column_order, $stat_name, $stat, $databases, $table_columns );

    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $common_url_query = PMA_generate_common_url() . '&amp;sort_by=' . $sort_by . '&amp;sort_order=' . $sort_order . '&amp;dbstats=' . $dbstats;
        echo '<img class="selectallarrow" src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png" width="38" height="22" alt="' . $strWithChecked . '" />' . "\n"
           . '<a href="./server_databases.php?' . $common_url_query . '&amp;checkall=1" onclick="if ( markAllRows(\'tabledatabases\') ) return false;">' . "\n"
           . '    ' . $strCheckAll . '</a> / ' . "\n"
           . '<a href="./server_databases.php?' . $common_url_query . '" onclick="if ( unMarkAllRows(\'tabledatabases\') ) return false;">' . "\n"
           . '    ' . $strUncheckAll . '</a>' . "\n"
           . '<i>' . $strWithChecked . '</i>' . "\n";
        PMA_buttonOrImage( 'drop_selected_dbs', 'mult_submit', 'drop_selected_dbs', $strDrop, 'b_deltbl.png' );
    }

    if ( PMA_MYSQL_INT_VERSION < 50002 ) {
        echo '<ul><li id="li_switch_dbstats"><strong>' . "\n";
        if ( empty( $dbstats ) ) {
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
    }
    echo '</form>';
} else {
    echo $strNoDatabases;
}

/**
 * Create new database.
 */
echo '<ul><li id="li_create_database">' . "\n";
require('./libraries/display_create_database.lib.php');
echo '    </li>' . "\n";
echo '</ul>' . "\n";

/**
 * Sends the footer
 */
require_once('./libraries/footer.inc.php');

?>
