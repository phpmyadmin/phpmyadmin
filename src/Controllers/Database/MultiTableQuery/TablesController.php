<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function array_map;
use function implode;

#[Route('/database/multi-table-query/tables', ['GET'])]
final class TablesController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $tables */
        $tables = $request->getQueryParam('tables', []);
        /** @var string $db */
        $db = $request->getQueryParam('db', '');

        $tablesListForQuery = array_map($this->dbi->quoteString(...), $tables);

        $constrains = $this->dbi->fetchResultSimple(
            QueryGenerator::getInformationSchemaForeignKeyConstraintsRequest(
                $this->dbi->quoteString($db),
                implode(',', $tablesListForQuery),
            ),
        );
        $this->response->addJSON(['foreignKeyConstrains' => $constrains]);

        return $this->response->response();
    }
}
