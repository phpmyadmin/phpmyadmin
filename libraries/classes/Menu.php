<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
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

    /**
     * @var Relation
     */
    private $relation;

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
    private function _getMenu()
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
        return Util::getHtmlTabs($tabs, $url_params, 'topmenu', true);
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
                . "."
                . Util::backquote($cfgRelation['usergroups']);
            $userTable = Util::backquote($cfgRelation['db'])
                . "." . Util::backquote($cfgRelation['users']);

            $sql_query = "SELECT `tab` FROM " . $groupTable
                . " WHERE `allowed` = 'N'"
                . " AND `tab` LIKE '" . $level . "%'"
                . " AND `usergroup` = (SELECT usergroup FROM "
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
    private function _getBreadcrumbs()
    {
        $retval = '';
        $tbl_is_view = $GLOBALS['dbi']->getTable($this->_db, (string) $this->_table)
            ->isView();
        if (empty($GLOBALS['cfg']['Server']['host'])) {
            $GLOBALS['cfg']['Server']['host'] = '';
        }
        $server_info = ! empty($GLOBALS['cfg']['Server']['verbose'])
            ? $GLOBALS['cfg']['Server']['verbose']
            : $GLOBALS['cfg']['Server']['host'];
        $server_info .= empty($GLOBALS['cfg']['Server']['port'])
            ? ''
            : ':' . $GLOBALS['cfg']['Server']['port'];

        $separator = "<span class='separator item'>&nbsp;»</span>";
        $item = '<a href="%1$s%2$s" class="item">';

        if (Util::showText('TabsMode')) {
            $item .= '%4$s: ';
        }
        $item .= '%3$s</a>';
        $retval .= "<div id='floating_menubar'></div>";
        $retval .= "<div id='serverinfo'>";
        if (Util::showIcons('TabsMode')) {
            $retval .= Util::getImage(
                's_host',
                '',
                ['class' => 'item']
            );
        }
        $scriptName = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabServer'],
            'server'
        );
        $retval .= sprintf(
            $item,
            $scriptName,
            Url::getCommon([], strpos($scriptName, '?') === false ? '?' : '&'),
            htmlspecialchars($server_info),
            __('Server')
        );

        if (strlen($this->_db) > 0) {
            $retval .= $separator;
            if (Util::showIcons('TabsMode')) {
                $retval .= Util::getImage(
                    's_db',
                    '',
                    ['class' => 'item']
                );
            }
            $scriptName = Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            );
            $retval .= sprintf(
                $item,
                $scriptName,
                Url::getCommon(['db' => $this->_db], strpos($scriptName, '?') === false ? '?' : '&'),
                htmlspecialchars($this->_db),
                __('Database')
            );
            // if the table is being dropped, $_REQUEST['purge'] is set to '1'
            // so do not display the table name in upper div
            if (strlen((string) $this->_table) > 0
                && ! (isset($_REQUEST['purge']) && $_REQUEST['purge'] == '1')
            ) {
                $table_class_object = $GLOBALS['dbi']->getTable(
                    $GLOBALS['db'],
                    $GLOBALS['table']
                );
                if ($table_class_object->isView()) {
                    $tbl_is_view = true;
                    $show_comment = null;
                } else {
                    $tbl_is_view = false;
                    $show_comment = $table_class_object->getComment();
                }
                $retval .= $separator;
                if (Util::showIcons('TabsMode')) {
                    $icon = $tbl_is_view ? 'b_views' : 's_tbl';
                    $retval .= Util::getImage(
                        $icon,
                        '',
                        ['class' => 'item']
                    );
                }
                $scriptName = Util::getScriptNameForOption(
                    $GLOBALS['cfg']['DefaultTabTable'],
                    'table'
                );
                $retval .= sprintf(
                    $item,
                    $scriptName,
                    Url::getCommon([
                        'db' => $this->_db,
                        'table' => $this->_table,
                    ], strpos($scriptName, '?') === false ? '?' : '&'),
                    str_replace(' ', '&nbsp;', htmlspecialchars($this->_table)),
                    $tbl_is_view ? __('View') : __('Table')
                );

                /**
                 * Displays table comment
                 */
                if (! empty($show_comment)
                    && ! isset($GLOBALS['avoid_show_comment'])
                ) {
                    if (mb_strstr($show_comment, '; InnoDB free')) {
                        $show_comment = preg_replace(
                            '@; InnoDB free:.*?$@',
                            '',
                            $show_comment
                        );
                    }
                    $retval .= '<span class="table_comment"';
                    $retval .= ' id="span_table_comment">';
                    $retval .= sprintf(
                        __('“%s”'),
                        htmlspecialchars($show_comment)
                    );
                    $retval .= '</span>';
                } // end if
            } else {
                // no table selected, display database comment if present
                $cfgRelation = $this->relation->getRelationsParam();

                // Get additional information about tables for tooltip is done
                // in Util::getDbInfo() only once
                if ($cfgRelation['commwork']) {
                    $comment = $this->relation->getDbComment($this->_db);
                    /**
                     * Displays table comment
                     */
                    if (! empty($comment)) {
                        $retval .= '<span class="table_comment"'
                            . ' id="span_table_comment">'
                            . sprintf(
                                __('“%s”'),
                                htmlspecialchars($comment)
                            )
                            . '</span>';
                    } // end if
                }
            }
        }
        $retval .= '<div class="clearfloat"></div>';
        $retval .= '</div>';
        return $retval;
    }

    /**
     * Returns the table tabs as an array
     *
     * @return array Data for generating table tabs
     */
    private function _getTableTabs()
    {
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
        $tabs['browse']['link'] = Url::getFromRoute('/sql');
        $tabs['browse']['args']['pos'] = 0;
        $tabs['browse']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/sql';

        $tabs['structure']['icon'] = 'b_props';
        $tabs['structure']['link'] = Url::getFromRoute('/table/structure');
        $tabs['structure']['text'] = __('Structure');
        $tabs['structure']['active'] = basename($GLOBALS['PMA_PHP_SELF']) === 'tbl_relation.php' ||
            (isset($_REQUEST['route']) && in_array($_REQUEST['route'], ['/table/structure']));

        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['link'] = Url::getFromRoute('/table/sql');
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/table/sql';

        $tabs['search']['icon'] = 'b_search';
        $tabs['search']['text'] = __('Search');
        $tabs['search']['link'] = Url::getFromRoute('/table/search');
        $tabs['search']['active'] = in_array(basename($GLOBALS['PMA_PHP_SELF']), [
            'tbl_zoom_select.php',
            'tbl_find_replace.php',
        ]) || (isset($_REQUEST['route']) && in_array($_REQUEST['route'], [
            '/table/search',
        ]));

        if (! $db_is_system_schema && (! $tbl_is_view || $updatable_view)) {
            $tabs['insert']['icon'] = 'b_insrow';
            $tabs['insert']['link'] = Url::getFromRoute('/table/change');
            $tabs['insert']['text'] = __('Insert');
        }

        $tabs['export']['icon'] = 'b_tblexport';
        $tabs['export']['link'] = 'tbl_export.php';
        $tabs['export']['args']['single_table'] = 'true';
        $tabs['export']['text'] = __('Export');

        /**
         * Don't display "Import" for views and information_schema
         */
        if (! $tbl_is_view && ! $db_is_system_schema) {
            $tabs['import']['icon'] = 'b_tblimport';
            $tabs['import']['link'] = 'tbl_import.php';
            $tabs['import']['text'] = __('Import');
        }
        if (($is_superuser || $isCreateOrGrantUser)
            && ! $db_is_system_schema
        ) {
            $tabs['privileges']['link'] = Url::getFromRoute('/server/privileges');
            $tabs['privileges']['args']['checkprivsdb'] = $this->_db;
            $tabs['privileges']['args']['checkprivstable'] = $this->_table;
            // stay on table view
            $tabs['privileges']['args']['viewing_mode'] = 'table';
            $tabs['privileges']['text'] = __('Privileges');
            $tabs['privileges']['icon'] = 's_rights';
        }
        /**
         * Don't display "Operations" for views and information_schema
         */
        if (! $tbl_is_view && ! $db_is_system_schema) {
            $tabs['operation']['icon'] = 'b_tblops';
            $tabs['operation']['link'] = 'tbl_operations.php';
            $tabs['operation']['text'] = __('Operations');
        }
        /**
         * Views support a limited number of operations
         */
        if ($tbl_is_view && ! $db_is_system_schema) {
            $tabs['operation']['icon'] = 'b_tblops';
            $tabs['operation']['link'] = 'view_operations.php';
            $tabs['operation']['text'] = __('Operations');
        }

        if (Tracker::isActive() && ! $db_is_system_schema) {
            $tabs['tracking']['icon'] = 'eye';
            $tabs['tracking']['text'] = __('Tracking');
            $tabs['tracking']['link'] = Url::getFromRoute('/table/tracking');
            $tabs['tracking']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/table/tracking';
        }
        if (! $db_is_system_schema
            && Util::currentUserHasPrivilege(
                'TRIGGER',
                $this->_db,
                $this->_table
            )
            && ! $tbl_is_view
        ) {
            $tabs['triggers']['link'] = Url::getFromRoute('/table/triggers');
            $tabs['triggers']['text'] = __('Triggers');
            $tabs['triggers']['icon'] = 'b_triggers';
            $tabs['triggers']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/table/triggers';
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

        $tabs['structure']['link'] = Url::getFromRoute('/database/structure');
        $tabs['structure']['text'] = __('Structure');
        $tabs['structure']['icon'] = 'b_props';
        $tabs['structure']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/structure';

        $tabs['sql']['link'] = Url::getFromRoute('/database/sql');
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/sql';

        $tabs['search']['text'] = __('Search');
        $tabs['search']['icon'] = 'b_search';
        $tabs['search']['link'] = Url::getFromRoute('/database/search');
        if ($num_tables == 0) {
            $tabs['search']['warning'] = __('Database seems to be empty!');
        }

        $tabs['query']['text'] = __('Query');
        $tabs['query']['icon'] = 's_db';
        $tabs['query']['link'] = Url::getFromRoute('/database/multi_table_query');
        $tabs['query']['active'] = isset($_REQUEST['route'])
            && ($_REQUEST['route'] === '/database/multi_table_query'
            || $_REQUEST['route'] === '/database/qbe');

        if ($num_tables == 0) {
            $tabs['query']['warning'] = __('Database seems to be empty!');
        }

        $tabs['export']['text'] = __('Export');
        $tabs['export']['icon'] = 'b_export';
        $tabs['export']['link'] = 'db_export.php';
        if ($num_tables == 0) {
            $tabs['export']['warning'] = __('Database seems to be empty!');
        }

        if (! $db_is_system_schema) {
            $tabs['import']['link'] = 'db_import.php';
            $tabs['import']['text'] = __('Import');
            $tabs['import']['icon'] = 'b_import';

            $tabs['operation']['link'] = Url::getFromRoute('/database/operations');
            $tabs['operation']['text'] = __('Operations');
            $tabs['operation']['icon'] = 'b_tblops';

            if ($is_superuser || $isCreateOrGrantUser) {
                $tabs['privileges']['link'] = Url::getFromRoute('/server/privileges');
                $tabs['privileges']['args']['checkprivsdb'] = $this->_db;
                // stay on database view
                $tabs['privileges']['args']['viewing_mode'] = 'db';
                $tabs['privileges']['text'] = __('Privileges');
                $tabs['privileges']['icon'] = 's_rights';
            }

            $tabs['routines']['link'] = Url::getFromRoute('/database/routines');
            $tabs['routines']['text'] = __('Routines');
            $tabs['routines']['icon'] = 'b_routines';
            $tabs['routines']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/routines';

            if (Util::currentUserHasPrivilege('EVENT', $this->_db)) {
                $tabs['events']['link'] = Url::getFromRoute('/database/events');
                $tabs['events']['text'] = __('Events');
                $tabs['events']['icon'] = 'b_events';
                $tabs['events']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/events';
            }

            if (Util::currentUserHasPrivilege('TRIGGER', $this->_db)) {
                $tabs['triggers']['link'] = Url::getFromRoute('/database/triggers');
                $tabs['triggers']['text'] = __('Triggers');
                $tabs['triggers']['icon'] = 'b_triggers';
                $tabs['triggers']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/triggers';
            }
        }

        if (Tracker::isActive() && ! $db_is_system_schema) {
            $tabs['tracking']['text'] = __('Tracking');
            $tabs['tracking']['icon'] = 'eye';
            $tabs['tracking']['link'] = Url::getFromRoute('/database/tracking');
            $tabs['tracking']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/tracking';
        }

        if (! $db_is_system_schema) {
            $tabs['designer']['text'] = __('Designer');
            $tabs['designer']['icon'] = 'b_relations';
            $tabs['designer']['link'] = Url::getFromRoute('/database/designer');
            $tabs['designer']['id'] = 'designer_tab';
            $tabs['designer']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/designer';
        }

        if (! $db_is_system_schema
            && $cfgRelation['centralcolumnswork']
        ) {
            $tabs['central_columns']['text'] = __('Central columns');
            $tabs['central_columns']['icon'] = 'centralColumns';
            $tabs['central_columns']['link'] = Url::getFromRoute('/database/central_columns');
            $tabs['central_columns']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/database/central_columns';
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
        $tabs['databases']['link'] = Url::getFromRoute('/server/databases');
        $tabs['databases']['text'] = __('Databases');

        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['link'] = Url::getFromRoute('/server/sql');
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/server/sql';

        $tabs['status']['icon'] = 's_status';
        $tabs['status']['link'] = Url::getFromRoute('/server/status');
        $tabs['status']['text'] = __('Status');
        $tabs['status']['active'] = isset($_REQUEST['route']) && in_array($_REQUEST['route'], [
            '/server/status',
            '/server/status/advisor',
            '/server/status/monitor',
            '/server/status/processes',
            '/server/status/queries',
            '/server/status/variables',
        ]);

        if ($is_superuser || $isCreateOrGrantUser) {
            $tabs['rights']['icon'] = 's_rights';
            $tabs['rights']['link'] = Url::getFromRoute('/server/privileges');
            $tabs['rights']['text'] = __('User accounts');
            $tabs['rights']['active'] = isset($_REQUEST['route']) && in_array($_REQUEST['route'], [
                '/server/privileges',
                '/server/user_groups',
            ]);
            $tabs['rights']['args']['viewing_mode'] = 'server';
        }

        $tabs['export']['icon'] = 'b_export';
        $tabs['export']['link'] = 'server_export.php';
        $tabs['export']['text'] = __('Export');

        $tabs['import']['icon'] = 'b_import';
        $tabs['import']['link'] = 'server_import.php';
        $tabs['import']['text'] = __('Import');

        $tabs['settings']['icon']   = 'b_tblops';
        $tabs['settings']['link']   = 'prefs_manage.php';
        $tabs['settings']['text']   = __('Settings');
        $tabs['settings']['active'] = in_array(
            basename($GLOBALS['PMA_PHP_SELF']),
            [
                'prefs_forms.php',
                'prefs_manage.php',
                'prefs_twofactor.php',
            ]
        );

        if (! empty($binary_logs)) {
            $tabs['binlog']['icon'] = 's_tbl';
            $tabs['binlog']['link'] = Url::getFromRoute('/server/binlog');
            $tabs['binlog']['text'] = __('Binary log');
            $tabs['binlog']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/server/binlog';
        }

        if ($is_superuser) {
            $tabs['replication']['icon'] = 's_replication';
            $tabs['replication']['link'] = Url::getFromRoute('/server/replication');
            $tabs['replication']['text'] = __('Replication');
            $tabs['replication']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/server/replication';
        }

        $tabs['vars']['icon'] = 's_vars';
        $tabs['vars']['link'] = Url::getFromRoute('/server/variables');
        $tabs['vars']['text'] = __('Variables');

        $tabs['charset']['icon'] = 's_asci';
        $tabs['charset']['link'] = Url::getFromRoute('/server/collations');
        $tabs['charset']['text'] = __('Charsets');

        $tabs['engine']['icon'] = 'b_engine';
        $tabs['engine']['link'] = Url::getFromRoute('/server/engines');
        $tabs['engine']['text'] = __('Engines');

        $tabs['plugins']['icon'] = 'b_plugin';
        $tabs['plugins']['link'] = Url::getFromRoute('/server/plugins');
        $tabs['plugins']['text'] = __('Plugins');

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
