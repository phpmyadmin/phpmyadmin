<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Designer\ColumnInfo;
use PhpMyAdmin\Database\Designer\DesignerTable;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use stdClass;

use function __;
use function is_array;
use function json_decode;
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
     * @return array<string|null> array of schema page id and names
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
            . ' WHERE db_name = ' . $this->dbi->quoteString($db)
            . ' ORDER BY `page_descr`';
        $pageRs = $this->dbi->tryQueryAsControlUser($pageQuery);

        if (! $pageRs) {
            return [];
        }

        return $pageRs->fetchAllKeyPair();
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
                . ' WHERE ' . Util::backquote('username') . ' = '
                . $this->dbi->quoteString(Config::getInstance()->selectedServer['user'])
                . ';';

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
        $paramsArray = $this->getSideMenuParamsArray();
        $selectedButton = 'M_butt_Selected_down';
        $normalButton = 'M_butt';

        return [
            'angular_direct' => ($paramsArray['angular_direct'] ?? '') === 'angular' ? $selectedButton : $normalButton,
            'snap_to_grid' => ($paramsArray['snap_to_grid'] ?? '') === 'on' ? $selectedButton : $normalButton,
            'pin_text' => ($paramsArray['pin_text'] ?? '') === 'true' ? $selectedButton : $normalButton,
            'relation_lines' => ($paramsArray['relation_lines'] ?? '') === 'false' ? $selectedButton : $normalButton,
            'small_big_all' => ($paramsArray['small_big_all'] ?? '') === 'v' ? $selectedButton : $normalButton,
            'side_menu' => ($paramsArray['side_menu'] ?? '') === 'true' ? $selectedButton : $normalButton,
        ];
    }

    /**
     * @param list<ColumnInfo>[]  $tableColumnsInfo table column info
     * @param array<string, bool> $tablesAllKeys    unique or primary indices
     *
     * @return array<string, string>
     */
    public function getColumnTypes(array $tableColumnsInfo, array $tablesAllKeys): array
    {
        $columnsType = [];
        foreach ($tableColumnsInfo as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {
                $tableColumnName = $tableName . '.' . $columnInfo->name;
                if (isset($tablesAllKeys[$tableColumnName]) && $tablesAllKeys[$tableColumnName]) {
                    $columnsType[$tableColumnName] = 'designer/FieldKey_small';
                } else {
                    $columnsType[$tableColumnName] = 'designer/Field_small';
                    if (
                        str_contains($columnInfo->type, 'char')
                        || str_contains($columnInfo->type, 'text')
                    ) {
                        $columnsType[$tableColumnName] .= '_char';
                    } elseif (
                        str_contains($columnInfo->type, 'int')
                        || str_contains($columnInfo->type, 'float')
                        || str_contains($columnInfo->type, 'double')
                        || str_contains($columnInfo->type, 'decimal')
                    ) {
                        $columnsType[$tableColumnName] .= '_int';
                    } elseif (
                        str_contains($columnInfo->type, 'date')
                        || str_contains($columnInfo->type, 'time')
                        || str_contains($columnInfo->type, 'year')
                    ) {
                        $columnsType[$tableColumnName] .= '_date';
                    }
                }
            }
        }

        return $columnsType;
    }

    /**
     * @param DesignerTable[] $designerTables The designer tables
     * @param mixed[]         $scriptTables   array on foreign key support for each table
     * @param mixed[]         $scriptContr    initialization data array
     */
    public function getDesignerConfig(
        string $db,
        array $designerTables,
        array $scriptTables,
        array $scriptContr,
        int $displayPage,
    ): stdClass {
        $relationParameters = $this->relation->getRelationParameters();

        $displayedFields = [];
        foreach ($designerTables as $designerTable) {
            if ($designerTable->getDisplayField() === null) {
                continue;
            }

            $displayedFields[$designerTable->getTableName()] = $designerTable->getDisplayField();
        }

        $designerConfig = new stdClass();
        $designerConfig->db = $db;
        $designerConfig->scriptTables = $scriptTables;
        $designerConfig->scriptContr = $scriptContr;
        $designerConfig->server = Current::$server;
        $designerConfig->scriptDisplayField = $displayedFields;
        $designerConfig->displayPage = $displayPage;
        $designerConfig->tablesEnabled = $relationParameters->pdfFeature !== null;

        return $designerConfig;
    }
}
