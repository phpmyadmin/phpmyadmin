<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\DisplayFeature;
use PhpMyAdmin\ConfigStorage\Features\RelationFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function array_keys;
use function md5;
use function strnatcasecmp;
use function uksort;
use function usort;

/**
 * Display table relations for viewing and editing.
 *
 * Includes phpMyAdmin relations and InnoDB relations.
 */
#[Route('/table/relation', ['GET', 'POST'])]
final readonly class RelationController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private Relation $relation,
        private DatabaseInterface $dbi,
        private Config $config,
    ) {
    }

    /**
     * Index
     */
    public function __invoke(ServerRequest $request): Response
    {
        $options = [
            'CASCADE' => 'CASCADE',
            'SET_NULL' => 'SET NULL',
            'NO_ACTION' => 'NO ACTION',
            'RESTRICT' => 'RESTRICT',
        ];

        $table = $this->dbi->getTable(Current::$database, Current::$table);
        $storageEngine = $table->getStorageEngine();

        $relationParameters = $this->relation->getRelationParameters();

        $relations = [];
        if ($relationParameters->relationFeature !== null) {
            $relations = $this->relation->getForeignersInternal(Current::$database, Current::$table);
        }

        $relationsForeign = [];
        if (ForeignKey::isSupported($storageEngine)) {
            $relationsForeign = $this->relation->getForeignKeysData(Current::$database, Current::$table);
        }

        // Send table of column names to populate corresponding dropdowns depending
        // on the current selection
        if (isset($_POST['getDropdownValues']) && $_POST['getDropdownValues'] === 'true') {
            // if both db and table are selected
            if (isset($_POST['foreignTable'])) {
                $this->getDropdownValueForTable();
            } else { // if only the db is selected
                $this->getDropdownValueForDatabase(
                    $storageEngine,
                    $request->getParsedBodyParamAsString('foreignDb'),
                    $request->getParsedBodyParamAsString('foreign', ''),
                );
            }

            return $this->response->response();
        }

        $this->response->addScriptFiles(['table/relation.js']);

        // Set the database
        $this->dbi->selectDb(Current::$database);

        // updates for Internal relations
        if (isset($_POST['destination_db']) && $relationParameters->relationFeature !== null) {
            $this->updateForInternalRelation($table, $relationParameters->relationFeature, $relations);
        }

        // updates for foreign keys
        $multiEditColumnsName = $_POST['foreign_key_fields_name'] ?? null;
        $previewSqlData = '';
        $displayQuery = '';
        $seenError = false;

        // (for now, one index name only; we keep the definitions if the
        // foreign db is not the same)
        if (
            isset(
                $_POST['destination_foreign_db'],
                $_POST['destination_foreign_table'],
                $_POST['destination_foreign_column'],
            )
        ) {
            [$html, $previewSqlData, $displayQuery, $seenError] = $table->updateForeignKeys(
                $_POST['destination_foreign_db'],
                $multiEditColumnsName,
                $_POST['destination_foreign_table'],
                $_POST['destination_foreign_column'],
                $options,
                Current::$table,
                $relationsForeign,
            );
            $this->response->addHTML($html);
        }

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            Core::previewSQL($previewSqlData);

            return $this->response->response();
        }

        if ($displayQuery !== '' && ! $seenError) {
            Current::$displayQuery = $displayQuery;
            $this->response->addHTML(
                Generator::getMessage(
                    __('Your SQL query has been executed successfully.'),
                    null,
                    MessageType::Success,
                ),
            );
        }

        // Updates for display field
        if ($relationParameters->displayFeature !== null && isset($_POST['display_field'])) {
            $this->updateForDisplayField($table, $relationParameters->displayFeature);
        }

        // If we did an update, refresh our data
        if (isset($_POST['destination_db']) && $relationParameters->relationFeature !== null) {
            $relations = $this->relation->getForeignersInternal(Current::$database, Current::$table);
        }

        if (isset($_POST['destination_foreign_db']) && ForeignKey::isSupported($storageEngine)) {
            $relationsForeign = $this->relation->getForeignKeysData(Current::$database, Current::$table);
        }

        /**
         * Dialog
         */
        // Now find out the columns of our $table
        $columns = $this->dbi->getColumns(Current::$database, Current::$table);

        $columnArray = [];
        $columnArray[''] = '';
        foreach ($columns as $column) {
            if ($storageEngine !== 'InnoDB' && $column->key === '') {
                continue;
            }

            $columnArray[$column->field] = $column->field;
        }

        if ($this->config->settings['NaturalOrder']) {
            uksort($columnArray, strnatcasecmp(...));
        }

        $foreignKeyRow = '';
        $i = 0;

        foreach ($relationsForeign as $oneKey) {
            $foreignDb = $oneKey->refDbName ?? Current::$database;
            $foreignTable = '';
            if ($foreignDb !== '') {
                $foreignTable = $oneKey->refTableName ?? '';
                $tables = $this->relation->getTables($foreignDb, $storageEngine);
            } else {
                $tables = $this->relation->getTables(Current::$database, $storageEngine);
            }

            $uniqueColumns = [];
            if ($foreignDb !== '' && $foreignTable !== '') {
                $tableObject = new Table($foreignTable, $foreignDb, $this->dbi);
                $uniqueColumns = $tableObject->getUniqueColumns(false, false);
            }

            $foreignKeyRow .= $this->template->render('table/relation/foreign_key_row', [
                'i' => $i,
                'one_key' => $oneKey,
                'column_array' => $columnArray,
                'options_array' => $options,
                'db' => Current::$database,
                'table' => Current::$table,
                'url_params' => UrlParams::$params,
                'databases' => $this->dbi->getDatabaseList(),
                'foreign_db' => $foreignDb,
                'foreign_table' => $foreignTable,
                'unique_columns' => $uniqueColumns,
                'tables' => $tables,
            ]);
            $i++;
        }

        $tables = $this->relation->getTables(Current::$database, $storageEngine);
        $foreignKeyRow .= $this->template->render('table/relation/foreign_key_row', [
            'i' => $i,
            'one_key' => [],
            'column_array' => $columnArray,
            'options_array' => $options,
            'db' => Current::$database,
            'table' => Current::$table,
            'url_params' => UrlParams::$params,
            'databases' => $this->dbi->getDatabaseList(),
            'foreign_db' => '',
            'foreign_table' => '',
            'unique_columns' => [],
            'tables' => $tables,
        ]);

        $internalRelationColumns = [];
        foreach ($columns as $column) {
            // Use a md5 as array index to avoid having special characters in the name attribute
            // (see bug https://github.com/phpmyadmin/phpmyadmin/issues/8827)
            $fieldHash = md5($column->field);

            $foreignTable = '';
            $foreignColumn = false;

            // Database dropdown
            if (isset($relations[$column->field])) {
                /** @var string $foreignDb */
                $foreignDb = $relations[$column->field]['foreign_db'];
            } else {
                $foreignDb = Current::$database;
            }

            // Table dropdown
            $foreignTables = [];
            if ($foreignDb !== '') {
                if (isset($relations[$column->field])) {
                    /** @var string $foreignTable */
                    $foreignTable = $relations[$column->field]['foreign_table'];
                }

                $foreignTables = $this->dbi->getTables($foreignDb);
            }

            // Column dropdown
            $uniqueColumns = [];
            if ($foreignDb !== '' && $foreignTable !== '') {
                if (isset($relations[$column->field])) {
                    /** @var string $foreignColumn */
                    $foreignColumn = $relations[$column->field]['foreign_field'];
                }

                $tableObject = new Table($foreignTable, $foreignDb, $this->dbi);
                $uniqueColumns = $tableObject->getUniqueColumns(false, false);
            }

            $internalRelationColumns[] = [
                'field' => $column->field,
                'field_hash' => $fieldHash,
                'foreign_db' => $foreignDb,
                'foreign_table' => $foreignTable,
                'foreign_column' => $foreignColumn,
                'tables' => $foreignTables,
                'unique_columns' => $uniqueColumns,
            ];
        }

        // common form
        $this->response->render('table/relation/common_form', [
            'is_foreign_key_supported' => ForeignKey::isSupported($storageEngine),
            'db' => Current::$database,
            'table' => Current::$table,
            'relation_parameters' => $relationParameters,
            'tbl_storage_engine' => $storageEngine,
            'options_array' => $options,
            'internal_relation_columns' => $internalRelationColumns,
            'url_params' => UrlParams::$params,
            'databases' => $this->dbi->getDatabaseList(),
            'default_sliders_state' => $this->config->settings['InitialSlidersState'],
            'route' => $request->getRoute(),
            'display_field' => $this->relation->getDisplayField(Current::$database, Current::$table),
            'foreign_key_row' => $foreignKeyRow,
        ]);

        return $this->response->response();
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
                MessageType::Success,
            ),
        );
    }

    /**
     * Update for internal relation
     *
     * @param mixed[][] $relations Relations
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
                MessageType::Success,
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

        if ($this->config->settings['NaturalOrder']) {
            usort($columnList, strnatcasecmp(...));
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
    public function getDropdownValueForDatabase(string $storageEngine, string $foreignDb, string $foreign): void
    {
        $foreign = $foreign === 'true';

        if ($foreign) {
            $tables = $this->relation->getTables($foreignDb, $storageEngine);
        } else {
            $tables = $this->dbi->getTables($foreignDb);
        }

        $this->response->addJSON('tables', $tables);
    }
}
