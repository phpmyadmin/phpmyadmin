<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Util;

use function array_key_exists;

/**
 * Handles viewing binary logs
 */
final class BinlogController implements InvocableController
{
    /**
     * binary log files
     *
     * @var mixed[]
     */
    private array $binaryLogs;

    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
        $this->binaryLogs = $this->dbi->fetchResult('SHOW BINARY LOGS', 'Log_name');
    }

    public function __invoke(ServerRequest $request): Response
    {
        $log = $request->getParsedBodyParamAsString('log', '');
        $position = (int) $request->getParsedBodyParamAsString('pos', '');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $urlParams = [];
        if (array_key_exists($log, $this->binaryLogs)) {
            $urlParams['log'] = $log;
        }

        $isFullQuery = false;
        if ($request->hasBodyParam('is_full_query')) {
            $isFullQuery = true;
            $urlParams['is_full_query'] = 1;
        }

        $config = Config::getInstance();
        $sqlQuery = $this->getSqlQuery($log, $position, $config->settings['MaxRows']);
        $result = $this->dbi->query($sqlQuery);

        $numRows = $result->numRows();

        $previousParams = $urlParams;
        $fullQueriesParams = $urlParams;
        $nextParams = $urlParams;
        if ($position > 0) {
            $fullQueriesParams['pos'] = $position;
            if ($position > $config->settings['MaxRows']) {
                $previousParams['pos'] = $position - $config->settings['MaxRows'];
            }
        }

        $fullQueriesParams['is_full_query'] = 1;
        if ($isFullQuery) {
            unset($fullQueriesParams['is_full_query']);
        }

        if ($numRows >= $config->settings['MaxRows']) {
            $nextParams['pos'] = $position + $config->settings['MaxRows'];
        }

        $values = $result->fetchAllAssoc();

        $this->response->render('server/binlog/index', [
            'url_params' => $urlParams,
            'binary_logs' => $this->binaryLogs,
            'log' => $log,
            'sql_message' => Generator::getMessage(Message::success(), $sqlQuery),
            'values' => $values,
            'has_previous' => $position > 0,
            'has_next' => $numRows >= $config->settings['MaxRows'],
            'previous_params' => $previousParams,
            'full_queries_params' => $fullQueriesParams,
            'next_params' => $nextParams,
            'has_icons' => Util::showIcons('TableNavigationLinksMode'),
            'is_full_query' => $isFullQuery,
        ]);

        return $this->response->response();
    }

    /**
     * @param string $log      Binary log file name
     * @param int    $position Position to display
     * @param int    $maxRows  Maximum number of rows
     */
    private function getSqlQuery(
        string $log,
        int $position,
        int $maxRows,
    ): string {
        $sqlQuery = 'SHOW BINLOG EVENTS';
        if ($log !== '') {
            $sqlQuery .= ' IN \'' . $log . '\'';
        }

        $sqlQuery .= ' LIMIT ' . $position . ', ' . $maxRows;

        return $sqlQuery;
    }
}
