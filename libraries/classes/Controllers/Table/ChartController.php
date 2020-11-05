<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function array_keys;
use function htmlspecialchars;
use function in_array;
use function json_encode;
use function min;
use function strlen;

/**
 * Handles creation of the chart.
 */
class ChartController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $table, $dbi)
    {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $db, $table, $cfg, $sql_query, $err_url;

        if (isset($_REQUEST['pos'], $_REQUEST['session_max_rows']) && $this->response->isAjax()
        ) {
            $this->ajax();

            return;
        }

        // Throw error if no sql query is set
        if (! isset($sql_query) || $sql_query == '') {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))
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

        $url_params = [];

        /**
         * Runs common work
         */
        if (strlen($table) > 0) {
            Util::checkParameters(['db', 'table']);

            $url_params = ['db' => $db, 'table' => $table];
            $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $err_url .= Url::getCommon($url_params, '&');

            DbTableExists::check();

            $url_params['goto'] = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $url_params['back'] = Url::getFromRoute('/table/sql');
            $this->dbi->selectDb($db);
        } elseif (strlen($db) > 0) {
            $url_params['goto'] = Util::getScriptNameForOption(
                $cfg['DefaultTabDatabase'],
                'database'
            );
            $url_params['back'] = Url::getFromRoute('/sql');

            Util::checkParameters(['db']);

            $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            $err_url .= Url::getCommon(['db' => $db], '&');

            if (! $this->hasDatabase()) {
                return;
            }
        } else {
            $url_params['goto'] = Util::getScriptNameForOption(
                $cfg['DefaultTabServer'],
                'server'
            );
            $url_params['back'] = Url::getFromRoute('/sql');
            $err_url = Url::getFromRoute('/');

            if ($this->dbi->isSuperUser()) {
                $this->dbi->selectDb('mysql');
            }
        }

        $data = [];

        $result = $this->dbi->tryQuery($sql_query);
        $fields_meta = $this->dbi->getFieldsMeta($result);
        while ($row = $this->dbi->fetchAssoc($result)) {
            $data[] = $row;
        }

        $keys = array_keys($data[0]);

        $numeric_types = [
            'int',
            'real',
        ];
        $numeric_column_count = 0;
        foreach ($keys as $idx => $key) {
            if (! in_array($fields_meta[$idx]->type, $numeric_types)) {
                continue;
            }

            $numeric_column_count++;
        }

        if ($numeric_column_count == 0) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                __('No numeric columns present in the table to plot.')
            );

            return;
        }

        $url_params['db'] = $db;
        $url_params['reload'] = 1;

        /**
         * Displays the page
         */
        $this->render('table/chart/tbl_chart', [
            'url_params' => $url_params,
            'keys' => $keys,
            'fields_meta' => $fields_meta,
            'numeric_types' => $numeric_types,
            'numeric_column_count' => $numeric_column_count,
            'sql_query' => $sql_query,
        ]);
    }

    /**
     * Handle ajax request
     */
    public function ajax(): void
    {
        global $db, $table, $sql_query, $url_params, $err_url, $cfg;

        if (strlen($table) > 0 && strlen($db) > 0) {
            Util::checkParameters(['db', 'table']);

            $url_params = ['db' => $db, 'table' => $table];
            $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $err_url .= Url::getCommon($url_params, '&');

            DbTableExists::check();
        }

        $parser = new Parser($sql_query);
        /**
         * @var SelectStatement $statement
         */
        $statement = $parser->statements[0];
        if (empty($statement->limit)) {
            $statement->limit = new Limit(
                $_REQUEST['session_max_rows'],
                $_REQUEST['pos']
            );
        } else {
            $start = $statement->limit->offset + $_REQUEST['pos'];
            $rows = min(
                $_REQUEST['session_max_rows'],
                $statement->limit->rowCount - $_REQUEST['pos']
            );
            $statement->limit = new Limit($rows, $start);
        }
        $sql_with_limit = $statement->build();

        $data = [];
        $result = $this->dbi->tryQuery($sql_with_limit);
        while ($row = $this->dbi->fetchAssoc($result)) {
            $data[] = $row;
        }

        if (empty($data)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No data to display'));

            return;
        }
        $sanitized_data = [];

        foreach ($data as $data_row_number => $data_row) {
            $tmp_row = [];
            foreach ($data_row as $data_column => $data_value) {
                $escaped_value = $data_value === null ? null : htmlspecialchars($data_value);
                $tmp_row[htmlspecialchars($data_column)] = $escaped_value;
            }
            $sanitized_data[] = $tmp_row;
        }
        $this->response->setRequestStatus(true);
        $this->response->addJSON('message', null);
        $this->response->addJSON('chartData', json_encode($sanitized_data));
    }
}
