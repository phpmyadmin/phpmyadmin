<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function rtrim;

final class TablesController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var string[] $tables */
        $tables = $request->getQueryParam('tables', []);
        /** @var string $db */
        $db = $request->getQueryParam('db', '');

        $tablesListForQuery = '';
        foreach ($tables as $table) {
            $tablesListForQuery .= "'" . $this->dbi->escapeString($table) . "',";
        }

        $tablesListForQuery = rtrim($tablesListForQuery, ',');

        $constrains = $this->dbi->fetchResult(
            QueryGenerator::getInformationSchemaForeignKeyConstraintsRequest(
                $this->dbi->escapeString($db),
                $tablesListForQuery,
            ),
        );
        $this->response->addJSON(['foreignKeyConstrains' => $constrains]);
    }
}
