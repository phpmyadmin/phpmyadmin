<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

use function rtrim;

/**
 * Handles database multi-table querying
 */
class MultiTableQueryController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name.
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        $this->addScriptFiles([
            'database/multi_table_query.js',
            'database/query_generator.js',
        ]);

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, $this->db);

        $this->response->addHTML($queryInstance->getFormHtml());
    }

    public function displayResults(): void
    {
        $params = [
            'sql_query' => $_POST['sql_query'],
            'db' => $_POST['db'] ?? $_GET['db'] ?? null,
        ];

        $this->response->addHTML(MultiTableQuery::displayResults(
            $params['sql_query'],
            $params['db']
        ));
    }

    public function table(): void
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
