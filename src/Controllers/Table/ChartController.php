<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
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
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function htmlspecialchars;
use function json_encode;
use function min;

/**
 * Handles creation of the chart.
 */
final class ChartController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['errorUrl'] ??= null;

        if (isset($_REQUEST['pos'], $_REQUEST['session_max_rows']) && $request->isAjax()) {
            if (
                Current::$table !== '' && Current::$database !== ''
                && ! $this->response->checkParameters(['db', 'table'])
            ) {
                return $this->response->response();
            }

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

        $config = Config::getInstance();
        /**
         * Runs common work
         */
        if (Current::$table !== '') {
            if (! $this->response->checkParameters(['db', 'table'])) {
                return $this->response->response();
            }

            $urlParams = ['db' => Current::$database, 'table' => Current::$table];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
            $GLOBALS['errorUrl'] .= Url::getCommon($urlParams, '&');

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

            $urlParams['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
            $urlParams['back'] = Url::getFromRoute('/table/sql');
            $this->dbi->selectDb(Current::$database);
        } elseif (Current::$database !== '') {
            $urlParams['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            $urlParams['back'] = Url::getFromRoute('/sql');

            if (! $this->response->checkParameters(['db'])) {
                return $this->response->response();
            }

            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

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
            $urlParams['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabServer'], 'server');
            $urlParams['back'] = Url::getFromRoute('/sql');
            $GLOBALS['errorUrl'] = Url::getFromRoute('/');

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
        $GLOBALS['errorUrl'] ??= null;
        if (Current::$table !== '' && Current::$database !== '') {
            UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                Config::getInstance()->settings['DefaultTabTable'],
                'table',
            );
            $GLOBALS['errorUrl'] .= Url::getCommon(UrlParams::$params, '&');

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
