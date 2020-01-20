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
    public function index(): string
    {
        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('vendor/jquery/jquery.md5.js');
        $scripts->addFile('database/multi_table_query.js');
        $scripts->addFile('database/query_generator.js');

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, $this->db);

        return $queryInstance->getFormHtml();
    }

    /**
     * @param array $params Request parameters
     *
     * @return void
     */
    public function displayResults(array $params): void
    {
        global $pmaThemeImage;

        MultiTableQuery::displayResults(
            $params['sql_query'],
            $params['db'],
            $pmaThemeImage
        );
    }

    /**
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function table(array $params): array
    {
        $constrains = $this->dbi->getForeignKeyConstrains(
            $params['db'],
            $params['tables']
        );

        return ['foreignKeyConstrains' => $constrains];
    }
}
