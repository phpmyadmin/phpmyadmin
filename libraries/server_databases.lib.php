<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server databases
 *
 * @usedby  server_databases.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns the html for Database List
 *
 * @param Array  $databases         GBI return databases
 * @param int    $databases_count   database count
 * @param int    $pos               display pos
 * @param Array  $dbstats           database status
 * @param string $sort_by           sort by string
 * @param string $sort_order        sort order string
 * @param bool   $is_superuser      User status
 * @param Array  $cfg               configuration
 * @param array  $replication_types replication types
 * @param array  $replication_info  replication info
 * @param string $url_query         url query
 *
 * @return string
 */
function PMA_getHtmlForDatabase(
    $databases, $databases_count, $pos, $dbstats,
    $sort_by, $sort_order, $is_superuser, $cfg,
    $replication_types, $replication_info, $url_query
) {
    $html = '<div id="tableslistcontainer">';
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

    $html .= '<form class="ajax" action="server_databases.php" ';
    $html .= 'method="post" name="dbStatsForm" id="dbStatsForm">' . "\n";
    $html .= PMA_URL_getHiddenInputs($_url_params);

    $_url_params['sort_by'] = 'SCHEMA_NAME';
    $_url_params['sort_order']
        = ($sort_by == 'SCHEMA_NAME' && $sort_order == 'asc') ? 'desc' : 'asc';

    $html .= '<table id="tabledatabases" class="data">' . "\n"
        . '<thead>' . "\n"
        . '<tr>' . "\n";

    $html .= PMA_getHtmlForColumnOrderWithSort(
        $is_superuser,
        $cfg['AllowUserDropDatabase'],
        $_url_params,
        $sort_by,
        $sort_order,
        $column_order,
        $first_database
    );

    $html .= PMA_getHtmlForReplicationType(
        $is_superuser,
        $replication_types,
        $cfg['ActionLinksMode']
    );

    $html .= '</tr>' . "\n"
        . '</thead>' . "\n";

    list($output, $column_order) = PMA_getHtmlAndColumnOrderForDatabaseList(
        $databases,
        $is_superuser,
        $url_query,
        $column_order,
        $replication_types,
        $replication_info
    );
    $html .= $output;
    unset($output);

    $html .= PMA_getHtmlForTableFooter(
        $cfg['AllowUserDropDatabase'],
        $is_superuser,
        $databases_count,
        $column_order,
        $replication_types,
        $first_database
    );

    $html .= '</table>' . "\n";

    $html .= PMA_getHtmlForTableFooterButtons(
        $cfg['AllowUserDropDatabase'],
        $is_superuser
    );

    if (empty($dbstats)) {
        //we should put notice above database list
        $html = PMA_getHtmlForNoticeEnableStatistics($url_query, $html);
    }
    $html .= '</form>';
    $html .= '</div>';

    return $html;
}

/**
 * Returns the html for Table footer buttons
 *
 * @param bool $is_allowUserDropDb Allow user drop database
 * @param bool $is_superuser       User status
 *
 * @return string
 */
function PMA_getHtmlForTableFooterButtons($is_allowUserDropDb, $is_superuser)
{
    if (!$is_superuser && !$is_allowUserDropDb) {
        return '';
    }

    $html = PMA_Util::getWithSelected(
        $GLOBALS['pmaThemeImage'], $GLOBALS['text_dir'], "dbStatsForm"
    );
    $html .= PMA_Util::getButtonOrImage(
        '',
        'mult_submit' . ' ajax',
        'drop_selected_dbs',
        __('Drop'), 'b_deltbl.png'
    );

    return $html;
}

/**
 * Returns the html for Table footer
 *
 * @param bool   $is_allowUserDropDb Allow user drop database
 * @param bool   $is_superuser       User status
 * @param int    $databases_count    Database count
 * @param string $column_order       column order
 * @param array  $replication_types  replication types
 * @param string $first_database     First database
 *
 * @return string
 */
function PMA_getHtmlForTableFooter(
    $is_allowUserDropDb, $is_superuser,
    $databases_count, $column_order,
    $replication_types, $first_database
) {
    $html = '<tfoot><tr>' . "\n";
    if ($is_superuser || $is_allowUserDropDb) {
        $html .= '    <th></th>' . "\n";
    }
    $html .= '    <th>' . __('Total') . ': <span id="databases_count">'
        . $databases_count . '</span></th>' . "\n";

    $html .= PMA_getHtmlForColumnOrder($column_order, $first_database);

    foreach ($replication_types as $type) {
        if ($GLOBALS['replication_info'][$type]['status']) {
            $html .= '    <th></th>' . "\n";
        }
    }

    if ($is_superuser) {
        $html .= '    <th></th>' . "\n";
    }
    $html .= '</tr>' . "\n";
    $html .= '</tfoot>' . "\n";
    return $html;
}

/**
 * Returns the html for Database List and Column order
 *
 * @param array  $databases         GBI return databases
 * @param bool   $is_superuser      User status
 * @param string $url_query         Url query
 * @param array  $column_order      column order
 * @param array  $replication_types replication types
 * @param array  $replication_info  replication info
 *
 * @return Array
 */
function PMA_getHtmlAndColumnOrderForDatabaseList(
    $databases, $is_superuser, $url_query,
    $column_order, $replication_types, $replication_info
) {
    $odd_row = true;
    $html = '<tbody>' . "\n";

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
    $html .= '</tbody>';
    return array($html, $column_order);
}

/**
 * Returns the html for Column Order
 *
 * @param array $column_order   Column order
 * @param array $first_database The first display database
 *
 * @return string
 */
function PMA_getHtmlForColumnOrder($column_order, $first_database)
{
    $html = "";
    // avoid execution path notice
    $unit = "";
    foreach ($column_order as $stat_name => $stat) {
        if (array_key_exists($stat_name, $first_database)) {
            if ($stat['format'] === 'byte') {
                list($value, $unit)
                    = PMA_Util::formatByteDown($stat['footer'], 3, 1);
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

    return $html;
}


/**
 * Returns the html for Column Order with Sort
 *
 * @param bool   $is_superuser       User status
 * @param bool   $is_allowUserDropDb Allow user drop database
 * @param Array  $_url_params        Url params
 * @param string $sort_by            sort column name
 * @param string $sort_order         order
 * @param array  $column_order       column order
 * @param array  $first_database     database to show
 *
 * @return string
 */
function PMA_getHtmlForColumnOrderWithSort(
    $is_superuser, $is_allowUserDropDb,
    $_url_params, $sort_by, $sort_order,
    $column_order, $first_database
) {
    $html = ($is_superuser || $is_allowUserDropDb
        ? '        <th></th>' . "\n"
        : '')
        . '    <th><a href="server_databases.php'
        . PMA_URL_getCommon($_url_params) . '">' . "\n"
        . '            ' . __('Database') . "\n"
        . ($sort_by == 'SCHEMA_NAME'
            ? '                ' . PMA_Util::getImage(
                's_' . $sort_order . '.png',
                ($sort_order == 'asc' ? __('Ascending') : __('Descending'))
            ) . "\n"
            : ''
          )
        . '        </a></th>' . "\n";
    $table_columns = 3;
    foreach ($column_order as $stat_name => $stat) {
        if (!array_key_exists($stat_name, $first_database)) {
            continue;
        }

        if ($stat['format'] === 'byte') {
            $table_columns += 2;
            $colspan = ' colspan="2"';
        } else {
            $table_columns++;
            $colspan = '';
        }
        $_url_params['sort_by'] = $stat_name;
        $_url_params['sort_order']
            = ($sort_by == $stat_name && $sort_order == 'desc') ? 'asc' : 'desc';
        $html .= '    <th' . $colspan . '>'
            . '<a href="server_databases.php'
            . PMA_URL_getCommon($_url_params) . '">' . "\n"
            . '            ' . $stat['disp_name'] . "\n"
            . ($sort_by == $stat_name
                ? '            ' . PMA_Util::getImage(
                    's_' . $sort_order . '.png',
                    ($sort_order == 'asc' ? __('Ascending') : __('Descending'))
                ) . "\n"
                : ''
              )
            . '        </a></th>' . "\n";
    }
    return $html;
}


/**
 * Returns the html for Enable Statistics
 *
 * @param string $url_query Url query
 * @param string $html      html for database list
 *
 * @return string
 */
function PMA_getHtmlForNoticeEnableStatistics($url_query, $html)
{
    $notice = PMA_Message::notice(
        __(
            'Note: Enabling the database statistics here might cause '
            . 'heavy traffic between the web server and the MySQL server.'
        )
    )->getDisplay();
    $html .= $notice;

    $items = array();
    $items[] = array(
        'content' => '<strong>' . "\n"
            . __('Enable statistics')
            . '</strong><br />' . "\n",
        'class' => 'li_switch_dbstats',
        'url' => array(
            'href' => 'server_databases.php' . $url_query . '&amp;dbstats=1',
            'title' => __('Enable statistics')
        ),
    );

    include_once './libraries/Template.class.php';
    $html .= PMA\Template::get('list/unordered')->render(
        array('items' => $items,)
    );

    return $html;
}

/**
 * Returns the html for database replication types
 *
 * @param bool  $is_superuser      User status
 * @param Array $replication_types replication types
 * @param bool  $cfg_iconic        cfg about Properties Iconic
 *
 * @return string
 */
function PMA_getHtmlForReplicationType(
    $is_superuser, $replication_types, $cfg_iconic
) {
    $html = '';
    foreach ($replication_types as $type) {
        if ($type == "master") {
            $name = __('Master replication');
        } elseif ($type == "slave") {
            $name = __('Slave replication');
        }

        if ($GLOBALS['replication_info'][$type]['status']) {
            $html .= '    <th>' . $name . '</th>' . "\n";
        }
    }

    if ($is_superuser && ! PMA_DRIZZLE) {
        $html .= '    <th>' . ($cfg_iconic ? '' : __('Action')) . "\n"
            . '    </th>' . "\n";
    }
    return $html;
}

/**
 * Returns the array about $sort_order and $sort_by
 *
 * @return Array
 */
function PMA_getListForSortDatabase()
{
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
        && /*overload*/mb_strtolower($_REQUEST['sort_order']) == 'desc'
    ) {
        $sort_order = 'desc';
    } else {
        $sort_order = 'asc';
    }

    return array($sort_by, $sort_order);
}

/**
 * Deal with Drops multiple databases
 *
 * @return null
 */
function PMA_dropMultiDatabases()
{
    if (! isset($_REQUEST['selected_dbs']) && ! isset($_REQUEST['query_type'])) {
        $message = PMA_Message::error(__('No databases selected.'));
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db';
        $err_url = 'server_databases.php' . PMA_URL_getCommon();
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
        //the following variables will be used on mult_submits.inc.php
        global $query_type, $selected, $mult_btn;

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

