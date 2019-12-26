<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Database\MultiTableQueryController
 *
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Template;

/**
 * Handles database multi-table querying
 * @package PhpMyAdmin\Controllers\Database
 */
class MultiTableQueryController extends AbstractController
{
    /**
     * @param Template $template Templace instance
     *
     * @return string HTML
     */
    public function index(Template $template): string
    {
        $queryInstance = new MultiTableQuery($this->dbi, $template, $this->db);

        return $queryInstance->getFormHtml();
    }

    /**
     * @param array $params Request parameters
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
