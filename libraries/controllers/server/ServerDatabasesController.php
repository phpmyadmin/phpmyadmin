<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerDatabasesController
 *
 * @package PMA\libraries\controllers\server
 */

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;
use PMA\libraries\Message;
use PMA\libraries\Template;
use PMA\libraries\Util;

/**
 * Handles viewing and creating and deleting databases
 *
 * @package PMA\libraries\controllers\server
 */
class ServerDatabasesController extends Controller
{
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        if (isset($_REQUEST['drop_selected_dbs'])
            && ($GLOBALS['is_superuser'] || $GLOBALS['cfg']['AllowUserDropDatabase'])
        ) {
            $this->dropDatabasesAction();
            return;
        }

        require_once 'libraries/server_common.inc.php';
        require_once 'libraries/replication.inc.php';
        require_once 'libraries/build_html_for_db.lib.php';

        $header  = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('server_databases.js');

        list($sort_by, $sort_order) = $this->_getListForSortDatabase();

        $dbstats = empty($_REQUEST['dbstats']) ? 0 : 1;
        $pos     = empty($_REQUEST['pos']) ? 0 : (int) $_REQUEST['pos'];

        /**
         * Displays the sub-page heading
         */
        $header_type = $dbstats ? "database_statistics" : "databases";
        $this->response->addHTML(PMA_getHtmlForSubPageHeader($header_type));

        /**
         * Displays For Create database.
         */
        $html = '';
        if ($GLOBALS['cfg']['ShowCreateDb']) {
            $html .= '<ul><li id="li_create_database" class="no_bullets">' . "\n";
            include 'libraries/display_create_database.lib.php';
            $html .= '    </li>' . "\n";
            $html .= '</ul>' . "\n";
        }

        /**
         * Gets the databases list
         */
        if ($GLOBALS['server'] > 0) {
            $databases = $this->dbi->getDatabasesFull(
                null, $dbstats, null, $sort_by, $sort_order, $pos, true
            );
            $databases_count = count($GLOBALS['pma']->databases);
        } else {
            $databases_count = 0;
        }

        /**
         * Displays the page
         */
        if ($databases_count > 0 && ! empty($databases)) {
            $html .= $this->_getHtmlForDatabase(
                $databases,
                $databases_count,
                $pos,
                $dbstats,
                $sort_by,
                $sort_order,
                $replication_types
            );
        } else {
            $html .= __('No databases');
        }
        unset($databases_count);

        $this->response->addHTML($html);
    }

    /**
     * Handles dropping multiple databases
     *
     * @return void
     */
    public function dropDatabasesAction()
    {
        if (! isset($_REQUEST['selected_dbs'])) {
            $message = Message::error(__('No databases selected.'));
        } else {
            $action = 'server_databases.php';
            $err_url = $action . PMA_URL_getCommon();

            $GLOBALS['submit_mult'] = 'drop_db';
            $GLOBALS['mult_btn'] = __('Yes');

            include 'libraries/mult_submits.inc.php';

            if (empty($message)) {
                $number_of_databases = count($selected);
                $message = Message::success(
                    _ngettext(
                        '%1$d database has been dropped successfully.',
                        '%1$d databases have been dropped successfully.',
                        $number_of_databases
                    )
                );
                $message->addParam($number_of_databases);
            }
        }

        if ($message instanceof Message) {
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON('message', $message);
        }
    }

    /**
     * Returns the array about $sort_order and $sort_by
     *
     * @return Array
     */
    private function _getListForSortDatabase()
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
     * Returns the html for Database List
     *
     * @param Array  $databases         GBI return databases
     * @param int    $databases_count   database count
     * @param int    $pos               display pos
     * @param Array  $dbstats           database status
     * @param string $sort_by           sort by string
     * @param string $sort_order        sort order string
     * @param array  $replication_types replication types
     *
     * @return string
     */
    private function _getHtmlForDatabase(
        $databases, $databases_count, $pos, $dbstats,
        $sort_by, $sort_order, $replication_types
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

        $html .= Util::getListNavigator(
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

        $html .= $this->_getHtmlForColumnOrderWithSort(
            $_url_params,
            $sort_by,
            $sort_order,
            $column_order,
            $first_database
        );

        $html .= $this->_getHtmlForReplicationType($replication_types);

        $html .= '</tr>' . "\n"
            . '</thead>' . "\n";

        list($output, $column_order) = $this->_getHtmlAndColumnOrderForDatabaseList(
            $databases,
            $column_order,
            $replication_types
        );
        $html .= $output;
        unset($output);

        $html .= $this->_getHtmlForTableFooter(
            $databases_count,
            $column_order,
            $replication_types,
            $first_database
        );

        $html .= '</table>' . "\n";

        $html .= $this->_getHtmlForTableFooterButtons();

        if (empty($dbstats)) {
            //we should put notice above database list
            $html = $this->_getHtmlForNoticeEnableStatistics($html);
        }
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns the html for Table footer buttons
     *
     * @return string
     */
    private function _getHtmlForTableFooterButtons()
    {
        if (!$GLOBALS['is_superuser'] && !$GLOBALS['cfg']['AllowUserDropDatabase']) {
            return '';
        }

        $html = Util::getWithSelected(
            $GLOBALS['pmaThemeImage'], $GLOBALS['text_dir'], "dbStatsForm"
        );
        $html .= Util::getButtonOrImage(
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
     * @param int    $databases_count    Database count
     * @param string $column_order       column order
     * @param array  $replication_types  replication types
     * @param string $first_database     First database
     *
     * @return string
     */
    private function _getHtmlForTableFooter(
        $databases_count, $column_order,
        $replication_types, $first_database
    ) {
        $html = '<tfoot><tr>' . "\n";
        if ($GLOBALS['is_superuser'] || $GLOBALS['cfg']['AllowUserDropDatabase']) {
            $html .= '    <th></th>' . "\n";
        }
        $html .= '    <th>' . __('Total') . ': <span id="databases_count">'
            . $databases_count . '</span></th>' . "\n";

        $html .= $this->_getHtmlForColumnOrder($column_order, $first_database);

        foreach ($replication_types as $type) {
            if ($GLOBALS['replication_info'][$type]['status']) {
                $html .= '    <th></th>' . "\n";
            }
        }

        if ($GLOBALS['is_superuser']) {
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
     * @param array  $column_order      column order
     * @param array  $replication_types replication types
     *
     * @return Array
     */
    private function _getHtmlAndColumnOrderForDatabaseList(
        $databases, $column_order, $replication_types
    ) {
        $odd_row = true;
        $html = '<tbody>' . "\n";

        foreach ($databases as $current) {
            $tr_class = $odd_row ? 'odd' : 'even';
            if ($this->dbi->isSystemSchema($current['SCHEMA_NAME'], true)) {
                $tr_class .= ' noclick';
            }
            $html .= '<tr class="' . $tr_class . '">' . "\n";
            $odd_row = ! $odd_row;

            list($column_order, $generated_html) = PMA_buildHtmlForDb(
                $current,
                $GLOBALS['is_superuser'],
                $GLOBALS['url_query'],
                $column_order,
                $replication_types,
                $GLOBALS['replication_info']
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
    private function _getHtmlForColumnOrder($column_order, $first_database)
    {
        $html = "";
        // avoid execution path notice
        $unit = "";
        foreach ($column_order as $stat_name => $stat) {
            if (array_key_exists($stat_name, $first_database)) {
                if ($stat['format'] === 'byte') {
                    list($value, $unit)
                        = Util::formatByteDown($stat['footer'], 3, 1);
                } elseif ($stat['format'] === 'number') {
                    $value = Util::formatNumber($stat['footer'], 0);
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
     * @param Array  $_url_params        Url params
     * @param string $sort_by            sort column name
     * @param string $sort_order         order
     * @param array  $column_order       column order
     * @param array  $first_database     database to show
     *
     * @return string
     */
    private function _getHtmlForColumnOrderWithSort(
        $_url_params, $sort_by, $sort_order,
        $column_order, $first_database
    ) {
        $html = ($GLOBALS['is_superuser'] || $GLOBALS['cfg']['AllowUserDropDatabase']
            ? '        <th></th>' . "\n"
            : '')
            . '    <th><a href="server_databases.php'
            . PMA_URL_getCommon($_url_params) . '">' . "\n"
            . '            ' . __('Database') . "\n"
            . ($sort_by == 'SCHEMA_NAME'
                ? '                ' . Util::getImage(
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
                    ? '            ' . Util::getImage(
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
     * @param string $html      html for database list
     *
     * @return string
     */
    private function _getHtmlForNoticeEnableStatistics($html)
    {
        $notice = Message::notice(
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
                'href' => 'server_databases.php' . $GLOBALS['url_query'] . '&amp;dbstats=1',
                'title' => __('Enable statistics')
            ),
        );

        $html .= Template::get('list/unordered')->render(
            array('items' => $items,)
        );

        return $html;
    }

    /**
     * Returns the html for database replication types
     *
     * @param array $replication_types replication types
     *
     * @return string
     */
    private function _getHtmlForReplicationType($replication_types) {
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

        if ($GLOBALS['is_superuser']) {
            $html .= '    <th>' . ($GLOBALS['cfg']['ActionLinksMode'] ? '' : __('Action')) . "\n"
                . '    </th>' . "\n";
        }
        return $html;
    }
}