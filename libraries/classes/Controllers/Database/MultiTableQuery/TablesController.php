<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function rtrim;

final class TablesController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param ResponseRenderer  $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $params = [
            'tables' => $_GET['tables'] ?? [],
            'db' => $_GET['db'] ?? '',
        ];

        $tablesListForQuery = '';
        foreach ($params['tables'] as $table) {
            $tablesListForQuery .= "'" . $this->dbi->escapeString($table) . "',";
        }

        $tablesListForQuery = rtrim($tablesListForQuery, ',');

        $constrains = $this->dbi->fetchResult(
            QueryGenerator::getInformationSchemaForeignKeyConstraintsRequest(
                $this->dbi->escapeString($params['db']),
                $tablesListForQuery
            ),
            null,
            null,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $this->response->addJSON(['foreignKeyConstrains' => $constrains]);
    }
}
