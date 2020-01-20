<?php
/**
 * Generates and renders the top menu
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Class for generating the top menu
 *
 * @package PhpMyAdmin
 */
class Menu
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;
    /**
     * Table name
     *
     * @access private
     * @var string
     */
    private $_table;

    /** @var Relation */
    private $relation;

    /** @var Template */
    private $template;

    /**
     * Creates a new instance of Menu
     *
     * @param string $db    Database name
     * @param string $table Table name
     */
    public function __construct($db, $table)
    {
        $this->_db = $db;
        $this->_table = $table;
        $this->relation = new Relation($GLOBALS['dbi']);
        $this->template = new Template();
    }

    /**
     * Prints the menu and the breadcrumbs
     *
     * @return void
     */
    public function display()
    {
        echo $this->getDisplay();
    }

    /**
     * Returns the menu and the breadcrumbs as a string
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval  = $this->_getBreadcrumbs();
        $retval .= $this->_getMenu();
        return $retval;
    }

    /**
     * Returns hash for the menu and the breadcrumbs
     *
     * @return string
     */
    public function getHash()
    {
        return substr(
            md5($this->_getMenu() . $this->_getBreadcrumbs()),
            0,
            8
        );
    }

    /**
     * Returns the menu as HTML
     *
     * @return string HTML formatted menubar
     */
    private function _getMenu(): string
    {
        $url_params = [];

        if (strlen((string) $this->_table) > 0) {
            $tabs = $this->_getTableTabs();
            $url_params['db'] = $this->_db;
            $url_params['table'] = $this->_table;
            $level = 'table';
        } elseif (strlen($this->_db) > 0) {
            $tabs = $this->_getDbTabs();
            $url_params['db'] = $this->_db;
            $level = 'db';
        } else {
            $tabs = $this->_getServerTabs();
            $level = 'server';
        }

        $allowedTabs = $this->_getAllowedTabs($level);
        foreach ($tabs as $key => $value) {
            if (! array_key_exists($key, $allowedTabs)) {
                unset($tabs[$key]);
            }
        }
        return $this->template->render('top_menu', [
            'tabs' => $tabs,
            'url_params' => $url_params,
        ]);
    }

    /**
     * Returns a list of allowed tabs for the current user for the given level
     *
     * @param string $level 'server', 'db' or 'table' level
     *
     * @return array list of allowed tabs
     */
    private function _getAllowedTabs($level)
    {
        $cache_key = 'menu-levels-' . $level;
        if (Util::cacheExists($cache_key)) {
            return Util::cacheGet($cache_key);
        }
        $allowedTabs = Util::getMenuTabList($level);
        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['menuswork']) {
            $groupTable = Util::backquote($cfgRelation['db'])
                . '.'
                . Util::backquote($cfgRelation['usergroups']);
            $userTable = Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['users']);

            $sql_query = 'SELECT `tab` FROM ' . $groupTable
                . " WHERE `allowed` = 'N'"
                . " AND `tab` LIKE '" . $level . "%'"
                . ' AND `usergroup` = (SELECT usergroup FROM '
                . $userTable . " WHERE `username` = '"
                . $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user']) . "')";

            $result = $this->relation->queryAsControlUser($sql_query, false);
            if ($result) {
                while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                    $tabName = mb_substr(
                        $row['tab'],
                        mb_strpos($row['tab'], '_') + 1
                    );
                    unset($allowedTabs[$tabName]);
                }
            }
        }
        Util::cacheSet($cache_key, $allowedTabs);
        return $allowedTabs;
    }

    /**
     * Returns the breadcrumbs as HTML
     *
     * @return string HTML formatted breadcrumbs
     */
    private function _getBreadcrumbs(): string
    {
        global $cfg, $dbi;

        $server = [];
        $database = [];
        $table = [];

        if (empty($cfg['Server']['host'])) {
            $cfg['Server']['host'] = '';
        }
        $server['name'] = ! empty($cfg['Server']['verbose'])
            ? $cfg['Server']['verbose'] : $cfg['Server']['host'];
        $server['name'] .= empty($cfg['Server']['port'])
            ? '' : ':' . $cfg['Server']['port'];
        $server['url'] = Util::getScriptNameForOption(
            $cfg['DefaultTabServer'],
            'server'
        );

        if (strlen($this->_db) > 0) {
            $database['name'] = $this->_db;
            $database['url'] = Util::getScriptNameForOption(
                $cfg['DefaultTabDatabase'],
                'database'
            );
            if (strlen((string) $this->_table) > 0) {
                $table['name'] = $this->_table;
                $table['url'] = Util::getScriptNameForOption(
                    $cfg['DefaultTabTable'],
                    'table'
                );
                /** @var Table $tableObj */
                $tableObj = $dbi->getTable($this->_db, $this->_table);
                $table['is_view'] = $tableObj->isView();
                $table['comment'] = '';
                if (! $table['is_view']) {
                    $table['comment'] = $tableObj->getComment();
                }
                if (mb_strstr($table['comment'], '; InnoDB free')) {
                    $table['comment'] = preg_replace(
                        '@; InnoDB free:.*?$@',
                        '',
                        $table['comment']
                    );
                }
            } else {
                // no table selected, display database comment if present
                $cfgRelation = $this->relation->getRelationsParam();

                // Get additional information about tables for tooltip is done
                // in Util::getDbInfo() only once
                if ($cfgRelation['commwork']) {
                    $database['comment'] = $this->relation->getDbComment($this->_db);
                }
            }
        }

        return $this->template->render('menu/breadcrumbs', [
            'server' => $server,
            'database' => $database,
            'table' => $table,
        ]);
    }

    /**
     * Returns the table tabs as an array
     *
     * @return array Data for generating table tabs
     */
    private function _getTableTabs()
    {
        global $route;

        $db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($this->_db);
        $tbl_is_view = $GLOBALS['dbi']->getTable($this->_db, $this->_table)
            ->isView();
        $updatable_view = false;
        if ($tbl_is_view) {
            $updatable_view = $GLOBALS['dbi']->getTable($this->_db, $this->_table)
                ->isUpdatableView();
        }
        $is_superuser = $GLOBALS['dbi']->isSuperuser();
        $isCreateOrGrantUser = $GLOBALS['dbi']->isUserType('grant')
            || $GLOBALS['dbi']->isUserType('create');

        $tabs = [];

        $tabs['browse']['icon'] = 'b_browse';
        $tabs['browse']['text'] = __('Browse');
        $tabs['browse']['route'] = '/sql';
        $tabs['browse']['args']['pos'] = 0;
        $tabs['browse']['active'] = $route === '/sql';

        $tabs['structure']['icon'] = 'b_props';
        $tabs['structure']['route'] = '/table/structure';
        $tabs['structure']['text'] = __('Structure');
        $tabs['structure']['active'] = in_array($route, [
            '/table/relation',
            '/table/structure',
        ]);

        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['route'] = '/table/sql';
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['active'] = $route === '/table/sql';

        $tabs['search']['icon'] = 'b_search';
        $tabs['search']['text'] = __('Search');
        $tabs['search']['route'] = '/table/search';
        $tabs['search']['active'] = in_array($route, [
            '/table/find-replace',
            '/table/search',
            '/table/zoom-search',
        ]);

        if (! $db_is_system_schema && (! $tbl_is_view || $updatable_view)) {
            $tabs['insert']['icon'] = 'b_insrow';
            $tabs['insert']['route'] = '/table/change';
            $tabs['insert']['text'] = __('Insert');
            $tabs['insert']['active'] = $route === '/table/change';
        }

        $tabs['export']['icon'] = 'b_tblexport';
        $tabs['export']['route'] = '/table/export';
        $tabs['export']['args']['single_table'] = 'true';
        $tabs['export']['text'] = __('Export');
        $tabs['export']['active'] = $route === '/table/export';

        /**
         * Don't display "Import" for views and information_schema
         */
        if (! $tbl_is_view && ! $db_is_system_schema) {
            $tabs['import']['icon'] = 'b_tblimport';
            $tabs['import']['route'] = '/table/import';
            $tabs['import']['text'] = __('Import');
            $tabs['import']['active'] = $route === '/table/import';
        }
        if (($is_superuser || $isCreateOrGrantUser)
            && ! $db_is_system_schema
        ) {
            $tabs['privileges']['route'] = '/server/privileges';
            $tabs['privileges']['args']['checkprivsdb'] = $this->_db;
            $tabs['privileges']['args']['checkprivstable'] = $this->_table;
            // stay on table view
            $tabs['privileges']['args']['viewing_mode'] = 'table';
            $tabs['privileges']['text'] = __('Privileges');
            $tabs['privileges']['icon'] = 's_rights';
            $tabs['privileges']['active'] = $route === '/server/privileges';
        }
        /**
         * Don't display "Operations" for views and information_schema
         */
        if (! $tbl_is_view && ! $db_is_system_schema) {
            $tabs['operation']['icon'] = 'b_tblops';
            $tabs['operation']['route'] = '/table/operations';
            $tabs['operation']['text'] = __('Operations');
            $tabs['operation']['active'] = $route === '/table/operations';
        }
        /**
         * Views support a limited number of operations
         */
        if ($tbl_is_view && ! $db_is_system_schema) {
            $tabs['operation']['icon'] = 'b_tblops';
            $tabs['operation']['route'] = '/view/operations';
            $tabs['operation']['text'] = __('Operations');
            $tabs['operation']['active'] = $route === '/view/operations';
        }

        if (Tracker::isActive() && ! $db_is_system_schema) {
            $tabs['tracking']['icon'] = 'eye';
            $tabs['tracking']['text'] = __('Tracking');
            $tabs['tracking']['route'] = '/table/tracking';
            $tabs['tracking']['active'] = $route === '/table/tracking';
        }
        if (! $db_is_system_schema
            && Util::currentUserHasPrivilege(
                'TRIGGER',
                $this->_db,
                $this->_table
            )
            && ! $tbl_is_view
        ) {
            $tabs['triggers']['route'] = '/table/triggers';
            $tabs['triggers']['text'] = __('Triggers');
            $tabs['triggers']['icon'] = 'b_triggers';
            $tabs['triggers']['active'] = $route === '/table/triggers';
        }

        return $tabs;
    }

    /**
     * Returns the db tabs as an array
     *
     * @return array Data for generating db tabs
     */
    private function _getDbTabs()
    {
        global $route;

        $db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($this->_db);
        $num_tables = count($GLOBALS['dbi']->getTables($this->_db));
        $is_superuser = $GLOBALS['dbi']->isSuperuser();
        $isCreateOrGrantUser = $GLOBALS['dbi']->isUserType('grant')
            || $GLOBALS['dbi']->isUserType('create');

        /**
         * Gets the relation settings
         */
        $cfgRelation = $this->relation->getRelationsParam();

        $tabs = [];

        $tabs['structure']['route'] = '/database/structure';
        $tabs['structure']['text'] = __('Structure');
        $tabs['structure']['icon'] = 'b_props';
        $tabs['structure']['active'] = $route === '/database/structure';

        $tabs['sql']['route'] = '/database/sql';
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['active'] = $route === '/database/sql';

        $tabs['search']['text'] = __('Search');
        $tabs['search']['icon'] = 'b_search';
        $tabs['search']['route'] = '/database/search';
        $tabs['search']['active'] = $route === '/database/search';
        if ($num_tables == 0) {
            $tabs['search']['warning'] = __('Database seems to be empty!');
        }

        $tabs['query']['text'] = __('Query');
        $tabs['query']['icon'] = 's_db';
        $tabs['query']['route'] = '/database/multi_table_query';
        $tabs['query']['active'] = $route === '/database/multi_table_query' || $route === '/database/qbe';
        if ($num_tables == 0) {
            $tabs['query']['warning'] = __('Database seems to be empty!');
        }

        $tabs['export']['text'] = __('Export');
        $tabs['export']['icon'] = 'b_export';
        $tabs['export']['route'] = '/database/export';
        $tabs['export']['active'] = $route === '/database/export';
        if ($num_tables == 0) {
            $tabs['export']['warning'] = __('Database seems to be empty!');
        }

        if (! $db_is_system_schema) {
            $tabs['import']['route'] = '/database/import';
            $tabs['import']['text'] = __('Import');
            $tabs['import']['icon'] = 'b_import';
            $tabs['import']['active'] = $route === '/database/import';

            $tabs['operation']['route'] = '/database/operations';
            $tabs['operation']['text'] = __('Operations');
            $tabs['operation']['icon'] = 'b_tblops';
            $tabs['operation']['active'] = $route === '/database/operations';

            if ($is_superuser || $isCreateOrGrantUser) {
                $tabs['privileges']['route'] = '/server/privileges';
                $tabs['privileges']['args']['checkprivsdb'] = $this->_db;
                // stay on database view
                $tabs['privileges']['args']['viewing_mode'] = 'db';
                $tabs['privileges']['text'] = __('Privileges');
                $tabs['privileges']['icon'] = 's_rights';
                $tabs['privileges']['active'] = $route === '/server/privileges';
            }

            $tabs['routines']['route'] = '/database/routines';
            $tabs['routines']['text'] = __('Routines');
            $tabs['routines']['icon'] = 'b_routines';
            $tabs['routines']['active'] = $route === '/database/routines';

            if (Util::currentUserHasPrivilege('EVENT', $this->_db)) {
                $tabs['events']['route'] = '/database/events';
                $tabs['events']['text'] = __('Events');
                $tabs['events']['icon'] = 'b_events';
                $tabs['events']['active'] = $route === '/database/events';
            }

            if (Util::currentUserHasPrivilege('TRIGGER', $this->_db)) {
                $tabs['triggers']['route'] = '/database/triggers';
                $tabs['triggers']['text'] = __('Triggers');
                $tabs['triggers']['icon'] = 'b_triggers';
                $tabs['triggers']['active'] = $route === '/database/triggers';
            }
        }

        if (Tracker::isActive() && ! $db_is_system_schema) {
            $tabs['tracking']['text'] = __('Tracking');
            $tabs['tracking']['icon'] = 'eye';
            $tabs['tracking']['route'] = '/database/tracking';
            $tabs['tracking']['active'] = $route === '/database/tracking';
        }

        if (! $db_is_system_schema) {
            $tabs['designer']['text'] = __('Designer');
            $tabs['designer']['icon'] = 'b_relations';
            $tabs['designer']['route'] = '/database/designer';
            $tabs['designer']['active'] = $route === '/database/designer';
        }

        if (! $db_is_system_schema
            && $cfgRelation['centralcolumnswork']
        ) {
            $tabs['central_columns']['text'] = __('Central columns');
            $tabs['central_columns']['icon'] = 'centralColumns';
            $tabs['central_columns']['route'] = '/database/central-columns';
            $tabs['central_columns']['active'] = $route === '/database/central-columns';
        }
        return $tabs;
    }

    /**
     * Returns the server tabs as an array
     *
     * @return array Data for generating server tabs
     */
    private function _getServerTabs()
    {
        global $route;

        $is_superuser = $GLOBALS['dbi']->isSuperuser();
        $isCreateOrGrantUser = $GLOBALS['dbi']->isUserType('grant')
            || $GLOBALS['dbi']->isUserType('create');
        if (Util::cacheExists('binary_logs')) {
            $binary_logs = Util::cacheGet('binary_logs');
        } else {
            $binary_logs = $GLOBALS['dbi']->fetchResult(
                'SHOW MASTER LOGS',
                'Log_name',
                null,
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            Util::cacheSet('binary_logs', $binary_logs);
        }

        $tabs = [];

        $tabs['databases']['icon'] = 's_db';
        $tabs['databases']['route'] = '/server/databases';
        $tabs['databases']['text'] = __('Databases');
        $tabs['databases']['active'] = $route === '/server/databases';

        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['route'] = '/server/sql';
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['active'] = $route === '/server/sql';

        $tabs['status']['icon'] = 's_status';
        $tabs['status']['route'] = '/server/status';
        $tabs['status']['text'] = __('Status');
        $tabs['status']['active'] = in_array($route, [
            '/server/status',
            '/server/status/advisor',
            '/server/status/monitor',
            '/server/status/processes',
            '/server/status/queries',
            '/server/status/variables',
        ]);

        if ($is_superuser || $isCreateOrGrantUser) {
            $tabs['rights']['icon'] = 's_rights';
            $tabs['rights']['route'] = '/server/privileges';
            $tabs['rights']['text'] = __('User accounts');
            $tabs['rights']['active'] = in_array($route, [
                '/server/privileges',
                '/server/user-groups',
            ]);
            $tabs['rights']['args']['viewing_mode'] = 'server';
        }

        $tabs['export']['icon'] = 'b_export';
        $tabs['export']['route'] = '/server/export';
        $tabs['export']['text'] = __('Export');
        $tabs['export']['active'] = $route === '/server/export';

        $tabs['import']['icon'] = 'b_import';
        $tabs['import']['route'] = '/server/import';
        $tabs['import']['text'] = __('Import');
        $tabs['import']['active'] = $route === '/server/import';

        $tabs['settings']['icon'] = 'b_tblops';
        $tabs['settings']['route'] = '/preferences/manage';
        $tabs['settings']['text'] = __('Settings');
        $tabs['settings']['active'] = in_array($route, [
            '/preferences/forms',
            '/preferences/manage',
            '/preferences/two-factor',
        ]);

        if (! empty($binary_logs)) {
            $tabs['binlog']['icon'] = 's_tbl';
            $tabs['binlog']['route'] = '/server/binlog';
            $tabs['binlog']['text'] = __('Binary log');
            $tabs['binlog']['active'] = $route === '/server/binlog';
        }

        if ($is_superuser) {
            $tabs['replication']['icon'] = 's_replication';
            $tabs['replication']['route'] = '/server/replication';
            $tabs['replication']['text'] = __('Replication');
            $tabs['replication']['active'] = $route === '/server/replication';
        }

        $tabs['vars']['icon'] = 's_vars';
        $tabs['vars']['route'] = '/server/variables';
        $tabs['vars']['text'] = __('Variables');
        $tabs['vars']['active'] = $route === '/server/variables';

        $tabs['charset']['icon'] = 's_asci';
        $tabs['charset']['route'] = '/server/collations';
        $tabs['charset']['text'] = __('Charsets');
        $tabs['charset']['active'] = $route === '/server/collations';

        $tabs['engine']['icon'] = 'b_engine';
        $tabs['engine']['route'] = '/server/engines';
        $tabs['engine']['text'] = __('Engines');
        $tabs['engine']['active'] = $route === '/server/engines';

        $tabs['plugins']['icon'] = 'b_plugin';
        $tabs['plugins']['route'] = '/server/plugins';
        $tabs['plugins']['text'] = __('Plugins');
        $tabs['plugins']['active'] = $route === '/server/plugins';

        return $tabs;
    }

    /**
     * Set current table
     *
     * @param string $table Current table
     *
     * @return Menu
     */
    public function setTable($table)
    {
        $this->_table = $table;
        return $this;
    }
}
