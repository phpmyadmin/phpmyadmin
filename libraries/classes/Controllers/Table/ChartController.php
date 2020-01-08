<?php
/**
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\Message;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles creation of the chart.
 *
 * @package PhpMyAdmin\Controllers
 */
class ChartController extends AbstractController
{
    /**
     * Execute the query and return the result
     *
     * @return void
     */
    public function index(): void
    {
        global $db, $table, $cfg, $sql_query, $url_query;

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

        $this->response->getHeader()->getScripts()->addFiles(
            [
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
            ]
        );

        $url_params = [];

        /**
         * Runs common work
         */
        if (strlen($table) > 0) {
            $url_params['goto'] = Util::getScriptNameForOption(
                $cfg['DefaultTabTable'],
                'table'
            );
            $url_params['back'] = Url::getFromRoute('/table/sql');
            Common::table();
            $this->dbi->selectDb($db);
        } elseif (strlen($db) > 0) {
            $url_params['goto'] = Util::getScriptNameForOption(
                $cfg['DefaultTabDatabase'],
                'database'
            );
            $url_params['back'] = Url::getFromRoute('/sql');
            Common::database();
        } else {
            $url_params['goto'] = Util::getScriptNameForOption(
                $cfg['DefaultTabServer'],
                'server'
            );
            $url_params['back'] = Url::getFromRoute('/sql');
            Common::server();
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
            if (in_array($fields_meta[$idx]->type, $numeric_types)) {
                $numeric_column_count++;
            }
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
        $this->response->addHTML(
            $this->template->render('table/chart/tbl_chart', [
                'url_query' => $url_query,
                'url_params' => $url_params,
                'keys' => $keys,
                'fields_meta' => $fields_meta,
                'numeric_types' => $numeric_types,
                'numeric_column_count' => $numeric_column_count,
                'sql_query' => $sql_query,
            ])
        );
    }

    /**
     * Handle ajax request
     *
     * @return void
     */
    public function ajax(): void
    {
        global $db, $table, $sql_query;

        if (strlen($table) > 0 && strlen($db) > 0) {
            Common::table();
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
