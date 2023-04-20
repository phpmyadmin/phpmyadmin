<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\SqlController as DatabaseSqlController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\SqlController as TableSqlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\EditField;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function array_values;
use function class_exists;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_numeric;
use function method_exists;
use function parse_str;
use function sprintf;

/**
 * Manipulation of table data like inserting, replacing and updating.
 */
final class ReplaceController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private InsertEdit $insertEdit,
        private Transformations $transformations,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['message'] ??= null;
        $this->checkParameters(['db', 'table', 'goto']);

        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['unsaved_values'] ??= null;
        $GLOBALS['active_page'] ??= null;
        $GLOBALS['disp_query'] ??= null;
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['query'] ??= null;

        $this->dbi->selectDb($GLOBALS['db']);

        $this->addScriptFiles(['makegrid.js', 'sql.js', 'gis_data_editor.js']);

        $insertRows = $_POST['insert_rows'] ?? null;
        if (is_numeric($insertRows) && $insertRows != $GLOBALS['cfg']['InsertRows']) {
            // check whether insert row mode, if so include /table/change
            $this->addScriptFiles(['vendor/jquery/additional-methods.js', 'table/change.js']);
            $GLOBALS['cfg']['InsertRows'] = $_POST['insert_rows'];
            /** @var ChangeController $controller */
            $controller = Core::getContainerBuilder()->get(ChangeController::class);
            $controller($request);

            return;
        }

        $afterInsertActions = ['new_insert', 'same_insert', 'edit_next'];
        if (isset($_POST['after_insert']) && in_array($_POST['after_insert'], $afterInsertActions)) {
            $GLOBALS['urlParams']['after_insert'] = $_POST['after_insert'];
            if (isset($_POST['where_clause'])) {
                foreach ($_POST['where_clause'] as $oneWhereClause) {
                    if ($_POST['after_insert'] === 'same_insert') {
                        $GLOBALS['urlParams']['where_clause'][] = $oneWhereClause;
                    } elseif ($_POST['after_insert'] === 'edit_next') {
                        $this->insertEdit->setSessionForEditNext($oneWhereClause);
                    }
                }
            }
        }

        //get $goto_include for different cases
        $gotoInclude = $this->insertEdit->getGotoInclude(false);

        // Defines the url to return in case of failure of the query
        $GLOBALS['errorUrl'] = $this->insertEdit->getErrorUrl($GLOBALS['urlParams']);

        /**
         * Prepares the update/insert of a row
         */
        [$loopArray, $usingKey, $isInsert] = $this->getParamsForUpdateOrInsert();

        $isInsertignore = isset($_POST['submit_type']) && $_POST['submit_type'] === 'insertignore';

        $GLOBALS['query'] = [];
        $valueSets = [];

        $mimeMap = $this->transformations->getMime($GLOBALS['db'], $GLOBALS['table']) ?? [];

        $queryFields = [];
        $insertErrors = [];
        $rowSkipped = false;
        $GLOBALS['unsaved_values'] = [];
        /** @var string|int $whereClause */
        foreach ($loopArray as $rowNumber => $whereClause) {
            // skip fields to be ignored
            if (! $usingKey && isset($_POST['insert_ignore_' . $whereClause])) {
                continue;
            }

            // Defines the SET part of the sql query
            $queryValues = [];

            // Map multi-edit keys to single-level arrays, dependent on how we got the fields
            $multiEditColumns = $_POST['fields']['multi_edit'][$rowNumber] ?? [];
            $multiEditColumnsName = $_POST['fields_name']['multi_edit'][$rowNumber] ?? [];
            $multiEditColumnsPrev = $_POST['fields_prev']['multi_edit'][$rowNumber] ?? [];
            $multiEditFuncs = $_POST['funcs']['multi_edit'][$rowNumber] ?? [];
            $multiEditSalt = $_POST['salt']['multi_edit'][$rowNumber] ?? [];
            $multiEditColumnsType = $_POST['fields_type']['multi_edit'][$rowNumber] ?? [];
            $multiEditColumnsNull = $_POST['fields_null']['multi_edit'][$rowNumber] ?? [];
            $multiEditColumnsNullPrev = $_POST['fields_null_prev']['multi_edit'][$rowNumber] ?? [];
            $multiEditAutoIncrement = $_POST['auto_increment']['multi_edit'][$rowNumber] ?? [];
            $multiEditVirtual = $_POST['virtual']['multi_edit'][$rowNumber] ?? [];

            // Iterate in the order of $multi_edit_columns_name,
            // not $multi_edit_columns, to avoid problems
            // when inserting multiple entries
            $insertFail = false;
            /** @var int|string $key */
            foreach ($multiEditColumnsName as $key => $columnName) {
                // Note: $key is an md5 of the fieldname. The actual fieldname is
                // available in $multi_edit_columns_name[$key]

                // When a select field is nullified, it's not present in $_POST so initialize it
                $multiEditColumns[$key] ??= '';

                /** @var string[]|string $currentValue */
                $currentValue = $multiEditColumns[$key];
                if (is_array($currentValue)) {
                    // Some column types accept comma-separated values e.g. set
                    $currentValue = implode(',', $currentValue);
                }

                $fileToInsert = new File();
                $fileToInsert->checkTblChangeForm((string) $key, (string) $rowNumber);

                $possiblyUploadedVal = $fileToInsert->getContent();
                if ($possiblyUploadedVal !== false) {
                    $currentValue = $possiblyUploadedVal;
                }

                // Apply Input Transformation if defined
                if (
                    ! empty($mimeMap[$columnName])
                    && ! empty($mimeMap[$columnName]['input_transformation'])
                ) {
                    $filename = 'libraries/classes/Plugins/Transformations/'
                        . $mimeMap[$columnName]['input_transformation'];
                    if (is_file(ROOT_PATH . $filename)) {
                        $className = $this->transformations->getClassName($filename);
                        if (class_exists($className)) {
                            /** @var IOTransformationsPlugin $transformationPlugin */
                            $transformationPlugin = new $className();
                            $transformationOptions = $this->transformations->getOptions(
                                $mimeMap[$columnName]['input_transformation_options'],
                            );
                            $currentValue = $transformationPlugin->applyTransformation(
                                $currentValue,
                                $transformationOptions,
                            );
                            // check if transformation was successful or not
                            // and accordingly set error messages & insert_fail
                            if (
                                method_exists($transformationPlugin, 'isSuccess')
                                && ! $transformationPlugin->isSuccess()
                            ) {
                                $insertFail = true;
                                $rowSkipped = true;
                                $insertErrors[] = sprintf(
                                    __('Row: %1$s, Column: %2$s, Error: %3$s'),
                                    $rowNumber,
                                    $columnName,
                                    $transformationPlugin->getError(),
                                );
                            }
                        }
                    }
                }

                if ($fileToInsert->isError()) {
                    $insertErrors[] = $fileToInsert->getError();
                }

                // delete $file_to_insert temporary variable
                $fileToInsert->cleanUp();

                $editField = new EditField(
                    $columnName,
                    $currentValue,
                    $multiEditColumnsType[$key] ?? '',
                    isset($multiEditAutoIncrement[$key]),
                    ! empty($multiEditColumnsNull[$key]),
                    ! empty($multiEditColumnsNullPrev[$key]),
                    $multiEditFuncs[$key] ?? '',
                    $multiEditSalt[$key] ?? null,
                    $multiEditColumnsPrev[$key] ?? null,
                    $possiblyUploadedVal !== false,
                );

                if (! isset($multiEditVirtual[$key])) {
                    if ($isInsert) {
                        $queryPart = $this->insertEdit->getQueryValueForInsert($editField, $usingKey, $whereClause);
                        if ($queryPart !== '' && $valueSets === []) {
                            // first inserted row so prepare the list of fields
                            $queryFields[] = Util::backquote($editField->columnName);
                        }
                    } else {
                        $queryPart = $this->insertEdit->getQueryValueForUpdate($editField);
                    }

                    if ($queryPart !== '') {
                        $queryValues[] = $queryPart;
                    }
                }

                // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
                if ($editField->isNull) {
                    $multiEditColumns[$key] = null;
                }
            }

            // temporarily store rows not inserted
            // so that they can be populated again.
            if ($insertFail) {
                $GLOBALS['unsaved_values'][$rowNumber] = $multiEditColumns;
            }

            if ($insertFail || $queryValues === []) {
                continue;
            }

            if ($isInsert) {
                $valueSets[] = implode(', ', $queryValues);
            } else {
                // build update query
                $clauseIsUnique = $_POST['clause_is_unique'] ?? $_GET['clause_is_unique'] ?? '';// Should contain 0 or 1
                $GLOBALS['query'][] = 'UPDATE ' . Util::backquote($GLOBALS['table'])
                    . ' SET ' . implode(', ', $queryValues)
                    . ' WHERE ' . $whereClause
                    . ($clauseIsUnique ? '' : ' LIMIT 1');
            }
        }

        unset(
            $multiEditColumnsName,
            $multiEditColumnsPrev,
            $multiEditFuncs,
            $multiEditColumnsType,
            $multiEditColumnsNull,
            $multiEditAutoIncrement,
            $key,
            $currentValue,
            $whereClause,
            $multiEditColumnsNullPrev,
            $insertFail,
            $multiEditColumns,
        );

        // Builds the sql query
        if ($isInsert && $valueSets !== []) {
            $GLOBALS['query'] = (array) $this->insertEdit->buildInsertSqlQuery(
                $GLOBALS['table'],
                $isInsertignore,
                $queryFields,
                $valueSets,
            );
        } elseif (empty($GLOBALS['query']) && ! isset($_POST['preview_sql']) && ! $rowSkipped) {
            // No change -> move back to the calling script
            //
            // Note: logic passes here for inline edit
            $GLOBALS['message'] = Message::success(__('No change'));
            // Avoid infinite recursion
            if ($gotoInclude === '/table/replace') {
                $gotoInclude = '/table/change';
            }

            $this->moveBackToCallingScript($gotoInclude, $request);

            return;
        }

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            Core::previewSQL($GLOBALS['query']);

            return;
        }

        $returnToSqlQuery = '';
        if (! empty($GLOBALS['sql_query'])) {
            $GLOBALS['urlParams']['sql_query'] = $GLOBALS['sql_query'];
            $returnToSqlQuery = $GLOBALS['sql_query'];
        }

        /**
         * Executes the sql query and get the result, then move back to the calling
         * page
         */
        [
            $totalAffectedRows,
            $lastMessages,
            $warningMessages,
            $errorMessages,
        ] = $this->insertEdit->executeSqlQuery($GLOBALS['query']);

        if ($isInsert && ($valueSets !== [] || $rowSkipped)) {
            $GLOBALS['message'] = Message::getMessageForInsertedRows($totalAffectedRows);
            $GLOBALS['unsaved_values'] = array_values($GLOBALS['unsaved_values']);
        } else {
            $GLOBALS['message'] = Message::getMessageForAffectedRows($totalAffectedRows);
        }

        if ($rowSkipped) {
            $gotoInclude = '/table/change';
            $GLOBALS['message']->addMessagesString($insertErrors, '<br>');
            $GLOBALS['message']->isError(true);
        }

        $GLOBALS['message']->addMessages($lastMessages, '<br>');

        if (! empty($warningMessages)) {
            $GLOBALS['message']->addMessagesString($warningMessages, '<br>');
            $GLOBALS['message']->isError(true);
        }

        if (! empty($errorMessages)) {
            $GLOBALS['message']->addMessagesString($errorMessages);
            $GLOBALS['message']->isError(true);
        }

        /**
         * The following section only applies to grid editing.
         * However, verifying isAjax() is not enough to ensure we are coming from
         * grid editing. If we are coming from the Edit or Copy link in Browse mode,
         * ajax_page_request is present in the POST parameters.
         */
        if ($this->response->isAjax() && ! isset($_POST['ajax_page_request'])) {
            /**
             * If we are in grid editing, we need to process the relational and
             * transformed fields, if they were edited. After that, output the correct
             * link/transformed value and exit
             */
            $this->doTransformations($mimeMap);

            return;
        }

        if (! empty($returnToSqlQuery)) {
            $GLOBALS['disp_query'] = $GLOBALS['sql_query'];
            $GLOBALS['disp_message'] = $GLOBALS['message'];
            unset($GLOBALS['message']);
            $GLOBALS['sql_query'] = $returnToSqlQuery;
        }

        $this->addScriptFiles(['vendor/jquery/additional-methods.js', 'table/change.js']);

        /**
         * If user asked for "and then Insert another new row" we have to remove
         * WHERE clause information so that /table/change does not go back
         * to the current record
         */
        if (isset($_POST['after_insert']) && $_POST['after_insert'] === 'new_insert') {
            unset($_POST['where_clause']);
        }

        $this->moveBackToCallingScript($gotoInclude, $request);
    }

    /** @param string[][] $mimeMap */
    private function doTransformations(array $mimeMap): void
    {
        if (isset($_POST['rel_fields_list']) && $_POST['rel_fields_list'] != '') {
            $map = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table']);

            /** @var array<int,array> $relationFields */
            $relationFields = [];
            parse_str($_POST['rel_fields_list'], $relationFields);

            // loop for each relation cell
            foreach ($relationFields as $cellIndex => $currRelField) {
                foreach ($currRelField as $relationField => $relationFieldValue) {
                    $whereComparison = "='" . $relationFieldValue . "'";
                    $dispval = $this->insertEdit->getDisplayValueForForeignTableColumn(
                        $whereComparison,
                        $map,
                        $relationField,
                    );

                    $extraData['relations'][$cellIndex] = $this->insertEdit->getLinkForRelationalDisplayField(
                        $map,
                        $relationField,
                        $whereComparison,
                        $dispval,
                        $relationFieldValue,
                    );
                }
            }
        }

        if (isset($_POST['do_transformations']) && $_POST['do_transformations'] == true) {
            $editedValues = [];
            parse_str($_POST['transform_fields_list'], $editedValues);

            if (! isset($extraData)) {
                $extraData = [];
            }

            $transformationTypes = ['input_transformation', 'transformation'];
            foreach ($mimeMap as $transformation) {
                $columnName = $transformation['column_name'];
                foreach ($transformationTypes as $type) {
                    $file = Core::securePath($transformation[$type]);
                    $extraData = $this->insertEdit->transformEditedValues(
                        $GLOBALS['db'],
                        $GLOBALS['table'],
                        $transformation,
                        $editedValues,
                        $file,
                        $columnName,
                        $extraData,
                        $type,
                    );
                }
            }
        }

        // Need to check the inline edited value can be truncated by MySQL
        // without informing while saving
        $columnName = $_POST['fields_name']['multi_edit'][0][0];

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            $GLOBALS['db'],
            $GLOBALS['table'],
            $columnName,
            $extraData,
        );

        /**Get the total row count of the table*/
        $tableObj = new Table($_POST['table'], $_POST['db'], $this->dbi);
        $extraData['row_count'] = $tableObj->countRecords();

        $extraData['sql_query'] = Generator::getMessage($GLOBALS['message'], $GLOBALS['display_query']);

        $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
        $this->response->addJSON('message', $GLOBALS['message']);
        $this->response->addJSON($extraData);
    }

    private function moveBackToCallingScript(string $gotoInclude, ServerRequest $request): void
    {
        $GLOBALS['active_page'] = $gotoInclude;
        $container = Core::getContainerBuilder();
        if ($gotoInclude === '/sql') {
            /** @var SqlController $controller */
            $controller = $container->get(SqlController::class);
            $controller($request);

            return;
        }

        if ($gotoInclude === '/database/sql') {
            /** @var DatabaseSqlController $controller */
            $controller = $container->get(DatabaseSqlController::class);
            $controller($request);

            return;
        }

        if ($gotoInclude === '/table/change') {
            /** @var ChangeController $controller */
            $controller = $container->get(ChangeController::class);
            $controller($request);

            return;
        }

        if ($gotoInclude === '/table/sql') {
            /** @var TableSqlController $controller */
            $controller = $container->get(TableSqlController::class);
            $controller($request);

            return;
        }

        /**
         * Load target page.
         */
        /** @psalm-suppress UnresolvableInclude */
        require ROOT_PATH . Core::securePath($gotoInclude);
    }

    /**
     * Prepares the update/insert of a row
     *
     * @return mixed[] $loop_array, $using_key, $is_insert
     * @psalm-return array{array, bool, bool}
     */
    private function getParamsForUpdateOrInsert(): array
    {
        if (isset($_POST['where_clause'])) {
            // we were editing something => use the WHERE clause
            $loopArray = is_array($_POST['where_clause'])
                ? $_POST['where_clause']
                : [$_POST['where_clause']];
            $usingKey = true;
            $isInsert = isset($_POST['submit_type'])
                && ($_POST['submit_type'] === 'insert'
                    || $_POST['submit_type'] === 'showinsert'
                    || $_POST['submit_type'] === 'insertignore');
        } else {
            // new row => use indexes
            $loopArray = [];
            if (! empty($_POST['fields'])) {
                $loopArray = array_keys($_POST['fields']['multi_edit']);
            }

            $usingKey = false;
            $isInsert = true;
        }

        return [$loopArray, $usingKey, $isInsert];
    }
}
