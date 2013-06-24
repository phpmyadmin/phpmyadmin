<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Does the common work
 */
require_once 'libraries/common.inc.php';
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_databases.js');

require 'libraries/server_common.inc.php';
if (! PMA_DRIZZLE) {
    include_once 'libraries/replication.inc.php';
} else {
    $replication_types = array();
    $replication_info = null;
}
require 'libraries/build_html_for_db.lib.php';

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'mult_btn',
    'query_type',
    'selected'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

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
    && strtolower($_REQUEST['sort_order']) == 'desc'
) {
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
    && ($is_superuser || $cfg['AllowUserDropDatabase'])
) {
    if (! isset($_REQUEST['selected_dbs']) && ! isset($_REQUEST['query_type'])) {
        $message = PMA_Message::error(__('No databases selected.'));
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db';
        $err_url = 'server_databases.php?' . PMA_generate_common_url();
        if (isset($_REQUEST['selected_dbs'])
            && !isset($_REQUEST['is_js_confirmed'])
        ) {
            $selected_db = $_REQUEST['selected_dbs'];
        }
        if (isset($_REQUEST['is_js_confirmed'])) {
            $_REQUEST = array(
                'query_type' => $submit_mult,
                'selected' => $_REQUEST['selected_dbs'],
                'mult_btn' => __('Yes'),
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table']);
        }
        include 'libraries/mult_submits.inc.php';
        unset($action, $submit_mult, $err_url, $selected_db, $GLOBALS['db']);
        if (empty($message)) {
            if ($mult_btn == __('Yes')) {
                $number_of_databases = count($selected);
            } else {
                $number_of_databases = 0;
            }
            $message = PMA_Message::success(
                _ngettext(
                    '%1$d database has been dropped successfully.',
                    '%1$d databases have been dropped successfully.',
                    $number_of_databases
                )
            );
            $message->addParam($number_of_databases);
        }
    }
    if ($GLOBALS['is_ajax_request'] && $message instanceof PMA_Message) {
        $response = PMA_Response::getInstance();
        $response->isSuccess($message->isSuccess());
        $response->addJSON('message', $message);
        exit;
    }
}

/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . PMA_Util::getImage('s_db.png')
   . ($dbstats ? __('Databases statistics') : __('Databases')) . "\n"
   .'</h2>' . "\n";

/**
 * Create database.
 */
if ($cfg['ShowCreateDb']) {
    echo '<ul><li id="li_create_database" class="no_bullets">' . "\n";
    include 'libraries/display_create_database.lib.php';
    echo '    </li>' . "\n";
    echo '</ul>' . "\n";
}

/**
 * Gets the databases list
 */
if ($server > 0) {
    $databases = $GLOBALS['dbi']->getDatabasesFull(
        null, $dbstats, null, $sort_by, $sort_order, $pos, true
    );
    $databases_count = count($GLOBALS['pma']->databases);
} else {
    $databases_count = 0;
}


/**
 * Displays the page
 */
$html = '';
if ($databases_count > 0) {
    $html .= '<div id="tableslistcontainer">';
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

    $html .= PMA_Util::getListNavigator(
        $databases_count, $pos, $_url_params, 'server_databases.php',
        'frame_content', $GLOBALS['cfg']['MaxDbList']
    );

    $_url_params['pos'] = $pos;
    $_url_params['drop_selected_dbs'] = 1;

    $html .= '<form class="ajax" action="server_databases.php" ';
    $html .= 'method="post" name="dbStatsForm" id="dbStatsForm">' . "\n";
    $html .= PMA_generate_common_hidden_inputs($_url_params);

    $_url_params['sort_by'] = 'SCHEMA_NAME';
    $_url_params['sort_order'] = ($sort_by == 'SCHEMA_NAME' && $sort_order == 'asc') ? 'desc' : 'asc';

    $html .= '<table id="tabledatabases" class="data">' . "\n"
        . '<thead>' . "\n"
        . '<tr>' . "\n"
        . ($is_superuser || $cfg['AllowUserDropDatabase'] ? '        <th></th>' . "\n" : '')
        . '    <th><a href="server_databases.php' . PMA_generate_common_url($_url_params) . '">' . "\n"
        . '            ' . __('Database') . "\n"
        . ($sort_by == 'SCHEMA_NAME' ? '                ' . PMA_Util::getImage('s_' . $sort_order . '.png', ($sort_order == 'asc' ? __('Ascending') : __('Descending'))) . "\n" : '')
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
            $html .= '    <th' . $colspan . '>'
                . '<a href="server_databases.php' . PMA_generate_common_url($_url_params) . '">' . "\n"
                . '            ' . $stat['disp_name'] . "\n"
                . ($sort_by == $stat_name ? '            ' . PMA_Util::getImage('s_' . $sort_order . '.png', ($sort_order == 'asc' ? __('Ascending') : __('Descending'))) . "\n" : '')
                . '        </a></th>' . "\n";
        }
    }

    foreach ($replication_types as $type) {
        if ($type=="master") {
            $name = __('Master replication');
        } elseif ($type == "slave") {
            $name = __('Slave replication');
        }
        if (${"server_{$type}_status"}) {
            $html .= '    <th>'. $name .'</th>' . "\n";
        }
    }

    if ($is_superuser && ! PMA_DRIZZLE) {
        $html .= '    <th>'
            . ($cfg['ActionLinksMode'] == 'icons' ? '' : __('Action')) . "\n"
            . '    </th>' . "\n";
    }
    $html .= '</tr>' . "\n"
        . '</thead>' . "\n"
        . '<tbody>' . "\n";

    $odd_row = true;
    foreach ($databases as $current) {
        $tr_class = $odd_row ? 'odd' : 'even';
        if ($GLOBALS['dbi']->isSystemSchema($current['SCHEMA_NAME'], true)) {
            $tr_class .= ' noclick';
        }
        $html .= '<tr class="' . $tr_class . '">' . "\n";
        $odd_row = ! $odd_row;

        list($column_order, $generated_html) = PMA_buildHtmlForDb(
            $current,
            $is_superuser,
            $url_query,
            $column_order,
            $replication_types,
            $replication_info
        );

        $html .= $generated_html;

        $html .= '</tr>' . "\n";
    } // end foreach ($databases as $key => $current)
    unset($current, $odd_row);

    $html .= '</tbody><tfoot><tr>' . "\n";
    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $html .= '    <th></th>' . "\n";
    }
    $html .= '    <th>' . __('Total') . ': <span id="databases_count">'
        . $databases_count . '</span></th>' . "\n";
    foreach ($column_order as $stat_name => $stat) {
        if (array_key_exists($stat_name, $first_database)) {
            if ($stat['format'] === 'byte') {
                list($value, $unit) = PMA_Util::formatByteDown($stat['footer'], 3, 1);
            } elseif ($stat['format'] === 'number') {
                $value = PMA_Util::formatNumber($stat['footer'], 0);
            } else {
                $value = htmlentities($stat['footer'], 0);
            }
            $html .= '    <th class="value">';
            if (isset($stat['description_function'])) {
                $html .= '<dfn title="'
                    . $stat['description_function']($stat['footer']) . '">';
            }
            $html .= $value;
            if (isset($stat['description_function'])) {
                $html .= '</dfn>';
            }
            $html .= '</th>' . "\n";
            if ($stat['format'] === 'byte') {
                $html .= '    <th class="unit">' . $unit . '</th>' . "\n";
            }
        }
    }

    foreach ($replication_types as $type) {
        if (${"server_{$type}_status"}) {
            $html .= '    <th></th>' . "\n";
        }
    }

    if ($is_superuser) {
        $html .= '    <th></th>' . "\n";
    }
    $html .= '</tr>' . "\n";
    $html .= '</tfoot>' . "\n"
        .'</table>' . "\n";
    unset($column_order, $stat_name, $stat, $databases, $table_columns);

    if ($is_superuser || $cfg['AllowUserDropDatabase']) {
        $common_url_query = PMA_generate_common_url(
            array(
                'sort_by' => $sort_by,
                'sort_order' => $sort_order,
                'dbstats' => $dbstats
            )
        );
        $html .= '<img class="selectallarrow" src="'
            . $pmaThemeImage . 'arrow_' . $text_dir . '.png"'
            . ' width="38" height="22" alt="' . __('With selected:') . '" />' . "\n"
            . '<input type="checkbox" id="dbStatsForm_checkall" class="checkall_box" '
            . 'title="' . __('Check All') . '" /> '
            . '<label for="dbStatsForm_checkall">' . __('Check All') . '</label> '
            . '<i style="margin-left: 2em">' . __('With selected:') . '</i>' . "\n";
        $html .= PMA_Util::getButtonOrImage(
            '',
            'mult_submit' . ' ajax',
            'drop_selected_dbs',
            __('Drop'), 'b_deltbl.png'
        );
    }

    if (empty($dbstats)) {
        $html .= '<ul><li id="li_switch_dbstats"><strong>' . "\n";
        $html .= '<a href="server_databases.php?' . $url_query . '&amp;dbstats=1"'
            . ' title="' . __('Enable Statistics') . '">' . "\n"
            . '            ' . __('Enable Statistics');
        $html .= '</a></strong><br />' . "\n";
        PMA_Message::notice(
            __('Note: Enabling the database statistics here might cause heavy traffic between the web server and the MySQL server.')
        )->display();
        $html .= '</li>' . "\n" . '</ul>' . "\n";
    }
    $html .= '</form>';
    $html .= '</div>';
} else {
    $html .= __('No databases');
}
unset($databases_count);

echo $html;

?>
