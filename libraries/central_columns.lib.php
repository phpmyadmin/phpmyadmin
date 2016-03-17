<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;
use PMA\libraries\Util;

/**
 * Defines the central_columns parameters for the current user
 *
 * @return array    the central_columns parameters for the current user
 * @access  public
 */
function PMA_centralColumnsGetParams()
{
    static $cfgCentralColumns = null;

    if (null !== $cfgCentralColumns) {
        return $cfgCentralColumns;
    }

    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['centralcolumnswork']) {
        $cfgCentralColumns = array(
            'user'  => $GLOBALS['cfg']['Server']['user'],
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
function PMA_getColumnsList($db, $from=0, $num=25)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return array();
    }
    $pmadb = $cfgCentralColumns['db'];
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    $central_list_table = $cfgCentralColumns['table'];
    //get current values of $db from central column list
    if ($num == 0) {
        $query = 'SELECT * FROM ' . Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\';';
    } else {
        $query = 'SELECT * FROM ' . Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\' '
            . 'LIMIT ' . $from . ', ' . $num . ';';
    }
    $has_list = (array) $GLOBALS['dbi']->fetchResult(
        $query, null, null, $GLOBALS['controllink']
    );
    PMA_handleColumnExtra($has_list);
    return $has_list;
}

/**
 * get the number of columns present in central list for given db
 *
 * @param string $db current database
 *
 * @return int number of columns in central list of columns for $db
 */
function PMA_getCentralColumnsCount($db)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return 0;
    }
    $pmadb = $cfgCentralColumns['db'];
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    $central_list_table = $cfgCentralColumns['table'];
    $query = 'SELECT count(db_name) FROM ' .
        Util::backquote($central_list_table) . ' '
        . 'WHERE db_name = \'' . $db . '\';';
    $res = $GLOBALS['dbi']->fetchResult(
        $query, null, null, $GLOBALS['controllink']
    );
    if (isset($res[0])) {
        return $res[0];
    } else {
        return 0;
    }
}
/**
 * return the existing columns in central list among the given list of columns
 *
 * @param string  $db        the selected database
 * @param string  $cols      comma separated list of given columns
 * @param boolean $allFields set if need all the fields of existing columns,
 * otherwise only column_name is returned
 *
 * @return array list of columns in central columns among given set of columns
 */
function PMA_findExistingColNames($db, $cols, $allFields=false)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return array();
    }
    $pmadb = $cfgCentralColumns['db'];
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    $central_list_table = $cfgCentralColumns['table'];
    if ($allFields) {
        $query = 'SELECT * FROM ' . Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';
        $has_list = (array) $GLOBALS['dbi']->fetchResult(
            $query, null, null, $GLOBALS['controllink']
        );
        PMA_handleColumnExtra($has_list);
    } else {
        $query = 'SELECT col_name FROM '
            . Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';
        $has_list = (array) $GLOBALS['dbi']->fetchResult(
            $query, null, null, $GLOBALS['controllink']
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
function PMA_configErrorMessage()
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
function PMA_getInsertQuery($column, $def, $db, $central_list_table)
{
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
        . 'VALUES ( \'' . Util::sqlAddSlashes($db) . '\' ,'
        . '\'' . Util::sqlAddSlashes($column) . '\',\''
        . Util::sqlAddSlashes($type) . '\','
        . '\'' . Util::sqlAddSlashes($length) . '\',\''
        . Util::sqlAddSlashes($collation) . '\','
        . '\'' . Util::sqlAddSlashes($isNull) . '\','
        . '\'' . implode(',', array($extra, $attribute))
        . '\',\'' . Util::sqlAddSlashes($default) . '\');';
    return $insQuery;
}

/**
 * If $isTable is true then unique columns from given tables as $field_select
 * are added to central list otherwise the $field_select is considered as
 * list of columns and these columns are added to central list if not already added
 *
 * @param array  $field_select if $isTable is true selected tables list
 * otherwise selected columns list
 * @param bool   $isTable      if passed array is of tables or columns
 * @param string $table        if $isTable is false,
 * then table name to which columns belong
 *
 * @return true|PMA\libraries\Message
 */
function PMA_syncUniqueColumns($field_select, $isTable=true, $table=null)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $db = $_REQUEST['db'];
    $pmadb = $cfgCentralColumns['db'];
    $central_list_table = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $existingCols = array();
    $cols = "";
    $insQuery = array();
    $fields = array();
    $message = true;
    if ($isTable) {
        foreach ($field_select as $table) {
            $fields[$table] = (array) $GLOBALS['dbi']->getColumns(
                $db, $table, null, true, $GLOBALS['userlink']
            );
            foreach ($fields[$table] as $field => $def) {
                $cols .= "'" . Util::sqlAddSlashes($field) . "',";
            }
        }

        $has_list = PMA_findExistingColNames($db, trim($cols, ','));
        foreach ($field_select as $table) {
            foreach ($fields[$table] as $field => $def) {
                if (!in_array($field, $has_list)) {
                    $has_list[] = $field;
                    $insQuery[] = PMA_getInsertQuery(
                        $field, $def, $db, $central_list_table
                    );
                } else {
                    $existingCols[] = "'" . $field . "'";
                }
            }
        }
    } else {
        if ($table === null) {
            $table = $_REQUEST['table'];
        }
        foreach ($field_select as $column) {
            $cols .= "'" . Util::sqlAddSlashes($column) . "',";
        }
        $has_list = PMA_findExistingColNames($db, trim($cols, ','));
        foreach ($field_select as $column) {
            if (!in_array($column, $has_list)) {
                $has_list[] = $column;
                $field = (array) $GLOBALS['dbi']->getColumns(
                    $db, $table, $column,
                    true, $GLOBALS['userlink']
                );
                $insQuery[] = PMA_getInsertQuery(
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
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    if (! empty($insQuery)) {
        foreach ($insQuery as $query) {
            if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
                $message = Message::error(__('Could not add columns!'));
                $message->addMessage(
                    Message::rawError(
                        $GLOBALS['dbi']->getError($GLOBALS['controllink'])
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
 * selected list of columns to remove from central list
 * @param bool  $isTable      if passed array is of tables or columns
 *
 * @return true|PMA\libraries\Message
 */
function PMA_deleteColumnsFromList($field_select, $isTable=true)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $db = $_REQUEST['db'];
    $pmadb = $cfgCentralColumns['db'];
    $central_list_table = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $message = true;
    $colNotExist = array();
    $fields = array();
    if ($isTable) {
        $cols = '';
        foreach ($field_select as $table) {
            $fields[$table] = (array) $GLOBALS['dbi']->getColumnNames(
                $db, $table, $GLOBALS['userlink']
            );
            foreach ($fields[$table] as $col_select) {
                $cols .= '\'' . Util::sqlAddSlashes($col_select) . '\',';
            }
        }
        $cols = trim($cols, ',');
        $has_list = PMA_findExistingColNames($db, $cols);
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
            $cols .= '\'' . Util::sqlAddSlashes($col_select) . '\',';
        }
        $cols = trim($cols, ',');
        $has_list = PMA_findExistingColNames($db, $cols);
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
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);

    $query = 'DELETE FROM ' . Util::backquote($central_list_table) . ' '
        . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';

    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        $message = Message::error(__('Could not remove columns!'));
        $message->addMessage('<br />' . htmlspecialchars($cols) . '<br />');
        $message->addMessage(
            Message::rawError(
                $GLOBALS['dbi']->getError($GLOBALS['controllink'])
            )
        );
    }
    return $message;
}

/**
 * make the columns of given tables consistent with central list of columns.
 * Updates only those columns which are not being referenced.
 *
 * @param string $db              current database
 * @param array  $selected_tables list of selected tables.
 *
 * @return true|PMA\libraries\Message
 */
function PMA_makeConsistentWithList($db, $selected_tables)
{
    $message = true;
    foreach ($selected_tables as $table) {
        $query = 'ALTER TABLE ' . Util::backquote($table);
        $has_list = PMA_getCentralColumnsFromTable($db, $table, true);
        $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
        foreach ($has_list as $column) {
            $column_status = PMA_checkChildForeignReferences(
                $db, $table, $column['col_name']
            );
            //column definition can only be changed if
            //it is not referenced by another column
            if ($column_status['isEditable']) {
                $query .= ' MODIFY ' . Util::backquote($column['col_name']) . ' '
                    . Util::sqlAddSlashes($column['col_type']);
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
                    if ($column['col_default'] != 'CURRENT_TIMESTAMP') {
                        $query .= ' DEFAULT \'' . Util::sqlAddSlashes(
                            $column['col_default']
                        ) . '\'';
                    } else {
                        $query .= ' DEFAULT ' . Util::sqlAddSlashes(
                            $column['col_default']
                        );
                    }
                }
                $query .= ',';
            }
        }
        $query = trim($query, " ,") . ";";
        if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['userlink'])) {
            if ($message === true) {
                $message = Message::error(
                    $GLOBALS['dbi']->getError($GLOBALS['userlink'])
                );
            } else {
                $message->addMessage('<br />');
                $message->addMessage(
                    $GLOBALS['dbi']->getError($GLOBALS['userlink'])
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
 * otherwise only column_name is returned
 *
 * @return array columns present in central list from given table of given db.
 */
function PMA_getCentralColumnsFromTable($db, $table, $allFields=false)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return array();
    }
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $fields = (array) $GLOBALS['dbi']->getColumnNames(
        $db, $table, $GLOBALS['userlink']
    );
    $cols = '';
    foreach ($fields as $col_select) {
        $cols .= '\'' . Util::sqlAddSlashes($col_select) . '\',';
    }
    $cols = trim($cols, ',');
    $has_list = PMA_findExistingColNames($db, $cols, $allFields);
    if (! empty($has_list)) {
        return (array)$has_list;
    } else {
        return array();
    }
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
 * @return true|PMA\libraries\Message
 */
function PMA_updateOneColumn($db, $orig_col_name, $col_name, $col_type,
    $col_attribute,$col_length, $col_isNull, $collation, $col_extra, $col_default
) {
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $centralTable = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($cfgCentralColumns['db'], $GLOBALS['controllink']);
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
        $query = PMA_getInsertQuery($col_name, $def, $db, $centralTable);
    } else {
        $query = 'UPDATE ' . Util::backquote($centralTable)
            . ' SET col_type = \'' . Util::sqlAddSlashes($col_type) . '\''
            . ', col_name = \'' . Util::sqlAddSlashes($col_name) . '\''
            . ', col_length = \'' . Util::sqlAddSlashes($col_length) . '\''
            . ', col_isNull = ' . $col_isNull
            . ', col_collation = \'' . Util::sqlAddSlashes($collation) . '\''
            . ', col_extra = \''
            . implode(',', array($col_extra, $col_attribute)) . '\''
            . ', col_default = \'' . Util::sqlAddSlashes($col_default) . '\''
            . ' WHERE db_name = \'' . Util::sqlAddSlashes($db) . '\' '
            . 'AND col_name = \'' . Util::sqlAddSlashes($orig_col_name)
            . '\'';
    }
    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        return Message::error(
            $GLOBALS['dbi']->getError($GLOBALS['controllink'])
        );
    }
    return true;
}

/**
 * Update Multiple column in central columns list if a chnage is requested
 *
 * @return true|PMA\libraries\Message
 */
function PMA_updateMultipleColumn()
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
        } else if ($col_default[$i] == 'USER_DEFINED') {
            $col_default[$i] = $_POST['field_default_value'][$i];
        }

        $message = PMA_updateOneColumn(
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
function PMA_getHTMLforTableNavigation($total_rows, $pos, $db)
{
    $max_rows = $GLOBALS['cfg']['MaxRows'];
    $pageNow = ($pos / $max_rows) + 1;
    $nbTotalPage = ceil($total_rows / $max_rows);
    $table_navigation_html = '<table style="display:inline-block;max-width:49%" '
        . 'class="navigation nospacing nopadding">'
        . '<tr>'
        . '<td class="navigation_separator"></td>';
    if ($pos - $max_rows >= 0) {
        $table_navigation_html .= '<td>'
            . '<form action="db_central_columns.php" method="post">'
            . PMA_URL_getHiddenInputs(
                $db
            )
            . '<input type="hidden" name="pos" value="' . ($pos - $max_rows) . '" />'
            . '<input type="hidden" name="total_rows" value="' . $total_rows . '"/>'
            . '<input type="submit" name="navig"'
            . ' class="ajax" '
            . 'value="&lt" />'
            . '</form>'
            . '</td>';
    }
    if ($nbTotalPage > 1) {
        $table_navigation_html .= '<td>';
        $table_navigation_html .= '<form action="db_central_columns.php'
            . '" method="post">'
            . PMA_URL_getHiddenInputs(
                $db
            )
            . '<input type="hidden" name="total_rows" value="' . $total_rows . '"/>';
        $table_navigation_html .= Util::pageselector(
            'pos', $max_rows, $pageNow, $nbTotalPage
        );
        $table_navigation_html .= '</form>'
            . '</td>';
    }
    if ($pos + $max_rows < $total_rows) {
        $table_navigation_html .= '<td>'
            . '<form action="db_central_columns.php" method="post">'
            . PMA_URL_getHiddenInputs(
                $db
            )
            . '<input type="hidden" name="pos" value="' . ($pos + $max_rows) . '" />'
            . '<input type="hidden" name="total_rows" value="' . $total_rows . '"/>'
            . '<input type="submit" name="navig"'
            . ' class="ajax" '
            . 'value="&gt" />'
            . '</form>'
            . '</td>';
    }
    $table_navigation_html .= '</form>'
        . '</td>'
        . '<td class="navigation_separator"></td>'
        . '<td>'
        . '<span>' . __('Filter rows') . ':</span>'
        . '<input type="text" class="filter_rows" placeholder="'
        . __('Search this table') . '">'
        . '</td>'
        . '<td class="navigation_separator"></td>'
        . '</tr>'
        . '</table>';

    return $table_navigation_html;
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
function PMA_getCentralColumnsTableHeader($class='', $title='', $actionCount=0)
{
    $action = '';
    if ($actionCount > 0) {
        $action .= '<th class="column_action" colspan="' . $actionCount . '">'
            . __('Action') . '</th>';
    }
    $tableheader = '<thead>';
    $tableheader .= '<tr>'
        . '<th class="' . $class . '"></th>'
        . '<th class="" style="display:none"></th>'
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
 * @param array $header_cells headers list
 *
 * @return string html for table header in central columns multi edit page
 */
function PMA_getCentralColumnsEditTableHeader($header_cells)
{
    $html = '<table id="table_columns" class="noclick"'
        . ' style="min-width: 100%;">';
    $html .= '<caption class="tblHeaders">' . __('Structure');
    $html .= '<tr>';
    foreach ($header_cells as $header_val) {
        $html .= '<th>' . $header_val . '</th>';
    }
    $html .= '</tr>';
    return $html;
}

/**
 * build the dropdown select html for tables of given database
 *
 * @param string $db current database
 *
 * @return string html dropdown for selecting table
 */
function PMA_getHTMLforTableDropdown($db)
{
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $tables = $GLOBALS['dbi']->getTables($db, $GLOBALS['userlink']);
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
function PMA_getHTMLforColumnDropdown($db, $selected_tbl)
{
    $existing_cols = PMA_getCentralColumnsFromTable($db, $selected_tbl);
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $columns = (array) $GLOBALS['dbi']->getColumnNames(
        $db, $selected_tbl, $GLOBALS['userlink']
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
 * html to display the form that let user to add a column on Central columns page
 *
 * @param int    $total_rows total number of rows in complete result set
 * @param int    $pos        offset of first result with complete result set
 * @param string $db         current database
 *
 * @return string html to add a column in the central list
 */
function PMA_getHTMLforAddCentralColumn($total_rows, $pos, $db)
{
    $columnAdd = '<table style="display:inline-block;margin-left:1%;max-width:50%" '
        . 'class="navigation nospacing nopadding">'
        . '<tr>'
        . '<td class="navigation_separator"></td>'
        . '<td style="padding:1.5% 0em">'
        . Util::getIcon(
            'centralColumns_add.png',
            __('Add column')
        )
        . '<form id="add_column" action="db_central_columns.php" method="post">'
        . PMA_URL_getHiddenInputs(
            $db
        )
        . '<input type="hidden" name="add_column" value="add">'
        . '<input type="hidden" name="pos" value="' . $pos . '" />'
        . '<input type="hidden" name="total_rows" value="' . $total_rows . '"/>'
        . PMA_getHTMLforTableDropdown($db)
        . '<select name="column-select" id="column-select">'
        . '<option value="" selected="selected">'
        . __('Select a column.') . '</option>'
        . '</select></form>'
        . '</td>'
        . '<td class="navigation_separator"></td>'
        . '</tr>'
        . '</table>';

    return $columnAdd;
}

/**
 * build html for a row in central columns table
 *
 * @param array   $row     array contains complete information of
 * a particular row of central list table
 * @param boolean $odd_row set true if the row is at odd number position
 * @param int     $row_num position the row in the table
 * @param string  $db      current database
 *
 * @return string html of a particular row in the central columns table.
 */
function PMA_getHTMLforCentralColumnsTableRow($row, $odd_row, $row_num, $db)
{
    $tableHtml = '<tr data-rownum="' . $row_num . '" id="f_' . $row_num . '" '
        . 'class="' . ($odd_row ? 'odd' : 'even') . '">'
        . PMA_URL_getHiddenInputs(
            $db
        )
        . '<input type="hidden" name="edit_save" value="save">'
        . '<td class="nowrap">'
        . '<input type="checkbox" class="checkall" name="selected_fld[]" '
        . 'value="' . htmlspecialchars($row['col_name']) . '" '
        . 'id="checkbox_row_' . $row_num . '"/>'
        . '</td>'
        . '<td id="edit_' . $row_num . '" class="edit center">'
        . '<a href="#">' . Util::getIcon('b_edit.png', __('Edit')) . '</a></td>'
        . '<td class="del_row" data-rownum = "' . $row_num . '">'
        . '<a hrf="#">' . Util::getIcon('b_drop.png', __('Delete')) . '</a>'
        . '<input type="submit" data-rownum = "' . $row_num . '"'
        . ' class="edit_cancel_form" value="Cancel"></td>'
        . '<td id="save_' . $row_num . '" style="display:none">'
        . '<input type="submit" data-rownum = "' . $row_num . '"'
        . ' class="edit_save_form" value="Save"></td>';

    $tableHtml .=
        '<td name="col_name" class="nowrap">'
        . '<span>' . htmlspecialchars($row['col_name']) . '</span>'
        . '<input name="orig_col_name" type="hidden" '
        . 'value="' . htmlspecialchars($row['col_name']) . '">'
        . PMA\libraries\Template::get('columns_definitions/column_name')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 0,
                'ci_offset' => 0,
                'columnMeta' => array(
                    'Field'=>$row['col_name']
                ),
                'cfgRelation' => array(
                    'centralcolumnswork' => false
                )
                )
            )
        . '</td>';
    $tableHtml .=
        '<td name = "col_type" class="nowrap"><span>'
        . htmlspecialchars($row['col_type']) . '</span>'
        . PMA\libraries\Template::get('columns_definitions/column_type')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 1,
                'ci_offset' => 0,
                'type_upper' => mb_strtoupper($row['col_type']),
                'columnMeta' => array()
                )
            )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_length">'
        . '<span>' . ($row['col_length']?htmlspecialchars($row['col_length']):"")
        . '</span>'
        . PMA\libraries\Template::get('columns_definitions/column_length')->render(
            array(
                'columnNumber' => $row_num,
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
            || $row['col_default'] == 'NULL'
        ) {
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
        . PMA\libraries\Template::get('columns_definitions/column_default')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 3,
                'ci_offset' => 0,
                'type_upper' => mb_strtoupper($row['col_type']),
                'columnMeta' => $meta
                )
            )
        . '</td>';

    $tableHtml .=
        '<td name="collation" class="nowrap">'
        . '<span>' . htmlspecialchars($row['col_collation']) . '</span>'
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION, 'field_collation[' . $row_num . ']',
            'field_' . $row_num . '_4', $row['col_collation'], false
        )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_attribute">'
        . '<span>' .
        ($row['col_attribute']
            ? htmlspecialchars($row['col_attribute']) : "" )
        . '</span>'
        . PMA\libraries\Template::get('columns_definitions/column_attribute')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 5,
                'ci_offset' => 0,
                'extracted_columnspec' => array(),
                'columnMeta' => $row['col_attribute'],
                'submit_attribute' => false,
                )
            )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_isNull">'
        . '<span>' . ($row['col_isNull'] ? __('Yes') : __('No'))
        . '</span>'
        . PMA\libraries\Template::get('columns_definitions/column_null')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 6,
                'ci_offset' => 0,
                'columnMeta' => array(
                    'Null' => $row['col_isNull']
                )
                )
            )
        . '</td>';

    $tableHtml .=
        '<td class="nowrap" name="col_extra"><span>'
        . htmlspecialchars($row['col_extra']) . '</span>'
        . PMA\libraries\Template::get('columns_definitions/column_extra')->render(
            array(
                'columnNumber' => $row_num,
                'ci' => 7,
                'ci_offset' => 0,
                'columnMeta' => array('Extra'=>$row['col_extra'])
            )
        )
        . '</td>';

    $tableHtml .= '</tr>';

    return $tableHtml;
}

/**
 * build html for editing a row in central columns table
 *
 * @param array   $row     array contains complete information of
 * a particular row of central list table
 * @param boolean $odd_row set true if the row is at odd number position
 * @param int     $row_num position the row in the table
 *
 * @return string html of a particular row in the central columns table.
 */
function PMA_getHTMLforCentralColumnsEditTableRow($row, $odd_row, $row_num)
{
    $tableHtml = '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
        . '<input name="orig_col_name[' . $row_num . ']" type="hidden" '
        . 'value="' . htmlspecialchars($row['col_name']) . '">'
        . '<td name="col_name" class="nowrap">'
        . PMA\libraries\Template::get('columns_definitions/column_name')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 0,
                'ci_offset' => 0,
                'columnMeta' => array(
                    'Field' => $row['col_name']
                ),
                'cfgRelation' => array(
                    'centralcolumnswork' => false
                )
                )
            )
        . '</td>';
    $tableHtml .=
        '<td name = "col_type" class="nowrap">'
        . PMA\libraries\Template::get('columns_definitions/column_type')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 1,
                'ci_offset' => 0,
                'type_upper' => mb_strtoupper($row['col_type']),
                'columnMeta' => array()
                )
            )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_length">'
        . PMA\libraries\Template::get('columns_definitions/column_length')->render(
            array(
                'columnNumber' => $row_num,
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
            || $row['col_default'] == 'NULL'
        ) {
            $meta['DefaultType'] = $row['col_default'];
        } else {
            $meta['DefaultType'] = 'USER_DEFINED';
            $meta['DefaultValue'] = $row['col_default'];
        }
    }
    $tableHtml .=
        '<td class="nowrap" name="col_default">'
        . PMA\libraries\Template::get('columns_definitions/column_default')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 3,
                'ci_offset' => 0,
                'type_upper' => mb_strtoupper($row['col_default']),
                'columnMeta' => $meta
                )
            )
        . '</td>';
    $tableHtml .=
        '<td name="collation" class="nowrap">'
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION, 'field_collation[' . $row_num . ']',
            'field_' . $row_num . '_4', $row['col_collation'], false
        )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_attribute">'
        . PMA\libraries\Template::get('columns_definitions/column_attribute')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 5,
                'ci_offset' => 0,
                'extracted_columnspec' => array(
                    'attribute' => $row['col_attribute']
                ),
                'columnMeta' => array(),
                'submit_attribute' => false,
                )
            )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_isNull">'
        . PMA\libraries\Template::get('columns_definitions/column_null')
            ->render(
                array(
                'columnNumber' => $row_num,
                'ci' => 6,
                'ci_offset' => 0,
                'columnMeta' => array(
                    'Null' => $row['col_isNull']
                )
                )
            )
        . '</td>';

    $tableHtml .=
        '<td class="nowrap" name="col_extra">'
        . PMA\libraries\Template::get('columns_definitions/column_extra')->render(
            array(
                'columnNumber' => $row_num,
                'ci' => 7,
                'ci_offset' => 0,
                'columnMeta' => array('Extra' => $row['col_extra'])
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
function PMA_getCentralColumnsListRaw($db, $table)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return json_encode(array());
    }
    $centralTable = $cfgCentralColumns['table'];
    if (empty($table) || $table == '') {
        $query = 'SELECT * FROM ' . Util::backquote($centralTable) . ' '
            . 'WHERE db_name = \'' . $db . '\';';
    } else {
        $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
        $columns = (array) $GLOBALS['dbi']->getColumnNames(
            $db, $table, $GLOBALS['userlink']
        );
        $cols = '';
        foreach ($columns as $col_select) {
            $cols .= '\'' . Util::sqlAddSlashes($col_select) . '\',';
        }
        $cols = trim($cols, ',');
        $query = 'SELECT * FROM ' . Util::backquote($centralTable) . ' '
            . 'WHERE db_name = \'' . $db . '\'';
        if ($cols) {
            $query .= ' AND col_name NOT IN (' . $cols . ')';
        }
        $query .= ';';
    }
    $GLOBALS['dbi']->selectDb($cfgCentralColumns['db'], $GLOBALS['controllink']);
    $columns_list = (array)$GLOBALS['dbi']->fetchResult(
        $query, null, null, $GLOBALS['controllink']
    );
    PMA_handleColumnExtra($columns_list);
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
function PMA_getCentralColumnsTableFooter($pmaThemeImage, $text_dir)
{
    $html_output = Util::getWithSelected(
        $pmaThemeImage, $text_dir, "tableslistcontainer"
    );
    $html_output .= Util::getButtonOrImage(
        'edit_central_columns', 'mult_submit change_central_columns',
        'submit_mult_change', __('Edit'), 'b_edit.png', 'edit central columns'
    );
    $html_output .= Util::getButtonOrImage(
        'delete_central_columns', 'mult_submit',
        'submit_mult_central_columns_remove',
        __('Delete'), 'b_drop.png',
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
function PMA_getCentralColumnsEditTableFooter()
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
function PMA_handleColumnExtra(&$columns_list)
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
 * @param string $db current database
 *
 * @return string html of the form to let user add a new user defined column to the
 *                list
 */
function PMA_getHTMLforAddNewColumn($db)
{
    $addNewColumn = '<div id="add_col_div"><a href="#">'
        . '<span>+</span> ' . __('Add new column') . '</a>'
        . '<form id="add_new" style="min-width:100%;display:none" '
        . 'method="post" action="db_central_columns.php">'
        . PMA_URL_getHiddenInputs(
            $db
        )
        . '<input type="hidden" name="add_new_column" value="add_new_column">'
        . '<table>';
    $addNewColumn .= PMA_getCentralColumnsTableHeader();
    $addNewColumn .= '<tr>'
        . '<td></td>'
        . '<td name="col_name" class="nowrap">'
        . PMA\libraries\Template::get('columns_definitions/column_name')
            ->render(
                array(
                'columnNumber' => 0,
                'ci' => 0,
                'ci_offset' => 0,
                'columnMeta' => array(),
                'cfgRelation' => array(
                    'centralcolumnswork' => false
                )
                )
            )
        . '</td>'
        . '<td name = "col_type" class="nowrap">'
        . PMA\libraries\Template::get('columns_definitions/column_type')
            ->render(
                array(
                'columnNumber' => 0,
                'ci' => 1,
                'ci_offset' => 0,
                'type_upper' => '',
                'columnMeta' => array()
                )
            )
        . '</td>'
        . '<td class="nowrap" name="col_length">'
        . PMA\libraries\Template::get('columns_definitions/column_length')->render(
            array(
                'columnNumber' => 0,
                'ci' => 2,
                'ci_offset' => 0,
                'length_values_input_size' => 8,
                'length_to_display' => ''
            )
        )
        . '</td>'
        . '<td class="nowrap" name="col_default">'
        . PMA\libraries\Template::get('columns_definitions/column_default')
            ->render(
                array(
                'columnNumber' => 0,
                'ci' => 3,
                'ci_offset' => 0,
                'type_upper' => '',
                'columnMeta' => array()
                )
            )
        . '</td>'
        . '<td name="collation" class="nowrap">'
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION, 'field_collation[0]',
            'field_0_4', null, false
        )
        . '</td>'
        . '<td class="nowrap" name="col_attribute">'
        . PMA\libraries\Template::get('columns_definitions/column_attribute')
            ->render(
                array(
                'columnNumber' => 0,
                'ci' => 5,
                'ci_offset' => 0,
                'extracted_columnspec' => array(),
                'columnMeta' => array(),
                'submit_attribute' => false,
                )
            )
        . '</td>'
        . '<td class="nowrap" name="col_isNull">'
        . PMA\libraries\Template::get('columns_definitions/column_null')
            ->render(
                array(
                'columnNumber' => 0,
                'ci' => 6,
                'ci_offset' => 0,
                'columnMeta' => array()
                )
            )
        . '</td>'
        . '<td class="nowrap" name="col_extra">'
        . PMA\libraries\Template::get('columns_definitions/column_extra')->render(
            array(
                'columnNumber' => 0,
                'ci' => 7,
                'ci_offset' => 0,
                'columnMeta' => array()
            )
        )
        . '</td>'
        . ' <td>'
        . '<input id="add_column_save" type="submit" '
        . ' value="Save"/></td>'
        . '</tr>';
    $addNewColumn .= '</table></form></div>';
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
function PMA_getHTMLforEditingPage($selected_fld,$selected_db)
{
    $html = '<form id="multi_edit_central_columns">';
    $header_cells = array(
        __('Name'), __('Type'), __('Length/Values'), __('Default'),
        __('Collation'), __('Attributes'), __('Null'), __('A_I')
    );
    $html .= PMA_getCentralColumnsEditTableHeader($header_cells);
    $selected_fld_safe = array();
    foreach ($selected_fld as $key) {
        $selected_fld_safe[] = Util::sqlAddSlashes($key);
    }
    $columns_list = implode("','", $selected_fld_safe);
    $columns_list = "'" . $columns_list . "'";
    $list_detail_cols = PMA_findExistingColNames($selected_db, $columns_list, true);
    $odd_row = false;
    $row_num = 0;
    foreach ($list_detail_cols as $row) {
        $tableHtmlRow = PMA_getHTMLforCentralColumnsEditTableRow(
            $row, $odd_row, $row_num
        );
        $html .= $tableHtmlRow;
        $odd_row = !$odd_row;
        $row_num++;
    }
    $html .= '</table>';
    $html .= PMA_getCentralColumnsEditTableFooter();
    $html .= '</form>';
    return $html;
}
