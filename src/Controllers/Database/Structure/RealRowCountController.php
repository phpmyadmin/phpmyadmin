<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;

use function __;

/**
 * Handles request for real row count on database level view page.
 */
#[Route('/database/structure/real-row-count', ['GET', 'POST'])]
final class RealRowCountController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $parameters = [
            'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
            'table' => $_REQUEST['table'] ?? null,
        ];

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return $this->response->response();
        }

        // If there is a request to update all table's row count.
        if (! isset($parameters['real_row_count_all'])) {
            // Get the real row count for the table.
            $realRowCount = $this->dbi
                ->getTable(Current::$database, (string) $parameters['table'])
                ->getRealRowCountTable();
            // Format the number.
            $realRowCount = Util::formatNumber($realRowCount, 0);

            $this->response->addJSON(['real_row_count' => $realRowCount]);

            return $this->response->response();
        }

        // Array to store the results.
        $realRowCountAll = [];
        // Iterate over each table and fetch real row count.
        foreach ($this->dbi->getTables(Current::$database) as $table) {
            $rowCount = $this->dbi
                ->getTable(Current::$database, $table)
                ->getRealRowCountTable();
            $realRowCountAll[] = ['table' => $table, 'row_count' => Util::formatNumber($rowCount, 0)];
        }

        $this->response->addJSON(['real_row_count_all' => $realRowCountAll]);

        return $this->response->response();
    }
}
