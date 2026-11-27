<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
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
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Table\SearchColumns;
use PhpMyAdmin\Table\SearchForm;
use PhpMyAdmin\UniqueCondition;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function array_map;
use function array_search;
use function array_values;
use function htmlspecialchars;
use function in_array;
use function is_numeric;
use function json_encode;
use function md5;
use function preg_replace;
use function strtoupper;

/**
 * Handles table zoom search tab.
 *
 * Display table zoom search form, create SQL queries from form data.
 */
#[Route('/table/zoom-search', ['GET', 'POST'])]
final class ZoomSearchController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Search $search,
        private readonly SearchForm $searchForm,
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
            return $this->response->respondWithErrorMessage(__('No database selected.'));
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            return $this->response->respondWithErrorMessage(__('No table selected.'));
        }

        $columns = $this->searchForm->getColumns($databaseName->getName(), $tableName->getName());

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
            $this->changeTableInfoAction($columns);

            return $this->response->response();
        }

        //Set default datalabel if not selected
        if (! isset($_POST['zoom_submit']) || $_POST['dataLabel'] == '') {
            $dataLabel = $this->relation->getDisplayField(Current::$database, Current::$table);
        } else {
            $dataLabel = $_POST['dataLabel'];
        }

        // Displays the zoom search form
        $this->displaySelectionFormAction($dataLabel, $columns);

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

        $this->zoomSubmitAction($dataLabel, UrlParams::$goto, $columns);

        return $this->response->response();
    }

    /**
     * Display selection form action
     */
    private function displaySelectionFormAction(string $dataLabel, SearchColumns $columns): void
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

            $properties[$i] = $this->searchForm->getColumnProperties(
                $columns,
                $i,
                (int) array_search($criteriaColumnNames[$i], $columns->names, true),
                'zoom_search_form',
            );
        }

        $this->response->render('table/zoom_search/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'goto' => UrlParams::$goto,
            'properties' => $properties,
            'geom_column_flag' => $columns->hasGeometry,
            'column_names' => $columns->names,
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
    private function changeTableInfoAction(SearchColumns $columns): void
    {
        $field = $_POST['field'];
        $key = array_search($field, $columns->names, true);
        if ($field === 'pma_null' || $key === false) {
            $this->response->addJSON('field_type', '');
            $this->response->addJSON('field_collation', '');
            $this->response->addJSON('field_operators', '');
            $this->response->addJSON('field_value', '');

            return;
        }

        $searchIndex = isset($_POST['it']) && is_numeric($_POST['it'])
            ? (int) $_POST['it'] : 0;

        $properties = $this->searchForm->getColumnProperties($columns, $searchIndex, $key, 'zoom_search_form');
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
    private function zoomSubmitAction(string $dataLabel, string $goto, SearchColumns $columns): void
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

        foreach ($columns->names as $columnIndex => $columnName) {
            $columnNamesHashes[$columnName] = md5($columnName);
            $foreignData[$columnIndex] = $this->relation->getForeignData(
                $columns->foreigners,
                $columnName,
                false,
                '',
                '',
            );
            $searchColumnInForeigners[$columnIndex] = $this->relation->searchColumnInForeigners(
                $columns->foreigners,
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
                $foreignData[$columnIndex]->foreignField ?? '',
                $foreignData[$columnIndex]->foreignDisplay,
                '',
                $this->config->config->ForeignKeyMaxLimit,
            );
        }

        $integerTypes = $this->dbi->types->getIntegerTypes();
        $floatTypes = $this->dbi->types->getFloatTypes();
        $columnDataTypes = array_map(static function (string $type) use ($integerTypes, $floatTypes): string {
            $cleanType = (string) preg_replace('@\(.*@s', '', $type);
            $isInteger = in_array($cleanType, $integerTypes, true);
            $isFloat = in_array($cleanType, $floatTypes, true);

            return $isInteger ? 'INT' : ($isFloat ? 'FLOAT' : strtoupper($cleanType));
        }, $columns->types);

        $this->response->render('table/zoom_search/result_form', [
            'db' => Current::$database,
            'table' => Current::$table,
            'column_names' => $columns->names,
            'column_names_hashes' => $columnNamesHashes,
            'column_null_flags' => $columns->nullFlags,
            'column_types' => $columns->types,
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
}
