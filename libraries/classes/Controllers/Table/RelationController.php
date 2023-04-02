<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Features\DisplayFeature;
use PhpMyAdmin\ConfigStorage\Features\RelationFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function array_key_exists;
use function array_keys;
use function array_values;
use function mb_strtoupper;
use function md5;
use function strtoupper;
use function uksort;
use function usort;

/**
 * Display table relations for viewing and editing.
 *
 * Includes phpMyAdmin relations and InnoDB relations.
 */
final class RelationController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    /**
     * Index
     */
    public function __invoke(ServerRequest $request): void
    {
        $options = [
            'CASCADE' => 'CASCADE',
            'SET_NULL' => 'SET NULL',
            'NO_ACTION' => 'NO ACTION',
            'RESTRICT' => 'RESTRICT',
        ];

        $table = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);
        $storageEngine = $table->getStorageEngine();

        $relationParameters = $this->relation->getRelationParameters();

        $relations = [];
        if ($relationParameters->relationFeature !== null) {
            $relations = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'internal');
        }

        $relationsForeign = [];
        if (ForeignKey::isSupported($storageEngine)) {
            $relationsForeign = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign');
        }

        // Send table of column names to populate corresponding dropdowns depending
        // on the current selection
        if (isset($_POST['getDropdownValues']) && $_POST['getDropdownValues'] === 'true') {
            // if both db and table are selected
            if (isset($_POST['foreignTable'])) {
                $this->getDropdownValueForTable();
            } else { // if only the db is selected
                $this->getDropdownValueForDatabase($storageEngine);
            }

            return;
        }

        $this->addScriptFiles(['table/relation.js']);

        // Set the database
        $this->dbi->selectDb($GLOBALS['db']);

        // updates for Internal relations
        if (isset($_POST['destination_db']) && $relationParameters->relationFeature !== null) {
            $this->updateForInternalRelation($table, $relationParameters->relationFeature, $relations);
        }

        // updates for foreign keys
        $this->updateForForeignKeys($table, $options, $relationsForeign);

        // Updates for display field
        if ($relationParameters->displayFeature !== null && isset($_POST['display_field'])) {
            $this->updateForDisplayField($table, $relationParameters->displayFeature);
        }

        // If we did an update, refresh our data
        if (isset($_POST['destination_db']) && $relationParameters->relationFeature !== null) {
            $relations = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'internal');
        }

        if (isset($_POST['destination_foreign_db']) && ForeignKey::isSupported($storageEngine)) {
            $relationsForeign = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign');
        }

        /**
         * Dialog
         */
        // Now find out the columns of our $table
        // need to use DatabaseInterface::QUERY_BUFFERED with $this->dbi->numRows()
        // in mysqli
        $columns = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table']);

        $columnArray = [];
        $columnHashArray = [];
        $columnArray[''] = '';
        foreach ($columns as $column) {
            if (strtoupper($storageEngine) !== 'INNODB' && empty($column['Key'])) {
                continue;
            }

            $columnArray[$column['Field']] = $column['Field'];
            $columnHashArray[$column['Field']] = md5($column['Field']);
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            uksort($columnArray, 'strnatcasecmp');
        }

        $foreignKeyRow = '';
        $existrelForeign = array_key_exists('foreign_keys_data', $relationsForeign)
            ? $relationsForeign['foreign_keys_data']
            : [];
        $i = 0;

        foreach ($existrelForeign as $oneKey) {
            $foreignDb = $oneKey['ref_db_name'] ?? $GLOBALS['db'];
            $foreignTable = false;
            if ($foreignDb) {
                $foreignTable = $oneKey['ref_table_name'] ?? false;
                $tables = $this->relation->getTables($foreignDb, $storageEngine);
            } else {
                $tables = $this->relation->getTables($GLOBALS['db'], $storageEngine);
            }

            $uniqueColumns = [];
            if ($foreignDb && $foreignTable) {
                $tableObject = new Table($foreignTable, $foreignDb, $this->dbi);
                $uniqueColumns = $tableObject->getUniqueColumns(false, false);
            }

            $foreignKeyRow .= $this->template->render('table/relation/foreign_key_row', [
                'i' => $i,
                'one_key' => $oneKey,
                'column_array' => $columnArray,
                'options_array' => $options,
                'tbl_storage_engine' => $storageEngine,
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
                'url_params' => $GLOBALS['urlParams'],
                'databases' => $this->dbi->getDatabaseList(),
                'foreign_db' => $foreignDb,
                'foreign_table' => $foreignTable,
                'unique_columns' => $uniqueColumns,
                'tables' => $tables,
            ]);
            $i++;
        }

        $tables = $this->relation->getTables($GLOBALS['db'], $storageEngine);
        $foreignKeyRow .= $this->template->render('table/relation/foreign_key_row', [
            'i' => $i,
            'one_key' => [],
            'column_array' => $columnArray,
            'options_array' => $options,
            'tbl_storage_engine' => $storageEngine,
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'url_params' => $GLOBALS['urlParams'],
            'databases' => $this->dbi->getDatabaseList(),
            'foreign_db' => false,
            'foreign_table' => false,
            'unique_columns' => [],
            'tables' => $tables,
        ]);

        // common form
        $this->render('table/relation/common_form', [
            'is_foreign_key_supported' => ForeignKey::isSupported($storageEngine),
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'relation_parameters' => $relationParameters,
            'tbl_storage_engine' => $storageEngine,
            'existrel' => $relations,
            'existrel_foreign' => $existrelForeign,
            'options_array' => $options,
            'column_array' => $columnArray,
            'column_hash_array' => $columnHashArray,
            'save_row' => array_values($columns),
            'url_params' => $GLOBALS['urlParams'],
            'databases' => $this->dbi->getDatabaseList(),
            'dbi' => $this->dbi,
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'route' => $request->getRoute(),
            'display_field' => $this->relation->getDisplayField($GLOBALS['db'], $GLOBALS['table']),
            'foreign_key_row' => $foreignKeyRow,
        ]);
    }

    /**
     * Update for display field
     */
    private function updateForDisplayField(Table $table, DisplayFeature $displayFeature): void
    {
        $table->updateDisplayField($_POST['display_field'], $displayFeature);

        $this->response->addHTML(
            Generator::getMessage(
                __('Display column was successfully updated.'),
                '',
                'success',
            ),
        );
    }

    /**
     * Update for FK
     *
     * @param Table   $table            Table
     * @param mixed[] $options          Options
     * @param mixed[] $relationsForeign External relations
     */
    private function updateForForeignKeys(Table $table, array $options, array $relationsForeign): void
    {
        $multiEditColumnsName = $_POST['foreign_key_fields_name'] ?? null;
        $previewSqlData = '';
        $seenError = false;

        // (for now, one index name only; we keep the definitions if the
        // foreign db is not the same)
        if (
            isset($_POST['destination_foreign_db'], $_POST['destination_foreign_table'])
            && isset($_POST['destination_foreign_column'])
        ) {
            [$html, $previewSqlData, $displayQuery, $seenError] = $table->updateForeignKeys(
                $_POST['destination_foreign_db'],
                $multiEditColumnsName,
                $_POST['destination_foreign_table'],
                $_POST['destination_foreign_column'],
                $options,
                $GLOBALS['table'],
                array_key_exists('foreign_keys_data', $relationsForeign)
                    ? $relationsForeign['foreign_keys_data']
                    : [],
            );
            $this->response->addHTML($html);
        }

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            Core::previewSQL($previewSqlData);

            exit;
        }

        if (empty($displayQuery) || $seenError) {
            return;
        }

        $GLOBALS['display_query'] = $displayQuery;
        $this->response->addHTML(
            Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                null,
                'success',
            ),
        );
    }

    /**
     * Update for internal relation
     *
     * @param mixed[] $relations Relations
     */
    private function updateForInternalRelation(
        Table $table,
        RelationFeature $relationFeature,
        array $relations,
    ): void {
        $multiEditColumnsName = $_POST['fields_name'] ?? null;

        if (
            ! $table->updateInternalRelations(
                $multiEditColumnsName,
                $_POST['destination_db'],
                $_POST['destination_table'],
                $_POST['destination_column'],
                $relationFeature,
                $relations,
            )
        ) {
            return;
        }

        $this->response->addHTML(
            Generator::getMessage(
                __('Internal relationships were successfully updated.'),
                '',
                'success',
            ),
        );
    }

    /**
     * Send table columns for foreign table dropdown
     */
    public function getDropdownValueForTable(): void
    {
        $foreignTable = $_POST['foreignTable'];
        $tableObj = $this->dbi->getTable($_POST['foreignDb'], $foreignTable);
        // Since views do not have keys defined on them provide the full list of
        // columns
        if ($tableObj->isView()) {
            $columnList = $tableObj->getColumns(false, false);
        } else {
            $columnList = $tableObj->getIndexedColumns(false, false);
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($columnList, 'strnatcasecmp');
        }

        $this->response->addJSON('columns', $columnList);

        $primary = Index::getPrimary($this->dbi, $foreignTable, $_POST['foreignDb']);
        if ($primary === null) {
            return;
        }

        $this->response->addJSON('primary', array_keys($primary->getColumns()));
    }

    /**
     * Send database selection values for dropdown
     *
     * @param string $storageEngine Storage engine.
     */
    public function getDropdownValueForDatabase(string $storageEngine): void
    {
        $tables = [];
        $foreign = isset($_POST['foreign']) && $_POST['foreign'] === 'true';

        if ($foreign) {
            $query = 'SHOW TABLE STATUS FROM '
                . Util::backquote($_POST['foreignDb']);
            $tablesRs = $this->dbi->query($query);

            foreach ($tablesRs as $row) {
                if (! isset($row['Engine']) || mb_strtoupper($row['Engine']) !== $storageEngine) {
                    continue;
                }

                $tables[] = $row['Name'];
            }
        } else {
            $query = 'SHOW TABLES FROM '
                . Util::backquote($_POST['foreignDb']);
            $tablesRs = $this->dbi->query($query);
            $tables = $tablesRs->fetchAllColumn();
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($tables, 'strnatcasecmp');
        }

        $this->response->addJSON('tables', $tables);
    }
}
