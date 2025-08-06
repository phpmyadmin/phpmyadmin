<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
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
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;

use function __;
use function array_keys;
use function in_array;
use function mb_strtolower;
use function md5;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function strncasecmp;
use function strtoupper;

/**
 * Handles table search tab.
 *
 * Display table search form, create SQL query from form data
 * and call Sql::executeQueryAndSendQueryResponse() to execute it.
 */
#[Route('/table/search', ['GET', 'POST'])]
final class SearchController implements InvocableController
{
    /**
     * Names of columns
     *
     * @var list<string>
     */
    private array $columnNames = [];
    /**
     * Types of columns
     *
     * @var list<string>
     */
    private array $columnTypes = [];
    /**
     * Types of columns without any replacement
     *
     * @var list<string>
     */
    private array $originalColumnTypes = [];
    /**
     * Collations of columns
     *
     * @var list<string>
     */
    private array $columnCollations = [];
    /**
     * Null Flags of columns
     *
     * @var list<bool>
     */
    private array $columnNullFlags = [];
    /**
     * Whether a geometry column is present
     */
    private bool $geomColumnFlag = false;
    /**
     * Foreign Keys
     *
     * @var mixed[]
     */
    private array $foreigners = [];

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Search $search,
        private readonly Relation $relation,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
        private readonly Config $config,
        private readonly Sql $sql,
    ) {
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
     * Index action
     */
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
            'makegrid.js',
            'sql.js',
            'table/select.js',
            'table/change.js',
            'vendor/jquery/jquery.uitablefilter.js',
            'gis_data_editor.js',
        ]);

        if (isset($_POST['range_search'])) {
            $this->rangeSearchAction();

            return $this->response->response();
        }

        /**
         * No selection criteria received -> display the selection form
         */
        if (! isset($_POST['columnsToDisplay']) && ! isset($_POST['displayAllColumns'])) {
            $this->displaySelectionFormAction();
        } else {
            $this->doSelectionAction();
        }

        return $this->response->response();
    }

    /**
     * Do selection action
     */
    private function doSelectionAction(): void
    {
        /**
         * Selection criteria have been submitted -> do the work
         */
        $sqlQuery = $this->search->buildSqlQuery();

        /**
         * Add this to ensure following procedures included running correctly.
         */
        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            null,
            false, // is_gotofile
            Current::$database, // db
            Current::$table, // table
            '', // sql_query_for_bookmark
            '', // message_to_show
            UrlParams::$goto, // goto
            null, // disp_query
            '', // disp_message
            $sqlQuery, // sql_query
            $sqlQuery, // complete_query
        ));
    }

    /**
     * Display selection form action
     */
    private function displaySelectionFormAction(): void
    {
        if (UrlParams::$goto === '') {
            UrlParams::$goto = Url::getFromRoute($this->config->settings['DefaultTabTable']);
        }

        $properties = [];
        foreach (array_keys($this->columnNames) as $columnIndex) {
            $properties[$columnIndex] = $this->getColumnProperties($columnIndex, $columnIndex);
        }

        $this->response->render('table/search/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'goto' => UrlParams::$goto,
            'properties' => $properties,
            'geom_column_flag' => $this->geomColumnFlag,
            'column_names' => $this->columnNames,
            'column_types' => $this->columnTypes,
            'column_collations' => $this->columnCollations,
            'default_sliders_state' => $this->config->settings['InitialSlidersState'],
            'max_rows' => (int) $this->config->settings['MaxRows'],
        ]);
    }

    /**
     * Range search action
     */
    private function rangeSearchAction(): void
    {
        $minMax = $this->getColumnMinMax($_POST['column']);
        $this->response->addJSON('column_data', $minMax);
    }

    /**
     * Finds minimum and maximum value of a given column.
     *
     * @param string $column Column name
     *
     * @return array<string|null>
     */
    private function getColumnMinMax(string $column): array
    {
        $sqlQuery = 'SELECT MIN(' . Util::backquote($column) . ') AS `min`, '
            . 'MAX(' . Util::backquote($column) . ') AS `max` '
            . 'FROM ' . Util::backquote(Current::$database) . '.'
            . Util::backquote(Current::$table);

        return $this->dbi->fetchSingleRow($sqlQuery);
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
                        . 'verifyAfterSearchFieldChange(' . $searchIndex . ', \'#tbl_search_form\')"';

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
