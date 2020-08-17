<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use function json_encode;

/**
 * Table/Column autocomplete in SQL editors.
 */
class SqlAutoCompleteController extends AbstractController
{
    public function index(): void
    {
        global $cfg, $db, $sql_autocomplete;

        $sql_autocomplete = true;
        if ($cfg['EnableAutocompleteForTablesAndColumns']) {
            $db = $_POST['db'] ?? $db;
            $sql_autocomplete = [];
            if ($db) {
                $tableNames = $this->dbi->getTables($db);
                foreach ($tableNames as $tableName) {
                    $sql_autocomplete[$tableName] = $this->dbi->getColumns(
                        $db,
                        $tableName
                    );
                }
            }
        }
        $this->response->addJSON(['tables' => json_encode($sql_autocomplete)]);
    }
}
