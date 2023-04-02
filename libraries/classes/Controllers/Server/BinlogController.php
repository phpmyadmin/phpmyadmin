<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function array_key_exists;

/**
 * Handles viewing binary logs
 */
class BinlogController extends AbstractController
{
    /**
     * binary log files
     *
     * @var mixed[]
     */
    protected array $binaryLogs;

    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);

        $this->binaryLogs = $this->dbi->fetchResult('SHOW MASTER LOGS', 'Log_name');
    }

    public function __invoke(ServerRequest $request): void
    {
        $log = $request->getParsedBodyParam('log');
        $position = (int) $request->getParsedBodyParam('pos', 0);

        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

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

        $sqlQuery = $this->getSqlQuery($log ?? '', $position, (int) $GLOBALS['cfg']['MaxRows']);
        $result = $this->dbi->query($sqlQuery);

        $numRows = $result->numRows();

        $previousParams = $urlParams;
        $fullQueriesParams = $urlParams;
        $nextParams = $urlParams;
        if ($position > 0) {
            $fullQueriesParams['pos'] = $position;
            if ($position > $GLOBALS['cfg']['MaxRows']) {
                $previousParams['pos'] = $position - $GLOBALS['cfg']['MaxRows'];
            }
        }

        $fullQueriesParams['is_full_query'] = 1;
        if ($isFullQuery) {
            unset($fullQueriesParams['is_full_query']);
        }

        if ($numRows >= $GLOBALS['cfg']['MaxRows']) {
            $nextParams['pos'] = $position + $GLOBALS['cfg']['MaxRows'];
        }

        $values = $result->fetchAllAssoc();

        $this->render('server/binlog/index', [
            'url_params' => $urlParams,
            'binary_logs' => $this->binaryLogs,
            'log' => $log,
            'sql_message' => Generator::getMessage(Message::success(), $sqlQuery),
            'values' => $values,
            'has_previous' => $position > 0,
            'has_next' => $numRows >= $GLOBALS['cfg']['MaxRows'],
            'previous_params' => $previousParams,
            'full_queries_params' => $fullQueriesParams,
            'next_params' => $nextParams,
            'has_icons' => Util::showIcons('TableNavigationLinksMode'),
            'is_full_query' => $isFullQuery,
        ]);
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
