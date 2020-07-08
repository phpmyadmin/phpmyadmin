<?php
/**
 * Holds the PhpMyAdmin\Controllers\Database\MultiTableQueryController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\MultiTableQuery;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handles database multi-table querying
 */
class MultiTableQueryController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('vendor/jquery/jquery.md5.js');
        $scripts->addFile('database/multi_table_query.js');
        $scripts->addFile('database/query_generator.js');

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, $this->db);

        $this->response->addHTML($queryInstance->getFormHtml());

        return $response;
    }

    public function displayResults(Request $request, Response $response): Response
    {
        global $pmaThemeImage;

        $params = [
            'sql_query' => $_POST['sql_query'],
            'db' => $_POST['db'] ?? $_GET['db'] ?? null,
        ];

        MultiTableQuery::displayResults(
            $params['sql_query'],
            $params['db'],
            $pmaThemeImage
        );

        return $response;
    }

    public function table(Request $request, Response $response): Response
    {
        $params = [
            'tables' => $_GET['tables'],
            'db' => $_GET['db'] ?? null,
        ];
        $constrains = $this->dbi->getForeignKeyConstrains(
            $params['db'],
            $params['tables']
        );
        $this->response->addJSON(['foreignKeyConstrains' => $constrains]);

        return $response;
    }
}
