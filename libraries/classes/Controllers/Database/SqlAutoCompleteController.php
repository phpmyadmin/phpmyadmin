<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

/**
 * Table/Column autocomplete in SQL editors.
 */
class SqlAutoCompleteController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $sqlAutocomplete = [];
        if ($GLOBALS['cfg']['EnableAutocompleteForTablesAndColumns']) {
            $db = $request->getParam('db', $GLOBALS['db']);
            if ($db) {
                $tableNames = $this->dbi->getTables($db);
                foreach ($tableNames as $tableName) {
                    $sqlAutocomplete[$tableName] = $this->dbi->getColumns($db, $tableName);
                }
            }
        }

        $this->response->addJSON(['tables' => $sqlAutocomplete]);
    }
}
