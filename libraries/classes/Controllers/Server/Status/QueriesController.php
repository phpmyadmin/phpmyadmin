<?php
/**
 * Displays query statistics for the server
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Common;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function array_sum;
use function arsort;
use function count;
use function str_replace;

class QueriesController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        Common::server();

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('chart.js');
        $scripts->addFile('vendor/jqplot/jquery.jqplot.js');
        $scripts->addFile('vendor/jqplot/plugins/jqplot.pieRenderer.js');
        $scripts->addFile('vendor/jqplot/plugins/jqplot.highlighter.js');
        $scripts->addFile('vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js');
        $scripts->addFile('vendor/jquery/jquery.tablesorter.js');
        $scripts->addFile('server/status/sorter.js');
        $scripts->addFile('server/status/queries.js');

        if ($this->data->dataLoaded) {
            $hourFactor = 3600 / $this->data->status['Uptime'];
            $usedQueries = $this->data->used_queries;
            $totalQueries = array_sum($usedQueries);

            $stats = [
                'total' => $totalQueries,
                'per_hour' => $totalQueries * $hourFactor,
                'per_minute' => $totalQueries * 60 / $this->data->status['Uptime'],
                'per_second' => $totalQueries / $this->data->status['Uptime'],
            ];

            // reverse sort by value to show most used statements first
            arsort($usedQueries);

            $chart = [];
            $querySum = array_sum($usedQueries);
            $otherSum = 0;
            $queries = [];
            foreach ($usedQueries as $key => $value) {
                // For the percentage column, use Questions - Connections, because
                // the number of connections is not an item of the Query types
                // but is included in Questions. Then the total of the percentages is 100.
                $name = str_replace(['Com_', '_'], ['', ' '], $key);
                // Group together values that make out less than 2% into "Other", but only
                // if we have more than 6 fractions already
                if ($value < $querySum * 0.02 && count($chart) > 6) {
                    $otherSum += $value;
                } else {
                    $chart[$name] = $value;
                }

                $queries[$key] = [
                    'name' => $name,
                    'value' => $value,
                    'per_hour' => $value * $hourFactor,
                    'percentage' => $value * 100 / $totalQueries,
                ];
            }

            if ($otherSum > 0) {
                $chart[__('Other')] = $otherSum;
            }
        }

        $this->render('server/status/queries/index', [
            'is_data_loaded' => $this->data->dataLoaded,
            'stats' => $stats ?? null,
            'queries' => $queries ?? [],
            'chart' => $chart ?? [],
        ]);

        return $response;
    }
}
