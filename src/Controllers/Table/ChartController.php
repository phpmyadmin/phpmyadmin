<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function htmlspecialchars;
use function json_encode;
use function min;

/**
 * Handles creation of the chart.
 */
class ChartController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        if (isset($_REQUEST['pos'], $_REQUEST['session_max_rows']) && $request->isAjax()) {
            if (Current::$table !== '' && Current::$database !== '' && ! $this->checkParameters(['db', 'table'])) {
                return;
            }

            $this->ajax($request);

            return;
        }

        // Throw error if no sql query is set
        if (! isset($GLOBALS['sql_query']) || $GLOBALS['sql_query'] == '') {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))->getDisplay(),
            );

            return;
        }

        $this->addScriptFiles([
            'chart.js',
            'table/chart.js',
            'vendor/jqplot/jquery.jqplot.js',
            'vendor/jqplot/plugins/jqplot.barRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasTextRenderer.js',
            'vendor/jqplot/plugins/jqplot.categoryAxisRenderer.js',
            'vendor/jqplot/plugins/jqplot.dateAxisRenderer.js',
            'vendor/jqplot/plugins/jqplot.pointLabels.js',
            'vendor/jqplot/plugins/jqplot.pieRenderer.js',
            'vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js',
            'vendor/jqplot/plugins/jqplot.highlighter.js',
        ]);

        $urlParams = [];

        $config = Config::getInstance();
        /**
         * Runs common work
         */
        if (Current::$table !== '') {
            if (! $this->checkParameters(['db', 'table'])) {
                return;
            }

            $urlParams = ['db' => Current::$database, 'table' => Current::$table];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
            $GLOBALS['errorUrl'] .= Url::getCommon($urlParams, '&');

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return;
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No table selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

                return;
            }

            $urlParams['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
            $urlParams['back'] = Url::getFromRoute('/table/sql');
            $this->dbi->selectDb(Current::$database);
        } elseif (Current::$database !== '') {
            $urlParams['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            $urlParams['back'] = Url::getFromRoute('/sql');

            if (! $this->checkParameters(['db'])) {
                return;
            }

            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return;
            }
        } else {
            $urlParams['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabServer'], 'server');
            $urlParams['back'] = Url::getFromRoute('/sql');
            $GLOBALS['errorUrl'] = Url::getFromRoute('/');

            if ($this->dbi->isSuperUser()) {
                $this->dbi->selectDb('mysql');
            }
        }

        $result = $this->dbi->tryQuery($GLOBALS['sql_query']);
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

            return;
        }

        $urlParams['db'] = Current::$database;
        $urlParams['reload'] = 1;

        $startAndNumberOfRowsFieldset = Generator::getStartAndNumberOfRowsFieldsetData($GLOBALS['sql_query']);

        /**
         * Displays the page
         */
        $this->render('table/chart/tbl_chart', [
            'url_params' => $urlParams,
            'keys' => $keys,
            'fields_meta' => $fieldsMeta,
            'table_has_a_numeric_column' => true,
            'start_and_number_of_rows_fieldset' => $startAndNumberOfRowsFieldset,
        ]);
    }

    /**
     * Handle ajax request
     */
    public function ajax(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        if (Current::$table !== '' && Current::$database !== '') {
            $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                Config::getInstance()->settings['DefaultTabTable'],
                'table',
            );
            $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

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

        $parser = new Parser($GLOBALS['sql_query']);
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
