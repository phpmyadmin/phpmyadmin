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
use function count;
use function implode;
use function in_array;
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
    /** @var InsertEdit */
    private $insertEdit;

    /** @var Transformations */
    private $transformations;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        InsertEdit $insertEdit,
        Transformations $transformations,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->insertEdit = $insertEdit;
        $this->transformations = $transformations;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['containerBuilder'] = $GLOBALS['containerBuilder'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $this->checkParameters(['db', 'table', 'goto']);

        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['unsaved_values'] = $GLOBALS['unsaved_values'] ?? null;
        $GLOBALS['active_page'] = $GLOBALS['active_page'] ?? null;
        $GLOBALS['disp_query'] = $GLOBALS['disp_query'] ?? null;
        $GLOBALS['disp_message'] = $GLOBALS['disp_message'] ?? null;
        $GLOBALS['query'] = $GLOBALS['query'] ?? null;

        $this->dbi->selectDb($GLOBALS['db']);

        $this->addScriptFiles(['makegrid.js', 'sql.js', 'indexes.js', 'gis_data_editor.js']);

        $insertRows = $_POST['insert_rows'] ?? null;
        if (is_numeric($insertRows) && $insertRows != $GLOBALS['cfg']['InsertRows']) {
            // check whether insert row mode, if so include /table/change
            $this->addScriptFiles([
                'vendor/jquery/additional-methods.js',
                'table/change.js',
            ]);
            $GLOBALS['cfg']['InsertRows'] = $_POST['insert_rows'];
            /** @var ChangeController $controller */
            $controller = $GLOBALS['containerBuilder']->get(ChangeController::class);
            $controller($request);

            return;
        }

        $after_insert_actions = [
            'new_insert',
            'same_insert',
            'edit_next',
        ];
        if (isset($_POST['after_insert']) && in_array($_POST['after_insert'], $after_insert_actions)) {
            $GLOBALS['urlParams']['after_insert'] = $_POST['after_insert'];
            if (isset($_POST['where_clause'])) {
                foreach ($_POST['where_clause'] as $one_where_clause) {
                    if ($_POST['after_insert'] === 'same_insert') {
                        $GLOBALS['urlParams']['where_clause'][] = $one_where_clause;
                    } elseif ($_POST['after_insert'] === 'edit_next') {
                        $this->insertEdit->setSessionForEditNext($one_where_clause);
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
        [
            $loopArray,
            $usingKey,
            $isInsert,
            $isInsertignore,
        ] = $this->insertEdit->getParamsForUpdateOrInsert();

        $GLOBALS['query'] = [];
        $valueSets = [];

        $mimeMap = $this->transformations->getMime($GLOBALS['db'], $GLOBALS['table']) ?? [];

        $queryFields = [];
        $insertErrors = [];
        $rowSkipped = false;
        $GLOBALS['unsaved_values'] = [];
        foreach ($loopArray as $rownumber => $where_clause) {
            // skip fields to be ignored
            if (! $usingKey && isset($_POST['insert_ignore_' . $where_clause])) {
                continue;
            }

            // Defines the SET part of the sql query
            $queryValues = [];

            // Map multi-edit keys to single-level arrays, dependent on how we got the fields
            $multi_edit_columns = $_POST['fields']['multi_edit'][$rownumber] ?? [];
            $multi_edit_columns_name = $_POST['fields_name']['multi_edit'][$rownumber] ?? [];
            $multi_edit_columns_prev = $_POST['fields_prev']['multi_edit'][$rownumber] ?? null;
            $multi_edit_funcs = $_POST['funcs']['multi_edit'][$rownumber] ?? null;
            $multi_edit_salt = $_POST['salt']['multi_edit'][$rownumber] ?? null;
            $multi_edit_columns_type = $_POST['fields_type']['multi_edit'][$rownumber] ?? null;
            $multi_edit_columns_null = $_POST['fields_null']['multi_edit'][$rownumber] ?? null;
            $multi_edit_columns_null_prev = $_POST['fields_null_prev']['multi_edit'][$rownumber] ?? null;
            $multi_edit_auto_increment = $_POST['auto_increment']['multi_edit'][$rownumber] ?? null;
            $multi_edit_virtual = $_POST['virtual']['multi_edit'][$rownumber] ?? null;

            // When a select field is nullified, it's not present in $_POST
            // so initialize it; this way, the foreach($multi_edit_columns) will process it
            foreach (array_keys($multi_edit_columns_name) as $key) {
                if (isset($multi_edit_columns[$key])) {
                    continue;
                }

                $multi_edit_columns[$key] = '';
            }

            // Iterate in the order of $multi_edit_columns_name,
            // not $multi_edit_columns, to avoid problems
            // when inserting multiple entries
            $insert_fail = false;
            foreach ($multi_edit_columns_name as $key => $column_name) {
                $current_value = $multi_edit_columns[$key];
                // Note: $key is an md5 of the fieldname. The actual fieldname is
                // available in $multi_edit_columns_name[$key]

                $file_to_insert = new File();
                $file_to_insert->checkTblChangeForm((string) $key, (string) $rownumber);

                $possibly_uploaded_val = $file_to_insert->getContent();
                if ($possibly_uploaded_val !== false) {
                    $current_value = $possibly_uploaded_val;
                }

                // Apply Input Transformation if defined
                if (
                    ! empty($mimeMap[$column_name])
                    && ! empty($mimeMap[$column_name]['input_transformation'])
                ) {
                    $filename = 'libraries/classes/Plugins/Transformations/'
                        . $mimeMap[$column_name]['input_transformation'];
                    if (is_file(ROOT_PATH . $filename)) {
                        $classname = $this->transformations->getClassName($filename);
                        if (class_exists($classname)) {
                            /** @var IOTransformationsPlugin $transformation_plugin */
                            $transformation_plugin = new $classname();
                            $transformation_options = $this->transformations->getOptions(
                                $mimeMap[$column_name]['input_transformation_options']
                            );
                            $current_value = $transformation_plugin->applyTransformation(
                                $current_value,
                                $transformation_options
                            );
                            // check if transformation was successful or not
                            // and accordingly set error messages & insert_fail
                            if (
                                method_exists($transformation_plugin, 'isSuccess')
                                && ! $transformation_plugin->isSuccess()
                            ) {
                                $insert_fail = true;
                                $rowSkipped = true;
                                $insertErrors[] = sprintf(
                                    __('Row: %1$s, Column: %2$s, Error: %3$s'),
                                    $rownumber,
                                    $column_name,
                                    $transformation_plugin->getError()
                                );
                            }
                        }
                    }
                }

                if ($file_to_insert->isError()) {
                    $insertErrors[] = $file_to_insert->getError();
                }

                // delete $file_to_insert temporary variable
                $file_to_insert->cleanUp();

                if (empty($multi_edit_funcs[$key])) {
                    $current_value_as_an_array = $this->insertEdit->getCurrentValueForDifferentTypes(
                        $possibly_uploaded_val,
                        $key,
                        $multi_edit_columns_type,
                        $current_value,
                        $multi_edit_auto_increment,
                        $rownumber,
                        $multi_edit_columns_name,
                        $multi_edit_columns_null,
                        $multi_edit_columns_null_prev,
                        $isInsert,
                        $usingKey,
                        $where_clause,
                        $GLOBALS['table']
                    );
                } else {
                    $current_value_as_an_array = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
                        $multi_edit_funcs[$key],
                        $multi_edit_salt[$key] ?? null,
                        $current_value
                    );
                }

                if (! isset($multi_edit_virtual, $multi_edit_virtual[$key])) {
                    [
                        $queryValues,
                        $queryFields,
                    ] = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
                        $multi_edit_columns_name,
                        $multi_edit_columns_null,
                        $current_value,
                        $multi_edit_columns_prev,
                        $multi_edit_funcs,
                        $isInsert,
                        $queryValues,
                        $queryFields,
                        $current_value_as_an_array,
                        $valueSets,
                        $key,
                        $multi_edit_columns_null_prev
                    );
                }

                if (! isset($multi_edit_columns_null[$key])) {
                    continue;
                }

                $multi_edit_columns[$key] = null;
            }

            // temporarily store rows not inserted
            // so that they can be populated again.
            if ($insert_fail) {
                $GLOBALS['unsaved_values'][$rownumber] = $multi_edit_columns;
            }

            if ($insert_fail || count($queryValues) <= 0) {
                continue;
            }

            if ($isInsert) {
                $valueSets[] = implode(', ', $queryValues);
            } else {
                // build update query
                $clauseIsUnique = $_POST['clause_is_unique'] ?? '';// Should contain 0 or 1
                $GLOBALS['query'][] = 'UPDATE ' . Util::backquote($GLOBALS['table'])
                    . ' SET ' . implode(', ', $queryValues)
                    . ' WHERE ' . $where_clause
                    . ($clauseIsUnique ? '' : ' LIMIT 1');
            }
        }

        unset(
            $multi_edit_columns_name,
            $multi_edit_columns_prev,
            $multi_edit_funcs,
            $multi_edit_columns_type,
            $multi_edit_columns_null,
            $multi_edit_auto_increment,
            $current_value_as_an_array,
            $key,
            $current_value,
            $where_clause,
            $multi_edit_columns_null_prev,
            $insert_fail
        );

        // Builds the sql query
        if ($isInsert && $valueSets !== []) {
            $GLOBALS['query'] = $this->insertEdit->buildSqlQuery($isInsertignore, $queryFields, $valueSets);
        } elseif (empty($GLOBALS['query']) && ! isset($_POST['preview_sql']) && ! $rowSkipped) {
            // No change -> move back to the calling script
            //
            // Note: logic passes here for inline edit
            $GLOBALS['message'] = Message::success(__('No change'));
            // Avoid infinite recursion
            if ($gotoInclude === '/table/replace') {
                $gotoInclude = '/table/change';
            }

            $GLOBALS['active_page'] = $gotoInclude;

            if ($gotoInclude === '/sql') {
                /** @var SqlController $controller */
                $controller = $GLOBALS['containerBuilder']->get(SqlController::class);
                $controller();

                return;
            }

            if ($gotoInclude === '/database/sql') {
                /** @var DatabaseSqlController $controller */
                $controller = $GLOBALS['containerBuilder']->get(DatabaseSqlController::class);
                $controller();

                return;
            }

            if ($gotoInclude === '/table/change') {
                /** @var ChangeController $controller */
                $controller = $GLOBALS['containerBuilder']->get(ChangeController::class);
                $controller($request);

                return;
            }

            if ($gotoInclude === '/table/sql') {
                /** @var TableSqlController $controller */
                $controller = $GLOBALS['containerBuilder']->get(TableSqlController::class);
                $controller();

                return;
            }

            /** @psalm-suppress UnresolvableInclude */
            include ROOT_PATH . Core::securePath($gotoInclude);

            return;
        }

        unset($multi_edit_columns);

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            Core::previewSQL($GLOBALS['query']);

            return;
        }

        /**
         * Executes the sql query and get the result, then move back to the calling
         * page
         */
        [
            $GLOBALS['urlParams'],
            $totalAffectedRows,
            $lastMessages,
            $warningMessages,
            $errorMessages,
            $returnToSqlQuery,
        ] = $this->insertEdit->executeSqlQuery($GLOBALS['urlParams'], $GLOBALS['query']);

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
            if (isset($_POST['rel_fields_list']) && $_POST['rel_fields_list'] != '') {
                $map = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table']);

                /** @var array<int,array> $relation_fields */
                $relation_fields = [];
                parse_str($_POST['rel_fields_list'], $relation_fields);

                // loop for each relation cell
                foreach ($relation_fields as $cell_index => $curr_rel_field) {
                    foreach ($curr_rel_field as $relation_field => $relation_field_value) {
                        $where_comparison = "='" . $relation_field_value . "'";
                        $dispval = $this->insertEdit->getDisplayValueForForeignTableColumn(
                            $where_comparison,
                            $map,
                            $relation_field
                        );

                        $extra_data['relations'][$cell_index] = $this->insertEdit->getLinkForRelationalDisplayField(
                            $map,
                            $relation_field,
                            $where_comparison,
                            $dispval,
                            $relation_field_value
                        );
                    }
                }
            }

            if (isset($_POST['do_transformations']) && $_POST['do_transformations'] == true) {
                $edited_values = [];
                parse_str($_POST['transform_fields_list'], $edited_values);

                if (! isset($extra_data)) {
                    $extra_data = [];
                }

                $transformation_types = [
                    'input_transformation',
                    'transformation',
                ];
                foreach ($mimeMap as $transformation) {
                    $column_name = $transformation['column_name'];
                    foreach ($transformation_types as $type) {
                        $file = Core::securePath($transformation[$type]);
                        $extra_data = $this->insertEdit->transformEditedValues(
                            $GLOBALS['db'],
                            $GLOBALS['table'],
                            $transformation,
                            $edited_values,
                            $file,
                            $column_name,
                            $extra_data,
                            $type
                        );
                    }
                }
            }

            // Need to check the inline edited value can be truncated by MySQL
            // without informing while saving
            $column_name = $_POST['fields_name']['multi_edit'][0][0];

            $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $column_name,
                $extra_data
            );

            /**Get the total row count of the table*/
            $_table = new Table($_POST['table'], $_POST['db']);
            $extra_data['row_count'] = $_table->countRecords();

            $extra_data['sql_query'] = Generator::getMessage($GLOBALS['message'], $GLOBALS['display_query']);

            $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
            $this->response->addJSON('message', $GLOBALS['message']);
            $this->response->addJSON($extra_data);

            return;
        }

        if (! empty($returnToSqlQuery)) {
            $GLOBALS['disp_query'] = $GLOBALS['sql_query'];
            $GLOBALS['disp_message'] = $GLOBALS['message'];
            unset($GLOBALS['message']);
            $GLOBALS['sql_query'] = $returnToSqlQuery;
        }

        $this->addScriptFiles(['vendor/jquery/additional-methods.js', 'table/change.js']);

        $GLOBALS['active_page'] = $gotoInclude;

        /**
         * If user asked for "and then Insert another new row" we have to remove
         * WHERE clause information so that /table/change does not go back
         * to the current record
         */
        if (isset($_POST['after_insert']) && $_POST['after_insert'] === 'new_insert') {
            unset($_POST['where_clause']);
        }

        if ($gotoInclude === '/sql') {
            /** @var SqlController $controller */
            $controller = $GLOBALS['containerBuilder']->get(SqlController::class);
            $controller();

            return;
        }

        if ($gotoInclude === '/database/sql') {
            /** @var DatabaseSqlController $controller */
            $controller = $GLOBALS['containerBuilder']->get(DatabaseSqlController::class);
            $controller();

            return;
        }

        if ($gotoInclude === '/table/change') {
            /** @var ChangeController $controller */
            $controller = $GLOBALS['containerBuilder']->get(ChangeController::class);
            $controller($request);

            return;
        }

        if ($gotoInclude === '/table/sql') {
            /** @var TableSqlController $controller */
            $controller = $GLOBALS['containerBuilder']->get(TableSqlController::class);
            $controller();

            return;
        }

        /**
         * Load target page.
         */
        /** @psalm-suppress UnresolvableInclude */
        require ROOT_PATH . Core::securePath($gotoInclude);
    }
}
