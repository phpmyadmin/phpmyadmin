<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Database\Designer class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PhpMyAdmin\Database\Designer\DesignerTable;
use stdClass;

/**
 * Set of functions related to database designer
 *
 * @package PhpMyAdmin
 */
class Designer
{
    /**
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var Template
     */
    public $template;

    /**
     * Designer constructor.
     *
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Relation          $relation Relation instance
     * @param Template          $template Template instance
     */
    public function __construct(DatabaseInterface $dbi, Relation $relation, Template $template)
    {
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->template = $template;
    }

    /**
     * Function to get html for displaying the page edit/delete form
     *
     * @param string $db        database name
     * @param string $operation 'edit' or 'delete' depending on the operation
     *
     * @return string html content
     */
    public function getHtmlForEditOrDeletePages($db, $operation)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        return $this->template->render('database/designer/edit_delete_pages', [
            'db' => $db,
            'operation' => $operation,
            'pdfwork' => $cfgRelation['pdfwork'],
            'pages' => $this->getPageIdsAndNames($db),
        ]);
    }

    /**
     * Function to get html for displaying the page save as form
     *
     * @param string $db database name
     *
     * @return string html content
     */
    public function getHtmlForPageSaveAs($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        return $this->template->render('database/designer/page_save_as', [
            'db' => $db,
            'pdfwork' => $cfgRelation['pdfwork'],
            'pages' => $this->getPageIdsAndNames($db),
        ]);
    }

    /**
     * Retrieve IDs and names of schema pages
     *
     * @param string $db database name
     *
     * @return array array of schema page id and names
     */
    private function getPageIdsAndNames($db)
    {
        $result = [];
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return $result;
        }

        $page_query = "SELECT `page_nr`, `page_descr` FROM "
            . Util::backquote($cfgRelation['db']) . "."
            . Util::backquote($cfgRelation['pdf_pages'])
            . " WHERE db_name = '" . $this->dbi->escapeString($db) . "'"
            . " ORDER BY `page_descr`";
        $page_rs = $this->relation->queryAsControlUser(
            $page_query,
            false,
            DatabaseInterface::QUERY_STORE
        );

        while ($curr_page = $this->dbi->fetchAssoc($page_rs)) {
            $result[intval($curr_page['page_nr'])] = $curr_page['page_descr'];
        }
        return $result;
    }

    /**
     * Function to get html for displaying the schema export
     *
     * @param string $db   database name
     * @param int    $page the page to be exported
     *
     * @return string
     */
    public function getHtmlForSchemaExport($db, $page)
    {
        /* Scan for schema plugins */
        /** @var SchemaPlugin[] $export_list */
        $export_list = Plugins::getPlugins(
            "schema",
            'libraries/classes/Plugins/Schema/',
            null
        );

        /* Fail if we didn't find any schema plugin */
        if (empty($export_list)) {
            return Message::error(
                __('Could not load schema plugins, please check your installation!')
            )->getDisplay();
        }

        return $this->template->render('database/designer/schema_export', [
            'db' => $db,
            'page' => $page,
            'export_list' => $export_list,
        ]);
    }

    /**
     * Returns array of stored values of Designer Settings
     *
     * @return array stored values
     */
    private function getSideMenuParamsArray()
    {
        $params = [];

        $cfgRelation = $this->relation->getRelationsParam();

        if ($cfgRelation['designersettingswork']) {
            $query = 'SELECT `settings_data` FROM '
                . Util::backquote($cfgRelation['db']) . '.'
                . Util::backquote($cfgRelation['designer_settings'])
                . ' WHERE ' . Util::backquote('username') . ' = "'
                . $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user'])
                . '";';

            $result = $this->dbi->fetchSingleRow($query);
            if (is_array($result)) {
                $params = json_decode((string) $result['settings_data'], true);
            }
        }

        return $params;
    }

    /**
     * Returns class names for various buttons on Designer Side Menu
     *
     * @return array class names of various buttons
     */
    public function returnClassNamesFromMenuButtons()
    {
        $classes_array = [];
        $params_array = $this->getSideMenuParamsArray();

        if (isset($params_array['angular_direct'])
            && $params_array['angular_direct'] == 'angular'
        ) {
            $classes_array['angular_direct'] = 'M_butt_Selected_down';
        } else {
            $classes_array['angular_direct'] = 'M_butt';
        }

        if (isset($params_array['snap_to_grid'])
            && $params_array['snap_to_grid'] == 'on'
        ) {
            $classes_array['snap_to_grid'] = 'M_butt_Selected_down';
        } else {
            $classes_array['snap_to_grid'] = 'M_butt';
        }

        if (isset($params_array['pin_text'])
            && $params_array['pin_text'] == 'true'
        ) {
            $classes_array['pin_text'] = 'M_butt_Selected_down';
        } else {
            $classes_array['pin_text'] = 'M_butt';
        }

        if (isset($params_array['relation_lines'])
            && $params_array['relation_lines'] == 'false'
        ) {
            $classes_array['relation_lines'] = 'M_butt_Selected_down';
        } else {
            $classes_array['relation_lines'] = 'M_butt';
        }

        if (isset($params_array['small_big_all'])
            && $params_array['small_big_all'] == 'v'
        ) {
            $classes_array['small_big_all'] = 'M_butt_Selected_down';
        } else {
            $classes_array['small_big_all'] = 'M_butt';
        }

        if (isset($params_array['side_menu'])
            && $params_array['side_menu'] == 'true'
        ) {
            $classes_array['side_menu'] = 'M_butt_Selected_down';
        } else {
            $classes_array['side_menu'] = 'M_butt';
        }

        return $classes_array;
    }

    /**
     * Get HTML to display tables on designer page
     *
     * @param string          $db                       The database name from the request
     * @param DesignerTable[] $designerTables           The designer tables
     * @param array           $tab_pos                  tables positions
     * @param int             $display_page             page number of the selected page
     * @param array           $tab_column               table column info
     * @param array           $tables_all_keys          all indices
     * @param array           $tables_pk_or_unique_keys unique or primary indices
     *
     * @return string html
     */
    public function getDatabaseTables(
        string $db,
        array $designerTables,
        array $tab_pos,
        $display_page,
        array $tab_column,
        array $tables_all_keys,
        array $tables_pk_or_unique_keys
    ) {
        $columns_type = [];
        foreach ($designerTables as $designerTable) {
            $table_name = $designerTable->getDbTableString();
            $limit = count($tab_column[$table_name]['COLUMN_ID']);
            for ($j = 0; $j < $limit; $j++) {
                $table_column_name = $table_name . '.' . $tab_column[$table_name]['COLUMN_NAME'][$j];
                if (isset($tables_pk_or_unique_keys[$table_column_name])) {
                    $columns_type[$table_column_name] = 'designer/FieldKey_small';
                } else {
                    $columns_type[$table_column_name] = 'designer/Field_small';
                    if (false !== strpos($tab_column[$table_name]['TYPE'][$j], 'char')
                        || false !== strpos($tab_column[$table_name]['TYPE'][$j], 'text')) {
                        $columns_type[$table_column_name] .= '_char';
                    } elseif (false !== strpos($tab_column[$table_name]['TYPE'][$j], 'int')
                        || false !== strpos($tab_column[$table_name]['TYPE'][$j], 'float')
                        || false !== strpos($tab_column[$table_name]['TYPE'][$j], 'double')
                        || false !== strpos($tab_column[$table_name]['TYPE'][$j], 'decimal')) {
                        $columns_type[$table_column_name] .= '_int';
                    } elseif (false !== strpos($tab_column[$table_name]['TYPE'][$j], 'date')
                        || false !== strpos($tab_column[$table_name]['TYPE'][$j], 'time')
                        || false !== strpos($tab_column[$table_name]['TYPE'][$j], 'year')) {
                        $columns_type[$table_column_name] .= '_date';
                    }
                }
            }
        }
        return $this->template->render('database/designer/database_tables', [
            'db' => $GLOBALS['db'],
            'get_db' => $db,
            'has_query' => isset($_REQUEST['query']),
            'tab_pos' => $tab_pos,
            'display_page' => $display_page,
            'tab_column' => $tab_column,
            'tables_all_keys' => $tables_all_keys,
            'tables_pk_or_unique_keys' => $tables_pk_or_unique_keys,
            'tables' => $designerTables,
            'columns_type' => $columns_type,
            'theme' => $GLOBALS['PMA_Theme'],
        ]);
    }


    /**
     * Returns HTML for Designer page
     *
     * @param string          $db                   database in use
     * @param string          $getDb                database in url
     * @param DesignerTable[] $designerTables       The designer tables
     * @param array           $scriptTables         array on foreign key support for each table
     * @param array           $scriptContr          initialization data array
     * @param DesignerTable[] $scriptDisplayField   displayed tables in designer with their display fields
     * @param int             $displayPage          page number of the selected page
     * @param boolean         $hasQuery             whether this is visual query builder
     * @param string          $selectedPage         name of the selected page
     * @param array           $paramsArray          array with class name for various buttons on side menu
     * @param array|null      $tabPos               table positions
     * @param array           $tabColumn            table column info
     * @param array           $tablesAllKeys        all indices
     * @param array           $tablesPkOrUniqueKeys unique or primary indices
     *
     * @return string html
     */
    public function getHtmlForMain(
        string $db,
        string $getDb,
        array $designerTables,
        array $scriptTables,
        array $scriptContr,
        array $scriptDisplayField,
        $displayPage,
        $hasQuery,
        $selectedPage,
        array $paramsArray,
        ?array $tabPos,
        array $tabColumn,
        array $tablesAllKeys,
        array $tablesPkOrUniqueKeys
    ): string {
        $cfgRelation = $this->relation->getRelationsParam();
        $columnsType = [];
        foreach ($designerTables as $designerTable) {
            $tableName = $designerTable->getDbTableString();
            $limit = count($tabColumn[$tableName]['COLUMN_ID']);
            for ($j = 0; $j < $limit; $j++) {
                $tableColumnName = $tableName . '.' . $tabColumn[$tableName]['COLUMN_NAME'][$j];
                if (isset($tablesPkOrUniqueKeys[$tableColumnName])) {
                    $columnsType[$tableColumnName] = 'designer/FieldKey_small';
                } else {
                    $columnsType[$tableColumnName] = 'designer/Field_small';
                    if (false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'char')
                        || false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'text')) {
                        $columnsType[$tableColumnName] .= '_char';
                    } elseif (false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'int')
                        || false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'float')
                        || false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'double')
                        || false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'decimal')) {
                        $columnsType[$tableColumnName] .= '_int';
                    } elseif (false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'date')
                        || false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'time')
                        || false !== strpos($tabColumn[$tableName]['TYPE'][$j], 'year')) {
                        $columnsType[$tableColumnName] .= '_date';
                    }
                }
            }
        }

        $displayedFields = [];
        foreach ($scriptDisplayField as $designerTable) {
            if ($designerTable->getDisplayField() !== null) {
                $displayedFields[$designerTable->getTableName()] = $designerTable->getDisplayField();
            }
        }

        $designerConfig = new stdClass();
        $designerConfig->db = $db;
        $designerConfig->scriptTables = $scriptTables;
        $designerConfig->scriptContr = $scriptContr;
        $designerConfig->server = $GLOBALS['server'];
        $designerConfig->scriptDisplayField = $displayedFields;
        $designerConfig->displayPage = (int) $displayPage;
        $designerConfig->tablesEnabled = $cfgRelation['pdfwork'];

        return $this->template->render('database/designer/main', [
            'db' => $db,
            'get_db' => $getDb,
            'designer_config' => json_encode($designerConfig),
            'display_page' => (int) $displayPage,
            'has_query' => $hasQuery,
            'selected_page' => $selectedPage,
            'params_array' => $paramsArray,
            'theme' => $GLOBALS['PMA_Theme'],
            'tab_pos' => $tabPos,
            'tab_column' => $tabColumn,
            'tables_all_keys' => $tablesAllKeys,
            'tables_pk_or_unique_keys' => $tablesPkOrUniqueKeys,
            'designerTables' => $designerTables,
            'columns_type' => $columnsType,
        ]);
    }
}
