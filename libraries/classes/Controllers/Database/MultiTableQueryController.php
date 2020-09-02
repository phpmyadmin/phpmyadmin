<?php
/**
 * Holds the PhpMyAdmin\Controllers\Database\MultiTableQueryController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\MultiTableQuery;

/**
 * Handles database multi-table querying
 */
class MultiTableQueryController extends AbstractController
{
    public function index(): void
    {
        $this->addScriptFiles([
            'vendor/jquery/jquery.md5.js',
            'database/multi_table_query.js',
            'database/query_generator.js',
        ]);

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, $this->db);

        $this->response->addHTML($queryInstance->getFormHtml());
    }

    public function displayResults(): void
    {
        global $PMA_Theme;

        $params = [
            'sql_query' => $_POST['sql_query'],
            'db' => $_POST['db'] ?? $_GET['db'] ?? null,
        ];

        $this->response->addHTML(MultiTableQuery::displayResults(
            $params['sql_query'],
            $params['db'],
            $PMA_Theme->getImgPath()
        ));
    }

    public function table(): void
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
    }
}
