<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Database\Designer\DesignerTable;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use stdClass;

use function __;
use function count;
use function intval;
use function is_array;
use function json_decode;
use function json_encode;
use function str_contains;

/**
 * Set of functions related to database designer
 */
class Designer
{
    public function __construct(private DatabaseInterface $dbi, private Relation $relation, public Template $template)
    {
    }

    /**
     * Function to get html for displaying the page edit/delete form
     *
     * @param string $db        database name
     * @param string $operation 'edit' or 'delete' depending on the operation
     *
     * @return string html content
     */
    public function getHtmlForEditOrDeletePages(string $db, string $operation): string
    {
        $relationParameters = $this->relation->getRelationParameters();

        return $this->template->render('database/designer/edit_delete_pages', [
            'db' => $db,
            'operation' => $operation,
            'pdfwork' => $relationParameters->pdfFeature !== null,
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
    public function getHtmlForPageSaveAs(string $db): string
    {
        $relationParameters = $this->relation->getRelationParameters();

        return $this->template->render('database/designer/page_save_as', [
            'db' => $db,
            'pdfwork' => $relationParameters->pdfFeature !== null,
            'pages' => $this->getPageIdsAndNames($db),
        ]);
    }

    /**
     * Retrieve IDs and names of schema pages
     *
     * @param string $db database name
     *
     * @return mixed[] array of schema page id and names
     */
    private function getPageIdsAndNames(string $db): array
    {
        $pdfFeature = $this->relation->getRelationParameters()->pdfFeature;
        if ($pdfFeature === null) {
            return [];
        }

        $pageQuery = 'SELECT `page_nr`, `page_descr` FROM '
            . Util::backquote($pdfFeature->database) . '.'
            . Util::backquote($pdfFeature->pdfPages)
            . " WHERE db_name = '" . $this->dbi->escapeString($db) . "'"
            . ' ORDER BY `page_descr`';
        $pageRs = $this->dbi->tryQueryAsControlUser($pageQuery);

        if (! $pageRs) {
            return [];
        }

        $result = [];
        while ($currPage = $pageRs->fetchAssoc()) {
            $result[intval($currPage['page_nr'])] = $currPage['page_descr'];
        }

        return $result;
    }

    /**
     * Function to get html for displaying the schema export
     *
     * @param string $db   database name
     * @param int    $page the page to be exported
     */
    public function getHtmlForSchemaExport(string $db, int $page): string
    {
        $exportList = Plugins::getSchema();

        /* Fail if we didn't find any schema plugin */
        if ($exportList === []) {
            return Message::error(
                __('Could not load schema plugins, please check your installation!'),
            )->getDisplay();
        }

        $default = isset($_GET['export_type'])
            ? (string) $_GET['export_type']
            : Plugins::getDefault('Schema', 'format');
        $choice = Plugins::getChoice($exportList, $default);
        $options = Plugins::getOptions('Schema', $exportList);

        return $this->template->render('database/designer/schema_export', [
            'db' => $db,
            'page' => $page,
            'plugins_choice' => $choice,
            'options' => $options,
        ]);
    }

    /**
     * Returns array of stored values of Designer Settings
     *
     * @return mixed[] stored values
     */
    private function getSideMenuParamsArray(): array
    {
        $params = [];

        $databaseDesignerSettingsFeature = $this->relation->getRelationParameters()->databaseDesignerSettingsFeature;
        if ($databaseDesignerSettingsFeature !== null) {
            $query = 'SELECT `settings_data` FROM '
                . Util::backquote($databaseDesignerSettingsFeature->database) . '.'
                . Util::backquote($databaseDesignerSettingsFeature->designerSettings)
                . ' WHERE ' . Util::backquote('username') . ' = "'
                . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user'])
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
     * @return array<string, string> class names of various buttons
     */
    public function returnClassNamesFromMenuButtons(): array
    {
        $classesArray = [];
        $paramsArray = $this->getSideMenuParamsArray();

        if (isset($paramsArray['angular_direct']) && $paramsArray['angular_direct'] === 'angular') {
            $classesArray['angular_direct'] = 'M_butt_Selected_down';
        } else {
            $classesArray['angular_direct'] = 'M_butt';
        }

        if (isset($paramsArray['snap_to_grid']) && $paramsArray['snap_to_grid'] === 'on') {
            $classesArray['snap_to_grid'] = 'M_butt_Selected_down';
        } else {
            $classesArray['snap_to_grid'] = 'M_butt';
        }

        if (isset($paramsArray['pin_text']) && $paramsArray['pin_text'] === 'true') {
            $classesArray['pin_text'] = 'M_butt_Selected_down';
        } else {
            $classesArray['pin_text'] = 'M_butt';
        }

        if (isset($paramsArray['relation_lines']) && $paramsArray['relation_lines'] === 'false') {
            $classesArray['relation_lines'] = 'M_butt_Selected_down';
        } else {
            $classesArray['relation_lines'] = 'M_butt';
        }

        if (isset($paramsArray['small_big_all']) && $paramsArray['small_big_all'] === 'v') {
            $classesArray['small_big_all'] = 'M_butt_Selected_down';
        } else {
            $classesArray['small_big_all'] = 'M_butt';
        }

        if (isset($paramsArray['side_menu']) && $paramsArray['side_menu'] === 'true') {
            $classesArray['side_menu'] = 'M_butt_Selected_down';
        } else {
            $classesArray['side_menu'] = 'M_butt';
        }

        return $classesArray;
    }

    /**
     * Get HTML to display tables on designer page
     *
     * @param string          $db                   The database name from the request
     * @param DesignerTable[] $designerTables       The designer tables
     * @param mixed[]         $tabPos               tables positions
     * @param int             $displayPage          page number of the selected page
     * @param mixed[]         $tabColumn            table column info
     * @param mixed[]         $tablesAllKeys        all indices
     * @param mixed[]         $tablesPkOrUniqueKeys unique or primary indices
     *
     * @return string html
     */
    public function getDatabaseTables(
        string $db,
        array $designerTables,
        array $tabPos,
        int $displayPage,
        array $tabColumn,
        array $tablesAllKeys,
        array $tablesPkOrUniqueKeys,
    ): string {
        $GLOBALS['text_dir'] ??= null;

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
                    if (
                        str_contains($tabColumn[$tableName]['TYPE'][$j], 'char')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'text')
                    ) {
                        $columnsType[$tableColumnName] .= '_char';
                    } elseif (
                        str_contains($tabColumn[$tableName]['TYPE'][$j], 'int')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'float')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'double')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'decimal')
                    ) {
                        $columnsType[$tableColumnName] .= '_int';
                    } elseif (
                        str_contains($tabColumn[$tableName]['TYPE'][$j], 'date')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'time')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'year')
                    ) {
                        $columnsType[$tableColumnName] .= '_date';
                    }
                }
            }
        }

        return $this->template->render('database/designer/database_tables', [
            'db' => $GLOBALS['db'],
            'text_dir' => $GLOBALS['text_dir'],
            'get_db' => $db,
            'has_query' => isset($_REQUEST['query']),
            'tab_pos' => $tabPos,
            'display_page' => $displayPage,
            'tab_column' => $tabColumn,
            'tables_all_keys' => $tablesAllKeys,
            'tables_pk_or_unique_keys' => $tablesPkOrUniqueKeys,
            'tables' => $designerTables,
            'columns_type' => $columnsType,
        ]);
    }

    /**
     * Returns HTML for Designer page
     *
     * @param string          $db                   database in use
     * @param string          $getDb                database in url
     * @param DesignerTable[] $designerTables       The designer tables
     * @param mixed[]         $scriptTables         array on foreign key support for each table
     * @param mixed[]         $scriptContr          initialization data array
     * @param DesignerTable[] $scriptDisplayField   displayed tables in designer with their display fields
     * @param int             $displayPage          page number of the selected page
     * @param bool            $visualBuilderMode    whether this is visual query builder
     * @param string|null     $selectedPage         name of the selected page
     * @param mixed[]         $paramsArray          array with class name for various buttons on side menu
     * @param mixed[]         $tablePositions       table positions
     * @param mixed[]         $tabColumn            table column info
     * @param mixed[]         $tablesAllKeys        all indices
     * @param mixed[]         $tablesPkOrUniqueKeys unique or primary indices
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
        int $displayPage,
        bool $visualBuilderMode,
        string|null $selectedPage,
        array $paramsArray,
        array $tablePositions,
        array $tabColumn,
        array $tablesAllKeys,
        array $tablesPkOrUniqueKeys,
    ): string {
        $GLOBALS['text_dir'] ??= null;

        $relationParameters = $this->relation->getRelationParameters();
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
                    if (
                        str_contains($tabColumn[$tableName]['TYPE'][$j], 'char')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'text')
                    ) {
                        $columnsType[$tableColumnName] .= '_char';
                    } elseif (
                        str_contains($tabColumn[$tableName]['TYPE'][$j], 'int')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'float')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'double')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'decimal')
                    ) {
                        $columnsType[$tableColumnName] .= '_int';
                    } elseif (
                        str_contains($tabColumn[$tableName]['TYPE'][$j], 'date')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'time')
                        || str_contains($tabColumn[$tableName]['TYPE'][$j], 'year')
                    ) {
                        $columnsType[$tableColumnName] .= '_date';
                    }
                }
            }
        }

        $displayedFields = [];
        foreach ($scriptDisplayField as $designerTable) {
            if ($designerTable->getDisplayField() === null) {
                continue;
            }

            $displayedFields[$designerTable->getTableName()] = $designerTable->getDisplayField();
        }

        $designerConfig = new stdClass();
        $designerConfig->db = $db;
        $designerConfig->scriptTables = $scriptTables;
        $designerConfig->scriptContr = $scriptContr;
        $designerConfig->server = $GLOBALS['server'];
        $designerConfig->scriptDisplayField = $displayedFields;
        $designerConfig->displayPage = $displayPage;
        $designerConfig->tablesEnabled = $relationParameters->pdfFeature !== null;

        return $this->template->render('database/designer/main', [
            'db' => $db,
            'text_dir' => $GLOBALS['text_dir'],
            'get_db' => $getDb,
            'designer_config' => json_encode($designerConfig),
            'display_page' => $displayPage,
            'has_query' => $visualBuilderMode,
            'visual_builder' => $visualBuilderMode,
            'selected_page' => $selectedPage,
            'params_array' => $paramsArray,
            'tab_pos' => $tablePositions,
            'tab_column' => $tabColumn,
            'tables_all_keys' => $tablesAllKeys,
            'tables_pk_or_unique_keys' => $tablesPkOrUniqueKeys,
            'designerTables' => $designerTables,
            'columns_type' => $columnsType,
        ]);
    }
}
