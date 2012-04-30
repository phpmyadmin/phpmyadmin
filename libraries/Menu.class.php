<?php

class Menu {
    private $server;
    private $db;
    private $table;
    private $is_superuser;

    public function __construct($server, $db, $table){
        $this->server = $server;
        $this->db = $db;
        $this->table = $table;
        $this->is_superuser = PMA_isSuperuser();

        if (! $GLOBALS['is_ajax_request']) {
            echo $this->getMenu();
            if (! empty($GLOBALS['message'])) {
                PMA_showMessage($GLOBALS['message']);
                unset($GLOBALS['message']);
            }
        } else {
            // nothing for now
        }
    }

    private function getMenu()
    {
        $tabs = '';
        $url_params = array('db' => $this->db);
        if (strlen($this->table)) {
            $tabs = $this->getTableTabs();
            $url_params['table'] = $this->table;
        } else if (strlen($this->db)) {
            $tabs = $this->getDbTabs();
        } else {
            $tabs = $this->getServerTabs();
        }
        return PMA_generate_html_tabs($tabs, $url_params);
    }

    private function getTableTabs()
    {
        $db_is_information_schema = PMA_is_system_schema($this->db);
        $tbl_is_view = PMA_Table::isView($this->db, $this->table);
        $table_status = PMA_Table::sGetStatusInfo($this->db, $this->table);
        $table_info_num_rows = isset($table_status['Rows']) ? $table_status['Rows'] : 0;

        $tabs = array();

        $tabs['browse']['icon'] = 'b_browse.png';
        $tabs['browse']['text'] = __('Browse');
        $tabs['browse']['link'] = 'sql.php';
        $tabs['browse']['args']['pos'] = 0;

        $tabs['structure']['icon'] = 'b_props.png';
        $tabs['structure']['link'] = 'tbl_structure.php';
        $tabs['structure']['text'] = __('Structure');

        $tabs['sql']['icon'] = 'b_sql.png';
        $tabs['sql']['link'] = 'tbl_sql.php';
        $tabs['sql']['text'] = __('SQL');

        $tabs['search']['icon'] = 'b_search.png';
        $tabs['search']['text'] = __('Search');
        $tabs['search']['link'] = 'tbl_select.php';

        if (!$db_is_information_schema) {
            $tabs['insert']['icon'] = 'b_insrow.png';
            $tabs['insert']['link'] = 'tbl_change.php';
            $tabs['insert']['text'] = __('Insert');
        }

        $tabs['export']['icon'] = 'b_tblexport.png';
        $tabs['export']['link'] = 'tbl_export.php';
        $tabs['export']['args']['single_table'] = 'true';
        $tabs['export']['text'] = __('Export');

        /**
         * Don't display "Import" and "Operations"
         * for views and information_schema
         */
        if (! $tbl_is_view && !$db_is_information_schema) {
            $tabs['import']['icon'] = 'b_tblimport.png';
            $tabs['import']['link'] = 'tbl_import.php';
            $tabs['import']['text'] = __('Import');

            $tabs['operation']['icon'] = 'b_tblops.png';
            $tabs['operation']['link'] = 'tbl_operations.php';
            $tabs['operation']['text'] = __('Operations');
        }
        if (PMA_Tracker::isActive()) {
            $tabs['tracking']['icon'] = 'eye.png';
            $tabs['tracking']['text'] = __('Tracking');
            $tabs['tracking']['link'] = 'tbl_tracking.php';
        }
        if (!$db_is_information_schema && !PMA_DRIZZLE) {
            if (PMA_currentUserHasPrivilege('TRIGGER', $this->db, $this->table) && ! $tbl_is_view) {
                $tabs['triggers']['link'] = 'tbl_triggers.php';
                $tabs['triggers']['text'] = __('Triggers');
                $tabs['triggers']['icon'] = 'b_triggers.png';
            }
        }

        /**
         * Views support a limited number of operations
         */
        if ($tbl_is_view && !$db_is_information_schema) {
            $tabs['operation']['icon'] = 'b_tblops.png';
            $tabs['operation']['link'] = 'view_operations.php';
            $tabs['operation']['text'] = __('Operations');
        }

        if ($table_info_num_rows == 0 && !$tbl_is_view) {
            $tabs['browse']['warning'] = __('Table seems to be empty!');
            $tabs['search']['warning'] = __('Table seems to be empty!');
        }
        return $tabs;
    }

    private function getDbTabs()
    {
        $db_is_information_schema = PMA_is_system_schema($this->db);
        $num_tables = count(PMA_DBI_get_tables($this->db));
        /**
         * Gets the relation settings
         */
        $cfgRelation = PMA_getRelationsParam();

        /**
         * export, search and qbe links if there is at least one table
         */
        if ($num_tables == 0) {
            $tab_qbe['warning'] = __('Database seems to be empty!');
            $tab_search['warning'] = __('Database seems to be empty!');
            $tab_export['warning'] = __('Database seems to be empty!');
        }

        $tab_structure['link']  = 'db_structure.php';
        $tab_structure['text']  = __('Structure');
        $tab_structure['icon']  = 'b_props.png';

        $tab_sql['link']        = 'db_sql.php';
        $tab_sql['args']['db_query_force'] = 1;
        $tab_sql['text']        = __('SQL');
        $tab_sql['icon']        = 'b_sql.png';

        $tab_export['text']     = __('Export');
        $tab_export['icon']     = 'b_export.png';
        $tab_export['link']     = 'db_export.php';

        $tab_search['text']     = __('Search');
        $tab_search['icon']     = 'b_search.png';
        $tab_search['link']     = 'db_search.php';

        if (PMA_Tracker::isActive()) {
            $tab_tracking['text'] = __('Tracking');
            $tab_tracking['icon'] = 'eye.png';
            $tab_tracking['link'] = 'db_tracking.php';
        }

        $tab_qbe['text']        = __('Query');
        $tab_qbe['icon']        = 's_db.png';
        $tab_qbe['link']        = 'db_qbe.php';

        if ($cfgRelation['designerwork']) {
            $tab_designer['text']   = __('Designer');
            $tab_designer['icon']   = 'b_relations.png';
            $tab_designer['link']   = 'pmd_general.php';
        }

        if (! $db_is_information_schema) {
            $tab_import['link']     = 'db_import.php';
            $tab_import['text']     = __('Import');
            $tab_import['icon']     = 'b_import.png';
            $tab_operation['link']  = 'db_operations.php';
            $tab_operation['text']  = __('Operations');
            $tab_operation['icon']  = 'b_tblops.png';
            if ($this->is_superuser && !PMA_DRIZZLE) {
                $tab_privileges['link'] = 'server_privileges.php';
                $tab_privileges['args']['checkprivs']       = $this->db;
                // stay on database view
                $tab_privileges['args']['viewing_mode'] = 'db';
                $tab_privileges['text'] = __('Privileges');
                $tab_privileges['icon'] = 's_rights.png';
            }
            $tab_routines['link']   = 'db_routines.php';
            $tab_routines['text']   = __('Routines');
            $tab_routines['icon']   = 'b_routines.png';

            $tab_events['link']     = 'db_events.php';
            $tab_events['text']     = __('Events');
            $tab_events['icon']     = 'b_events.png';

            $tab_triggers['link']   = 'db_triggers.php';
            $tab_triggers['text']   = __('Triggers');
            $tab_triggers['icon']   = 'b_triggers.png';
        }

        /**
         * Displays tab links
         */
        $tabs = array();
        $tabs[] =& $tab_structure;
        $tabs[] =& $tab_sql;
        $tabs[] =& $tab_search;
        $tabs[] =& $tab_qbe;
        $tabs[] =& $tab_export;
        if (! $db_is_information_schema) {
            $tabs[] =& $tab_import;
            $tabs[] =& $tab_operation;
            if ($this->is_superuser && !PMA_DRIZZLE) {
                $tabs[] =& $tab_privileges;
            }
            if (!PMA_DRIZZLE) {
                $tabs[] =& $tab_routines;
            }
            if (PMA_MYSQL_INT_VERSION >= 50106 && ! PMA_DRIZZLE) {
                if (PMA_currentUserHasPrivilege('EVENT', $this->db)) {
                    $tabs[] =& $tab_events;
                }
            }
            if (!PMA_DRIZZLE) {
                if (PMA_currentUserHasPrivilege('TRIGGER', $this->db)) {
                    $tabs[] =& $tab_triggers;
                }
            }
        }
        if (PMA_Tracker::isActive()) {
            $tabs[] =& $tab_tracking;
        }
        if (! $db_is_information_schema) {
            if ($cfgRelation['designerwork']) {
                $tabs[] =& $tab_designer;
            }
        }
        return $tabs;
    }

    private function getServerTabs()
    {
        $binary_logs = PMA_DRIZZLE
            ? null
            : PMA_DBI_fetch_result('SHOW MASTER LOGS', 'Log_name', null, null, PMA_DBI_QUERY_STORE);

        $tabs = array();

        $tabs['databases']['icon'] = 's_db.png';
        $tabs['databases']['link'] = 'server_databases.php';
        $tabs['databases']['text'] = __('Databases');

        $tabs['sql']['icon'] = 'b_sql.png';
        $tabs['sql']['link'] = 'server_sql.php';
        $tabs['sql']['text'] = __('SQL');

        $tabs['status']['icon'] = 's_status.png';
        $tabs['status']['link'] = 'server_status.php';
        $tabs['status']['text'] = __('Status');

        if ($this->is_superuser && ! PMA_DRIZZLE) {
            $tabs['rights']['icon'] = 's_rights.png';
            $tabs['rights']['link'] = 'server_privileges.php';
            $tabs['rights']['text'] = __('Users');
        }

        $tabs['export']['icon'] = 'b_export.png';
        $tabs['export']['link'] = 'server_export.php';
        $tabs['export']['text'] = __('Export');

        $tabs['import']['icon'] = 'b_import.png';
        $tabs['import']['link'] = 'server_import.php';
        $tabs['import']['text'] = __('Import');

        $tabs['settings']['icon'] = 'b_tblops.png';
        $tabs['settings']['link'] = 'prefs_manage.php';
        $tabs['settings']['text'] = __('Settings');
        $tabs['settings']['active'] = in_array(
            basename($GLOBALS['PMA_PHP_SELF']),
            array('prefs_forms.php', 'prefs_manage.php')
        );

        $tabs['synchronize']['icon'] = 's_sync.png';
        $tabs['synchronize']['link'] = 'server_synchronize.php';
        $tabs['synchronize']['text'] = __('Synchronize');

        if (! empty($binary_logs)) {
            $tabs['binlog']['icon'] = 's_tbl.png';
            $tabs['binlog']['link'] = 'server_binlog.php';
            $tabs['binlog']['text'] = __('Binary log');
        }

        if ($this->is_superuser && ! PMA_DRIZZLE) {
            $tabs['replication']['icon'] = 's_replication.png';
            $tabs['replication']['link'] = 'server_replication.php';
            $tabs['replication']['text'] = __('Replication');
        }

        $tabs['vars']['icon'] = 's_vars.png';
        $tabs['vars']['link'] = 'server_variables.php';
        $tabs['vars']['text'] = __('Variables');

        $tabs['charset']['icon'] = 's_asci.png';
        $tabs['charset']['link'] = 'server_collations.php';
        $tabs['charset']['text'] = __('Charsets');

        if (PMA_DRIZZLE) {
            $tabs['plugins']['icon'] = 'b_engine.png';
            $tabs['plugins']['link'] = 'server_plugins.php';
            $tabs['plugins']['text'] = __('Plugins');
        } else {
            $tabs['engine']['icon'] = 'b_engine.png';
            $tabs['engine']['link'] = 'server_engines.php';
            $tabs['engine']['text'] = __('Engines');
        }
        return $tabs;
    }
}

?>
