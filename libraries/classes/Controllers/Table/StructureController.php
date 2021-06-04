<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Partition;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\TablePartitionDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use stdClass;
use function array_keys;
use function array_splice;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function mb_strpos;
use function mb_strtoupper;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function trim;

/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns.
 */
class StructureController extends AbstractController
{
    /** @var Table  The table object */
    protected $tableObj;

    /** @var CreateAddField */
    private $createAddField;

    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name
     * @param string            $table    Table name
     * @param DatabaseInterface $dbi
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $table,
        Relation $relation,
        Transformations $transformations,
        CreateAddField $createAddField,
        RelationCleanup $relationCleanup,
        $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->createAddField = $createAddField;
        $this->relation = $relation;
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
        $this->dbi = $dbi;

        $this->tableObj = $this->dbi->getTable($this->db, $this->table);
    }

    public function index(): void
    {
        global $reread_info, $showtable, $db, $table, $cfg, $err_url;
        global $tbl_is_view, $tbl_storage_engine, $tbl_collation, $table_info_num_rows;

        $this->dbi->selectDb($this->db);
        $reread_info = $this->tableObj->getStatusInfo(null, true);
        $showtable = $this->tableObj->getStatusInfo(
            null,
            (isset($reread_info) && $reread_info)
        );

        if ($this->tableObj->isView()) {
            $tbl_is_view = true;
            $tbl_storage_engine = __('View');
        } else {
            $tbl_is_view = false;
            $tbl_storage_engine = $this->tableObj->getStorageEngine();
        }

        $tbl_collation = $this->tableObj->getCollation();
        $table_info_num_rows = $this->tableObj->getNumRows();

        $pageSettings = new PageSettings('TableStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['table/structure.js', 'indexes.js']);

        $cfgRelation = $this->relation->getRelationsParam();

        Util::checkParameters(['db', 'table']);

        $isSystemSchema = Utilities::isSystemSchema($db);
        $url_params = ['db' => $db, 'table' => $table];
        $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $err_url .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        $primary = Index::getPrimary($this->table, $this->db);
        $columns_with_index = $this->dbi
            ->getTable($this->db, $this->table)
            ->getColumnsWithIndex(
                Index::UNIQUE | Index::INDEX | Index::SPATIAL
                | Index::FULLTEXT
            );
        $columns_with_unique_index = $this->dbi
            ->getTable($this->db, $this->table)
            ->getColumnsWithIndex(Index::UNIQUE);

        $fields = (array) $this->dbi->getColumns(
            $this->db,
            $this->table,
            null,
            true
        );

        $this->response->addHTML($this->displayStructure(
            $cfgRelation,
            $columns_with_unique_index,
            $primary,
            $fields,
            $columns_with_index,
            $isSystemSchema
        ));
    }

    public function save(): void
    {
        $regenerate = $this->updateColumns();
        if (! $regenerate) {
            // continue to show the table's structure
            unset($_POST['selected']);
        }

        $this->index();
    }

    public function addKey(): void
    {
        global $containerBuilder, $reload;

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index();

        $reload = true;

        $this->index();
    }

    public function browse(): void
    {
        global $PMA_Theme;

        if (empty($_POST['selected_fld'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->displayTableBrowseForSelectedColumns(
            $GLOBALS['goto'],
            $PMA_Theme->getImgPath()
        );
    }

    public function change(): void
    {
        if (isset($_GET['change_column'])) {
            $this->displayHtmlForColumnChange(null);

            return;
        }

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->displayHtmlForColumnChange($selected);
    }

    public function addToCentralColumns(): void
    {
        global $sql_query, $message;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $centralColsError = $centralColumns->syncUniqueColumns(
            $selected,
            false
        );

        if ($centralColsError instanceof Message) {
            $message = $centralColsError;
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function removeFromCentralColumns(): void
    {
        global $sql_query, $db, $message;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $centralColsError = $centralColumns->deleteColumnsFromList(
            $db,
            $selected,
            false
        );

        if ($centralColsError instanceof Message) {
            $message = $centralColsError;
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function fulltext(): void
    {
        global $sql_query, $db, $table, $message;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $i = 1;
        $selectedCount = count($selected);
        $sql_query = 'ALTER TABLE ' . Util::backquote($table) . ' ADD FULLTEXT(';

        foreach ($selected as $field) {
            $sql_query .= Util::backquote($field);
            $sql_query .= $i++ === $selectedCount ? ');' : ', ';
        }

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql_query);

        if (! $result) {
            $message = Message::error((string) $this->dbi->getError());
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function spatial(): void
    {
        global $sql_query, $db, $table, $message;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $i = 1;
        $selectedCount = count($selected);
        $sql_query = 'ALTER TABLE ' . Util::backquote($table) . ' ADD SPATIAL(';

        foreach ($selected as $field) {
            $sql_query .= Util::backquote($field);
            $sql_query .= $i++ === $selectedCount ? ');' : ', ';
        }

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql_query);

        if (! $result) {
            $message = Message::error((string) $this->dbi->getError());
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function unique(): void
    {
        global $sql_query, $db, $table, $message;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $i = 1;
        $selectedCount = count($selected);
        $sql_query = 'ALTER TABLE ' . Util::backquote($table) . ' ADD UNIQUE(';

        foreach ($selected as $field) {
            $sql_query .= Util::backquote($field);
            $sql_query .= $i++ === $selectedCount ? ');' : ', ';
        }

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql_query);

        if (! $result) {
            $message = Message::error((string) $this->dbi->getError());
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function addIndex(): void
    {
        global $sql_query, $db, $table, $message;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $i = 1;
        $selectedCount = count($selected);
        $sql_query = 'ALTER TABLE ' . Util::backquote($table) . ' ADD INDEX(';

        foreach ($selected as $field) {
            $sql_query .= Util::backquote($field);
            $sql_query .= $i++ === $selectedCount ? ');' : ', ';
        }

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql_query);

        if (! $result) {
            $message = Message::error((string) $this->dbi->getError());
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function primary(): void
    {
        global $db, $table, $message, $sql_query, $url_params, $err_url, $cfg;

        $selected = $_POST['selected'] ?? [];
        $selected_fld = $_POST['selected_fld'] ?? [];

        if (empty($selected) && empty($selected_fld)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $primary = $this->getKeyForTablePrimary();
        if (empty($primary) && ! empty($selected_fld)) {
            // no primary key, so we can safely create new
            $mult_btn = __('Yes');
            $selected = $selected_fld;
        }

        $mult_btn = $_POST['mult_btn'] ?? $mult_btn ?? '';

        if (! empty($selected_fld) && ! empty($primary)) {
            Util::checkParameters(['db', 'table']);

            $url_params = ['db' => $db, 'table' => $table];
            $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $err_url .= Url::getCommon($url_params, '&');

            DbTableExists::check();

            $this->render('table/structure/primary', [
                'db' => $db,
                'table' => $table,
                'selected' => $selected_fld,
            ]);

            return;
        }

        if ($mult_btn === __('Yes')) {
            $sql_query = 'ALTER TABLE ' . Util::backquote($table);
            if (! empty($primary)) {
                $sql_query .= ' DROP PRIMARY KEY,';
            }
            $sql_query .= ' ADD PRIMARY KEY(';

            $i = 1;
            $selectedCount = count($selected);
            foreach ($selected as $field) {
                $sql_query .= Util::backquote($field);
                $sql_query .= $i++ === $selectedCount ? ');' : ', ';
            }

            $this->dbi->selectDb($db);
            $result = $this->dbi->tryQuery($sql_query);

            if (! $result) {
                $message = Message::error((string) $this->dbi->getError());
            }
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function drop(): void
    {
        global $db, $table, $message, $sql_query;

        $selected = $_POST['selected'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $sql_query = '';

        if (($_POST['mult_btn'] ?? '') === __('Yes')) {
            $i = 1;
            $selectedCount = count($selected);
            $sql_query = 'ALTER TABLE ' . Util::backquote($table);

            foreach ($selected as $field) {
                $this->relationCleanup->column($db, $table, $field);
                $sql_query .= ' DROP ' . Util::backquote($field);
                $sql_query .= $i++ === $selectedCount ? ';' : ',';
            }

            $this->dbi->selectDb($db);
            $result = $this->dbi->tryQuery($sql_query);

            if (! $result) {
                $message = Message::error((string) $this->dbi->getError());
            }
        }

        if (empty($message)) {
            $message = Message::success();
        }

        $this->index();
    }

    public function dropConfirm(): void
    {
        global $db, $table, $url_params, $err_url, $cfg;

        $selected = $_POST['selected_fld'] ?? null;

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        Util::checkParameters(['db', 'table']);

        $url_params = ['db' => $db, 'table' => $table];
        $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $err_url .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        $this->render('table/structure/drop_confirm', [
            'db' => $db,
            'table' => $table,
            'fields' => $selected,
        ]);
    }

    /**
     * Moves columns in the table's structure based on $_REQUEST
     */
    public function moveColumns(): void
    {
        if (! isset($_POST['move_columns'])
            || ! is_array($_POST['move_columns'])
            || ! $this->response->isAjax()
        ) {
            return;
        }

        $this->dbi->selectDb($this->db);

        /**
         * load the definitions for all columns
         */
        $columns = $this->dbi->getColumnsFull($this->db, $this->table);
        $column_names = array_keys($columns);
        $changes = [];

        // @see https://mariadb.com/kb/en/library/changes-improvements-in-mariadb-102/#information-schema
        $usesLiteralNull = $this->dbi->isMariaDB() && $this->dbi->getVersion() >= 100200;
        $defaultNullValue = $usesLiteralNull ? 'NULL' : null;
        // move columns from first to last
        for ($i = 0, $l = count($_POST['move_columns']); $i < $l; $i++) {
            $column = $_POST['move_columns'][$i];
            // is this column already correctly placed?
            if ($column_names[$i] == $column) {
                continue;
            }

            // it is not, let's move it to index $i
            $data = $columns[$column];
            $extracted_columnspec = Util::extractColumnSpec($data['Type']);
            if (isset($data['Extra'])
                && $data['Extra'] === 'on update CURRENT_TIMESTAMP'
            ) {
                $extracted_columnspec['attribute'] = $data['Extra'];
                unset($data['Extra']);
            }
            $timeType = $data['Type'] === 'timestamp' || $data['Type'] === 'datetime';
            $timeDefault = $data['Default'] === 'CURRENT_TIMESTAMP' || $data['Default'] === 'current_timestamp()';
            $current_timestamp = $timeType && $timeDefault;

            // @see https://mariadb.com/kb/en/library/information-schema-columns-table/#examples
            if ($data['Null'] === 'YES' && in_array($data['Default'], [$defaultNullValue, null])) {
                $default_type = 'NULL';
            } elseif ($current_timestamp) {
                $default_type = 'CURRENT_TIMESTAMP';
            } elseif ($data['Default'] === null) {
                $default_type = 'NONE';
            } else {
                $default_type = 'USER_DEFINED';
            }

            $virtual = [
                'VIRTUAL',
                'PERSISTENT',
                'VIRTUAL GENERATED',
                'STORED GENERATED',
            ];
            $data['Virtuality'] = '';
            $data['Expression'] = '';
            if (isset($data['Extra']) && in_array($data['Extra'], $virtual)) {
                $data['Virtuality'] = str_replace(' GENERATED', '', $data['Extra']);
                $expressions = $this->tableObj->getColumnGenerationExpression($column);
                $data['Expression'] = is_array($expressions) ? $expressions[$column] : null;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                $column,
                $column,
                mb_strtoupper($extracted_columnspec['type']),
                $extracted_columnspec['spec_in_brackets'],
                $extracted_columnspec['attribute'],
                $data['Collation'] ?? '',
                $data['Null'] === 'YES' ? 'YES' : 'NO',
                $default_type,
                $current_timestamp ? '' : $data['Default'],
                isset($data['Extra']) && $data['Extra'] !== '' ? $data['Extra']
                : false,
                isset($data['COLUMN_COMMENT']) && $data['COLUMN_COMMENT'] !== ''
                ? $data['COLUMN_COMMENT'] : false,
                $data['Virtuality'],
                $data['Expression'],
                $i === 0 ? '-first' : $column_names[$i - 1]
            );
            // update current column_names array, first delete old position
            for ($j = 0, $ll = count($column_names); $j < $ll; $j++) {
                if ($column_names[$j] != $column) {
                    continue;
                }

                unset($column_names[$j]);
            }
            // insert moved column
            array_splice($column_names, $i, 0, $column);
        }
        if (empty($changes) && ! isset($_REQUEST['preview_sql'])) { // should never happen
            $this->response->setRequestStatus(false);

            return;
        }
        // query for moving the columns
        $sql_query = sprintf(
            'ALTER TABLE %s %s',
            Util::backquote($this->table),
            implode(', ', $changes)
        );

        if (isset($_REQUEST['preview_sql'])) { // preview sql
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sql_query])
            );
        } else { // move column
            $this->dbi->tryQuery($sql_query);
            $tmp_error = $this->dbi->getError();
            if (is_string($tmp_error)) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error($tmp_error));
            } else {
                $message = Message::success(
                    __('The columns have been moved successfully.')
                );
                $this->response->addJSON('message', $message);
                $this->response->addJSON('columns', $column_names);
            }
        }
    }

    /**
     * Displays HTML for changing one or more columns
     *
     * @param array|null $selected the selected columns
     */
    protected function displayHtmlForColumnChange($selected): void
    {
        global $action, $num_fields;

        if (empty($selected)) {
            $selected[] = $_REQUEST['field'];
            $selected_cnt = 1;
        } else { // from a multiple submit
            $selected_cnt = count($selected);
        }

        /**
         * @todo optimize in case of multiple fields to modify
         */
        $fields_meta = [];
        for ($i = 0; $i < $selected_cnt; $i++) {
            $value = $this->dbi->getColumns(
                $this->db,
                $this->table,
                $selected[$i],
                true
            );
            if (count($value) === 0) {
                $message = Message::error(
                    __('Failed to get description of column %s!')
                );
                $message->addParam($selected[$i]);
                $this->response->addHTML($message);
            } else {
                $fields_meta[] = $value;
            }
        }
        $num_fields = count($fields_meta);

        $action = Url::getFromRoute('/table/structure/save');

        /**
         * Form for changing properties.
         */
        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js', 'indexes.js']);

        $templateData = ColumnsDefinition::displayForm(
            $this->transformations,
            $this->relation,
            $this->dbi,
            $action,
            $num_fields,
            null,
            $selected,
            $fields_meta
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }

    public function partitioning(): void
    {
        if (isset($_POST['save_partitioning'])) {
            $this->dbi->selectDb($this->db);
            $this->updatePartitioning();
            $this->index();

            return;
        }

        $pageSettings = new PageSettings('TableStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles(['table/structure.js', 'indexes.js']);

        $partitionDetails = null;
        if (! isset($_POST['partition_by'])) {
            $partitionDetails = $this->extractPartitionDetails();
        }

        $storageEngines = StorageEngine::getArray();

        $partitionDetails = TablePartitionDefinition::getDetails($partitionDetails);
        $this->render('table/structure/partition_definition_form', [
            'db' => $this->db,
            'table' => $this->table,
            'partition_details' => $partitionDetails,
            'storage_engines' => $storageEngines,
        ]);
    }

    /**
     * Extracts partition details from CREATE TABLE statement
     *
     * @return array[]|null array of partition details
     */
    private function extractPartitionDetails(): ?array
    {
        $createTable = (new Table($this->table, $this->db))->showCreate();
        if (! $createTable) {
            return null;
        }

        $parser = new Parser($createTable);
        /**
         * @var CreateStatement $stmt
         */
        $stmt = $parser->statements[0];

        $partitionDetails = [];

        $partitionDetails['partition_by'] = '';
        $partitionDetails['partition_expr'] = '';
        $partitionDetails['partition_count'] = '';

        if (! empty($stmt->partitionBy)) {
            $openPos = strpos($stmt->partitionBy, '(');
            $closePos = strrpos($stmt->partitionBy, ')');

            $partitionDetails['partition_by']
                = trim(substr($stmt->partitionBy, 0, $openPos));
            $partitionDetails['partition_expr']
                = trim(substr($stmt->partitionBy, $openPos + 1, $closePos - ($openPos + 1)));
            if (isset($stmt->partitionsNum)) {
                $count = $stmt->partitionsNum;
            } else {
                $count = count($stmt->partitions);
            }
            $partitionDetails['partition_count'] = $count;
        }

        $partitionDetails['subpartition_by'] = '';
        $partitionDetails['subpartition_expr'] = '';
        $partitionDetails['subpartition_count'] = '';

        if (! empty($stmt->subpartitionBy)) {
            $openPos = strpos($stmt->subpartitionBy, '(');
            $closePos = strrpos($stmt->subpartitionBy, ')');

            $partitionDetails['subpartition_by']
                = trim(substr($stmt->subpartitionBy, 0, $openPos));
            $partitionDetails['subpartition_expr']
                = trim(substr($stmt->subpartitionBy, $openPos + 1, $closePos - ($openPos + 1)));
            if (isset($stmt->subpartitionsNum)) {
                $count = $stmt->subpartitionsNum;
            } else {
                $count = count($stmt->partitions[0]->subpartitions);
            }
            $partitionDetails['subpartition_count'] = $count;
        }

        // Only LIST and RANGE type parameters allow subpartitioning
        $partitionDetails['can_have_subpartitions']
            = $partitionDetails['partition_count'] > 1
                && ($partitionDetails['partition_by'] === 'RANGE'
                || $partitionDetails['partition_by'] === 'RANGE COLUMNS'
                || $partitionDetails['partition_by'] === 'LIST'
                || $partitionDetails['partition_by'] === 'LIST COLUMNS');

        // Values are specified only for LIST and RANGE type partitions
        $partitionDetails['value_enabled'] = isset($partitionDetails['partition_by'])
            && ($partitionDetails['partition_by'] === 'RANGE'
            || $partitionDetails['partition_by'] === 'RANGE COLUMNS'
            || $partitionDetails['partition_by'] === 'LIST'
            || $partitionDetails['partition_by'] === 'LIST COLUMNS');

        $partitionDetails['partitions'] = [];

        for ($i = 0, $iMax = (int) $partitionDetails['partition_count']; $i < $iMax; $i++) {
            if (! isset($stmt->partitions[$i])) {
                $partitionDetails['partitions'][$i] = [
                    'name' => 'p' . $i,
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                ];
            } else {
                $p = $stmt->partitions[$i];
                $type = $p->type;
                $expr = trim((string) $p->expr, '()');
                if ($expr === 'MAXVALUE') {
                    $type .= ' MAXVALUE';
                    $expr = '';
                }
                $partitionDetails['partitions'][$i] = [
                    'name' => $p->name,
                    'value_type' => $type,
                    'value' => $expr,
                    'engine' => $p->options->has('ENGINE', true),
                    'comment' => trim((string) $p->options->has('COMMENT', true), "'"),
                    'data_directory' => trim((string) $p->options->has('DATA DIRECTORY', true), "'"),
                    'index_directory' => trim((string) $p->options->has('INDEX_DIRECTORY', true), "'"),
                    'max_rows' => $p->options->has('MAX_ROWS', true),
                    'min_rows' => $p->options->has('MIN_ROWS', true),
                    'tablespace' => $p->options->has('TABLESPACE', true),
                    'node_group' => $p->options->has('NODEGROUP', true),
                ];
            }

            $partition =& $partitionDetails['partitions'][$i];
            $partition['prefix'] = 'partitions[' . $i . ']';

            if ($partitionDetails['subpartition_count'] <= 1) {
                continue;
            }

            $partition['subpartition_count'] = $partitionDetails['subpartition_count'];
            $partition['subpartitions'] = [];

            for ($j = 0, $jMax = (int) $partitionDetails['subpartition_count']; $j < $jMax; $j++) {
                if (! isset($stmt->partitions[$i]->subpartitions[$j])) {
                    $partition['subpartitions'][$j] = [
                        'name' => $partition['name'] . '_s' . $j,
                        'engine' => '',
                        'comment' => '',
                        'data_directory' => '',
                        'index_directory' => '',
                        'max_rows' => '',
                        'min_rows' => '',
                        'tablespace' => '',
                        'node_group' => '',
                    ];
                } else {
                    $sp = $stmt->partitions[$i]->subpartitions[$j];
                    $partition['subpartitions'][$j] = [
                        'name' => $sp->name,
                        'engine' => $sp->options->has('ENGINE', true),
                        'comment' => trim((string) $sp->options->has('COMMENT', true), "'"),
                        'data_directory' => trim((string) $sp->options->has('DATA DIRECTORY', true), "'"),
                        'index_directory' => trim((string) $sp->options->has('INDEX_DIRECTORY', true), "'"),
                        'max_rows' => $sp->options->has('MAX_ROWS', true),
                        'min_rows' => $sp->options->has('MIN_ROWS', true),
                        'tablespace' => $sp->options->has('TABLESPACE', true),
                        'node_group' => $sp->options->has('NODEGROUP', true),
                    ];
                }

                $subpartition =& $partition['subpartitions'][$j];
                $subpartition['prefix'] = 'partitions[' . $i . ']'
                    . '[subpartitions][' . $j . ']';
            }
        }

        return $partitionDetails;
    }

    private function updatePartitioning(): void
    {
        $sql_query = 'ALTER TABLE ' . Util::backquote($this->table) . ' '
            . $this->createAddField->getPartitionsDefinition();

        // Execute alter query
        $result = $this->dbi->tryQuery($sql_query);

        if ($result === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                Message::rawError(
                    __('Query error') . ':<br>' . $this->dbi->getError()
                )
            );

            return;
        }

        $message = Message::success(
            __('Table %1$s has been altered successfully.')
        );
        $message->addParam($this->table);
        $this->response->addHTML(
            Generator::getMessage($message, $sql_query, 'success')
        );
    }

    /**
     * Function to display table browse for selected columns
     *
     * @param string $goto           goto page url
     * @param string $themeImagePath URI of the pma theme image
     *
     * @return void
     */
    protected function displayTableBrowseForSelectedColumns($goto, $themeImagePath)
    {
        $GLOBALS['active_page'] = Url::getFromRoute('/sql');
        $fields = [];
        foreach ($_POST['selected_fld'] as $sval) {
            $fields[] = Util::backquote($sval);
        }
        $sql_query = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $fields),
            Util::backquote($this->db),
            Util::backquote($this->table)
        );

        // Parse and analyze the query
        $db = &$this->db;
        [
            $analyzed_sql_results,
            $db,
        ] = ParseAnalyze::sqlQuery($sql_query, $db);

        $sql = new Sql(
            $this->dbi,
            $this->relation,
            $this->relationCleanup,
            new Operations($this->dbi, $this->relation),
            $this->transformations,
            $this->template
        );

        $this->response->addHTML(
            $sql->executeQueryAndGetQueryResponse(
                $analyzed_sql_results ?? '',
                false, // is_gotofile
                $this->db, // db
                $this->table, // table
                null, // find_real_end
                null, // sql_query_for_bookmark
                null, // extra_data
                null, // message_to_show
                null, // sql_data
                $goto, // goto
                $themeImagePath,
                null, // disp_query
                null, // disp_message
                $sql_query, // sql_query
                null // complete_query
            )
        );
    }

    /**
     * Update the table's structure based on $_REQUEST
     *
     * @return bool true if error occurred
     */
    protected function updateColumns()
    {
        $err_url = Url::getFromRoute('/table/structure', [
            'db' => $this->db,
            'table' => $this->table,
        ]);
        $regenerate = false;
        $field_cnt = count($_POST['field_name'] ?? []);
        $changes = [];
        $adjust_privileges = [];
        $columns_with_index = $this->dbi
            ->getTable($this->db, $this->table)
            ->getColumnsWithIndex(
                Index::PRIMARY | Index::UNIQUE
            );
        for ($i = 0; $i < $field_cnt; $i++) {
            if (! $this->columnNeedsAlterTable($i)) {
                continue;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                Util::getValueByKey($_POST, "field_orig.${i}", ''),
                $_POST['field_name'][$i],
                $_POST['field_type'][$i],
                $_POST['field_length'][$i],
                $_POST['field_attribute'][$i],
                Util::getValueByKey($_POST, "field_collation.${i}", ''),
                Util::getValueByKey($_POST, "field_null.${i}", 'NO'),
                $_POST['field_default_type'][$i],
                $_POST['field_default_value'][$i],
                Util::getValueByKey($_POST, "field_extra.${i}", false),
                Util::getValueByKey($_POST, "field_comments.${i}", ''),
                Util::getValueByKey($_POST, "field_virtuality.${i}", ''),
                Util::getValueByKey($_POST, "field_expression.${i}", ''),
                Util::getValueByKey($_POST, "field_move_to.${i}", ''),
                $columns_with_index
            );

            // find the remembered sort expression
            $sorted_col = $this->tableObj->getUiProp(
                Table::PROP_SORTED_COLUMN
            );
            // if the old column name is part of the remembered sort expression
            if (mb_strpos(
                (string) $sorted_col,
                Util::backquote($_POST['field_orig'][$i])
            ) !== false) {
                // delete the whole remembered sort expression
                $this->tableObj->removeUiProp(Table::PROP_SORTED_COLUMN);
            }

            if (! isset($_POST['field_adjust_privileges'][$i])
                || empty($_POST['field_adjust_privileges'][$i])
                || $_POST['field_orig'][$i] == $_POST['field_name'][$i]
            ) {
                continue;
            }

            $adjust_privileges[$_POST['field_orig'][$i]]
                = $_POST['field_name'][$i];
        }

        if (count($changes) > 0 || isset($_POST['preview_sql'])) {
            // Builds the primary keys statements and updates the table
            $key_query = '';
            /**
             * this is a little bit more complex
             *
             * @todo if someone selects A_I when altering a column we need to check:
             *  - no other column with A_I
             *  - the column has an index, if not create one
             */

            // To allow replication, we first select the db to use
            // and then run queries on this db.
            if (! $this->dbi->selectDb($this->db)) {
                Generator::mysqlDie(
                    $this->dbi->getError(),
                    'USE ' . Util::backquote($this->db) . ';',
                    false,
                    $err_url
                );
            }

            $sql_query = 'ALTER TABLE ' . Util::backquote($this->table) . ' ';
            $sql_query .= implode(', ', $changes) . $key_query;
            if (isset($_POST['online_transaction'])) {
                $sql_query .= ', ALGORITHM=INPLACE, LOCK=NONE';
            }
            $sql_query .= ';';

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL(count($changes) > 0 ? $sql_query : '');

                exit;
            }

            $columns_with_index = $this->dbi
                ->getTable($this->db, $this->table)
                ->getColumnsWithIndex(
                    Index::PRIMARY | Index::UNIQUE | Index::INDEX
                    | Index::SPATIAL | Index::FULLTEXT
                );

            $changedToBlob = [];
            // While changing the Column Collation
            // First change to BLOB
            for ($i = 0; $i < $field_cnt; $i++) {
                if (isset($_POST['field_collation'][$i], $_POST['field_collation_orig'][$i])
                    && $_POST['field_collation'][$i] !== $_POST['field_collation_orig'][$i]
                    && ! in_array($_POST['field_orig'][$i], $columns_with_index)
                ) {
                    $secondary_query = 'ALTER TABLE ' . Util::backquote(
                        $this->table
                    )
                    . ' CHANGE ' . Util::backquote(
                        $_POST['field_orig'][$i]
                    )
                    . ' ' . Util::backquote($_POST['field_orig'][$i])
                    . ' BLOB';

                    if (isset($_POST['field_virtuality'][$i], $_POST['field_expression'][$i])) {
                        if ($_POST['field_virtuality'][$i]) {
                            $secondary_query .= ' AS (' . $_POST['field_expression'][$i] . ') '
                                . $_POST['field_virtuality'][$i];
                        }
                    }

                    $secondary_query .= ';';

                    $this->dbi->query($secondary_query);
                    $changedToBlob[$i] = true;
                } else {
                    $changedToBlob[$i] = false;
                }
            }

            // Then make the requested changes
            $result = $this->dbi->tryQuery($sql_query);

            if ($result !== false) {
                $changed_privileges = $this->adjustColumnPrivileges(
                    $adjust_privileges
                );

                if ($changed_privileges) {
                    $message = Message::success(
                        __(
                            'Table %1$s has been altered successfully. Privileges ' .
                            'have been adjusted.'
                        )
                    );
                } else {
                    $message = Message::success(
                        __('Table %1$s has been altered successfully.')
                    );
                }
                $message->addParam($this->table);

                $this->response->addHTML(
                    Generator::getMessage($message, $sql_query, 'success')
                );
            } else {
                // An error happened while inserting/updating a table definition

                // Save the Original Error
                $orig_error = $this->dbi->getError();
                $changes_revert = [];

                // Change back to Original Collation and data type
                for ($i = 0; $i < $field_cnt; $i++) {
                    if (! $changedToBlob[$i]) {
                        continue;
                    }

                    $changes_revert[] = 'CHANGE ' . Table::generateAlter(
                        Util::getValueByKey($_POST, "field_orig.${i}", ''),
                        $_POST['field_name'][$i],
                        $_POST['field_type_orig'][$i],
                        $_POST['field_length_orig'][$i],
                        $_POST['field_attribute_orig'][$i],
                        Util::getValueByKey($_POST, "field_collation_orig.${i}", ''),
                        Util::getValueByKey($_POST, "field_null_orig.${i}", 'NO'),
                        $_POST['field_default_type_orig'][$i],
                        $_POST['field_default_value_orig'][$i],
                        Util::getValueByKey($_POST, "field_extra_orig.${i}", false),
                        Util::getValueByKey($_POST, "field_comments_orig.${i}", ''),
                        Util::getValueByKey($_POST, "field_virtuality_orig.${i}", ''),
                        Util::getValueByKey($_POST, "field_expression_orig.${i}", ''),
                        Util::getValueByKey($_POST, "field_move_to_orig.${i}", '')
                    );
                }

                $revert_query = 'ALTER TABLE ' . Util::backquote($this->table)
                    . ' ';
                $revert_query .= implode(', ', $changes_revert) . '';
                $revert_query .= ';';

                // Column reverted back to original
                $this->dbi->query($revert_query);

                $this->response->setRequestStatus(false);
                $this->response->addJSON(
                    'message',
                    Message::rawError(
                        __('Query error') . ':<br>' . $orig_error
                    )
                );
                $regenerate = true;
            }
        }

        // update field names in relation
        if (isset($_POST['field_orig']) && is_array($_POST['field_orig'])) {
            foreach ($_POST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_POST['field_name'][$fieldindex] == $fieldcontent) {
                    continue;
                }

                $this->relation->renameField(
                    $this->db,
                    $this->table,
                    $fieldcontent,
                    $_POST['field_name'][$fieldindex]
                );
            }
        }

        // update mime types
        if (isset($_POST['field_mimetype'])
            && is_array($_POST['field_mimetype'])
            && $GLOBALS['cfg']['BrowseMIME']
        ) {
            foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                if (! isset($_POST['field_name'][$fieldindex])
                    || strlen($_POST['field_name'][$fieldindex]) <= 0
                ) {
                    continue;
                }

                $this->transformations->setMime(
                    $this->db,
                    $this->table,
                    $_POST['field_name'][$fieldindex],
                    $mimetype,
                    $_POST['field_transformation'][$fieldindex],
                    $_POST['field_transformation_options'][$fieldindex],
                    $_POST['field_input_transformation'][$fieldindex],
                    $_POST['field_input_transformation_options'][$fieldindex]
                );
            }
        }

        return $regenerate;
    }

    /**
     * Adjusts the Privileges for all the columns whose names have changed
     *
     * @param array $adjust_privileges assoc array of old col names mapped to new
     *                                 cols
     *
     * @return bool boolean whether at least one column privileges
     * adjusted
     */
    protected function adjustColumnPrivileges(array $adjust_privileges)
    {
        $changed = false;

        if (Util::getValueByKey($GLOBALS, 'col_priv', false)
            && Util::getValueByKey($GLOBALS, 'is_reload_priv', false)
        ) {
            $this->dbi->selectDb('mysql');

            // For Column specific privileges
            foreach ($adjust_privileges as $oldCol => $newCol) {
                $this->dbi->query(
                    sprintf(
                        'UPDATE %s SET Column_name = "%s"
                        WHERE Db = "%s"
                        AND Table_name = "%s"
                        AND Column_name = "%s";',
                        Util::backquote('columns_priv'),
                        $newCol,
                        $this->db,
                        $this->table,
                        $oldCol
                    )
                );

                // i.e. if atleast one column privileges adjusted
                $changed = true;
            }

            if ($changed) {
                // Finally FLUSH the new privileges
                $this->dbi->query('FLUSH PRIVILEGES;');
            }
        }

        return $changed;
    }

    /**
     * Verifies if some elements of a column have changed
     *
     * @param int $i column index in the request
     *
     * @return bool true if we need to generate ALTER TABLE
     */
    protected function columnNeedsAlterTable($i)
    {
        // these two fields are checkboxes so might not be part of the
        // request; therefore we define them to avoid notices below
        if (! isset($_POST['field_null'][$i])) {
            $_POST['field_null'][$i] = 'NO';
        }
        if (! isset($_POST['field_extra'][$i])) {
            $_POST['field_extra'][$i] = '';
        }

        // field_name does not follow the convention (corresponds to field_orig)
        if ($_POST['field_name'][$i] != $_POST['field_orig'][$i]) {
            return true;
        }

        $fields = [
            'field_attribute',
            'field_collation',
            'field_comments',
            'field_default_value',
            'field_default_type',
            'field_extra',
            'field_length',
            'field_null',
            'field_type',
        ];
        foreach ($fields as $field) {
            if ($_POST[$field][$i] != $_POST[$field . '_orig'][$i]) {
                return true;
            }
        }

        return ! empty($_POST['field_move_to'][$i]);
    }

    /**
     * Displays the table structure ('show table' works correct since 3.23.03)
     *
     * @param array       $cfgRelation               current relation parameters
     * @param array       $columns_with_unique_index Columns with unique index
     * @param Index|false $primary_index             primary index or false if
     *                                               no one exists
     * @param array       $fields                    Fields
     * @param array       $columns_with_index        Columns with index
     *
     * @return string
     */
    protected function displayStructure(
        array $cfgRelation,
        array $columns_with_unique_index,
        $primary_index,
        array $fields,
        array $columns_with_index,
        bool $isSystemSchema
    ) {
        global $route, $tbl_is_view, $tbl_storage_engine, $PMA_Theme;

        // prepare comments
        $comments_map = [];
        $mime_map = [];

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            $comments_map = $this->relation->getComments($this->db, $this->table);
            if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
                $mime_map = $this->transformations->getMime($this->db, $this->table, true);
            }
        }
        $centralColumns = new CentralColumns($this->dbi);
        $central_list = $centralColumns->getFromTable(
            $this->db,
            $this->table
        );

        /**
         * Displays Space usage and row statistics
         */
        // BEGIN - Calc Table Space
        // Get valid statistics whatever is the table type
        if ($GLOBALS['cfg']['ShowStats']) {
            //get table stats in HTML format
            $tablestats = $this->getTableStats($isSystemSchema);
            //returning the response in JSON format to be used by Ajax
            $this->response->addJSON('tableStat', $tablestats);
        }
        // END - Calc Table Space

        $hideStructureActions = false;
        if ($GLOBALS['cfg']['HideStructureActions'] === true) {
            $hideStructureActions = true;
        }

        // logic removed from Template
        $rownum = 0;
        $columns_list = [];
        $attributes = [];
        $displayed_fields = [];
        $row_comments = [];
        $extracted_columnspecs = [];
        $collations = [];
        foreach ($fields as &$field) {
            ++$rownum;
            $columns_list[] = $field['Field'];

            $extracted_columnspecs[$rownum] = Util::extractColumnSpec($field['Type']);
            $attributes[$rownum] = $extracted_columnspecs[$rownum]['attribute'];
            if (strpos($field['Extra'], 'on update CURRENT_TIMESTAMP') !== false) {
                $attributes[$rownum] = 'on update CURRENT_TIMESTAMP';
            }

            $displayed_fields[$rownum] = new stdClass();
            $displayed_fields[$rownum]->text = $field['Field'];
            $displayed_fields[$rownum]->icon = '';
            $row_comments[$rownum] = '';

            if (isset($comments_map[$field['Field']])) {
                $displayed_fields[$rownum]->comment = $comments_map[$field['Field']];
                $row_comments[$rownum] = $comments_map[$field['Field']];
            }

            if ($primary_index && $primary_index->hasColumn($field['Field'])) {
                $displayed_fields[$rownum]->icon .=
                    Generator::getImage('b_primary', __('Primary'));
            }

            if (in_array($field['Field'], $columns_with_index)) {
                $displayed_fields[$rownum]->icon .=
                    Generator::getImage('bd_primary', __('Index'));
            }

            $collation = Charsets::findCollationByName(
                $this->dbi,
                $GLOBALS['cfg']['Server']['DisableIS'],
                $field['Collation'] ?? ''
            );
            if ($collation === null) {
                continue;
            }

            $collations[$collation->getName()] = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        $engine = $this->tableObj->getStorageEngine();

        return $this->template->render('table/structure/display_structure', [
            'collations' => $collations,
            'is_foreign_key_supported' => Util::isForeignKeySupported($engine),
            'indexes' => Index::getFromTable($this->table, $this->db),
            'indexes_duplicates' => Index::findDuplicates($this->table, $this->db),
            'cfg_relation' => $this->relation->getRelationsParam(),
            'hide_structure_actions' => $hideStructureActions,
            'db' => $this->db,
            'table' => $this->table,
            'db_is_system_schema' => $isSystemSchema,
            'tbl_is_view' => $tbl_is_view,
            'mime_map' => $mime_map,
            'tbl_storage_engine' => $tbl_storage_engine,
            'primary' => $primary_index,
            'columns_with_unique_index' => $columns_with_unique_index,
            'columns_list' => $columns_list,
            'table_stats' => $tablestats ?? null,
            'fields' => $fields,
            'extracted_columnspecs' => $extracted_columnspecs,
            'columns_with_index' => $columns_with_index,
            'central_list' => $central_list,
            'comments_map' => $comments_map,
            'browse_mime' => $GLOBALS['cfg']['BrowseMIME'],
            'show_column_comments' => $GLOBALS['cfg']['ShowColumnComments'],
            'show_stats' => $GLOBALS['cfg']['ShowStats'],
            'relation_commwork' => $GLOBALS['cfgRelation']['commwork'],
            'relation_mimework' => $GLOBALS['cfgRelation']['mimework'],
            'central_columns_work' => $GLOBALS['cfgRelation']['centralcolumnswork'],
            'mysql_int_version' => $this->dbi->getVersion(),
            'is_mariadb' => $this->dbi->isMariaDB(),
            'theme_image_path' => $PMA_Theme->getImgPath(),
            'text_dir' => $GLOBALS['text_dir'],
            'is_active' => Tracker::isActive(),
            'have_partitioning' => Partition::havePartitioning(),
            'partitions' => Partition::getPartitions($this->db, $this->table),
            'partition_names' => Partition::getPartitionNames($this->db, $this->table),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'attributes' => $attributes,
            'displayed_fields' => $displayed_fields,
            'row_comments' => $row_comments,
            'route' => $route,
        ]);
    }

    /**
     * Get HTML snippet for display table statistics
     *
     * @return string
     */
    protected function getTableStats(bool $isSystemSchema)
    {
        global $showtable, $tbl_is_view;
        global $tbl_storage_engine, $table_info_num_rows, $tbl_collation;

        if (empty($showtable)) {
            $showtable = $this->dbi->getTable(
                $this->db,
                $this->table
            )->getStatusInfo(null, true);
        }

        if (is_string($showtable)) {
            $showtable = [];
        }

        if (empty($showtable['Data_length'])) {
            $showtable['Data_length'] = 0;
        }
        if (empty($showtable['Index_length'])) {
            $showtable['Index_length'] = 0;
        }

        $is_innodb = (isset($showtable['Type'])
            && $showtable['Type'] === 'InnoDB');

        $mergetable = $this->tableObj->isMerge();

        // this is to display for example 261.2 MiB instead of 268k KiB
        $max_digits = 3;
        $decimals = 1;
        [$data_size, $data_unit] = Util::formatByteDown(
            $showtable['Data_length'],
            $max_digits,
            $decimals
        );
        if ($mergetable === false) {
            [$index_size, $index_unit] = Util::formatByteDown(
                $showtable['Index_length'],
                $max_digits,
                $decimals
            );
        }
        if (isset($showtable['Data_free'])) {
            [$free_size, $free_unit] = Util::formatByteDown(
                $showtable['Data_free'],
                $max_digits,
                $decimals
            );
            [$effect_size, $effect_unit] = Util::formatByteDown(
                $showtable['Data_length']
                + $showtable['Index_length']
                - $showtable['Data_free'],
                $max_digits,
                $decimals
            );
        } else {
            [$effect_size, $effect_unit] = Util::formatByteDown(
                $showtable['Data_length']
                + $showtable['Index_length'],
                $max_digits,
                $decimals
            );
        }
        [$tot_size, $tot_unit] = Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'],
            $max_digits,
            $decimals
        );
        if ($table_info_num_rows > 0) {
            [$avg_size, $avg_unit] = Util::formatByteDown(
                ($showtable['Data_length']
                + $showtable['Index_length'])
                / $showtable['Rows'],
                6,
                1
            );
        } else {
            $avg_size = $avg_unit = '';
        }
        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodb_file_per_table = $innodbEnginePlugin->supportsFilePerTable();

        $engine = $this->dbi->getTable($this->db, $this->table)->getStorageEngine();

        $tableCollation = [];
        $collation = Charsets::findCollationByName(
            $this->dbi,
            $GLOBALS['cfg']['Server']['DisableIS'],
            $tbl_collation
        );
        if ($collation !== null) {
            $tableCollation = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        return $this->template->render('table/structure/display_table_stats', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'is_foreign_key_supported' => Util::isForeignKeySupported($engine),
            'cfg_relation' => $this->relation->getRelationsParam(),
            'showtable' => $showtable,
            'table_info_num_rows' => $table_info_num_rows,
            'tbl_is_view' => $tbl_is_view,
            'db_is_system_schema' => $isSystemSchema,
            'tbl_storage_engine' => $tbl_storage_engine,
            'table_collation' => $tableCollation,
            'is_innodb' => $is_innodb,
            'mergetable' => $mergetable,
            'avg_size' => $avg_size ?? null,
            'avg_unit' => $avg_unit ?? null,
            'data_size' => $data_size,
            'data_unit' => $data_unit,
            'index_size' => $index_size ?? null,
            'index_unit' => $index_unit ?? null,
            'innodb_file_per_table' => $innodb_file_per_table,
            'free_size' => $free_size ?? null,
            'free_unit' => $free_unit ?? null,
            'effect_size' => $effect_size,
            'effect_unit' => $effect_unit,
            'tot_size' => $tot_size,
            'tot_unit' => $tot_unit,
        ]);
    }

    /**
     * Gets table primary key
     *
     * @return string
     */
    protected function getKeyForTablePrimary()
    {
        $this->dbi->selectDb($this->db);
        $result = $this->dbi->query(
            'SHOW KEYS FROM ' . Util::backquote($this->table) . ';'
        );
        $primary = '';
        while ($row = $this->dbi->fetchAssoc($result)) {
            // Backups the list of primary keys
            if (! is_array($row) || $row['Key_name'] !== 'PRIMARY') {
                continue;
            }

            $primary .= $row['Column_name'] . ', ';
        }
        $this->dbi->freeResult($result);

        return $primary;
    }

    /**
     * Handles MySQL reserved words columns check.
     */
    public function reservedWordCheck(): void
    {
        if ($GLOBALS['cfg']['ReservedWordDisableWarning'] !== false) {
            $this->response->setRequestStatus(false);

            return;
        }

        $columns_names = $_POST['field_name'];
        $reserved_keywords_names = [];
        foreach ($columns_names as $column) {
            if (! Context::isKeyword(trim($column), true)) {
                continue;
            }

            $reserved_keywords_names[] = trim($column);
        }
        if (Context::isKeyword(trim($this->table), true)) {
            $reserved_keywords_names[] = trim($this->table);
        }
        if (count($reserved_keywords_names) === 0) {
            $this->response->setRequestStatus(false);
        }
        $this->response->addJSON(
            'message',
            sprintf(
                _ngettext(
                    'The name \'%s\' is a MySQL reserved keyword.',
                    'The names \'%s\' are MySQL reserved keywords.',
                    count($reserved_keywords_names)
                ),
                implode(',', $reserved_keywords_names)
            )
        );
    }
}
