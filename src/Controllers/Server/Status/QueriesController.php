<?php
/**
 * Displays query statistics for the server
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

use function __;
use function array_keys;
use function array_sum;
use function array_values;
use function arsort;
use function count;
use function mb_convert_case;
use function str_replace;

use const MB_CASE_TITLE;

#[Route('/server/status/queries', ['GET'])]
final class QueriesController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->response->addScriptFiles(['vendor/chart.umd.js', 'server/status/queries.js']);

        $chart = [];
        if ($this->data->dataLoaded) {
            $hourFactor = 3600 / $this->data->status['Uptime'];
            $usedQueries = $this->data->usedQueries;
            $totalQueries = array_sum($usedQueries);

            $stats = [
                'total' => $totalQueries,
                'per_hour' => $totalQueries * $hourFactor,
                'per_minute' => $totalQueries * 60 / $this->data->status['Uptime'],
                'per_second' => $totalQueries / $this->data->status['Uptime'],
            ];

            // reverse sort by value to show most used statements first
            arsort($usedQueries);

            $querySum = array_sum($usedQueries);
            $otherSum = 0;
            $queries = [];
            foreach ($usedQueries as $key => $value) {
                // For the percentage column, use Questions - Connections, because
                // the number of connections is not an item of the Query types
                // but is included in Questions. Then the total of the percentages is 100.
                $name = mb_convert_case(str_replace(['Com_', '_'], ['', ' '], $key), MB_CASE_TITLE);
                // Group together values that make out less than 2% into "Other", but only
                // if we have more than 6 fractions already
                if ($value < $querySum * 0.02 && count($chart) > 6) {
                    $otherSum += $value;
                } else {
                    $chart[$name] = (int) $value;
                }

                $queries[$key] = [
                    'name' => $name,
                    'value' => $value,
                    'per_hour' => $value * $hourFactor,
                    'percentage' => $value * 100 / $totalQueries,
                ];
            }

            if ($otherSum > 0) {
                $chart[__('Other statements')] = $otherSum;
            }
        }

        $chartData = ['labels' => array_keys($chart), 'data' => array_values($chart)];

        $this->response->render('server/status/queries/index', [
            'is_data_loaded' => $this->data->dataLoaded,
            'stats' => $stats ?? null,
            'queries' => $queries ?? [],
            'chart_data' => $chartData,
        ]);

        return $this->response->response();
    }
}
