<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\TableStructureController
 *
 * @package PhpMyAdmin\Controllers
 */
namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\CentralColumns;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\TableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Partition;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Sql;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles table structure logic
 *
 * @package PhpMyAdmin\Controllers
 */
class TableStructureController extends TableController
{
    /**
     * @var Table  The table object
     */
    protected $table_obj;
    /**
     * @var string  The URL query string
     */
    protected $_url_query;
    /**
     * @var bool DB is information_schema
     */
    protected $_db_is_system_schema;
    /**
     * @var bool Table is a view
     */
    protected $_tbl_is_view;
    /**
     * @var string Table storage engine
     */
    protected $_tbl_storage_engine;
    /**
     * @var int Number of rows
     */
    protected $_table_info_num_rows;
    /**
     * @var string Table collation
     */
    protected $_tbl_collation;
    /**
     * @var array Show table info
     */
    protected $_showtable;

    /**
     * @var CreateAddField
     */
    private $createAddField;

    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * TableStructureController constructor
     *
     * @param string $db                  DB name
     * @param string $table               Table name
     * @param string $type                Indicate the db_structure or tbl_structure
     * @param int    $num_tables          Number of tables
     * @param int    $pos                 Current position in the list
     * @param bool   $db_is_system_schema DB is information_schema
     * @param int    $total_num_tables    Number of tables
     * @param array  $tables              Tables in the DB
     * @param bool   $is_show_stats       Whether stats show or not
     * @param bool   $tbl_is_view         Table is a view
     * @param string $tbl_storage_engine  Table storage engine
     * @param int    $table_info_num_rows Number of rows
     * @param string $tbl_collation       Table collation
     * @param array  $showtable           Show table info
     */
    public function __construct(
        $response,
        $dbi,
        $db,
        $table,
        $type,
        $num_tables,
        $pos,
        $db_is_system_schema,
        $total_num_tables,
        $tables,
        $is_show_stats,
        $tbl_is_view,
        $tbl_storage_engine,
        $table_info_num_rows,
        $tbl_collation,
        $showtable
    ) {
        parent::__construct($response, $dbi, $db, $table);

        $this->_db_is_system_schema = $db_is_system_schema;
        $this->_url_query = Url::getCommonRaw(array('db' => $db, 'table' => $table));
        $this->_tbl_is_view = $tbl_is_view;
        $this->_tbl_storage_engine = $tbl_storage_engine;
        $this->_table_info_num_rows = $table_info_num_rows;
        $this->_tbl_collation = $tbl_collation;
        $this->_showtable = $showtable;
        $this->table_obj = $this->dbi->getTable($this->db, $this->table);

        $this->createAddField = new CreateAddField($dbi);
        $this->relation = new Relation();
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        PageSettings::showGroup('TableStructure');

        /**
         * Function implementations for this script
         */
        include_once 'libraries/check_user_privileges.inc.php';

        $this->response->getHeader()->getScripts()->addFiles(
            array(
                'tbl_structure.js',
                'indexes.js'
            )
        );

        /**
         * Handle column moving
         */
        if (isset($_POST['move_columns'])
            && is_array($_POST['move_columns'])
            && $this->response->isAjax()
        ) {
            $this->moveColumns();
            return;
        }

        /**
         * handle MySQL reserved words columns check
         */
        if (isset($_POST['reserved_word_check'])) {
            if ($GLOBALS['cfg']['ReservedWordDisableWarning'] === false) {
                $columns_names = $_POST['field_name'];
                $reserved_keywords_names = array();
                foreach ($columns_names as $column) {
                    if (Context::isKeyword(trim($column), true)) {
                        $reserved_keywords_names[] = trim($column);
                    }
                }
                if (Context::isKeyword(trim($this->table), true)) {
                    $reserved_keywords_names[] = trim($this->table);
                }
                if (count($reserved_keywords_names) == 0) {
                    $this->response->setRequestStatus(false);
                }
                $this->response->addJSON(
                    'message', sprintf(
                        _ngettext(
                            'The name \'%s\' is a MySQL reserved keyword.',
                            'The names \'%s\' are MySQL reserved keywords.',
                            count($reserved_keywords_names)
                        ),
                        implode(',', $reserved_keywords_names)
                    )
                );
            } else {
                $this->response->setRequestStatus(false);
            }
            return;
        }
        /**
         * A click on Change has been made for one column
         */
        if (isset($_GET['change_column'])) {
            $this->displayHtmlForColumnChange(null, 'tbl_structure.php');
            return;
        }

        /**
         * Adding or editing partitioning of the table
         */
        if (isset($_POST['edit_partitioning'])
            && ! isset($_POST['save_partitioning'])
        ) {
            $this->displayHtmlForPartitionChange();
            return;
        }

        /**
         * handle multiple field commands if required
         *
         * submit_mult_*_x comes from IE if <input type="img" ...> is used
         */
        $submit_mult = $this->getMultipleFieldCommandType();

        if (! empty($submit_mult)) {
            if (isset($_POST['selected_fld'])) {
                if ($submit_mult == 'browse') {
                    // browsing the table displaying only selected columns
                    $this->displayTableBrowseForSelectedColumns(
                        $GLOBALS['goto'], $GLOBALS['pmaThemeImage']
                    );
                } else {
                    // handle multiple field commands
                    // handle confirmation of deleting multiple columns
                    $action = 'tbl_structure.php';
                    $GLOBALS['selected'] = $_POST['selected_fld'];
                    list(
                        $what_ret, $query_type_ret, $is_unset_submit_mult,
                        $mult_btn_ret, $centralColsError
                        )
                            = $this->getDataForSubmitMult(
                                $submit_mult, $_POST['selected_fld'], $action
                            );
                    //update the existing variables
                    // todo: refactor mult_submits.inc.php such as
                    // below globals are not needed anymore
                    if (isset($what_ret)) {
                        $GLOBALS['what'] = $what_ret;
                        global $what;
                    }
                    if (isset($query_type_ret)) {
                        $GLOBALS['query_type'] = $query_type_ret;
                        global $query_type;
                    }
                    if ($is_unset_submit_mult) {
                        unset($submit_mult);
                    }
                    if (isset($mult_btn_ret)) {
                        $GLOBALS['mult_btn'] = $mult_btn_ret;
                        global $mult_btn;
                    }
                    include 'libraries/mult_submits.inc.php';
                    /**
                     * if $submit_mult == 'change', execution will have stopped
                     * at this point
                     */
                    if (empty($message)) {
                        $message = Message::success();
                    }
                    $this->response->addHTML(
                        Util::getMessage($message, $sql_query)
                    );
                }
            } else {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', __('No column selected.'));
            }
        }

        // display secondary level tabs if necessary
        $engine = $this->table_obj->getStorageEngine();
        $this->response->addHTML(
            Template::get('table/secondary_tabs')->render(
                array(
                    'url_params' => array(
                        'db' => $this->db,
                        'table' => $this->table
                    ),
                    'is_foreign_key_supported' => Util::isForeignKeySupported($engine),
                    'cfg_relation' => $this->relation->getRelationsParam(),
                )
            )
        );
        $this->response->addHTML('<div id="structure_content">');

        /**
         * Modifications have been submitted -> updates the table
         */
        if (isset($_POST['do_save_data'])) {
            $regenerate = $this->updateColumns();
            if ($regenerate) {
                // This happens when updating failed
                // @todo: do something appropriate
            } else {
                // continue to show the table's structure
                unset($_POST['selected']);
            }
        }

        /**
         * Modifications to the partitioning have been submitted -> updates the table
         */
        if (isset($_POST['save_partitioning'])) {
            $this->updatePartitioning();
        }

        /**
         * Adding indexes
         */
        if (isset($_POST['add_key'])
            || isset($_POST['partition_maintenance'])
        ) {
            //todo: set some variables for sql.php include, to be eliminated
            //after refactoring sql.php
            $db = $this->db;
            $table = $this->table;
            $sql_query = $GLOBALS['sql_query'];
            $cfg = $GLOBALS['cfg'];
            $pmaThemeImage = $GLOBALS['pmaThemeImage'];
            include 'sql.php';
            $GLOBALS['reload'] = true;
        }

        /**
         * Gets the relation settings
         */
        $cfgRelation = $this->relation->getRelationsParam();

        /**
         * Runs common work
         */
        // set db, table references, for require_once that follows
        // got to be eliminated in long run
        $db = &$this->db;
        $table = &$this->table;
        $url_params = array();
        include_once 'libraries/tbl_common.inc.php';
        $this->_db_is_system_schema = $db_is_system_schema;
        $this->_url_query = Url::getCommonRaw(array(
            'db' => $db,
            'table' => $table,
            'goto' => 'tbl_structure.php',
            'back' => 'tbl_structure.php',
        ));
        /* The url_params array is initialized in above include */
        $url_params['goto'] = 'tbl_structure.php';
        $url_params['back'] = 'tbl_structure.php';

        // 2. Gets table keys and retains them
        // @todo should be: $server->db($db)->table($table)->primary()
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

        // 3. Get fields
        $fields = (array)$this->dbi->getColumns(
            $this->db, $this->table, null, true
        );

        //display table structure
        $this->response->addHTML(
            $this->displayStructure(
                $cfgRelation, $columns_with_unique_index, $url_params,
                $primary, $fields, $columns_with_index
            )
        );

        $this->response->addHTML('</div>');
    }

    /**
     * Moves columns in the table's structure based on $_REQUEST
     *
     * @return void
     */
    protected function moveColumns()
    {
        $this->dbi->selectDb($this->db);

        /*
         * load the definitions for all columns
         */
        $columns = $this->dbi->getColumnsFull($this->db, $this->table);
        $column_names = array_keys($columns);
        $changes = array();

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
                && $data['Extra'] == 'on update CURRENT_TIMESTAMP'
            ) {
                $extracted_columnspec['attribute'] = $data['Extra'];
                unset($data['Extra']);
            }
            $current_timestamp = ($data['Type'] == 'timestamp'
                    || $data['Type'] == 'datetime')
                && ($data['Default'] == 'CURRENT_TIMESTAMP'
                    || $data['Default'] == 'current_timestamp()');

            if ($data['Null'] === 'YES' && $data['Default'] === null) {
                $default_type = 'NULL';
            } elseif ($current_timestamp) {
                $default_type = 'CURRENT_TIMESTAMP';
            } elseif ($data['Default'] === null) {
                $default_type = 'NONE';
            } else {
                $default_type = 'USER_DEFINED';
            }

            $virtual = array(
                'VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'
            );
            $data['Virtuality'] = '';
            $data['Expression'] = '';
            if (isset($data['Extra']) && in_array($data['Extra'], $virtual)) {
                $data['Virtuality'] = str_replace(' GENERATED', '', $data['Extra']);
                $expressions = $this->table_obj->getColumnGenerationExpression($column);
                $data['Expression'] = $expressions[$column];
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                $column,
                $column,
                mb_strtoupper($extracted_columnspec['type']),
                $extracted_columnspec['spec_in_brackets'],
                $extracted_columnspec['attribute'],
                isset($data['Collation']) ? $data['Collation'] : '',
                $data['Null'] === 'YES' ? 'NULL' : 'NOT NULL',
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
                if ($column_names[$j] == $column) {
                    unset($column_names[$j]);
                }
            }
            // insert moved column
            array_splice($column_names, $i, 0, $column);
        }
        if (empty($changes)) { // should never happen
            $this->response->setRequestStatus(false);
            return;
        }
        // move columns
        $this->dbi->tryQuery(
            sprintf(
                'ALTER TABLE %s %s',
                Util::backquote($this->table),
                implode(', ', $changes)
            )
        );
        $tmp_error = $this->dbi->getError();
        if ($tmp_error) {
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

    /**
     * Displays HTML for changing one or more columns
     *
     * @param array  $selected the selected columns
     * @param string $action   target script to call
     *
     * @return boolean $regenerate true if error occurred
     *
     */
    protected function displayHtmlForColumnChange($selected, $action)
    {
        // $selected comes from mult_submits.inc.php
        if (empty($selected)) {
            $selected[] = $_REQUEST['field'];
            $selected_cnt = 1;
        } else { // from a multiple submit
            $selected_cnt = count($selected);
        }

        /**
         * @todo optimize in case of multiple fields to modify
         */
        $fields_meta = array();
        for ($i = 0; $i < $selected_cnt; $i++) {
            $value = $this->dbi->getColumns(
                $this->db, $this->table, $selected[$i], true
            );
            if (count($value) == 0) {
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
        // set these globals because tbl_columns_definition_form.inc.php
        // verifies them
        // @todo: refactor tbl_columns_definition_form.inc.php so that it uses
        // protected function params
        $GLOBALS['action'] = $action;
        $GLOBALS['num_fields'] = $num_fields;

        /**
         * Form for changing properties.
         */
        include_once 'libraries/check_user_privileges.inc.php';
        include 'libraries/tbl_columns_definition_form.inc.php';
    }

    /**
     * Displays HTML for partition change
     *
     * @return string HTML for partition change
     */
    protected function displayHtmlForPartitionChange()
    {
        $partitionDetails = null;
        if (! isset($_POST['partition_by'])) {
            $partitionDetails = $this->_extractPartitionDetails();
        }

        include 'libraries/tbl_partition_definition.inc.php';
        $this->response->addHTML(
            Template::get('table/structure/partition_definition_form')
                ->render(
                    array(
                        'db' => $this->db,
                        'table' => $this->table,
                        'partition_details' => $partitionDetails,
                    )
                )
        );
    }

    /**
     * Extracts partition details from CREATE TABLE statement
     *
     * @return array[] array of partition details
     */
    private function _extractPartitionDetails()
    {
        $createTable = (new Table($this->table, $this->db))->showCreate();
        if (! $createTable) {
            return null;
        }

        $parser = new Parser($createTable);
        /**
         * @var $stmt PhpMyAdmin\SqlParser\Statements\CreateStatement
         */
        $stmt = $parser->statements[0];

        $partitionDetails = array();

        $partitionDetails['partition_by'] = '';
        $partitionDetails['partition_expr'] = '';
        $partitionDetails['partition_count'] = '';

        if (! empty($stmt->partitionBy)) {
            $openPos = strpos($stmt->partitionBy, "(");
            $closePos = strrpos($stmt->partitionBy, ")");

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
            $openPos = strpos($stmt->subpartitionBy, "(");
            $closePos = strrpos($stmt->subpartitionBy, ")");

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
                && ($partitionDetails['partition_by'] == 'RANGE'
                || $partitionDetails['partition_by'] == 'RANGE COLUMNS'
                || $partitionDetails['partition_by'] == 'LIST'
                || $partitionDetails['partition_by'] == 'LIST COLUMNS');

        // Values are specified only for LIST and RANGE type partitions
        $partitionDetails['value_enabled'] = isset($partitionDetails['partition_by'])
            && ($partitionDetails['partition_by'] == 'RANGE'
            || $partitionDetails['partition_by'] == 'RANGE COLUMNS'
            || $partitionDetails['partition_by'] == 'LIST'
            || $partitionDetails['partition_by'] == 'LIST COLUMNS');

        $partitionDetails['partitions'] = array();

        for ($i = 0; $i < intval($partitionDetails['partition_count']); $i++) {

            if (! isset($stmt->partitions[$i])) {
                $partitionDetails['partitions'][$i] = array(
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
                );
            } else {
                $p = $stmt->partitions[$i];
                $type = $p->type;
                $expr = trim($p->expr, '()');
                if ($expr == 'MAXVALUE') {
                    $type .= ' MAXVALUE';
                    $expr = '';
                }
                $partitionDetails['partitions'][$i] = array(
                    'name' => $p->name,
                    'value_type' => $type,
                    'value' => $expr,
                    'engine' => $p->options->has('ENGINE', true),
                    'comment' => trim($p->options->has('COMMENT', true), "'"),
                    'data_directory' => trim($p->options->has('DATA DIRECTORY', true), "'"),
                    'index_directory' => trim($p->options->has('INDEX_DIRECTORY', true), "'"),
                    'max_rows' => $p->options->has('MAX_ROWS', true),
                    'min_rows' => $p->options->has('MIN_ROWS', true),
                    'tablespace' => $p->options->has('TABLESPACE', true),
                    'node_group' => $p->options->has('NODEGROUP', true),
                );
            }

            $partition =& $partitionDetails['partitions'][$i];
            $partition['prefix'] = 'partitions[' . $i . ']';

            if ($partitionDetails['subpartition_count'] > 1) {
                $partition['subpartition_count'] = $partitionDetails['subpartition_count'];
                $partition['subpartitions'] = array();

                for ($j = 0; $j < intval($partitionDetails['subpartition_count']); $j++) {
                    if (! isset($stmt->partitions[$i]->subpartitions[$j])) {
                        $partition['subpartitions'][$j] = array(
                            'name' => $partition['name'] . '_s' . $j,
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        );
                    } else {
                        $sp = $stmt->partitions[$i]->subpartitions[$j];
                        $partition['subpartitions'][$j] = array(
                            'name' => $sp->name,
                            'engine' => $sp->options->has('ENGINE', true),
                            'comment' => trim($sp->options->has('COMMENT', true), "'"),
                            'data_directory' => trim($sp->options->has('DATA DIRECTORY', true), "'"),
                            'index_directory' => trim($sp->options->has('INDEX_DIRECTORY', true), "'"),
                            'max_rows' => $sp->options->has('MAX_ROWS', true),
                            'min_rows' => $sp->options->has('MIN_ROWS', true),
                            'tablespace' => $sp->options->has('TABLESPACE', true),
                            'node_group' => $sp->options->has('NODEGROUP', true),
                        );
                    }

                    $subpartition =& $partition['subpartitions'][$j];
                    $subpartition['prefix'] = 'partitions[' . $i . ']'
                        . '[subpartitions][' . $j . ']';
                }
            }
        }

        return $partitionDetails;
    }

    /**
     * Update the table's partitioning based on $_REQUEST
     *
     * @return void
     */
    protected function updatePartitioning()
    {
        $sql_query = "ALTER TABLE " . Util::backquote($this->table) . " "
            . $this->createAddField->getPartitionsDefinition();

        // Execute alter query
        $result = $this->dbi->tryQuery($sql_query);

        if ($result !== false) {
            $message = Message::success(
                __('Table %1$s has been altered successfully.')
            );
            $message->addParam($this->table);
            $this->response->addHTML(
                Util::getMessage($message, $sql_query, 'success')
            );
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                Message::rawError(
                    __('Query error') . ':<br />' . $this->dbi->getError()
                )
            );
        }
    }

    /**
     * Function to get the type of command for multiple field handling
     *
     * @return string
     */
    protected function getMultipleFieldCommandType()
    {
        $types = array(
            'change', 'drop', 'primary',
            'index', 'unique', 'spatial',
            'fulltext', 'browse'
        );

        foreach ($types as $type) {
            if (isset($_POST['submit_mult_' . $type . '_x'])) {
                return $type;
            }
        }

        if (isset($_POST['submit_mult'])) {
            return $_POST['submit_mult'];
        } elseif (isset($_POST['mult_btn'])
            && $_POST['mult_btn'] == __('Yes')
        ) {
            if (isset($_POST['selected'])) {
                $_POST['selected_fld'] = $_POST['selected'];
            }
            return 'row_delete';
        }

        return null;
    }

    /**
     * Function to display table browse for selected columns
     *
     * @param string $goto          goto page url
     * @param string $pmaThemeImage URI of the pma theme image
     *
     * @return void
     */
    protected function displayTableBrowseForSelectedColumns($goto, $pmaThemeImage)
    {
        $GLOBALS['active_page'] = 'sql.php';
        $fields = array();
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
        list(
            $analyzed_sql_results,
            $db,
        ) = ParseAnalyze::sqlQuery($sql_query, $db);
        // @todo: possibly refactor
        extract($analyzed_sql_results);

        $sql = new Sql();
        $this->response->addHTML(
            $sql->executeQueryAndGetQueryResponse(
                isset($analyzed_sql_results) ? $analyzed_sql_results : '',
                false, // is_gotofile
                $this->db, // db
                $this->table, // table
                null, // find_real_end
                null, // sql_query_for_bookmark
                null, // extra_data
                null, // message_to_show
                null, // message
                null, // sql_data
                $goto, // goto
                $pmaThemeImage, // pmaThemeImage
                null, // disp_query
                null, // disp_message
                null, // query_type
                $sql_query, // sql_query
                null, // selectedTables
                null // complete_query
            )
        );
    }

    /**
     * Update the table's structure based on $_REQUEST
     *
     * @return boolean $regenerate              true if error occurred
     *
     */
    protected function updateColumns()
    {
        $err_url = 'tbl_structure.php' . Url::getCommon(
            array(
                'db' => $this->db, 'table' => $this->table
            )
        );
        $regenerate = false;
        $field_cnt = count($_POST['field_name']);
        $changes = array();
        $adjust_privileges = array();

        for ($i = 0; $i < $field_cnt; $i++) {
            if (!$this->columnNeedsAlterTable($i)) {
                continue;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                Util::getValueByKey($_POST, "field_orig.${i}", ''),
                $_POST['field_name'][$i],
                $_POST['field_type'][$i],
                $_POST['field_length'][$i],
                $_POST['field_attribute'][$i],
                Util::getValueByKey($_POST, "field_collation.${i}", ''),
                Util::getValueByKey($_POST, "field_null.${i}", 'NOT NULL'),
                $_POST['field_default_type'][$i],
                $_POST['field_default_value'][$i],
                Util::getValueByKey($_POST, "field_extra.${i}", false),
                Util::getValueByKey($_POST, "field_comments.${i}", ''),
                Util::getValueByKey($_POST, "field_virtuality.${i}", ''),
                Util::getValueByKey($_POST, "field_expression.${i}", ''),
                Util::getValueByKey($_POST, "field_move_to.${i}", '')
            );

            // find the remembered sort expression
            $sorted_col = $this->table_obj->getUiProp(
                Table::PROP_SORTED_COLUMN
            );
            // if the old column name is part of the remembered sort expression
            if (mb_strpos(
                $sorted_col,
                Util::backquote($_POST['field_orig'][$i])
            ) !== false) {
                // delete the whole remembered sort expression
                $this->table_obj->removeUiProp(Table::PROP_SORTED_COLUMN);
            }

            if (isset($_POST['field_adjust_privileges'][$i])
                && ! empty($_POST['field_adjust_privileges'][$i])
                && $_POST['field_orig'][$i] != $_POST['field_name'][$i]
            ) {
                $adjust_privileges[$_POST['field_orig'][$i]]
                    = $_POST['field_name'][$i];
            }
        } // end for

        if (count($changes) > 0 || isset($_POST['preview_sql'])) {
            // Builds the primary keys statements and updates the table
            $key_query = '';
            /**
             * this is a little bit more complex
             *
             * @todo if someone selects A_I when altering a column we need to check:
             *  - no other column with A_I
             *  - the column has an index, if not create one
             *
             */

            // To allow replication, we first select the db to use
            // and then run queries on this db.
            if (!$this->dbi->selectDb($this->db)) {
                Util::mysqlDie(
                    $this->dbi->getError(),
                    'USE ' . Util::backquote($this->db) . ';',
                    false,
                    $err_url
                );
            }
            $sql_query = 'ALTER TABLE ' . Util::backquote($this->table) . ' ';
            $sql_query .= implode(', ', $changes) . $key_query;
            $sql_query .= ';';

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL(count($changes) > 0 ? $sql_query : '');
            }

            $columns_with_index = $this->dbi
                ->getTable($this->db, $this->table)
                ->getColumnsWithIndex(
                    Index::PRIMARY | Index::UNIQUE | Index::INDEX
                    | Index::SPATIAL | Index::FULLTEXT
                );

            $changedToBlob = array();
            // While changing the Column Collation
            // First change to BLOB
            for ($i = 0; $i < $field_cnt; $i++ ) {
                if (isset($_POST['field_collation'][$i])
                    && isset($_POST['field_collation_orig'][$i])
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

                    if (isset($_POST['field_virtuality'][$i])
                        && isset($_POST['field_expression'][$i])) {
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
                    Util::getMessage($message, $sql_query, 'success')
                );
            } else {
                // An error happened while inserting/updating a table definition

                // Save the Original Error
                $orig_error = $this->dbi->getError();
                $changes_revert = array();

                // Change back to Original Collation and data type
                for ($i = 0; $i < $field_cnt; $i++) {
                    if ($changedToBlob[$i]) {
                        $changes_revert[] = 'CHANGE ' . Table::generateAlter(
                            Util::getValueByKey($_POST, "field_orig.${i}", ''),
                            $_POST['field_name'][$i],
                            $_POST['field_type_orig'][$i],
                            $_POST['field_length_orig'][$i],
                            $_POST['field_attribute_orig'][$i],
                            Util::getValueByKey($_POST, "field_collation_orig.${i}", ''),
                            Util::getValueByKey($_POST, "field_null_orig.${i}", 'NOT NULL'),
                            $_POST['field_default_type_orig'][$i],
                            $_POST['field_default_value_orig'][$i],
                            Util::getValueByKey($_POST, "field_extra_orig.${i}", false),
                            Util::getValueByKey($_POST, "field_comments_orig.${i}", ''),
                            Util::getValueByKey($_POST, "field_virtuality_orig.${i}", ''),
                            Util::getValueByKey($_POST, "field_expression_orig.${i}", ''),
                            Util::getValueByKey($_POST, "field_move_to_orig.${i}", '')
                        );
                    }
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
                        __('Query error') . ':<br />' . $orig_error
                    )
                );
                $regenerate = true;
            }
        }

        // update field names in relation
        if (isset($_POST['field_orig']) && is_array($_POST['field_orig'])) {
            foreach ($_POST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_POST['field_name'][$fieldindex] != $fieldcontent) {
                    $this->relation->renameField(
                        $this->db, $this->table, $fieldcontent,
                        $_POST['field_name'][$fieldindex]
                    );
                }
            }
        }

        // update mime types
        if (isset($_POST['field_mimetype'])
            && is_array($_POST['field_mimetype'])
            && $GLOBALS['cfg']['BrowseMIME']
        ) {
            foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_POST['field_name'][$fieldindex])
                    && strlen($_POST['field_name'][$fieldindex]) > 0
                ) {
                    Transformations::setMIME(
                        $this->db, $this->table,
                        $_POST['field_name'][$fieldindex],
                        $mimetype,
                        $_POST['field_transformation'][$fieldindex],
                        $_POST['field_transformation_options'][$fieldindex],
                        $_POST['field_input_transformation'][$fieldindex],
                        $_POST['field_input_transformation_options'][$fieldindex]
                    );
                }
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
     * @return boolean $changed  boolean whether at least one column privileges
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
                        $newCol, $this->db, $this->table, $oldCol
                    )
                );

                // i.e. if atleast one column privileges adjusted
                $changed = true;
            }

            if ($changed) {
                // Finally FLUSH the new privileges
                $this->dbi->query("FLUSH PRIVILEGES;");
            }
        }

        return $changed;
    }

    /**
     * Verifies if some elements of a column have changed
     *
     * @param integer $i column index in the request
     *
     * @return boolean $alterTableNeeded true if we need to generate ALTER TABLE
     *
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

        $fields = array(
            'field_attribute', 'field_collation', 'field_comments',
            'field_default_value', 'field_default_type', 'field_extra',
            'field_length', 'field_null', 'field_type'
        );
        foreach ($fields as $field) {
            if ($_POST[$field][$i] != $_POST[$field . '_orig'][$i]) {
                return true;
            }
        }
        return !empty($_POST['field_move_to'][$i]);
    }

    /**
     * Displays the table structure ('show table' works correct since 3.23.03)
     *
     * @param array       $cfgRelation               current relation parameters
     * @param array       $columns_with_unique_index Columns with unique index
     * @param mixed       $url_params                Contains an associative
     *                                               array with url params
     * @param Index|false $primary_index             primary index or false if
     *                                               no one exists
     * @param array       $fields                    Fields
     * @param array       $columns_with_index        Columns with index
     *
     * @return string
     */
    protected function displayStructure(
        array $cfgRelation, array $columns_with_unique_index, $url_params,
        $primary_index, array $fields, array $columns_with_index
    ) {
        // prepare comments
        $comments_map = array();
        $mime_map = array();

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            $comments_map = $this->relation->getComments($this->db, $this->table);
            if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
                $mime_map = Transformations::getMIME($this->db, $this->table, true);
            }
        }
        $centralColumns = new CentralColumns($GLOBALS['dbi']);
        $central_list = $centralColumns->getFromTable(
            $this->db,
            $this->table
        );
        $columns_list = array();

        $titles = array(
            'Change' => Util::getIcon('b_edit', __('Change')),
            'Drop' => Util::getIcon('b_drop', __('Drop')),
            'NoDrop' => Util::getIcon('b_drop', __('Drop')),
            'Primary' => Util::getIcon('b_primary', __('Primary')),
            'Index' => Util::getIcon('b_index', __('Index')),
            'Unique' => Util::getIcon('b_unique', __('Unique')),
            'Spatial' => Util::getIcon('b_spatial', __('Spatial')),
            'IdxFulltext' => Util::getIcon('b_ftext', __('Fulltext')),
            'NoPrimary' => Util::getIcon('bd_primary', __('Primary')),
            'NoIndex' => Util::getIcon('bd_index', __('Index')),
            'NoUnique' => Util::getIcon('bd_unique', __('Unique')),
            'NoSpatial' => Util::getIcon('bd_spatial', __('Spatial')),
            'NoIdxFulltext' => Util::getIcon('bd_ftext', __('Fulltext')),
            'DistinctValues' => Util::getIcon('b_browse', __('Distinct values')),
        );

        $edit_view_url = '';
        if ($this->_tbl_is_view && ! $this->_db_is_system_schema) {
            $edit_view_url = Url::getCommon(
                array('db' => $this->db, 'table' => $this->table)
            );
        }

        /**
         * Displays Space usage and row statistics
         */
        // BEGIN - Calc Table Space
        // Get valid statistics whatever is the table type
        if ($GLOBALS['cfg']['ShowStats']) {
            //get table stats in HTML format
            $tablestats = $this->getTableStats();
            //returning the response in JSON format to be used by Ajax
            $this->response->addJSON('tableStat', $tablestats);
        }
        // END - Calc Table Space

        $hideStructureActions = false;
        if ($GLOBALS['cfg']['HideStructureActions'] === true) {
            $hideStructureActions = true;
        }

        return Template::get('table/structure/display_structure')->render(
            array(
                'hide_structure_actions' => $hideStructureActions,
                'db' => $this->db,
                'table' => $this->table,
                'db_is_system_schema' => $this->_db_is_system_schema,
                'tbl_is_view' => $this->_tbl_is_view,
                'mime_map' => $mime_map,
                'url_query' => $this->_url_query,
                'titles' => $titles,
                'tbl_storage_engine' => $this->_tbl_storage_engine,
                'primary' => $primary_index,
                'columns_with_unique_index' => $columns_with_unique_index,
                'edit_view_url' => $edit_view_url,
                'columns_list' => $columns_list,
                'table_stats' => isset($tablestats) ? $tablestats : null,
                'fields' => $fields,
                'columns_with_index' => $columns_with_index,
                'central_list' => $central_list,
                'comments_map' => $comments_map,
                'browse_mime' => $GLOBALS['cfg']['BrowseMIME'],
                'show_column_comments' => $GLOBALS['cfg']['ShowColumnComments'],
                'show_stats' => $GLOBALS['cfg']['ShowStats'],
                'relation_commwork' => $GLOBALS['cfgRelation']['commwork'],
                'relation_mimework' => $GLOBALS['cfgRelation']['mimework'],
                'central_columns_work' => $GLOBALS['cfgRelation']['centralcolumnswork'],
                'mysql_int_version' => $GLOBALS['dbi']->getVersion(),
                'is_mariadb' => $GLOBALS['dbi']->isMariaDB(),
                'pma_theme_image' => $GLOBALS['pmaThemeImage'],
                'text_dir' => $GLOBALS['text_dir'],
                'is_active' => Tracker::isActive(),
                'have_partitioning' => Partition::havePartitioning(),
                'partition_names' => Partition::getPartitionNames($this->db, $this->table),
            )
        );
    }

    /**
     * Get HTML snippet for display table statistics
     *
     * @return string $html_output
     */
    protected function getTableStats()
    {
        if (empty($this->_showtable)) {
            $this->_showtable = $this->dbi->getTable(
                $this->db, $this->table
            )->getStatusInfo(null, true);
        }

        if (empty($this->_showtable['Data_length'])) {
            $this->_showtable['Data_length'] = 0;
        }
        if (empty($this->_showtable['Index_length'])) {
            $this->_showtable['Index_length'] = 0;
        }

        $is_innodb = (isset($this->_showtable['Type'])
            && $this->_showtable['Type'] == 'InnoDB');

        $mergetable = $this->table_obj->isMerge();

        // this is to display for example 261.2 MiB instead of 268k KiB
        $max_digits = 3;
        $decimals = 1;
        list($data_size, $data_unit) = Util::formatByteDown(
            $this->_showtable['Data_length'], $max_digits, $decimals
        );
        if ($mergetable == false) {
            list($index_size, $index_unit) = Util::formatByteDown(
                $this->_showtable['Index_length'], $max_digits, $decimals
            );
        }
        // InnoDB returns a huge value in Data_free, do not use it
        if (! $is_innodb && isset($this->_showtable['Data_free'])
            && $this->_showtable['Data_free'] > 0
        ) {
            list($free_size, $free_unit) = Util::formatByteDown(
                $this->_showtable['Data_free'], $max_digits, $decimals
            );
            list($effect_size, $effect_unit) = Util::formatByteDown(
                $this->_showtable['Data_length']
                + $this->_showtable['Index_length']
                - $this->_showtable['Data_free'],
                $max_digits, $decimals
            );
        } else {
            list($effect_size, $effect_unit) = Util::formatByteDown(
                $this->_showtable['Data_length']
                + $this->_showtable['Index_length'],
                $max_digits, $decimals
            );
        }
        list($tot_size, $tot_unit) = Util::formatByteDown(
            $this->_showtable['Data_length'] + $this->_showtable['Index_length'],
            $max_digits, $decimals
        );
        if ($this->_table_info_num_rows > 0) {
            list($avg_size, $avg_unit) = Util::formatByteDown(
                ($this->_showtable['Data_length']
                + $this->_showtable['Index_length'])
                / $this->_showtable['Rows'],
                6,
                1
            );
        } else {
            $avg_size = $avg_unit = '';
        }

        return Template::get('table/structure/display_table_stats')->render(
            array(
                'showtable' => $this->_showtable,
                'table_info_num_rows' => $this->_table_info_num_rows,
                'tbl_is_view' => $this->_tbl_is_view,
                'db_is_system_schema' => $this->_db_is_system_schema,
                'tbl_storage_engine' => $this->_tbl_storage_engine,
                'url_query' => $this->_url_query,
                'tbl_collation' => $this->_tbl_collation,
                'is_innodb' => $is_innodb,
                'mergetable' => $mergetable,
                'avg_size' => isset($avg_size) ? $avg_size : null,
                'avg_unit' => isset($avg_unit) ? $avg_unit : null,
                'data_size' => $data_size,
                'data_unit' => $data_unit,
                'index_size' => isset($index_size) ? $index_size : null,
                'index_unit' => isset($index_unit) ? $index_unit : null,
                'free_size' => isset($free_size) ? $free_size : null,
                'free_unit' => isset($free_unit) ? $free_unit : null,
                'effect_size' => $effect_size,
                'effect_unit' => $effect_unit,
                'tot_size' => $tot_size,
                'tot_unit' => $tot_unit,
                'table' => $GLOBALS['table']
            )
        );
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
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
            }
        } // end while
        $this->dbi->freeResult($result);

        return $primary;
    }

    /**
     * Get List of information for Submit Mult
     *
     * @param string $submit_mult mult_submit type
     * @param array  $selected    the selected columns
     * @param string $action      action type
     *
     * @return array
     */
    protected function getDataForSubmitMult($submit_mult, $selected, $action)
    {
        $centralColumns = new CentralColumns($GLOBALS['dbi']);
        $what = null;
        $query_type = null;
        $is_unset_submit_mult = false;
        $mult_btn = null;
        $centralColsError = null;
        switch ($submit_mult) {
        case 'drop':
            $what     = 'drop_fld';
            break;
        case 'primary':
            // Gets table primary key
            $primary = $this->getKeyForTablePrimary();
            if (empty($primary)) {
                // no primary key, so we can safely create new
                $is_unset_submit_mult = true;
                $query_type = 'primary_fld';
                $mult_btn   = __('Yes');
            } else {
                // primary key exists, so lets as user
                $what = 'primary_fld';
            }
            break;
        case 'index':
            $is_unset_submit_mult = true;
            $query_type = 'index_fld';
            $mult_btn   = __('Yes');
            break;
        case 'unique':
            $is_unset_submit_mult = true;
            $query_type = 'unique_fld';
            $mult_btn   = __('Yes');
            break;
        case 'spatial':
            $is_unset_submit_mult = true;
            $query_type = 'spatial_fld';
            $mult_btn   = __('Yes');
            break;
        case 'ftext':
            $is_unset_submit_mult = true;
            $query_type = 'fulltext_fld';
            $mult_btn   = __('Yes');
            break;
        case 'add_to_central_columns':
            $centralColsError = $centralColumns->syncUniqueColumns(
                $selected,
                false
            );
            break;
        case 'remove_from_central_columns':
            $centralColsError = $centralColumns->deleteColumnsFromList(
                $selected,
                false
            );
            break;
        case 'change':
            $this->displayHtmlForColumnChange($selected, $action);
            // execution stops here but PhpMyAdmin\Response correctly finishes
            // the rendering
            exit;
        case 'browse':
            // this should already be handled by tbl_structure.php
        }

        return array(
            $what, $query_type, $is_unset_submit_mult, $mult_btn,
            $centralColsError
        );
    }
}
