<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function json_encode;

/**
 * Table/Column autocomplete in SQL editors.
 */
class SqlAutoCompleteController extends AbstractController
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
        $GLOBALS['sql_autocomplete'] = true;
        if ($GLOBALS['cfg']['EnableAutocompleteForTablesAndColumns']) {
            $GLOBALS['db'] = $_POST['db'] ?? $GLOBALS['db'];
            $GLOBALS['sql_autocomplete'] = [];
            if ($GLOBALS['db']) {
                $tableNames = $this->dbi->getTables($GLOBALS['db']);
                foreach ($tableNames as $tableName) {
                    $GLOBALS['sql_autocomplete'][$tableName] = $this->dbi->getColumns($GLOBALS['db'], $tableName);
                }
            }
        }

        $this->response->addJSON(['tables' => json_encode($GLOBALS['sql_autocomplete'])]);
    }
}
