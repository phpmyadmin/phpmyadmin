<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\CentralColumns class
 *
 * @package PhpMyAdmin
 */
class CentralColumns
{
    /**
     * DatabaseInterface instance
     *
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Current user
     *
     * @var string
     */
    private $user;

    /**
     * Number of rows displayed when browsing a result set
     *
     * @var int
     */
    private $maxRows;

    /**
     * Which editor should be used for CHAR/VARCHAR fields
     *
     * @var string
     */
    private $charEditing;

    /**
     * Disable use of INFORMATION_SCHEMA
     *
     * @var boolean
     */
    private $disableIs;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Constructor
     *
     * @param DatabaseInterface $dbi DatabaseInterface instance
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;

        $this->user = $GLOBALS['cfg']['Server']['user'];
        $this->maxRows = (int) $GLOBALS['cfg']['MaxRows'];
        $this->charEditing = $GLOBALS['cfg']['CharEditing'];
        $this->disableIs = (bool) $GLOBALS['cfg']['Server']['DisableIS'];

        $this->relation = new Relation();
    }

    /**
     * Defines the central_columns parameters for the current user
     *
     * @return array the central_columns parameters for the current user
     * @access public
     */
    public function getParams()
    {
        static $cfgCentralColumns = null;

        if (null !== $cfgCentralColumns) {
            return $cfgCentralColumns;
        }

        $cfgRelation = $this->relation->getRelationsParam();

        if ($cfgRelation['centralcolumnswork']) {
            $cfgCentralColumns = array(
                'user'  => $this->user,
                'db'    => $cfgRelation['db'],
                'table' => $cfgRelation['central_columns'],
            );
        } else {
            $cfgCentralColumns = false;
        }

        return $cfgCentralColumns;
    }

    /**
     * get $num columns of given database from central columns list
     * starting at offset $from
     *
     * @param string $db   selected database
     * @param int    $from starting offset of first result
     * @param int    $num  maximum number of results to return
     *
     * @return array list of $num columns present in central columns list
     * starting at offset $from for the given database
     */
    public function getColumnsList($db, $from = 0, $num = 25)
    {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return array();
        }
        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, DatabaseInterface::CONNECT_CONTROL);
        $central_list_table = $cfgCentralColumns['table'];
        //get current values of $db from central column list
        if ($num == 0) {
            $query = 'SELECT * FROM ' . Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\';';
        } else {
            $query = 'SELECT * FROM ' . Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' '
                . 'LIMIT ' . $from . ', ' . $num . ';';
        }
        $has_list = (array) $this->dbi->fetchResult(
            $query, null, null, DatabaseInterface::CONNECT_CONTROL
        );
        $this->handleColumnExtra($has_list);
        return $has_list;
    }

    /**
     * Get the number of columns present in central list for given db
     *
     * @param string $db current database
     *
     * @return int number of columns in central list of columns for $db
     */
    public function getCount($db)
    {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return 0;
        }
        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, DatabaseInterface::CONNECT_CONTROL);
        $central_list_table = $cfgCentralColumns['table'];
        $query = 'SELECT count(db_name) FROM ' .
            Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\';';
        $res = $this->dbi->fetchResult(
            $query, null, null, DatabaseInterface::CONNECT_CONTROL
        );
        if (isset($res[0])) {
            return $res[0];
        }

        return 0;
    }

    /**
     * return the existing columns in central list among the given list of columns
     *
     * @param string  $db        the selected database
     * @param string  $cols      comma separated list of given columns
     * @param boolean $allFields set if need all the fields of existing columns,
     *                           otherwise only column_name is returned
     *
     * @return array list of columns in central columns among given set of columns
     */
    private function findExistingColNames(
        $db,
        $cols,
        $allFields = false
    ) {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return array();
        }
        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, DatabaseInterface::CONNECT_CONTROL);
        $central_list_table = $cfgCentralColumns['table'];
        if ($allFields) {
            $query = 'SELECT * FROM ' . Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' AND col_name IN (' . $cols . ');';
            $has_list = (array) $this->dbi->fetchResult(
                $query, null, null, DatabaseInterface::CONNECT_CONTROL
            );
            $this->handleColumnExtra($has_list);
        } else {
            $query = 'SELECT col_name FROM '
                . Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' AND col_name IN (' . $cols . ');';
            $has_list = (array) $this->dbi->fetchResult(
                $query, null, null, DatabaseInterface::CONNECT_CONTROL
            );
        }

        return $has_list;
    }

    /**
     * return error message to be displayed if central columns
     * configuration storage is not completely configured
     *
     * @return Message
     */
    private function configErrorMessage()
    {
        return Message::error(
            __(
                'The configuration storage is not ready for the central list'
                . ' of columns feature.'
            )
        );
    }

    /**
     * build the insert query for central columns list given PMA storage
     * db, central_columns table, column name and corresponding definition to be added
     *
     * @param string $column             column to add into central list
     * @param array  $def                list of attributes of the column being added
     * @param string $db                 PMA configuration storage database name
     * @param string $central_list_table central columns configuration storage table name
     *
     * @return string query string to insert the given column
     * with definition into central list
     */
    private function getInsertQuery(
        $column,
        array $def,
        $db,
        $central_list_table
    ) {
        $type = "";
        $length = 0;
        $attribute = "";
        if (isset($def['Type'])) {
            $extracted_columnspec = Util::extractColumnSpec($def['Type']);
            $attribute = trim($extracted_columnspec[ 'attribute']);
            $type = $extracted_columnspec['type'];
            $length = $extracted_columnspec['spec_in_brackets'];
        }
        if (isset($def['Attribute'])) {
            $attribute = $def['Attribute'];
        };
        $collation = isset($def['Collation'])?$def['Collation']:"";
        $isNull = ($def['Null'] == "NO")?0:1;
        $extra = isset($def['Extra'])?$def['Extra']:"";
        $default = isset($def['Default'])?$def['Default']:"";
        $insQuery = 'INSERT INTO '
            . Util::backquote($central_list_table) . ' '
            . 'VALUES ( \'' . $this->dbi->escapeString($db) . '\' ,'
            . '\'' . $this->dbi->escapeString($column) . '\',\''
            . $this->dbi->escapeString($type) . '\','
            . '\'' . $this->dbi->escapeString($length) . '\',\''
            . $this->dbi->escapeString($collation) . '\','
            . '\'' . $this->dbi->escapeString($isNull) . '\','
            . '\'' . implode(',', array($extra, $attribute))
            . '\',\'' . $this->dbi->escapeString($default) . '\');';
        return $insQuery;
    }

    /**
     * If $isTable is true then unique columns from given tables as $field_select
     * are added to central list otherwise the $field_select is considered as
     * list of columns and these columns are added to central list if not already added
     *
     * @param array  $field_select if $isTable is true selected tables list
     *                             otherwise selected columns list
     * @param bool   $isTable      if passed array is of tables or columns
     * @param string $table        if $isTable is false, then table name to
     *                             which columns belong
     *
     * @return true|PhpMyAdmin\Message
     */
    public function syncUniqueColumns(
        array $field_select,
        $isTable = true,
        $table = null
    ) {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return $this->configErrorMessage();
        }
        $db = $_POST['db'];
        $pmadb = $cfgCentralColumns['db'];
        $central_list_table = $cfgCentralColumns['table'];
        $this->dbi->selectDb($db);
        $existingCols = array();
        $cols = "";
        $insQuery = array();
        $fields = array();
        $message = true;
        if ($isTable) {
            foreach ($field_select as $table) {
                $fields[$table] = (array) $this->dbi->getColumns(
                    $db, $table, null, true
                );
                foreach ($fields[$table] as $field => $def) {
                    $cols .= "'" . $this->dbi->escapeString($field) . "',";
                }
            }

            $has_list = $this->findExistingColNames($db, trim($cols, ','));
            foreach ($field_select as $table) {
                foreach ($fields[$table] as $field => $def) {
                    if (!in_array($field, $has_list)) {
                        $has_list[] = $field;
                        $insQuery[] = $this->getInsertQuery(
                            $field, $def, $db, $central_list_table
                        );
                    } else {
                        $existingCols[] = "'" . $field . "'";
                    }
                }
            }
        } else {
            if ($table === null) {
                $table = $_POST['table'];
            }
            foreach ($field_select as $column) {
                $cols .= "'" . $this->dbi->escapeString($column) . "',";
            }
            $has_list = $this->findExistingColNames($db, trim($cols, ','));
            foreach ($field_select as $column) {
                if (!in_array($column, $has_list)) {
                    $has_list[] = $column;
                    $field = (array) $this->dbi->getColumns(
                        $db, $table, $column,
                        true
                    );
                    $insQuery[] = $this->getInsertQuery(
                        $column, $field, $db, $central_list_table
                    );
                } else {
                    $existingCols[] = "'" . $column . "'";
                }
            }
        }
        if (! empty($existingCols)) {
            $existingCols = implode(",", array_unique($existingCols));
            $message = Message::notice(
                sprintf(
                    __(
                        'Could not add %1$s as they already exist in central list!'
                    ), htmlspecialchars($existingCols)
                )
            );
            $message->addMessage(
                Message::notice(
                    "Please remove them first "
                    . "from central list if you want to update above columns"
                )
            );
        }
        $this->dbi->selectDb($pmadb, DatabaseInterface::CONNECT_CONTROL);
        if (! empty($insQuery)) {
            foreach ($insQuery as $query) {
                if (!$this->dbi->tryQuery($query, DatabaseInterface::CONNECT_CONTROL)) {
                    $message = Message::error(__('Could not add columns!'));
                    $message->addMessage(
                        Message::rawError(
                            $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)
                        )
                    );
                    break;
                }
            }
        }
        return $message;
    }

    /**
     * if $isTable is true it removes all columns of given tables as $field_select from
     * central columns list otherwise $field_select is columns list and it removes
     * given columns if present in central list
     *
     * @param array $field_select if $isTable selected list of tables otherwise
     *                            selected list of columns to remove from central list
     * @param bool  $isTable      if passed array is of tables or columns
     *
     * @return true|PhpMyAdmin\Message
     */
    public function deleteColumnsFromList(
        array $field_select,
        $isTable = true
    ) {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return $this->configErrorMessage();
        }
        $db = $_POST['db'];
        $pmadb = $cfgCentralColumns['db'];
        $central_list_table = $cfgCentralColumns['table'];
        $this->dbi->selectDb($db);
        $message = true;
        $colNotExist = array();
        $fields = array();
        if ($isTable) {
            $cols = '';
            foreach ($field_select as $table) {
                $fields[$table] = (array) $this->dbi->getColumnNames(
                    $db, $table
                );
                foreach ($fields[$table] as $col_select) {
                    $cols .= '\'' . $this->dbi->escapeString($col_select) . '\',';
                }
            }
            $cols = trim($cols, ',');
            $has_list = $this->findExistingColNames($db, $cols);
            foreach ($field_select as $table) {
                foreach ($fields[$table] as $column) {
                    if (!in_array($column, $has_list)) {
                        $colNotExist[] = "'" . $column . "'";
                    }
                }
            }

        } else {
            $cols = '';
            foreach ($field_select as $col_select) {
                $cols .= '\'' . $this->dbi->escapeString($col_select) . '\',';
            }
            $cols = trim($cols, ',');
            $has_list = $this->findExistingColNames($db, $cols);
            foreach ($field_select as $column) {
                if (!in_array($column, $has_list)) {
                    $colNotExist[] = "'" . $column . "'";
                }
            }
        }
        if (!empty($colNotExist)) {
            $colNotExist = implode(",", array_unique($colNotExist));
            $message = Message::notice(
                sprintf(
                    __(
                        'Couldn\'t remove Column(s) %1$s '
                        . 'as they don\'t exist in central columns list!'
                    ), htmlspecialchars($colNotExist)
                )
            );
        }
        $this->dbi->selectDb($pmadb, DatabaseInterface::CONNECT_CONTROL);

        $query = 'DELETE FROM ' . Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' AND col_name IN (' . $cols . ');';

        if (!$this->dbi->tryQuery($query, DatabaseInterface::CONNECT_CONTROL)) {
            $message = Message::error(__('Could not remove columns!'));
            $message->addHtml('<br />' . htmlspecialchars($cols) . '<br />');
            $message->addMessage(
                Message::rawError(
                    $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)
                )
            );
        }
        return $message;
    }

    /**
     * Make the columns of given tables consistent with central list of columns.
     * Updates only those columns which are not being referenced.
     *
     * @param string $db              current database
     * @param array  $selected_tables list of selected tables.
     *
     * @return true|PhpMyAdmin\Message
     */
    public function makeConsistentWithList(
        $db,
        array $selected_tables
    ) {
        $message = true;
        foreach ($selected_tables as $table) {
            $query = 'ALTER TABLE ' . Util::backquote($table);
            $has_list = $this->getFromTable($db, $table, true);
            $this->dbi->selectDb($db);
            foreach ($has_list as $column) {
                $column_status = $this->relation->checkChildForeignReferences(
                    $db, $table, $column['col_name']
                );
                //column definition can only be changed if
                //it is not referenced by another column
                if ($column_status['isEditable']) {
                    $query .= ' MODIFY ' . Util::backquote($column['col_name']) . ' '
                        . $this->dbi->escapeString($column['col_type']);
                    if ($column['col_length']) {
                        $query .= '(' . $column['col_length'] . ')';
                    }

                    $query .= ' ' . $column['col_attribute'];
                    if ($column['col_isNull']) {
                        $query .= ' NULL';
                    } else {
                        $query .= ' NOT NULL';
                    }

                    $query .= ' ' . $column['col_extra'];
                    if ($column['col_default']) {
                        if ($column['col_default'] != 'CURRENT_TIMESTAMP'
                            || $column['col_default'] != 'current_timestamp()') {
                            $query .= ' DEFAULT \'' . $this->dbi->escapeString(
                                $column['col_default']
                            ) . '\'';
                        } else {
                            $query .= ' DEFAULT ' . $this->dbi->escapeString(
                                $column['col_default']
                            );
                        }
                    }
                    $query .= ',';
                }
            }
            $query = trim($query, " ,") . ";";
            if (!$this->dbi->tryQuery($query)) {
                if ($message === true) {
                    $message = Message::error(
                        $this->dbi->getError()
                    );
                } else {
                    $message->addText(
                        $this->dbi->getError(),
                        '<br />'
                    );
                }
            }
        }
        return $message;
    }

    /**
     * return the columns present in central list of columns for a given
     * table of a given database
     *
     * @param string  $db        given database
     * @param string  $table     given table
     * @param boolean $allFields set if need all the fields of existing columns,
     *                           otherwise only column_name is returned
     *
     * @return array columns present in central list from given table of given db.
     */
    public function getFromTable(
        $db,
        $table,
        $allFields = false
    ) {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return array();
        }
        $this->dbi->selectDb($db);
        $fields = (array) $this->dbi->getColumnNames(
            $db, $table
        );
        $cols = '';
        foreach ($fields as $col_select) {
            $cols .= '\'' . $this->dbi->escapeString($col_select) . '\',';
        }
        $cols = trim($cols, ',');
        $has_list = $this->findExistingColNames($db, $cols, $allFields);
        if (! empty($has_list)) {
            return (array)$has_list;
        }

        return array();
    }

    /**
     * update a column in central columns list if a edit is requested
     *
     * @param string $db            current database
     * @param string $orig_col_name original column name before edit
     * @param string $col_name      new column name
     * @param string $col_type      new column type
     * @param string $col_attribute new column attribute
     * @param string $col_length    new column length
     * @param int    $col_isNull    value 1 if new column isNull is true, 0 otherwise
     * @param string $collation     new column collation
     * @param string $col_extra     new column extra property
     * @param string $col_default   new column default value
     *
     * @return true|PhpMyAdmin\Message
     */
    public function updateOneColumn(
        $db,
        $orig_col_name,
        $col_name,
        $col_type,
        $col_attribute,
        $col_length,
        $col_isNull,
        $collation,
        $col_extra,
        $col_default
    ) {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return $this->configErrorMessage();
        }
        $centralTable = $cfgCentralColumns['table'];
        $this->dbi->selectDb($cfgCentralColumns['db'], DatabaseInterface::CONNECT_CONTROL);
        if ($orig_col_name == "") {
            $def = array();
            $def['Type'] = $col_type;
            if ($col_length) {
                $def['Type'] .= '(' . $col_length . ')';
            }
            $def['Collation'] = $collation;
            $def['Null'] = $col_isNull?__('YES'):__('NO');
            $def['Extra'] = $col_extra;
            $def['Attribute'] = $col_attribute;
            $def['Default'] = $col_default;
            $query = $this->getInsertQuery($col_name, $def, $db, $centralTable);
        } else {
            $query = 'UPDATE ' . Util::backquote($centralTable)
                . ' SET col_type = \'' . $this->dbi->escapeString($col_type) . '\''
                . ', col_name = \'' . $this->dbi->escapeString($col_name) . '\''
                . ', col_length = \'' . $this->dbi->escapeString($col_length) . '\''
                . ', col_isNull = ' . $col_isNull
                . ', col_collation = \'' . $this->dbi->escapeString($collation) . '\''
                . ', col_extra = \''
                . implode(',', array($col_extra, $col_attribute)) . '\''
                . ', col_default = \'' . $this->dbi->escapeString($col_default) . '\''
                . ' WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' '
                . 'AND col_name = \'' . $this->dbi->escapeString($orig_col_name)
                . '\'';
        }
        if (!$this->dbi->tryQuery($query, DatabaseInterface::CONNECT_CONTROL)) {
            return Message::error(
                $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)
            );
        }
        return true;
    }

    /**
     * Update Multiple column in central columns list if a chnage is requested
     *
     * @return true|PhpMyAdmin\Message
     */
    public function updateMultipleColumn()
    {
        $db = $_POST['db'];
        $col_name = $_POST['field_name'];
        $orig_col_name = $_POST['orig_col_name'];
        $col_default = $_POST['field_default_type'];
        $col_length = $_POST['field_length'];
        $col_attribute = $_POST['field_attribute'];
        $col_type = $_POST['field_type'];
        $collation = $_POST['field_collation'];
        $col_isNull = array();
        $col_extra = array();
        $num_central_fields = count($orig_col_name);
        for ($i = 0; $i < $num_central_fields ; $i++) {
            $col_isNull[$i] = isset($_POST['field_null'][$i]) ? 1 : 0;
            $col_extra[$i] = isset($_POST['col_extra'][$i])
                ? $_POST['col_extra'][$i] : '';

            if ($col_default[$i] == 'NONE') {
                $col_default[$i] = "";
            } elseif ($col_default[$i] == 'USER_DEFINED') {
                $col_default[$i] = $_POST['field_default_value'][$i];
            }

            $message = $this->updateOneColumn(
                $db, $orig_col_name[$i], $col_name[$i], $col_type[$i],
                $col_attribute[$i], $col_length[$i], $col_isNull[$i], $collation[$i],
                $col_extra[$i], $col_default[$i]
            );
            if (!is_bool($message)) {
                return $message;
            }
        }
        return true;
    }

    /**
     * get the html for table navigation in Central columns page
     *
     * @param int    $total_rows total number of rows in complete result set
     * @param int    $pos        offset of first result with complete result set
     * @param string $db         current database
     *
     * @return string html for table navigation in Central columns page
     */
    public function getHtmlForTableNavigation($total_rows, $pos, $db)
    {
        $pageNow = ($pos / $this->maxRows) + 1;
        $nbTotalPage = ceil($total_rows / $this->maxRows);
        $page_selector = ($nbTotalPage > 1)?(Util::pageselector(
            'pos', $this->maxRows, $pageNow, $nbTotalPage
        )):'';
        return  Template::get('database/central_columns/table_navigation')->render(array(
            "pos" => $pos,
            "max_rows" => $this->maxRows,
            "db" => $db,
            "total_rows" => $total_rows,
            "nb_total_page" => $nbTotalPage,
            "page_selector" => $page_selector,
        ));
    }

    /**
     * function generate and return the table header for central columns page
     *
     * @param string  $class       styling class of 'th' elements
     * @param string  $title       title of the 'th' elements
     * @param integer $actionCount number of actions
     *
     * @return string html for table header in central columns view/edit page
     */
    public function getTableHeader($class = '', $title = '', $actionCount = 0)
    {
        $action = '';
        if ($actionCount > 0) {
            $action .= '<th class="column_action" colspan="' . $actionCount . '">'
                . __('Action') . '</th>';
        }
        $tableheader = '<thead>';
        $tableheader .= '<tr>'
            . '<th class="' . $class . '"></th>'
            . '<th class="hide"></th>'
            . $action
            . '<th class="' . $class . '" title="' . $title . '" data-column="name">'
            . __('Name') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title . '" data-column="type">'
            . __('Type') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title . '" data-column="length">'
            . __('Length/Values') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title . '" data-column="default">'
            . __('Default') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title . '" data-column="collation"'
            . '>' . __('Collation') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title
            . '" data-column="attribute">'
            . __('Attribute') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title . '" data-column="isnull">'
            . __('Null') . '<div class="sorticon"></div></th>'
            . '<th class="' . $class . '" title="' . $title . '" data-column="extra">'
            . __('A_I') . '<div class="sorticon"></div></th>'
            . '</tr>';
        $tableheader .= '</thead>';
        return $tableheader;
    }

    /**
     * Function generate and return the table header for
     * multiple edit central columns page
     *
     * @param array $headers headers list
     *
     * @return string html for table header in central columns multi edit page
     */
    private function getEditTableHeader(array $headers)
    {
        return Template::get(
            'database/central_columns/edit_table_header'
        )->render([
            'headers' => $headers,
        ]);
    }

    /**
     * build the dropdown select html for tables of given database
     *
     * @param string $db current database
     *
     * @return string html dropdown for selecting table
     */
    private function getHtmlForTableDropdown($db)
    {
        $this->dbi->selectDb($db);
        $tables = $this->dbi->getTables($db);
        $selectHtml = '<select name="table-select" id="table-select">'
            . '<option value="" disabled="disabled" selected="selected">'
            . __('Select a table') . '</option>';
        foreach ($tables as $table) {
            $selectHtml .= '<option value="' . htmlspecialchars($table) . '">'
                . htmlspecialchars($table) . '</option>';
        }
        $selectHtml .= '</select>';
        return $selectHtml;
    }

    /**
     * build dropdown select html to select column in selected table,
     * include only columns which are not already in central list
     *
     * @param string $db           current database to which selected table belongs
     * @param string $selected_tbl selected table
     *
     * @return string html to select column
     */
    public function getHtmlForColumnDropdown($db, $selected_tbl)
    {
        $existing_cols = $this->getFromTable($db, $selected_tbl);
        $this->dbi->selectDb($db);
        $columns = (array) $this->dbi->getColumnNames(
            $db, $selected_tbl
        );
        $selectColHtml = "";
        foreach ($columns as $column) {
            if (!in_array($column, $existing_cols)) {
                $selectColHtml .= '<option value="' . htmlspecialchars($column) . '">'
                    . htmlspecialchars($column)
                    . '</option>';
            }
        }
        return $selectColHtml;
    }

    /**
     * HTML to display the form that let user to add a column on Central columns page
     *
     * @param int    $total_rows total number of rows in complete result set
     * @param int    $pos        offset of first result with complete result set
     * @param string $db         current database
     *
     * @return string html to add a column in the central list
     */
     public function getHtmlForAddColumn(
         $total_rows,
         $pos,
         $db
     ) {
         $icon = Util::getIcon(
             'centralColumns_add',
             __('Add column')
         );
         $table_drop_down = $this->getHtmlForTableDropdown($db);
         return Template::get('database/central_columns/add_column')->render(array(
             'icon' => $icon,
             'pos' => $pos,
             'db' => $db,
             'total_rows' => $total_rows,
             'table_drop_down' => $table_drop_down,
         ));
     }

    /**
     * build html for a row in central columns table
     *
     * @param array  $row     array contains complete information of a particular row of central list table
     * @param int    $row_num position the row in the table
     * @param string $db      current database
     *
     * @return string html of a particular row in the central columns table.
     */
    public function getHtmlForTableRow(array $row, $row_num, $db)
    {
        $tableHtml = '<tr data-rownum="' . $row_num . '" id="f_' . $row_num . '">'
            . Url::getHiddenInputs(
                $db
            )
            . '<input type="hidden" name="edit_save" value="save">'
            . '<td class="nowrap">'
            . '<input type="checkbox" class="checkall" name="selected_fld[]" '
            . 'value="' . htmlspecialchars($row['col_name']) . '" '
            . 'id="checkbox_row_' . $row_num . '"/>'
            . '</td>'
            . '<td id="edit_' . $row_num . '" class="edit center">'
            . '<a href="#">' . Util::getIcon('b_edit', __('Edit')) . '</a></td>'
            . '<td class="del_row" data-rownum = "' . $row_num . '">'
            . '<a hrf="#">' . Util::getIcon('b_drop', __('Delete')) . '</a>'
            . '<input type="submit" data-rownum = "' . $row_num . '"'
            . ' class="edit_cancel_form" value="Cancel"></td>'
            . '<td id="save_' . $row_num . '" class="hide">'
            . '<input type="submit" data-rownum = "' . $row_num . '"'
            . ' class="edit_save_form" value="Save"></td>';

        $tableHtml .=
            '<td name="col_name" class="nowrap">'
            . '<span>' . htmlspecialchars($row['col_name']) . '</span>'
            . '<input name="orig_col_name" type="hidden" '
            . 'value="' . htmlspecialchars($row['col_name']) . '">'
            . Template::get('columns_definitions/column_name')->render(array(
                'column_number' => $row_num,
                'ci' => 0,
                'ci_offset' => 0,
                'column_meta' => array(
                    'Field'=>$row['col_name']
                ),
                'cfg_relation' => array(
                    'centralcolumnswork' => false
                ),
                'max_rows' => $this->maxRows,
            ))
            . '</td>';
        $tableHtml .=
            '<td name = "col_type" class="nowrap"><span>'
            . htmlspecialchars($row['col_type']) . '</span>'
            . Template::get('columns_definitions/column_type')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 1,
                    'ci_offset' => 0,
                    'type_upper' => mb_strtoupper($row['col_type']),
                    'column_meta' => array()
                    )
                )
            . '</td>';
        $tableHtml .=
            '<td class="nowrap" name="col_length">'
            . '<span>' . ($row['col_length']?htmlspecialchars($row['col_length']):"")
            . '</span>'
            . Template::get('columns_definitions/column_length')->render(
                array(
                    'column_number' => $row_num,
                    'ci' => 2,
                    'ci_offset' => 0,
                    'length_values_input_size' => 8,
                    'length_to_display' => $row['col_length']
                )
            )
            . '</td>';

        $meta = array();
        if (!isset($row['col_default']) || $row['col_default'] == '') {
            $meta['DefaultType'] = 'NONE';
        } else {
            if ($row['col_default'] == 'CURRENT_TIMESTAMP'
                || $row['col_default'] == 'current_timestamp()'
            ) {
                $meta['DefaultType'] = 'CURRENT_TIMESTAMP';
            } elseif ($row['col_default'] == 'NULL') {
                $meta['DefaultType'] = $row['col_default'];
            } else {
                $meta['DefaultType'] = 'USER_DEFINED';
                $meta['DefaultValue'] = $row['col_default'];
            }
        }
        $tableHtml .=
            '<td class="nowrap" name="col_default"><span>' . (isset($row['col_default'])
                ? htmlspecialchars($row['col_default']) : 'None')
            . '</span>'
            . Template::get('columns_definitions/column_default')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 3,
                    'ci_offset' => 0,
                    'type_upper' => mb_strtoupper($row['col_type']),
                    'column_meta' => $meta,
                    'char_editing' => $this->charEditing,
                    )
                )
            . '</td>';

        $tableHtml .=
            '<td name="collation" class="nowrap">'
            . '<span>' . htmlspecialchars($row['col_collation']) . '</span>'
            . Charsets::getCollationDropdownBox(
                $this->dbi,
                $this->disableIs,
                'field_collation[' . $row_num . ']',
                'field_' . $row_num . '_4', $row['col_collation'], false
            )
            . '</td>';
        $tableHtml .=
            '<td class="nowrap" name="col_attribute">'
            . '<span>' .
            ($row['col_attribute']
                ? htmlspecialchars($row['col_attribute']) : "" )
            . '</span>'
            . Template::get('columns_definitions/column_attribute')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 5,
                    'ci_offset' => 0,
                    'extracted_columnspec' => array(),
                    'column_meta' => $row['col_attribute'],
                    'submit_attribute' => false,
                    'attribute_types' => $this->dbi->types->getAttributes(),
                    )
                )
            . '</td>';
        $tableHtml .=
            '<td class="nowrap" name="col_isNull">'
            . '<span>' . ($row['col_isNull'] ? __('Yes') : __('No'))
            . '</span>'
            . Template::get('columns_definitions/column_null')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 6,
                    'ci_offset' => 0,
                    'column_meta' => array(
                        'Null' => $row['col_isNull']
                    )
                    )
                )
            . '</td>';

        $tableHtml .=
            '<td class="nowrap" name="col_extra"><span>'
            . htmlspecialchars($row['col_extra']) . '</span>'
            . Template::get('columns_definitions/column_extra')->render(
                array(
                    'column_number' => $row_num,
                    'ci' => 7,
                    'ci_offset' => 0,
                    'column_meta' => array('Extra'=>$row['col_extra'])
                )
            )
            . '</td>';

        $tableHtml .= '</tr>';

        return $tableHtml;
    }

    /**
     * build html for editing a row in central columns table
     *
     * @param array $row     array contains complete information of a
     *                       particular row of central list table
     * @param int   $row_num position the row in the table
     *
     * @return string html of a particular row in the central columns table.
     */
    private function getHtmlForEditTableRow(array $row, $row_num)
    {
        $tableHtml = '<tr>'
            . '<input name="orig_col_name[' . $row_num . ']" type="hidden" '
            . 'value="' . htmlspecialchars($row['col_name']) . '">'
            . '<td name="col_name" class="nowrap">'
            . Template::get('columns_definitions/column_name')->render(array(
                'column_number' => $row_num,
                'ci' => 0,
                'ci_offset' => 0,
                'column_meta' => array(
                    'Field' => $row['col_name']
                ),
                'cfg_relation' => array(
                    'centralcolumnswork' => false
                ),
                'max_rows' => $this->maxRows,
            ))
            . '</td>';
        $tableHtml .=
            '<td name = "col_type" class="nowrap">'
            . Template::get('columns_definitions/column_type')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 1,
                    'ci_offset' => 0,
                    'type_upper' => mb_strtoupper($row['col_type']),
                    'column_meta' => array()
                    )
                )
            . '</td>';
        $tableHtml .=
            '<td class="nowrap" name="col_length">'
            . Template::get('columns_definitions/column_length')->render(
                array(
                    'column_number' => $row_num,
                    'ci' => 2,
                    'ci_offset' => 0,
                    'length_values_input_size' => 8,
                    'length_to_display' => $row['col_length']
                )
            )
            . '</td>';
        $meta = array();
        if (!isset($row['col_default']) || $row['col_default'] == '') {
            $meta['DefaultType'] = 'NONE';
        } else {
            if ($row['col_default'] == 'CURRENT_TIMESTAMP'
                || $row['col_default'] == 'current_timestamp()'
            ) {
                $meta['DefaultType'] = 'CURRENT_TIMESTAMP';
            } elseif ($row['col_default'] == 'NULL') {
                $meta['DefaultType'] = $row['col_default'];
            } else {
                $meta['DefaultType'] = 'USER_DEFINED';
                $meta['DefaultValue'] = $row['col_default'];
            }
        }
        $tableHtml .=
            '<td class="nowrap" name="col_default">'
            . Template::get('columns_definitions/column_default')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 3,
                    'ci_offset' => 0,
                    'type_upper' => mb_strtoupper($row['col_default']),
                    'column_meta' => $meta,
                    'char_editing' => $this->charEditing,
                    )
                )
            . '</td>';
        $tableHtml .=
            '<td name="collation" class="nowrap">'
            . Charsets::getCollationDropdownBox(
                $this->dbi,
                $this->disableIs,
                'field_collation[' . $row_num . ']',
                'field_' . $row_num . '_4', $row['col_collation'], false
            )
            . '</td>';
        $tableHtml .=
            '<td class="nowrap" name="col_attribute">'
            . Template::get('columns_definitions/column_attribute')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 5,
                    'ci_offset' => 0,
                    'extracted_columnspec' => array(
                        'attribute' => $row['col_attribute']
                    ),
                    'column_meta' => array(),
                    'submit_attribute' => false,
                    'attribute_types' => $this->dbi->types->getAttributes(),
                    )
                )
            . '</td>';
        $tableHtml .=
            '<td class="nowrap" name="col_isNull">'
            . Template::get('columns_definitions/column_null')
                ->render(
                    array(
                    'column_number' => $row_num,
                    'ci' => 6,
                    'ci_offset' => 0,
                    'column_meta' => array(
                        'Null' => $row['col_isNull']
                    )
                    )
                )
            . '</td>';

        $tableHtml .=
            '<td class="nowrap" name="col_extra">'
            . Template::get('columns_definitions/column_extra')->render(
                array(
                    'column_number' => $row_num,
                    'ci' => 7,
                    'ci_offset' => 0,
                    'column_meta' => array('Extra' => $row['col_extra'])
                )
            )
            . '</td>';
        $tableHtml .= '</tr>';
        return $tableHtml;
    }

    /**
     * get the list of columns in given database excluding
     * the columns present in current table
     *
     * @param string $db    selected database
     * @param string $table current table name
     *
     * @return string encoded list of columns present in central list for the given
     *                database
     */
    public function getListRaw($db, $table)
    {
        $cfgCentralColumns = $this->getParams();
        if (empty($cfgCentralColumns)) {
            return json_encode(array());
        }
        $centralTable = $cfgCentralColumns['table'];
        if (empty($table) || $table == '') {
            $query = 'SELECT * FROM ' . Util::backquote($centralTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\';';
        } else {
            $this->dbi->selectDb($db);
            $columns = (array) $this->dbi->getColumnNames(
                $db, $table
            );
            $cols = '';
            foreach ($columns as $col_select) {
                $cols .= '\'' . $this->dbi->escapeString($col_select) . '\',';
            }
            $cols = trim($cols, ',');
            $query = 'SELECT * FROM ' . Util::backquote($centralTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\'';
            if ($cols) {
                $query .= ' AND col_name NOT IN (' . $cols . ')';
            }
            $query .= ';';
        }
        $this->dbi->selectDb($cfgCentralColumns['db'], DatabaseInterface::CONNECT_CONTROL);
        $columns_list = (array)$this->dbi->fetchResult(
            $query, null, null, DatabaseInterface::CONNECT_CONTROL
        );
        $this->handleColumnExtra($columns_list);
        return json_encode($columns_list);
    }

    /**
     * Get HTML for "check all" check box with "with selected" dropdown
     *
     * @param string $pmaThemeImage pma theme image url
     * @param string $text_dir      url for text directory
     *
     * @return string $html_output
     */
    public function getTableFooter($pmaThemeImage, $text_dir)
    {
        $html_output = Template::get('select_all')
            ->render(
                array(
                    'pma_theme_image' => $pmaThemeImage,
                    'text_dir'        => $text_dir,
                    'form_name'       => 'tableslistcontainer',
                )
            );
        $html_output .= Util::getButtonOrImage(
            'edit_central_columns', 'mult_submit change_central_columns',
            __('Edit'), 'b_edit', 'edit central columns'
        );
        $html_output .= Util::getButtonOrImage(
            'delete_central_columns', 'mult_submit',
            __('Delete'), 'b_drop',
            'remove_from_central_columns'
        );
        return $html_output;
    }

    /**
     * function generate and return the table footer for
     * multiple edit central columns page
     *
     * @return string html for table footer in central columns multi edit page
     */
    private function getEditTableFooter()
    {
        $html_output = '<fieldset class="tblFooters">'
            . '<input type="submit" '
            . 'name="save_multi_central_column_edit" value="' . __('Save') . '" />'
            . '</fieldset>';
        return $html_output;
    }

    /**
     * Column `col_extra` is used to store both extra and attributes for a column.
     * This method separates them.
     *
     * @param array &$columns_list columns list
     *
     * @return void
     */
    private function handleColumnExtra(array &$columns_list)
    {
        foreach ($columns_list as &$row) {
            $vals = explode(',', $row['col_extra']);

            if (in_array('BINARY', $vals)) {
                $row['col_attribute'] = 'BINARY';
            } elseif (in_array('UNSIGNED', $vals)) {
                $row['col_attribute'] = 'UNSIGNED';
            } elseif (in_array('UNSIGNED ZEROFILL', $vals)) {
                $row['col_attribute'] = 'UNSIGNED ZEROFILL';
            } elseif (in_array('on update CURRENT_TIMESTAMP', $vals)) {
                $row['col_attribute'] = 'on update CURRENT_TIMESTAMP';
            } else {
                $row['col_attribute'] = '';
            }

            if (in_array('auto_increment', $vals)) {
                $row['col_extra'] = 'auto_increment';
            } else {
                $row['col_extra'] = '';
            }
        }
    }

    /**
     * build html for adding a new user defined column to central list
     *
     * @param string  $db         current database
     * @param integer $total_rows number of rows in central columns
     *
     * @return string html of the form to let user add a new user defined column to the
     *                list
     */
    public function getHtmlForAddNewColumn($db, $total_rows)
    {
        $addNewColumn = '<div id="add_col_div" class="topmargin"><a href="#">'
            . '<span>+</span> ' . __('Add new column') . '</a>'
            . '<form id="add_new" class="new_central_col '
            . ($total_rows != 0 ? 'hide"' : '"')
            . 'method="post" action="db_central_columns.php">'
            . Url::getHiddenInputs(
                $db
            )
            . '<input type="hidden" name="add_new_column" value="add_new_column">'
            . '<div class="responsivetable">'
            . '<table>';
        $addNewColumn .= $this->getTableHeader();
        $addNewColumn .= '<tr>'
            . '<td></td>'
            . '<td name="col_name" class="nowrap">'
            . Template::get('columns_definitions/column_name')->render(array(
                'column_number' => 0,
                'ci' => 0,
                'ci_offset' => 0,
                'column_meta' => array(),
                'cfg_relation' => array(
                    'centralcolumnswork' => false
                ),
                'max_rows' => $this->maxRows,
            ))
            . '</td>'
            . '<td name = "col_type" class="nowrap">'
            . Template::get('columns_definitions/column_type')
                ->render(
                    array(
                    'column_number' => 0,
                    'ci' => 1,
                    'ci_offset' => 0,
                    'type_upper' => '',
                    'column_meta' => array()
                    )
                )
            . '</td>'
            . '<td class="nowrap" name="col_length">'
            . Template::get('columns_definitions/column_length')->render(
                array(
                    'column_number' => 0,
                    'ci' => 2,
                    'ci_offset' => 0,
                    'length_values_input_size' => 8,
                    'length_to_display' => ''
                )
            )
            . '</td>'
            . '<td class="nowrap" name="col_default">'
            . Template::get('columns_definitions/column_default')
                ->render(
                    array(
                    'column_number' => 0,
                    'ci' => 3,
                    'ci_offset' => 0,
                    'type_upper' => '',
                    'column_meta' => array(),
                    'char_editing' => $this->charEditing,
                    )
                )
            . '</td>'
            . '<td name="collation" class="nowrap">'
            . Charsets::getCollationDropdownBox(
                $this->dbi,
                $this->disableIs,
                'field_collation[0]',
                'field_0_4', null, false
            )
            . '</td>'
            . '<td class="nowrap" name="col_attribute">'
            . Template::get('columns_definitions/column_attribute')
                ->render(
                    array(
                    'column_number' => 0,
                    'ci' => 5,
                    'ci_offset' => 0,
                    'extracted_columnspec' => array(),
                    'column_meta' => array(),
                    'submit_attribute' => false,
                    'attribute_types' => $this->dbi->types->getAttributes(),
                    )
                )
            . '</td>'
            . '<td class="nowrap" name="col_isNull">'
            . Template::get('columns_definitions/column_null')
                ->render(
                    array(
                    'column_number' => 0,
                    'ci' => 6,
                    'ci_offset' => 0,
                    'column_meta' => array()
                    )
                )
            . '</td>'
            . '<td class="nowrap" name="col_extra">'
            . Template::get('columns_definitions/column_extra')->render(
                array(
                    'column_number' => 0,
                    'ci' => 7,
                    'ci_offset' => 0,
                    'column_meta' => array()
                )
            )
            . '</td>'
            . ' <td>'
            . '<input id="add_column_save" type="submit" '
            . ' value="Save"/></td>'
            . '</tr>';
        $addNewColumn .= '</table></div></form></div>';
        return $addNewColumn;
    }

    /**
     * Get HTML for editing page central columns
     *
     * @param array  $selected_fld Array containing the selected fields
     * @param string $selected_db  String containing the name of database
     *
     * @return string HTML for complete editing page for central columns
     */
    public function getHtmlForEditingPage(array $selected_fld, $selected_db)
    {
        $html = '<form id="multi_edit_central_columns">';
        $header_cells = array(
            __('Name'), __('Type'), __('Length/Values'), __('Default'),
            __('Collation'), __('Attributes'), __('Null'), __('A_I')
        );
        $html .= $this->getEditTableHeader($header_cells);
        $selected_fld_safe = array();
        foreach ($selected_fld as $key) {
            $selected_fld_safe[] = $this->dbi->escapeString($key);
        }
        $columns_list = implode("','", $selected_fld_safe);
        $columns_list = "'" . $columns_list . "'";
        $list_detail_cols = $this->findExistingColNames($selected_db, $columns_list, true);
        $row_num = 0;
        foreach ($list_detail_cols as $row) {
            $tableHtmlRow = $this->getHtmlForEditTableRow(
                $row,
                $row_num
            );
            $html .= $tableHtmlRow;
            $row_num++;
        }
        $html .= '</table>';
        $html .= $this->getEditTableFooter();
        $html .= '</form>';
        return $html;
    }
}
