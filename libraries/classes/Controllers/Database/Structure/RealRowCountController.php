<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function json_encode;

/**
 * Handles request for real row count on database level view page.
 */
final class RealRowCountController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $parameters = [
            'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
            'table' => $_REQUEST['table'] ?? null,
        ];

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase() || ! $this->response->isAjax()) {
            return;
        }

        [$tables] = Util::getDbInfo($GLOBALS['db'], '_structure');

        // If there is a request to update all table's row count.
        if (! isset($parameters['real_row_count_all'])) {
            // Get the real row count for the table.
            $realRowCount = (int) $this->dbi
                ->getTable($GLOBALS['db'], (string) $parameters['table'])
                ->getRealRowCountTable();
            // Format the number.
            $realRowCount = Util::formatNumber($realRowCount, 0);

            $this->response->addJSON(['real_row_count' => $realRowCount]);

            return;
        }

        // Array to store the results.
        $realRowCountAll = [];
        // Iterate over each table and fetch real row count.
        foreach ($tables as $table) {
            $rowCount = $this->dbi
                ->getTable($GLOBALS['db'], $table['TABLE_NAME'])
                ->getRealRowCountTable();
            $realRowCountAll[] = [
                'table' => $table['TABLE_NAME'],
                'row_count' => $rowCount,
            ];
        }

        $this->response->addJSON(['real_row_count_all' => json_encode($realRowCountAll)]);
    }
}
