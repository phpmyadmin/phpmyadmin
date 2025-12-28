<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Foreigners;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\UniqueCondition;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;

use function __;
use function array_map;
use function array_search;
use function array_values;
use function htmlspecialchars;
use function in_array;
use function is_numeric;
use function json_encode;
use function mb_strtolower;
use function md5;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function strncasecmp;
use function strtoupper;

/**
 * Handles table zoom search tab.
 *
 * Display table zoom search form, create SQL queries from form data.
 */
#[Route('/table/zoom-search', ['GET', 'POST'])]
final class ZoomSearchController implements InvocableController
{
    /** @var list<string> */
    private array $columnNames = [];

    /** @var list<string> */
    private array $columnTypes = [];

    /** @var list<string> */
    private array $originalColumnTypes = [];

    /** @var list<string> */
    private array $columnCollations = [];

    /** @var list<bool> */
    private array $columnNullFlags = [];

    /** @var bool Whether a geometry column is present */
    private bool $geomColumnFlag = false;

    private Foreigners $foreigners;

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Search $search,
        private readonly Relation $relation,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);
        }

        $this->loadTableInfo();

        $this->response->addScriptFiles([
            'vendor/chart.umd.js',
            'vendor/hammer.js',
            'vendor/chartjs-plugin-zoom.js',
            'makegrid.js',
            'sql.js',
            'table/zoom_search.js',
            'table/select.js',
            'table/change.js',
            'gis_data_editor.js',
        ]);

        /**
         * Handle AJAX request for data row on point select
         */
        if (isset($_POST['get_data_row']) && $_POST['get_data_row']) {
            $this->getDataRowAction();

            return $this->response->response();
        }

        /**
         * Handle AJAX request for changing field information
         * (value,collation,operators,field values) in input form
         */
        if ($request->hasBodyParam('change_tbl_info')) {
            $this->changeTableInfoAction();

            return $this->response->response();
        }

        //Set default datalabel if not selected
        if (! isset($_POST['zoom_submit']) || $_POST['dataLabel'] == '') {
            $dataLabel = $this->relation->getDisplayField(Current::$database, Current::$table);
        } else {
            $dataLabel = $_POST['dataLabel'];
        }

        // Displays the zoom search form
        $this->displaySelectionFormAction($dataLabel);

        /**
         * Handle the input criteria and generate the query result
         * Form for displaying query results
         */
        if (
            ! isset($_POST['zoom_submit'])
            || $_POST['criteriaColumnNames'][0] === 'pma_null'
            || $_POST['criteriaColumnNames'][1] === 'pma_null'
            || $_POST['criteriaColumnNames'][0] == $_POST['criteriaColumnNames'][1]
        ) {
            return $this->response->response();
        }

        if (UrlParams::$goto === '') {
            UrlParams::$goto = Url::getFromRoute($this->config->config->DefaultTabTable);
        }

        $this->zoomSubmitAction($dataLabel, UrlParams::$goto);

        return $this->response->response();
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     */
    private function loadTableInfo(): void
    {
        // Gets the list and number of columns
        $columns = $this->dbi->getColumns(Current::$database, Current::$table);
        // Get details about the geometry functions
        $geomTypes = Gis::getDataTypes();

        foreach ($columns as $row) {
            // set column name
            $this->columnNames[] = $row->field;

            $type = $row->type;
            // before any replacement
            $this->originalColumnTypes[] = mb_strtolower($type);
            // check whether table contains geometric columns
            if (in_array($type, $geomTypes, true)) {
                $this->geomColumnFlag = true;
            }

            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) === 0 || strncasecmp($type, 'enum', 4) === 0) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (preg_match('@BINARY[\(]@i', $type) !== 1) {
                    $type = str_ireplace('BINARY', '', $type);
                }

                $type = str_ireplace('ZEROFILL', '', $type);
                $type = str_ireplace('UNSIGNED', '', $type);
                $type = mb_strtolower($type);
            }

            if ($type === '') {
                $type = '&nbsp;';
            }

            $this->columnTypes[] = $type;
            $this->columnNullFlags[] = $row->isNull;
            $this->columnCollations[] = ! empty($row->collation) && $row->collation !== 'NULL'
                ? $row->collation
                : '';
        }

        // Retrieve foreign keys
        $this->foreigners = $this->relation->getForeigners(Current::$database, Current::$table);
    }

    /**
     * Display selection form action
     */
    private function displaySelectionFormAction(string $dataLabel): void
    {
        if (UrlParams::$goto === '') {
            UrlParams::$goto = Url::getFromRoute($this->config->config->DefaultTabTable);
        }

        $criteriaColumnNames = $_POST['criteriaColumnNames'] ?? null;
        $properties = [];
        /** @infection-ignore-all */
        for ($i = 0; $i < 4; $i++) {
            if (! isset($criteriaColumnNames[$i])) {
                continue;
            }

            if ($criteriaColumnNames[$i] === 'pma_null') {
                continue;
            }

            $properties[$i] = $this->getColumnProperties(
                $i,
                (int) array_search($criteriaColumnNames[$i], $this->columnNames),
            );
        }

        $this->response->render('table/zoom_search/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'goto' => UrlParams::$goto,
            'properties' => $properties,
            'geom_column_flag' => $this->geomColumnFlag,
            'column_names' => $this->columnNames,
            'data_label' => $dataLabel,
            'criteria_column_names' => $criteriaColumnNames,
            'criteria_column_types' => $_POST['criteriaColumnTypes'] ?? null,
            'max_plot_limit' => ! empty($_POST['maxPlotLimit'])
                ? (int) $_POST['maxPlotLimit']
                : $this->config->config->maxRowPlotLimit,
        ]);
    }

    /**
     * Get data row action
     */
    private function getDataRowAction(): void
    {
        if (! Core::checkSqlQuerySignature($_POST['where_clause'], $_POST['where_clause_sign'])) {
            return;
        }

        $extraData = [];
        $rowInfoQuery = 'SELECT * FROM ' . Util::backquote($_POST['db']) . '.'
            . Util::backquote($_POST['table']) . ' WHERE ' . $_POST['where_clause'];
        $result = $this->dbi->query($rowInfoQuery . ';');
        $fieldsMeta = $this->dbi->getFieldsMeta($result);
        while ($row = $result->fetchAssoc()) {
            // for bit fields we need to convert them to printable form
            $i = 0;
            foreach ($row as $col => $val) {
                if ($fieldsMeta[$i]->isMappedTypeBit) {
                    $row[$col] = Util::printableBitValue((int) $val, $fieldsMeta[$i]->length);
                }

                $i++;
            }

            $extraData['row_info'] = $row;
        }

        $this->response->addJSON($extraData);
    }

    /**
     * Change table info action
     */
    private function changeTableInfoAction(): void
    {
        $field = $_POST['field'];
        if ($field === 'pma_null') {
            $this->response->addJSON('field_type', '');
            $this->response->addJSON('field_collation', '');
            $this->response->addJSON('field_operators', '');
            $this->response->addJSON('field_value', '');

            return;
        }

        $key = array_search($field, $this->columnNames);
        $searchIndex = isset($_POST['it']) && is_numeric($_POST['it'])
            ? (int) $_POST['it'] : 0;

        $properties = $this->getColumnProperties($searchIndex, $key);
        $this->response->addJSON(
            'field_type',
            htmlspecialchars($properties['type']),
        );
        $this->response->addJSON('field_collation', $properties['collation']);
        $this->response->addJSON('field_operators', $properties['func']);
        $this->response->addJSON('field_value', $properties['value']);
    }

    /**
     * Zoom submit action
     *
     * @param string $dataLabel Data label
     * @param string $goto      Goto
     */
    private function zoomSubmitAction(string $dataLabel, string $goto): void
    {
        //Query generation part
        $sqlQuery = $this->search->buildSqlQuery();
        $sqlQuery .= ' LIMIT ' . $_POST['maxPlotLimit'];

        //Query execution part
        $result = $this->dbi->query($sqlQuery . ';');
        $fieldsMeta = $this->dbi->getFieldsMeta($result);
        $data = [];
        while ($row = $result->fetchAssoc()) {
            //Need a row with indexes as 0,1,2 for the UniqueCondition
            // hence using a temporary array
            $tmpRow = array_values($row);

            //Get unique condition on each row (will be needed for row update)
            $uniqueCondition = (new UniqueCondition($fieldsMeta, $tmpRow, true))->getWhereClause();
            //Append it to row array as where_clause
            $row['where_clause'] = $uniqueCondition;
            $row['where_clause_sign'] = Core::signSqlQuery($uniqueCondition);

            $tmpData = [
                $_POST['criteriaColumnNames'][0] => $row[$_POST['criteriaColumnNames'][0]],
                $_POST['criteriaColumnNames'][1] => $row[$_POST['criteriaColumnNames'][1]],
                'where_clause' => $uniqueCondition,
                'where_clause_sign' => Core::signSqlQuery($uniqueCondition),
            ];
            $tmpData[$dataLabel] = $dataLabel !== '' ? $row[$dataLabel] : '';
            $data[] = $tmpData;
        }

        unset($tmpData);

        $columnNamesHashes = [];
        $foreignDropdown = [];
        $searchColumnInForeigners = [];
        $foreignData = [];

        foreach ($this->columnNames as $columnIndex => $columnName) {
            $columnNamesHashes[$columnName] = md5($columnName);
            $foreignData[$columnIndex] = $this->relation->getForeignData($this->foreigners, $columnName, false, '', '');
            $searchColumnInForeigners[$columnIndex] = $this->relation->searchColumnInForeigners(
                $this->foreigners,
                $columnName,
            );
            if (
                $searchColumnInForeigners[$columnIndex] === []
                || $searchColumnInForeigners[$columnIndex] === false
                || $foreignData[$columnIndex]->dispRow === null
            ) {
                continue;
            }

            $foreignDropdown[$columnIndex] = $this->relation->foreignDropdown(
                $foreignData[$columnIndex]->dispRow,
                $foreignData[$columnIndex]->foreignField,
                $foreignData[$columnIndex]->foreignDisplay,
                '',
                $this->config->settings['ForeignKeyMaxLimit'],
            );
        }

        $integerTypes = $this->dbi->types->getIntegerTypes();
        $floatTypes = $this->dbi->types->getFloatTypes();
        $columnDataTypes = array_map(static function (string $type) use ($integerTypes, $floatTypes): string {
            $cleanType = (string) preg_replace('@\(.*@s', '', $type);
            $isInteger = in_array($cleanType, $integerTypes, true);
            $isFloat = in_array($cleanType, $floatTypes, true);

            return $isInteger ? 'INT' : ($isFloat ? 'FLOAT' : strtoupper($cleanType));
        }, $this->columnTypes);

        $this->response->render('table/zoom_search/result_form', [
            'db' => Current::$database,
            'table' => Current::$table,
            'column_names' => $this->columnNames,
            'column_names_hashes' => $columnNamesHashes,
            'column_null_flags' => $this->columnNullFlags,
            'column_types' => $this->columnTypes,
            'column_data_types' => $columnDataTypes,
            'goto' => $goto,
            'data' => $data,
            'data_json' => json_encode($data),
            'zoom_submit' => isset($_POST['zoom_submit']),
            'foreign_dropdown' => $foreignDropdown,
            'search_columns_in_foreigners' => $searchColumnInForeigners,
            'foreign_data' => $foreignData,
        ]);
    }

    /**
     * Provides a column's type, collation, operators list, and criteria value
     * to display in table search form
     *
     * @param int $searchIndex Row number in table search form
     * @param int $columnIndex Column index in ColumnNames array
     *
     * @return array<string, string> Array containing column's properties
     */
    private function getColumnProperties(int $searchIndex, int $columnIndex): array
    {
        $selectedOperator = $_POST['criteriaColumnOperators'][$searchIndex] ?? '';
        $enteredValue = $_POST['criteriaValues'] ?? '';
        //Gets column's type and collation
        $type = $this->columnTypes[$columnIndex];
        $collation = $this->columnCollations[$columnIndex];
        $cleanType = preg_replace('@\(.*@s', '', $type);
        //Gets column's comparison operators depending on column type
        $typeOperators = $this->dbi->types->getTypeOperatorsHtml(
            $cleanType,
            $this->columnNullFlags[$columnIndex],
            $selectedOperator,
        );
        $func = $this->template->render('table/search/column_comparison_operators', [
            'search_index' => $searchIndex,
            'type_operators' => $typeOperators,
        ]);
        //Gets link to browse foreign data(if any) and criteria inputbox
        $foreignData = $this->relation->getForeignData(
            $this->foreigners,
            $this->columnNames[$columnIndex],
            false,
            '',
            '',
        );
        $htmlAttributes = '';
        $isInteger = in_array($cleanType, $this->dbi->types->getIntegerTypes(), true);
        $isFloat = in_array($cleanType, $this->dbi->types->getFloatTypes(), true);
        if ($isInteger) {
            $extractedColumnspec = Util::extractColumnSpec($this->originalColumnTypes[$columnIndex]);
            $isUnsigned = $extractedColumnspec['unsigned'];
            $minMaxValues = $this->dbi->types->getIntegerRange($cleanType, ! $isUnsigned);
            $htmlAttributes = 'data-min="' . $minMaxValues[0] . '" '
                            . 'data-max="' . $minMaxValues[1] . '"';
        }

        $htmlAttributes .= ' onfocus="return '
                        . 'verifyAfterSearchFieldChange(' . $searchIndex . ', \'#zoom_search_form\')"';

        $foreignDropdown = '';
        $searchColumnInForeigners = $this->relation->searchColumnInForeigners(
            $this->foreigners,
            $this->columnNames[$columnIndex],
        );

        $hasForeigner = $searchColumnInForeigners !== false && $searchColumnInForeigners !== [];

        if ($hasForeigner && $foreignData->dispRow !== null) {
            $foreignDropdown = $this->relation->foreignDropdown(
                $foreignData->dispRow,
                $foreignData->foreignField,
                $foreignData->foreignDisplay,
                '',
                $this->config->settings['ForeignKeyMaxLimit'],
            );
        }

        $value = $this->template->render('table/search/input_box', [
            'str' => '',
            'column_type' => $type,
            'column_data_type' => $isInteger ? 'INT' : ($isFloat ? 'FLOAT' : strtoupper($cleanType)),
            'html_attributes' => $htmlAttributes,
            'column_id' => 'fieldID_',
            'in_zoom_search_edit' => false,
            'column_name' => $this->columnNames[$columnIndex],
            'column_name_hash' => md5($this->columnNames[$columnIndex]),
            'foreign_data' => $foreignData,
            'table' => Current::$table,
            'column_index' => $searchIndex,
            'criteria_values' => $enteredValue,
            'db' => Current::$database,
            'in_fbs' => true,
            'foreign_dropdown' => $foreignDropdown,
            'has_foreigner' => $hasForeigner,
            'is_integer' => $isInteger,
            'is_float' => $isFloat,
        ]);

        return ['type' => $type, 'collation' => $collation, 'func' => $func, 'value' => $value];
    }
}
