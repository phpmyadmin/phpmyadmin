<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerDatabasesController
 *
 * @package PhpMyAdmin\Controllers
 */

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Charsets;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Common;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles viewing and creating and deleting databases
 *
 * @package PhpMyAdmin\Controllers
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
        include_once 'libraries/check_user_privileges.inc.php';

        $response = Response::getInstance();

        if (isset($_REQUEST['drop_selected_dbs'])
            && $response->isAjax()
            && ($GLOBALS['dbi']->isSuperuser() || $GLOBALS['cfg']['AllowUserDropDatabase'])
        ) {
            $this->dropDatabasesAction();
            return;
        }

        include_once 'libraries/replication.inc.php';

        if (! empty($_POST['new_db'])
            && $response->isAjax()
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
        $this->response->addHTML(Common::getHtmlForSubPageHeader($header_type));

        /**
         * Gets the databases list
         */
        if ($GLOBALS['server'] > 0) {
            $this->_databases = $this->dbi->getDatabasesFull(
                null, $this->_dbstats, DatabaseInterface::CONNECT_USER, $this->_sort_by,
                $this->_sort_order, $this->_pos, true
            );
            $this->_database_count = count($GLOBALS['dblist']->databases);
        } else {
            $this->_database_count = 0;
        }

        if ($this->_database_count > 0 && ! empty($this->_databases)) {
            $databases = $this->_getHtmlForDatabases($replication_types);
        }

        $this->response->addHTML(Template::get('server/databases/index')->render([
            'show_create_db' => $GLOBALS['cfg']['ShowCreateDb'],
            'is_create_db_priv' => $GLOBALS['is_create_db_priv'],
            'dbstats' => $this->_dbstats,
            'db_to_create' => $GLOBALS['db_to_create'],
            'server_collation' => $GLOBALS['dbi']->getServerCollation(),
            'databases' => isset($databases) ? $databases : null,
        ]));
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
            $charsets = Charsets::getMySQLCharsets();
            $collations = Charsets::getMySQLCollations();
            if (in_array($db_charset, $charsets)
                && in_array($_POST['db_collation'], $collations[$db_charset])
            ) {
                $sql_query .= ' DEFAULT'
                    . Util::getCharsetQueryPart($_POST['db_collation']);
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

            $this->response->addJSON(
                'url_query',
                Util::getScriptNameForOption(
                    $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
                )
                . Url::getCommon(array('db' => $_POST['new_db']))
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
            $err_url = $action . Url::getCommon();

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
    private function _getHtmlForDatabases(array $replication_types)
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
        $html .= Url::getHiddenInputs($_url_params);

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
        $html .= '<div class="responsivetable"><table id="tabledatabases" class="data">' . "\n";
        $html .= $this->_getHtmlForTableHeader(
            $_url_params, $column_order, $first_database
        );
        $html .= $this->_getHtmlForTableBody($column_order, $replication_types);
        $html .= $this->_getHtmlForTableFooter($column_order, $first_database);
        $html .= '</table></div>' . "\n";

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
            'description_function' => array(Charsets::class, 'getCollationDescr'),
            'format'    => 'string',
            'footer'    => $this->dbi->getServerCollation(),
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
        $column_order['SCHEMA_DATA_FREE'] = array(
            'disp_name' => __('Overhead'),
            'format'    => 'byte',
            'footer'    => 0,
        );

        return $column_order;
    }

    /**
     * Returns the html for Table footer buttons
     *
     * @return string
     */
    private function _getHtmlForTableFooterButtons()
    {
        if (! $GLOBALS['dbi']->isSuperuser()
            && ! $GLOBALS['cfg']['AllowUserDropDatabase']
        ) {
            return '';
        }

        $html = Template::get('select_all')
            ->render(
                array(
                    'pma_theme_image' => $GLOBALS['pmaThemeImage'],
                    'text_dir'        => $GLOBALS['text_dir'],
                    'form_name'       => 'dbStatsForm',
                )
            );

        $html .= Util::getButtonOrImage(
            '',
            'mult_submit' . ' ajax',
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
        return Template::get('server/databases/table_footer')->render([
            'column_order' => $column_order,
            'first_database' => $first_database,
            'master_replication' => $GLOBALS['replication_info']['master']['status'],
            'slave_replication' => $GLOBALS['replication_info']['slave']['status'],
            'database_count' => $this->_database_count,
            'is_superuser' => $GLOBALS['dbi']->isSuperuser(),
            'allow_user_drop_database' => $GLOBALS['cfg']['AllowUserDropDatabase'],
        ]);
    }

    /**
     * Returns the html for Database List
     *
     * @param array $column_order      column order
     * @param array $replication_types replication types
     *
     * @return string
     */
    private function _getHtmlForTableBody(array $column_order, array $replication_types)
    {
        $html = '<tbody>' . "\n";

        foreach ($this->_databases as $current) {
            $tr_class = ' db-row';
            if ($this->dbi->isSystemSchema($current['SCHEMA_NAME'], true)) {
                $tr_class .= ' noclick';
            }

            $generated_html = $this->_buildHtmlForDb(
                $current,
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
     * @param array  $column_order      column order
     * @param array  $replication_types replication types
     * @param array  $replication_info  replication info
     * @param string $tr_class          HTMl class for the row
     *
     * @return array $column_order, $out
     */
    function _buildHtmlForDb(
        array $current, array $column_order,
        array $replication_types, array $replication_info, $tr_class = ''
    ) {
        $master_replication = $slave_replication = '';
        foreach ($replication_types as $type) {
            if ($replication_info[$type]['status']) {
                $out = '';
                $key = array_search(
                    $current["SCHEMA_NAME"],
                    $replication_info[$type]['Ignore_DB']
                );
                if (strlen($key) > 0) {
                    $out = Util::getIcon(
                        's_cancel.png',
                        __('Not replicated')
                    );
                } else {
                    $key = array_search(
                        $current["SCHEMA_NAME"], $replication_info[$type]['Do_DB']
                    );

                    if (strlen($key) > 0
                        || count($replication_info[$type]['Do_DB']) == 0
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

        return Template::get('server/databases/table_row')->render([
            'current' => $current,
            'tr_class' => $tr_class,
            'column_order' => $column_order,
            'master_replication_status' => $GLOBALS['replication_info']['master']['status'],
            'master_replication' => $master_replication,
            'slave_replication_status' => $GLOBALS['replication_info']['slave']['status'],
            'slave_replication' => $slave_replication,
            'is_superuser' => $GLOBALS['dbi']->isSuperuser(),
            'allow_user_drop_database' => $GLOBALS['cfg']['AllowUserDropDatabase'],
            'is_system_schema' => $GLOBALS['dbi']->isSystemSchema($current['SCHEMA_NAME'], true),
            'default_tab_database' => $GLOBALS['cfg']['DefaultTabDatabase'],
        ]);
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
        array $_url_params, array $column_order, array $first_database
    ) {
        return Template::get('server/databases/table_header')->render([
            'url_params' => $_url_params,
            'sort_by' => $this->_sort_by,
            'sort_order' => $this->_sort_order,
            'sort_order_text' => ($this->_sort_order == 'asc'
                ? __('Ascending') : __('Descending')),
            'column_order' => $column_order,
            'first_database' => $first_database,
            'master_replication' => $GLOBALS['replication_info']['master']['status'],
            'slave_replication' => $GLOBALS['replication_info']['slave']['status'],
            'is_superuser' => $GLOBALS['dbi']->isSuperuser(),
            'allow_user_drop_database' => $GLOBALS['cfg']['AllowUserDropDatabase'],
        ]);
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
                    . Url::getCommon(array('dbstats' => '1')),
                'title' => __('Enable statistics')
            ),
        );

        $html .= Template::get('list/unordered')->render(
            array('items' => $items,)
        );

        return $html;
    }
}
