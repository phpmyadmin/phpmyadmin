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
     * @var array array of database details
     */
    private $_databases;
    /**
     * @var int number of databases
     */
    private $_database_count;
    /**
     * @var string sort by column
     */
    private $_sort_by;
    /**
     * @var string sort order of databases
     */
    private $_sort_order;
    /**
     * @var boolean whether to show database statistics
     */
    private $_dbstats;
    /**
     * @var int position in list navigation
     */
    private $_pos;

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        include_once 'libraries/check_user_privileges.lib.php';

        if (isset($_REQUEST['drop_selected_dbs'])
            && $GLOBALS['is_ajax_request']
            && ($GLOBALS['is_superuser'] || $GLOBALS['cfg']['AllowUserDropDatabase'])
        ) {
            $this->dropDatabasesAction();
            return;
        }

        include_once 'libraries/replication.inc.php';
        include_once 'libraries/mysql_charsets.inc.php';

        if (! empty($_POST['new_db'])
            && $GLOBALS['is_ajax_request']
        ) {
            $this->createDatabaseAction();
            return;
        }

        include_once 'libraries/server_common.inc.php';

        $header  = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('server_databases.js');

        $this->_setSortDetails();
        $this->_dbstats = empty($_REQUEST['dbstats']) ? false : true;
        $this->_pos     = empty($_REQUEST['pos']) ? 0 : (int) $_REQUEST['pos'];

        /**
         * Displays the sub-page heading
         */
        $header_type = $this->_dbstats ? "database_statistics" : "databases";
        $this->response->addHTML(PMA_getHtmlForSubPageHeader($header_type));

        /**
         * Displays For Create database.
         */
        $html = '';
        if ($GLOBALS['cfg']['ShowCreateDb']) {
            $html .= Template::get('server/databases/create')->render();
        }

        /**
         * Gets the databases list
         */
        if ($GLOBALS['server'] > 0) {
            $this->_databases = $this->dbi->getDatabasesFull(
                null, $this->_dbstats, null, $this->_sort_by,
                $this->_sort_order, $this->_pos, true
            );
            $this->_database_count = count($GLOBALS['dblist']->databases);
        } else {
            $this->_database_count = 0;
        }

        /**
         * Displays the page
         */
        if ($this->_database_count > 0 && ! empty($this->_databases)) {
            $html .= $this->_getHtmlForDatabases($replication_types);
        } else {
            $html .= __('No databases');
        }

        $this->response->addHTML($html);
    }

    /**
     * Handles creating a new database
     *
     * @return void
     */
    public function createDatabaseAction()
    {
        /**
         * Builds and executes the db creation sql query
         */
        $sql_query = 'CREATE DATABASE ' . Util::backquote($_POST['new_db']);
        if (! empty($_POST['db_collation'])) {
            list($db_charset) = explode('_', $_POST['db_collation']);
            if (in_array($db_charset, $GLOBALS['mysql_charsets'])
                && in_array($_POST['db_collation'], $GLOBALS['mysql_collations'][$db_charset])
            ) {
                $sql_query .= ' DEFAULT'
                    . PMA_generateCharsetQueryPart($_POST['db_collation']);
            }
        }
        $sql_query .= ';';

        $result = $GLOBALS['dbi']->tryQuery($sql_query);

        if (! $result) {
            // avoid displaying the not-created db name in header or navi panel
            $GLOBALS['db'] = '';

            $message = Message::rawError($GLOBALS['dbi']->getError());
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);
        } else {
            $GLOBALS['db'] = $_POST['new_db'];

            $message = Message::success(__('Database %1$s has been created.'));
            $message->addParam($_POST['new_db']);
            $this->response->addJSON('message', $message);
            $this->response->addJSON(
                'sql_query', Util::getMessage(null, $sql_query, 'success')
            );

            $url_query = PMA_URL_getCommon(array('db' => $_POST['new_db']));
            $this->response->addJSON(
                'url_query',
                Util::getScriptNameForOption(
                    $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
                )
                . $url_query . '&amp;db='
                . urlencode($_POST['new_db'])
            );
        }
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

            if (empty($message)) { // no error message
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
     * Extracts parameters $sort_order and $sort_by
     *
     * @return void
     */
    private function _setSortDetails()
    {
        if (empty($_REQUEST['sort_by'])) {
            $this->_sort_by = 'SCHEMA_NAME';
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
                $this->_sort_by = $_REQUEST['sort_by'];
            } else {
                $this->_sort_by = 'SCHEMA_NAME';
            }
        }

        if (isset($_REQUEST['sort_order'])
            && mb_strtolower($_REQUEST['sort_order']) == 'desc'
        ) {
            $this->_sort_order = 'desc';
        } else {
            $this->_sort_order = 'asc';
        }
    }

    /**
     * Returns the html for Database List
     *
     * @param array $replication_types replication types
     *
     * @return string
     */
    private function _getHtmlForDatabases($replication_types)
    {

        $html = '<div id="tableslistcontainer">';
        $first_database = reset($this->_databases);
        // table col order
        $column_order = $this->_getColumnOrder();

        $_url_params = array(
            'pos' => $this->_pos,
            'dbstats' => $this->_dbstats,
            'sort_by' => $this->_sort_by,
            'sort_order' => $this->_sort_order,
        );

        $html .= Util::getListNavigator(
            $this->_database_count, $this->_pos, $_url_params,
            'server_databases.php', 'frame_content', $GLOBALS['cfg']['MaxDbList']
        );

        $_url_params['pos'] = $this->_pos;

        $html .= '<form class="ajax" action="server_databases.php" ';
        $html .= 'method="post" name="dbStatsForm" id="dbStatsForm">' . "\n";
        $html .= PMA_URL_getHiddenInputs($_url_params);

        $_url_params['sort_by'] = 'SCHEMA_NAME';
        $_url_params['sort_order']
            = ($this->_sort_by == 'SCHEMA_NAME' && $this->_sort_order == 'asc')
            ? 'desc' : 'asc';

        // calculate aggregate stats to display in footer
        foreach ($this->_databases as $current) {
            foreach ($column_order as $stat_name => $stat) {
                if (array_key_exists($stat_name, $current)
                    && is_numeric($stat['footer'])
                ) {
                    $column_order[$stat_name]['footer'] += $current[$stat_name];
                }
            }
        }

        // database table
        $html .= '<table id="tabledatabases" class="data">' . "\n";
        $html .= $this->_getHtmlForTableHeader(
            $_url_params, $column_order, $first_database
        );
        $html .= $this->_getHtmlForTableBody($column_order, $replication_types);
        $html .= $this->_getHtmlForTableFooter($column_order, $first_database);
        $html .= '</table>' . "\n";

        $html .= $this->_getHtmlForTableFooterButtons();

        if (empty($this->_dbstats)) {
            //we should put notice above database list
            $html .= $this->_getHtmlForNoticeEnableStatistics();
        }
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Prepares the $column_order array
     *
     * @return array
     */
    private function _getColumnOrder()
    {
        $column_order = array();
        $column_order['DEFAULT_COLLATION_NAME'] = array(
            'disp_name' => __('Collation'),
            'description_function' => 'PMA_getCollationDescr',
            'format'    => 'string',
            'footer'    => PMA_getServerCollation(),
        );
        $column_order['SCHEMA_TABLES'] = array(
            'disp_name' => __('Tables'),
            'format'    => 'number',
            'footer'    => 0,
        );
        $column_order['SCHEMA_TABLE_ROWS'] = array(
            'disp_name' => __('Rows'),
            'format'    => 'number',
            'footer'    => 0,
        );
        $column_order['SCHEMA_DATA_LENGTH'] = array(
            'disp_name' => __('Data'),
            'format'    => 'byte',
            'footer'    => 0,
        );
        $column_order['SCHEMA_INDEX_LENGTH'] = array(
            'disp_name' => __('Indexes'),
            'format'    => 'byte',
            'footer'    => 0,
        );
        $column_order['SCHEMA_LENGTH'] = array(
            'disp_name' => __('Total'),
            'format'    => 'byte',
            'footer'    => 0,
        );
        // At this point we were preparing the display of Overhead using DATA_FREE
        // but its content does not represent the real overhead in the case
        // of InnoDB

        return $column_order;
    }

    /**
     * Returns the html for Table footer buttons
     *
     * @return string
     */
    private function _getHtmlForTableFooterButtons()
    {
        if (! $GLOBALS['is_superuser']
            && ! $GLOBALS['cfg']['AllowUserDropDatabase']
        ) {
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
     * @param string $column_order   column order
     * @param string $first_database first database
     *
     * @return string
     */
    private function _getHtmlForTableFooter($column_order, $first_database)
    {
        return Template::get('server/databases/table_footer')->render(
            array(
                'column_order' => $column_order,
                'first_database' => $first_database,
                'master_replication' => $GLOBALS['replication_info']['master']['status'],
                'slave_replication' => $GLOBALS['replication_info']['slave']['status'],
                'databaseCount' => $this->_database_count,
            )
        );
    }

    /**
     * Returns the html for Database List
     *
     * @param array $column_order      column order
     * @param array $replication_types replication types
     *
     * @return string
     */
    private function _getHtmlForTableBody($column_order, $replication_types)
    {
        $odd_row = true;
        $html = '<tbody>' . "\n";

        foreach ($this->_databases as $current) {
            $tr_class = $odd_row ? 'odd' : 'even';
            if ($this->dbi->isSystemSchema($current['SCHEMA_NAME'], true)) {
                $tr_class .= ' noclick';
            }
            $odd_row = ! $odd_row;

            $generated_html = $this->_buildHtmlForDb(
                $current,
                $GLOBALS['url_query'],
                $column_order,
                $replication_types,
                $GLOBALS['replication_info'],
                $tr_class
            );
            $html .= $generated_html;
        } // end foreach ($this->_databases as $key => $current)
        $html .= '</tbody>';

        return $html;
    }

    /**
     * Builds the HTML for one database to display in the list
     * of databases from server_databases.php
     *
     * @param array  $current           current database
     * @param string $url_query         url query
     * @param array  $column_order      column order
     * @param array  $replication_types replication types
     * @param array  $replication_info  replication info
     * @param string $tr_class          HTMl class for the row
     *
     * @return array $column_order, $out
     */
    function _buildHtmlForDb(
        $current, $url_query, $column_order,
        $replication_types, $replication_info, $tr_class = ''
    ) {
        $master_replication = $slave_replication = '';
        foreach ($replication_types as $type) {
            if ($replication_info[$type]['status']) {
                $out = '';
                $key = array_search(
                    $current["SCHEMA_NAME"],
                    $replication_info[$type]['Ignore_DB']
                );
                if (mb_strlen($key) > 0) {
                    $out = Util::getIcon(
                        's_cancel.png',
                        __('Not replicated')
                    );
                } else {
                    $key = array_search(
                        $current["SCHEMA_NAME"], $replication_info[$type]['Do_DB']
                    );

                    if (mb_strlen($key) > 0
                        || (isset($replication_info[$type]['Do_DB'][0])
                        && $replication_info[$type]['Do_DB'][0] == ""
                        && count($replication_info[$type]['Do_DB']) == 1)
                    ) {
                        // if ($key != null) did not work for index "0"
                        $out = Util::getIcon(
                            's_success.png',
                            __('Replicated')
                        );
                    }
                }

                if ($type == 'master') {
                    $master_replication = $out;
                } elseif ($type == 'slave') {
                    $slave_replication = $out;
                }
            }
        }

        return Template::get('server/databases/table_row')->render(
            array(
                'current' => $current,
                'tr_class' => $tr_class,
                'url_query' => $url_query,
                'column_order' => $column_order,
                'master_replication_status'
                    => $GLOBALS['replication_info']['master']['status'],
                'master_replication' => $master_replication,
                'slave_replication_status'
                    => $GLOBALS['replication_info']['slave']['status'],
                'slave_replication' => $slave_replication,
            )
        );
    }

    /**
     * Returns the html for table header
     *
     * @param array $_url_params    url params
     * @param array $column_order   column order
     * @param array $first_database database to show
     *
     * @return string
     */
    private function _getHtmlForTableHeader(
        $_url_params, $column_order, $first_database
    ) {
        return Template::get('server/databases/table_header')->render(
            array(
                '_url_params' => $_url_params,
                'sort_by' => $this->_sort_by,
                'sort_order' => $this->_sort_order,
                'sort_order_text' => ($this->_sort_order == 'asc'
                    ? __('Ascending') : __('Descending')),
                'column_order' => $column_order,
                'first_database' => $first_database,
                'master_replication'
                    => $GLOBALS['replication_info']['master']['status'],
                'slave_replication'
                    => $GLOBALS['replication_info']['slave']['status'],
            )
        );
    }


    /**
     * Returns the html for Enable Statistics
     *
     * @return string
     */
    private function _getHtmlForNoticeEnableStatistics()
    {
        $html = '';

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
                'href' => 'server_databases.php'
                    . $GLOBALS['url_query'] . '&amp;dbstats=1',
                'title' => __('Enable statistics')
            ),
        );

        $html .= Template::get('list/unordered')->render(
            array('items' => $items,)
        );

        return $html;
    }
}
