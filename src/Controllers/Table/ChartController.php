<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function array_keys;
use function htmlspecialchars;
use function json_encode;
use function min;

/**
 * Handles creation of the chart.
 */
final readonly class ChartController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (isset($_REQUEST['pos'], $_REQUEST['session_max_rows']) && $request->isAjax()) {
            $this->ajax($request);

            return $this->response->response();
        }

        // Throw error if no sql query is set
        if (Current::$sqlQuery === '') {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))->getDisplay(),
            );

            return $this->response->response();
        }

        $this->response->addScriptFiles([
            'vendor/chart.umd.js',
            'vendor/chartjs-adapter-date-fns.bundle.js',
            'table/chart.js',
        ]);

        $urlParams = [];

        /**
         * Runs common work
         */
        if (Current::$table !== '') {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            $urlParams = ['db' => Current::$database, 'table' => Current::$table];

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return $this->response->response();
                }

                $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return $this->response->response();
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No table selected.')));

                    return $this->response->response();
                }

                $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);

                return $this->response->response();
            }

            $urlParams['goto'] = Url::getFromRoute($this->config->settings['DefaultTabTable']);
            $urlParams['back'] = Url::getFromRoute('/table/sql');
            $this->dbi->selectDb(Current::$database);
        } elseif (Current::$database !== '') {
            $urlParams['goto'] = Url::getFromRoute($this->config->settings['DefaultTabDatabase']);
            $urlParams['back'] = Url::getFromRoute('/sql');

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return $this->response->response();
                }

                $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return $this->response->response();
            }
        } else {
            $urlParams['goto'] = Url::getFromRoute($this->config->settings['DefaultTabServer']);
            $urlParams['back'] = Url::getFromRoute('/sql');

            if ($this->dbi->isSuperUser()) {
                $this->dbi->selectDb('mysql');
            }
        }

        $result = $this->dbi->tryQuery(Current::$sqlQuery);
        $fieldsMeta = $row = [];
        if ($result !== false) {
            $fieldsMeta = $this->dbi->getFieldsMeta($result);
            $row = $result->fetchAssoc();
        }

        $keys = array_keys($row);
        $numericColumnFound = false;
        foreach (array_keys($keys) as $idx) {
            if (
                isset($fieldsMeta[$idx]) && (
                $fieldsMeta[$idx]->isType(FieldMetadata::TYPE_INT)
                || $fieldsMeta[$idx]->isType(FieldMetadata::TYPE_REAL)
                )
            ) {
                $numericColumnFound = true;
                break;
            }
        }

        if (! $numericColumnFound) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                __('No numeric columns present in the table to plot.'),
            );

            return $this->response->response();
        }

        $urlParams['db'] = Current::$database;
        $urlParams['reload'] = 1;

        $startAndNumberOfRowsFieldset = Generator::getStartAndNumberOfRowsFieldsetData(Current::$sqlQuery);

        /**
         * Displays the page
         */
        $this->response->render('table/chart/tbl_chart', [
            'url_params' => $urlParams,
            'keys' => $keys,
            'fields_meta' => $fieldsMeta,
            'table_has_a_numeric_column' => true,
            'start_and_number_of_rows_fieldset' => $startAndNumberOfRowsFieldset,
        ]);

        return $this->response->response();
    }

    /**
     * Handle ajax request
     */
    public function ajax(ServerRequest $request): void
    {
        if (Current::$table !== '' && Current::$database !== '') {
            UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return;
            }
        }

        $parser = new Parser(Current::$sqlQuery);
        /** @var SelectStatement $statement */
        $statement = $parser->statements[0];
        if (empty($statement->limit)) {
            $statement->limit = new Limit($_REQUEST['session_max_rows'], $_REQUEST['pos']);
        } else {
            $start = $statement->limit->offset + $_REQUEST['pos'];
            $rows = min($_REQUEST['session_max_rows'], $statement->limit->rowCount - $_REQUEST['pos']);
            $statement->limit = new Limit($rows, $start);
        }

        $sqlWithLimit = $statement->build();

        $result = $this->dbi->tryQuery($sqlWithLimit);
        $data = [];
        if ($result !== false) {
            $data = $result->fetchAllAssoc();
        }

        if ($data === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No data to display'));

            return;
        }

        $sanitizedData = [];

        foreach ($data as $dataRow) {
            $tmpRow = [];
            foreach ($dataRow as $dataColumn => $dataValue) {
                $escapedValue = $dataValue === null ? null : htmlspecialchars($dataValue);
                $tmpRow[htmlspecialchars((string) $dataColumn)] = $escapedValue;
            }

            $sanitizedData[] = $tmpRow;
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON('message', null);
        $this->response->addJSON('chartData', json_encode($sanitizedData));
    }
}
