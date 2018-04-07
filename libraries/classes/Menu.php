<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Generates and renders the top menu
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Class for generating the top menu
 *
 * @package PhpMyAdmin
 */
class Menu
{
    /**
     * Server id
     *
     * @access private
     * @var int
     */
    private $_server;
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
     * @var Relation $relation
     */
    private $relation;

    /**
     * Creates a new instance of Menu
     *
     * @param int    $server Server id
     * @param string $db     Database name
     * @param string $table  Table name
     */
    public function __construct($server, $db, $table)
    {
        $this->_server = $server;
        $this->_db = $db;
        $this->_table = $table;
        $this->relation = new Relation();
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
        $url_params = array();

        if (strlen($this->_table) > 0) {
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
        $tbl_is_view = $GLOBALS['dbi']->getTable($this->_db, $this->_table)
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
                array('class' => 'item')
            );
        }
        $retval .= sprintf(
            $item,
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabServer'], 'server'
            ),
            Url::getCommon(),
            htmlspecialchars($server_info),
            __('Server')
        );

        if (strlen($this->_db) > 0) {
            $retval .= $separator;
            if (Util::showIcons('TabsMode')) {
                $retval .= Util::getImage(
                    's_db',
                    '',
                    array('class' => 'item')
                );
            }
            $retval .= sprintf(
                $item,
                Util::getScriptNameForOption(
                    $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
                ),
                Url::getCommon(array('db' => $this->_db)),
                htmlspecialchars($this->_db),
                __('Database')
            );
            // if the table is being dropped, $_REQUEST['purge'] is set to '1'
            // so do not display the table name in upper div
            if (strlen($this->_table) > 0
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
                        array('class' => 'item')
                    );
                }
                $retval .= sprintf(
                    $item,
                    Util::getScriptNameForOption(
                        $GLOBALS['cfg']['DefaultTabTable'], 'table'
                    ),
                    Url::getCommon(
                        array(
                            'db' => $this->_db, 'table' => $this->_table
                        )
                    ),
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
                        __('“%s”'), htmlspecialchars($show_comment)
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

        $tabs = array();

        $tabs['browse']['icon'] = 'b_browse';
        $tabs['browse']['text'] = __('Browse');
        $tabs['browse']['link'] = 'sql.php';
        $tabs['browse']['args']['pos'] = 0;

        $tabs['structure']['icon'] = 'b_props';
        $tabs['structure']['link'] = 'tbl_structure.php';
        $tabs['structure']['text'] = __('Structure');
        $tabs['structure']['active'] = in_array(
            basename($GLOBALS['PMA_PHP_SELF']),
            array('tbl_structure.php', 'tbl_relation.php')
        );

        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['link'] = 'tbl_sql.php';
        $tabs['sql']['text'] = __('SQL');

        $tabs['search']['icon'] = 'b_search';
        $tabs['search']['text'] = __('Search');
        $tabs['search']['link'] = 'tbl_select.php';
        $tabs['search']['active'] = in_array(
            basename($GLOBALS['PMA_PHP_SELF']),
            array('tbl_select.php', 'tbl_zoom_select.php', 'tbl_find_replace.php')
        );

        if (! $db_is_system_schema && (! $tbl_is_view || $updatable_view)) {
            $tabs['insert']['icon'] = 'b_insrow';
            $tabs['insert']['link'] = 'tbl_change.php';
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
            $tabs['privileges']['link'] = 'server_privileges.php';
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
            $tabs['tracking']['link'] = 'tbl_tracking.php';
        }
        if (! $db_is_system_schema
            && Util::currentUserHasPrivilege(
                'TRIGGER',
                $this->_db,
                $this->_table
            )
            && ! $tbl_is_view
        ) {
            $tabs['triggers']['link'] = 'tbl_triggers.php';
            $tabs['triggers']['text'] = __('Triggers');
            $tabs['triggers']['icon'] = 'b_triggers';
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

        $tabs = array();

        $tabs['structure']['link'] = 'db_structure.php';
        $tabs['structure']['text'] = __('Structure');
        $tabs['structure']['icon'] = 'b_props';

        $tabs['sql']['link'] = 'db_sql.php';
        $tabs['sql']['text'] = __('SQL');
        $tabs['sql']['icon'] = 'b_sql';

        $tabs['search']['text'] = __('Search');
        $tabs['search']['icon'] = 'b_search';
        $tabs['search']['link'] = 'db_search.php';
        if ($num_tables == 0) {
            $tabs['search']['warning'] = __('Database seems to be empty!');
        }

        $tabs['multi_table_query']['text'] = __('Query');
        $tabs['multi_table_query']['icon'] = 's_db';
        $tabs['multi_table_query']['link'] = 'db_multi_table_query.php';
        if ($num_tables == 0) {
            $tabs['qbe']['warning'] = __('Database seems to be empty!');
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

            $tabs['operation']['link'] = 'db_operations.php';
            $tabs['operation']['text'] = __('Operations');
            $tabs['operation']['icon'] = 'b_tblops';

            if (($is_superuser || $isCreateOrGrantUser)) {
                $tabs['privileges']['link'] = 'server_privileges.php';
                $tabs['privileges']['args']['checkprivsdb'] = $this->_db;
                // stay on database view
                $tabs['privileges']['args']['viewing_mode'] = 'db';
                $tabs['privileges']['text'] = __('Privileges');
                $tabs['privileges']['icon'] = 's_rights';
            }

            $tabs['routines']['link'] = 'db_routines.php';
            $tabs['routines']['text'] = __('Routines');
            $tabs['routines']['icon'] = 'b_routines';

            if (Util::currentUserHasPrivilege('EVENT', $this->_db)) {
                $tabs['events']['link'] = 'db_events.php';
                $tabs['events']['text'] = __('Events');
                $tabs['events']['icon'] = 'b_events';
            }

            if (Util::currentUserHasPrivilege('TRIGGER', $this->_db)) {
                $tabs['triggers']['link'] = 'db_triggers.php';
                $tabs['triggers']['text'] = __('Triggers');
                $tabs['triggers']['icon'] = 'b_triggers';
            }
        }

        if (Tracker::isActive() && ! $db_is_system_schema) {
            $tabs['tracking']['text'] = __('Tracking');
            $tabs['tracking']['icon'] = 'eye';
            $tabs['tracking']['link'] = 'db_tracking.php';
        }

        if (! $db_is_system_schema) {
            $tabs['designer']['text'] = __('Designer');
            $tabs['designer']['icon'] = 'b_relations';
            $tabs['designer']['link'] = 'db_designer.php';
            $tabs['designer']['id'] = 'designer_tab';
        }

        if (! $db_is_system_schema
            && $cfgRelation['centralcolumnswork']
        ) {
            $tabs['central_columns']['text'] = __('Central columns');
            $tabs['central_columns']['icon'] = 'centralColumns';
            $tabs['central_columns']['link'] = 'db_central_columns.php';
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

        $tabs = array();

        $tabs['databases']['icon'] = 's_db';
        $tabs['databases']['link'] = 'server_databases.php';
        $tabs['databases']['text'] = __('Databases');

        $tabs['sql']['icon'] = 'b_sql';
        $tabs['sql']['link'] = 'server_sql.php';
        $tabs['sql']['text'] = __('SQL');

        $tabs['status']['icon'] = 's_status';
        $tabs['status']['link'] = 'server_status.php';
        $tabs['status']['text'] = __('Status');
        $tabs['status']['active'] = in_array(
            basename($GLOBALS['PMA_PHP_SELF']),
            array(
                'server_status.php',
                'server_status_advisor.php',
                'server_status_monitor.php',
                'server_status_queries.php',
                'server_status_variables.php',
                'server_status_processes.php'
            )
        );

        if ($is_superuser || $isCreateOrGrantUser) {
            $tabs['rights']['icon'] = 's_rights';
            $tabs['rights']['link'] = 'server_privileges.php';
            $tabs['rights']['text'] = __('User accounts');
            $tabs['rights']['active'] = in_array(
                basename($GLOBALS['PMA_PHP_SELF']),
                array('server_privileges.php', 'server_user_groups.php')
            );
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
            array('prefs_forms.php', 'prefs_manage.php')
        );

        if (! empty($binary_logs)) {
            $tabs['binlog']['icon'] = 's_tbl';
            $tabs['binlog']['link'] = 'server_binlog.php';
            $tabs['binlog']['text'] = __('Binary log');
        }

        if ($is_superuser) {
            $tabs['replication']['icon'] = 's_replication';
            $tabs['replication']['link'] = 'server_replication.php';
            $tabs['replication']['text'] = __('Replication');
        }

        $tabs['vars']['icon'] = 's_vars';
        $tabs['vars']['link'] = 'server_variables.php';
        $tabs['vars']['text'] = __('Variables');

        $tabs['charset']['icon'] = 's_asci';
        $tabs['charset']['link'] = 'server_collations.php';
        $tabs['charset']['text'] = __('Charsets');

        $tabs['engine']['icon'] = 'b_engine';
        $tabs['engine']['link'] = 'server_engines.php';
        $tabs['engine']['text'] = __('Engines');

        $tabs['plugins']['icon'] = 'b_plugin';
        $tabs['plugins']['link'] = 'server_plugins.php';
        $tabs['plugins']['text'] = __('Plugins');

        return $tabs;
    }

    /**
     * Set current table
     *
     * @param string $table Current table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->_table = $table;
        return $this;
    }
}
