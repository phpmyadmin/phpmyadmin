<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Does the common work
 */
require_once './libraries/common.inc.php';

require './libraries/server_common.inc.php';
if (!PMA_DRIZZLE) {
    include_once './libraries/replication.inc.php';
} else {
    $replication_types = array();
    $replication_info = null;
}
require './libraries/build_html_for_db.lib.php';

/**
 * avoids 'undefined index' errors
 */
if (empty($_REQUEST['sort_by'])) {
    $sort_by = 'SCHEMA_NAME';
} else {
    $sort_by_whitelist = array(
        'SCHEMA_NAME',
        'DEFAULT_COLLATION_NAME',
        'SCHEMA_TABLES',
        'SCHEMA_TABLE_ROWS',
        'SCHEMA_DATA_LENGTH',
        'SCHEMA_INDEX_LENGTH',
        'SCHEMA_LENGTH',
        'SCHEMA_DATA_FREE'
    );
    if (in_array($_REQUEST['sort_by'], $sort_by_whitelist)) {
        $sort_by = $_REQUEST['sort_by'];
    } else {
        $sort_by = 'SCHEMA_NAME';
    }
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
        $message = PMA_Message::error(__('No databases selected.'));
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db' ;
        $err_url = 'server_databases.php?' . PMA_generate_common_url();
        if (isset($_REQUEST['selected_dbs'])) {
            $selected_db = $_REQUEST['selected_dbs'];
        }
        include './libraries/mult_submits.inc.php';
        unset($action, $submit_mult, $err_url, $selected_db, $GLOBALS['db']);
        if (empty($message)) {
            $message = PMA_Message::success(__('%s databases have been dropped successfully.'));
            if ($mult_btn == __('Yes')) {
                $message->addParam(count($selected));
            } else {
                $message->addParam(0);
            }
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
      ? PMA_getImage('s_db.png')
      : '')
   . ($dbstats ? __('Databases statistics') : __('Databases')) . "\n"
   .'</h2>' . "\n";

/**
 * Create database.
 */
if ($cfg['ShowCreateDb']) {
    echo '<ul><li id="li_create_database">' . "\n";
    include './libraries/display_create_database.lib.php';
    echo '    </li>' . "\n";
    echo '</ul>' . "\n";
}

/**
 * Gets the databases list
 */
if ($server > 0) {
    $databases = PMA_DBI_get_databases_full(null, $dbstats, null, $sort_by,
        $sort_order, $pos, true);
    $databases_count = count($GLOBALS['pma']->databases);
} else {
    $databases_count = 0;
}


/**
 * Displays the page
 */
if ($databases_count > 0) {
    echo '<div id="tableslistcontainer">';
    reset($databases);
    $first_database = current($databases);
    // table col order
    $column_order = PMA_getColumnOrder();

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
       . ($is_superuser || $cfg['AllowUserDropDatabase'] ? '        <th></th>' . "\n" : '')
       . '    <th><a href="./server_databases.php' . PMA_generate_common_url($_url_params) . '">' . "\n"
       . '            ' . __('Database') . "\n"
       . ($sort_by == 'SCHEMA_NAME' ? '                ' . PMA_getImage('s_' . $sort_order . '.png', ($sort_order == 'asc' ? __('Ascending') : __('Descending'))) . "\n" : '')
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
                .($sort_by == $stat_name ? '            ' . PMA_getImage('s_' . $sort_order . '.png', ($sort_order == 'asc' ? __('Ascending') : __('Descending'))) . "\n" : '')
                .'        </a></th>' . "\n";
        }
    }

    foreach ($replication_types as $type) {
      if ($type=="master")
        $name = __('Master replication');
      elseif ($type == "slave")
        $name = __('Slave replication');
      if (${"server_{$type}_status"})
        echo '    <th>'. $name .'</th>' . "\n";
    }

    if ($is_superuser && !PMA_DRIZZLE) {
        echo '    <th>' . ($cfg['PropertiesIconic'] ? '' : __('Action')) . "\n"
           . '    </th>' . "\n";
    }
    echo '</tr>' . "\n"
       . '</thead>' . "\n"
       . '<tbody>' . "\n";

    $odd_row = true;
    foreach ($databases as $current) {
        $tr_class = $odd_row ? 'odd' : 'even';
        if (PMA_is_system_schema($current['SCHEMA_NAME'], true)) {
            $tr_class .= ' noclick';
        }
        echo '<tr class="' . $tr_class . '">' . "\n";
        $odd_row = ! $odd_row;

        list($column_order, $generated_html) = PMA_buildHtmlForDb($current, $is_superuser, (isset($checkall) ? $checkall : ''), $url_query, $column_order, $replication_types, $replication_info);

        echo $generated_html;

        echo '</tr>' . "\n";
    } // end foreach ($databases as $key => $current)
    unset($current, $odd_row);

    echo '<tr id="db_summary_row">' . "\n";
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        echo '    <th></th>' . "\n";
    }
    echo '    <th>' . __('Total') . ': <span id="databases_count">' . $databases_count . '</span></th>' . "\n";
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

    foreach ($replication_types as $type) {
        if (${"server_{$type}_status"}) {
            echo '    <th></th>' . "\n";
        }
    }

    if ($is_superuser) {
        echo '    <th></th>' . "\n";
    }
    echo '</tr>' . "\n";
    echo '</tbody>' . "\n"
        .'</table>' . "\n";
    unset($column_order, $stat_name, $stat, $databases, $table_columns);

    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $common_url_query = PMA_generate_common_url(array('sort_by' => $sort_by, 'sort_order' => $sort_order, 'dbstats' => $dbstats));
        echo '<img class="selectallarrow" src="' . $pmaThemeImage . 'arrow_' . $text_dir . '.png" width="38" height="22" alt="' . __('With selected:') . '" />' . "\n"
           . '<a href="./server_databases.php' . $common_url_query . '&amp;checkall=1" onclick="if (markAllRows(\'tabledatabases\')) return false;">' . "\n"
           . '    ' . __('Check All') . '</a> / ' . "\n"
           . '<a href="./server_databases.php' . $common_url_query . '" onclick="if (unMarkAllRows(\'tabledatabases\')) return false;">' . "\n"
           . '    ' . __('Uncheck All') . '</a>' . "\n"
           . '<i>' . __('With selected:') . '</i>' . "\n";
        PMA_buttonOrImage('drop_selected_dbs', 'mult_submit', 'drop_selected_dbs', __('Drop'), 'b_deltbl.png');
    }

    if (empty($dbstats)) {
        echo '<ul><li id="li_switch_dbstats"><strong>' . "\n";
            echo '        <a href="./server_databases.php?' . $url_query . '&amp;dbstats=1"'
                .' title="' . __('Enable Statistics') . '">' . "\n"
                .'            ' . __('Enable Statistics');
        echo '</a></strong><br />' . "\n";
        PMA_Message::notice(__('Note: Enabling the database statistics here might cause heavy traffic between the web server and the MySQL server.'))->display();
        echo '</li>' . "\n" . '</ul>' . "\n";
    }
    echo '</form>';
    echo '</div>';
} else {
    echo __('No databases');
}
unset($databases_count);

/**
 * Sends the footer
 */
require './libraries/footer.inc.php';

?>
