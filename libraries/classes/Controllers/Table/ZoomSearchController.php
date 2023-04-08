<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;

use function array_search;
use function array_values;
use function count;
use function htmlspecialchars;
use function in_array;
use function intval;
use function is_array;
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
class ZoomSearchController extends AbstractController
{
    /** @var mixed[] */
    private array $columnNames = [];

    /** @var mixed[] */
    private array $columnTypes = [];

    /** @var mixed[] */
    private array $originalColumnTypes = [];

    /** @var mixed[] */
    private array $columnCollations = [];

    /** @var mixed[] */
    private array $columnNullFlags = [];

    /** @var bool Whether a geometry column is present */
    private bool $geomColumnFlag = false;

    /** @var mixed[] Foreign keys */
    private array $foreigners = [];

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Search $search,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);

        $this->loadTableInfo();
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['goto'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $this->checkParameters(['db', 'table']);

        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $this->addScriptFiles([
            'makegrid.js',
            'sql.js',
            'vendor/jqplot/jquery.jqplot.js',
            'vendor/jqplot/plugins/jqplot.canvasTextRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js',
            'vendor/jqplot/plugins/jqplot.dateAxisRenderer.js',
            'vendor/jqplot/plugins/jqplot.highlighter.js',
            'vendor/jqplot/plugins/jqplot.cursor.js',
            'table/zoom_plot_jqplot.js',
            'table/change.js',
        ]);

        /**
         * Handle AJAX request for data row on point select
         */
        if (isset($_POST['get_data_row']) && $_POST['get_data_row'] == true) {
            $this->getDataRowAction();

            return;
        }

        /**
         * Handle AJAX request for changing field information
         * (value,collation,operators,field values) in input form
         */
        if (isset($_POST['change_tbl_info']) && $_POST['change_tbl_info'] == true) {
            $this->changeTableInfoAction();

            return;
        }

        //Set default datalabel if not selected
        if (! isset($_POST['zoom_submit']) || $_POST['dataLabel'] == '') {
            $dataLabel = $this->relation->getDisplayField($GLOBALS['db'], $GLOBALS['table']);
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
            return;
        }

        if (! isset($GLOBALS['goto'])) {
            $GLOBALS['goto'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        }

        $this->zoomSubmitAction($dataLabel, $GLOBALS['goto']);
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     */
    private function loadTableInfo(): void
    {
        // Gets the list and number of columns
        $columns = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'], true);
        // Get details about the geometry functions
        $geomTypes = Gis::getDataTypes();

        foreach ($columns as $row) {
            // set column name
            $this->columnNames[] = $row['Field'];

            $type = (string) $row['Type'];
            // before any replacement
            $this->originalColumnTypes[] = mb_strtolower($type);
            // check whether table contains geometric columns
            if (in_array($type, $geomTypes)) {
                $this->geomColumnFlag = true;
            }

            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) == 0 || strncasecmp($type, 'enum', 4) == 0) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (! preg_match('@BINARY[\(]@i', $type)) {
                    $type = str_ireplace('BINARY', '', $type);
                }

                $type = str_ireplace('ZEROFILL', '', $type);
                $type = str_ireplace('UNSIGNED', '', $type);
                $type = mb_strtolower($type);
            }

            if (empty($type)) {
                $type = '&nbsp;';
            }

            $this->columnTypes[] = $type;
            $this->columnNullFlags[] = $row['Null'] === 'YES';
            $this->columnCollations[] = ! empty($row['Collation']) && $row['Collation'] !== 'NULL'
                ? $row['Collation']
                : '';
        }

        // Retrieve foreign keys
        $this->foreigners = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table']);
    }

    /**
     * Display selection form action
     */
    public function displaySelectionFormAction(string $dataLabel): void
    {
        if (! isset($GLOBALS['goto'])) {
            $GLOBALS['goto'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        }

        $criteriaColumnNames = $_POST['criteriaColumnNames'] ?? null;
        $keys = [];
        for ($i = 0; $i < 4; $i++) {
            if (! isset($criteriaColumnNames[$i])) {
                continue;
            }

            if ($criteriaColumnNames[$i] === 'pma_null') {
                continue;
            }

            $keys[$criteriaColumnNames[$i]] = array_search($criteriaColumnNames[$i], $this->columnNames);
        }

        $this->render('table/zoom_search/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'goto' => $GLOBALS['goto'],
            'self' => $this,
            'geom_column_flag' => $this->geomColumnFlag,
            'column_names' => $this->columnNames,
            'data_label' => $dataLabel,
            'keys' => $keys,
            'criteria_column_names' => $criteriaColumnNames,
            'criteria_column_types' => $_POST['criteriaColumnTypes'] ?? null,
            'max_plot_limit' => ! empty($_POST['maxPlotLimit'])
                ? intval($_POST['maxPlotLimit'])
                : intval($GLOBALS['cfg']['maxRowPlotLimit']),
        ]);
    }

    /**
     * Get data row action
     */
    public function getDataRowAction(): void
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
    public function changeTableInfoAction(): void
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
        $searchIndex = (isset($_POST['it']) && is_numeric($_POST['it'])
            ? intval($_POST['it']) : 0);

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
    public function zoomSubmitAction(string $dataLabel, string $goto): void
    {
        //Query generation part
        $sqlQuery = $this->search->buildSqlQuery();
        $sqlQuery .= ' LIMIT ' . $_POST['maxPlotLimit'];

        //Query execution part
        $result = $this->dbi->query($sqlQuery . ';');
        $fieldsMeta = $this->dbi->getFieldsMeta($result);
        $data = [];
        while ($row = $result->fetchAssoc()) {
            //Need a row with indexes as 0,1,2 for the getUniqueCondition
            // hence using a temporary array
            $tmpRow = array_values($row);

            //Get unique condition on each row (will be needed for row update)
            [$uniqueCondition] = Util::getUniqueCondition(
                count($this->columnNames),
                $fieldsMeta,
                $tmpRow,
                true,
            );
            //Append it to row array as where_clause
            $row['where_clause'] = $uniqueCondition;
            $row['where_clause_sign'] = Core::signSqlQuery($uniqueCondition);

            $tmpData = [
                $_POST['criteriaColumnNames'][0] => $row[$_POST['criteriaColumnNames'][0]],
                $_POST['criteriaColumnNames'][1] => $row[$_POST['criteriaColumnNames'][1]],
                'where_clause' => $uniqueCondition,
                'where_clause_sign' => Core::signSqlQuery($uniqueCondition),
            ];
            $tmpData[$dataLabel] = $dataLabel ? $row[$dataLabel] : '';
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
                ! $this->foreigners
                || ! $searchColumnInForeigners[$columnIndex]
                || ! is_array($foreignData[$columnIndex]['disp_row'])
            ) {
                continue;
            }

            $foreignDropdown[$columnIndex] = $this->relation->foreignDropdown(
                $foreignData[$columnIndex]['disp_row'],
                (string) $foreignData[$columnIndex]['foreign_field'],
                $foreignData[$columnIndex]['foreign_display'],
                '',
                $GLOBALS['cfg']['ForeignKeyMaxLimit'],
            );
        }

        $this->render('table/zoom_search/result_form', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'column_names' => $this->columnNames,
            'column_names_hashes' => $columnNamesHashes,
            'foreigners' => $this->foreigners,
            'column_null_flags' => $this->columnNullFlags,
            'column_types' => $this->columnTypes,
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
     * @return mixed[] Array containing column's properties
     */
    public function getColumnProperties(int $searchIndex, int $columnIndex): array
    {
        $selectedOperator = ($_POST['criteriaColumnOperators'][$searchIndex] ?? '');
        $enteredValue = ($_POST['criteriaValues'] ?? '');
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
        $isInteger = in_array($cleanType, $this->dbi->types->getIntegerTypes());
        $isFloat = in_array($cleanType, $this->dbi->types->getFloatTypes());
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

        if (
            $this->foreigners
            && $this->relation->searchColumnInForeigners($this->foreigners, $this->columnNames[$columnIndex])
            && is_array($foreignData['disp_row'])
        ) {
            $foreignDropdown = $this->relation->foreignDropdown(
                $foreignData['disp_row'],
                $foreignData['foreign_field'],
                $foreignData['foreign_display'],
                '',
                $GLOBALS['cfg']['ForeignKeyMaxLimit'],
            );
        }

        $value = $this->template->render('table/search/input_box', [
            'str' => '',
            'column_type' => (string) $type,
            'column_data_type' => $isInteger ? 'INT' : ($isFloat ? 'FLOAT' : strtoupper($cleanType)),
            'html_attributes' => $htmlAttributes,
            'column_id' => 'fieldID_',
            'in_zoom_search_edit' => false,
            'foreigners' => $this->foreigners,
            'column_name' => $this->columnNames[$columnIndex],
            'column_name_hash' => md5($this->columnNames[$columnIndex]),
            'foreign_data' => $foreignData,
            'table' => $GLOBALS['table'],
            'column_index' => $searchIndex,
            'criteria_values' => $enteredValue,
            'db' => $GLOBALS['db'],
            'in_fbs' => true,
            'foreign_dropdown' => $foreignDropdown,
            'is_integer' => $isInteger,
            'is_float' => $isFloat,
        ]);

        return ['type' => $type, 'collation' => $collation, 'func' => $func, 'value' => $value];
    }
}
