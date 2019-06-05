<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\BinlogController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Handles viewing binary logs
 *
 * @package PhpMyAdmin\Controllers
 */
class BinlogController extends AbstractController
{
    /**
     * array binary log files
     */
    protected $binaryLogs;

    /**
     * Constructs BinlogController
     *
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     */
    public function __construct($response, $dbi, Template $template)
    {
        parent::__construct($response, $dbi, $template);
        $this->binaryLogs = $this->dbi->fetchResult(
            'SHOW MASTER LOGS',
            'Log_name',
            null,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
    }

    /**
     * Index action
     *
     * @param array $params Request params
     *
     * @return string
     */
    public function indexAction(array $params): string
    {
        global $cfg, $pmaThemeImage;

        include_once ROOT_PATH . 'libraries/server_common.inc.php';

        $position = ! empty($params['pos']) ? (int) $params['pos'] : 0;

        $urlParams = [];
        if (isset($params['log'])
            && array_key_exists($params['log'], $this->binaryLogs)
        ) {
            $urlParams['log'] = $params['log'];
        }

        $isFullQuery = false;
        if (! empty($params['is_full_query'])) {
            $isFullQuery = true;
            $urlParams['is_full_query'] = 1;
        }

        $sqlQuery = $this->getSqlQuery(
            $params['log'] ?? '',
            $position,
            (int) $cfg['MaxRows']
        );
        $result = $this->dbi->query($sqlQuery);

        $numRows = 0;
        if (isset($result) && $result) {
            $numRows = $this->dbi->numRows($result);
        }

        $previousParams = $urlParams;
        $fullQueriesParams = $urlParams;
        $nextParams = $urlParams;
        if ($position > 0) {
            $fullQueriesParams['pos'] = $position;
            if ($position > $cfg['MaxRows']) {
                $previousParams['pos'] = $position - $cfg['MaxRows'];
            }
        }
        $fullQueriesParams['is_full_query'] = 1;
        if ($isFullQuery) {
            unset($fullQueriesParams['is_full_query']);
        }
        if ($numRows >= $cfg['MaxRows']) {
            $nextParams['pos'] = $position + $cfg['MaxRows'];
        }

        $values = [];
        while ($value = $this->dbi->fetchAssoc($result)) {
            $values[] = $value;
        }

        return $this->template->render('server/binlog/index', [
            'url_params' => $urlParams,
            'binary_logs' => $this->binaryLogs,
            'log' => $params['log'],
            'sql_message' => Util::getMessage(Message::success(), $sqlQuery),
            'values' => $values,
            'has_previous' => $position > 0,
            'has_next' => $numRows >= $cfg['MaxRows'],
            'previous_params' => $previousParams,
            'full_queries_params' => $fullQueriesParams,
            'next_params' => $nextParams,
            'has_icons' => Util::showIcons('TableNavigationLinksMode'),
            'is_full_query' => $isFullQuery,
            'image_path' => $pmaThemeImage,
        ]);
    }

    /**
     * @param string $log      Binary log file name
     * @param int    $position Position to display
     * @param int    $maxRows  Maximum number of rows
     *
     * @return string
     */
    private function getSqlQuery(
        string $log,
        int $position,
        int $maxRows
    ): string {
        $sqlQuery = 'SHOW BINLOG EVENTS';
        if (! empty($log)) {
            $sqlQuery .= ' IN \'' . $log . '\'';
        }
        $sqlQuery .= ' LIMIT ' . $position . ', ' . $maxRows;

        return $sqlQuery;
    }
}
