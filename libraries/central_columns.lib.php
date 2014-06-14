<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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

    if ($cfgRelation['central_columnswork']) {
        $cfgCentralColumns = array(
            'user'  => $GLOBALS['cfg']['Server']['user'],
            'db'    => $GLOBALS['cfg']['Server']['pmadb'],
            'table' => $GLOBALS['cfg']['Server']['central_columns'],
        );
    } else {
        $cfgCentralColumns = false;
    }

    return $cfgCentralColumns;
}

/**
 * get $num columns of given database from central columnslist
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
    $pmadb = $GLOBALS['cfg']['Server']['pmadb'];
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    $central_list_table = $GLOBALS['cfg']['Server']['central_columns'];
    //get current values of $db from central column list
    if ($num == 0) {
        $query = 'SELECT * FROM ' . PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\';';
    } else {
        $query = 'SELECT * FROM ' . PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\''
            . 'LIMIT ' . $from . ', ' . $num . ';';
    }
    $has_list = (array) $GLOBALS['dbi']->fetchResult(
        $query, null, null, $GLOBALS['controllink']
    );
    return $has_list;
}

/* to do block, need to complete
function PMA_getCentralColumnsCount($db)
{
    $pmadb = $GLOBALS['cfg']['Server']['pmadb'];
    $GLOBALS['dbi']->selectDb($pmadb);
    $central_list_table = $GLOBALS['cfg']['Server']['central_columns'];
    $query = 'SELECT count(db_name) FROM ' .
 *              PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\';';
    return $GLOBALS['dbi']->fetchResult($query);
}*/
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
        $query = 'SELECT * FROM ' . PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';
    } else {
        $query = 'SELECT col_name FROM '
            . PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';
    }
    $has_list = (array) $GLOBALS['dbi']->fetchResult(
        $query, null, null, $GLOBALS['controllink']
    );
    return $has_list;
}

/**
 * return error message to be displayed if central columns
 * configurartion storage is not completely configured
 *
 * @return PMA_Message
 */
function PMA_configErrorMessage()
{
    return PMA_Message::error(
        __(
            'Central list of columns configuration Storage '
            . 'is not completely configured!'
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
    if (isset($def['Type'])) {
        $extracted_columnspec = PMA_Util::extractColumnSpec($def['Type']);
        $type = $extracted_columnspec['type'];
        $length = $extracted_columnspec['spec_in_brackets'];
    }
    $collation = isset($def['Collation'])?$def['Collation']:"";
    $isNull = ($def['Null'] == "NO")?0:1;
    $extra = isset($def['Extra'])?$def['Extra']:"";
    $default = isset($def['Default'])?$def['Default']:"";
    $insQuery = 'INSERT INTO '
    . PMA_Util::backquote($central_list_table) . ' '
    . 'VALUES ( \'' . PMA_Util::sqlAddSlashes($db) . '\' ,'
    . '\'' . PMA_Util::sqlAddSlashes($column) . '\',\''
    . PMA_Util::sqlAddSlashes($type) . '\','
    . '\'' . PMA_Util::sqlAddSlashes($length) . '\',\''
    . PMA_Util::sqlAddSlashes($collation) . '\','
    . '\'' . PMA_Util::sqlAddSlashes($isNull) . '\','
    . '\'' . PMA_Util::sqlAddSlashes($extra) . '\',\''
    . PMA_Util::sqlAddSlashes($default) . '\');';
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
 * @return true|PMA_Message
 */
function PMA_syncUniqueColumns($field_select, $isTable=true, $table=null)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $db = $_POST['db'];
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
                $cols .= "'" . PMA_Util::sqlAddSlashes($field) . "',";
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
        if ($table == null) {
            $table = $_POST['table'];
        }
        foreach ($field_select as $column) {
            $cols .= "'" . PMA_Util::sqlAddSlashes($column) . "',";
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
    if ($existingCols) {
        $existingCols = implode(",", array_unique($existingCols));
        $message = PMA_Message::notice(
            sprintf(
                __(
                    'Could not add %1$s as they already exist in central list!'
                ), htmlspecialchars($existingCols)
            )
        );
        $message->addMessage('<br /><br />');
        $message->addMessage(
            PMA_Message::notice(
                "Please remove them first "
                . "from central list if you want to update above columns"
            )
        );
    }
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    if ($insQuery) {
        foreach ($insQuery as $query) {
            if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
                $message = PMA_Message::error(__('Could not add columns!'));
                $message->addMessage('<br /><br />');
                $message->addMessage(
                    PMA_Message::rawError(
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
 * @param array $field_select if $isTable selectd list of tables otherwise
 * selected list of columns to remove from central list
 * @param bool  $isTable      if passed array is of tables or columns
 *
 * @return true|PMA_Message
 */
function PMA_deleteColumnsFromList($field_select, $isTable=true)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $db = $_POST['db'];
    $pmadb = $cfgCentralColumns['db'];
    $central_list_table = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $message = true;
    $colNotExist = array();
    $fields = array();
    $cols ="";
    if ($isTable) {
        $cols = '';
        foreach ($field_select as $table) {
            $fields[$table] = (array) $GLOBALS['dbi']->getColumnNames(
                $db, $table, $GLOBALS['userlink']
            );
            foreach ($fields[$table] as $col_select) {
                $cols .= '\'' . PMA_Util::sqlAddSlashes($col_select) . '\',';
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
            $cols .= '\'' . PMA_Util::sqlAddSlashes($col_select) . '\',';
        }
        $cols = trim($cols, ',');
        $has_list = PMA_findExistingColNames($db, $cols);
        foreach ($field_select as $column) {
            if (!in_array($column, $has_list)) {
                $colNotExist[] = "'" . $column . "'";
            }
        }
    }
    if ($colNotExist) {
            $colNotExist = implode(",", array_unique($colNotExist));
            $message = PMA_Message::notice(
                sprintf(
                    __(
                        'Couldn\'t remove Column(s) %1$s '
                        . 'as they don\'t exist in central columns list!'
                    ), htmlspecialchars($colNotExist)
                )
            );
    }
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);

    $query = 'DELETE FROM ' . PMA_Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';

    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not remove columns!'));
        $message->addMessage('<br />' . htmlspecialchars($cols) . '<br />');
        $message->addMessage(
            PMA_Message::rawError(
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
 * @return true|PMA_Message
 */
function PMA_makeConsistentWithList($db, $selected_tables)
{
    $message = true;
    foreach ($selected_tables as $table) {
        $query = 'ALTER TABLE ' . PMA_Util::backquote($table);
        $has_list = PMA_getCentralColumnsFromTable($db, $table, true);
        $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
        foreach ($has_list as $column) {
            $column_status = PMA_checkChildForeignReferences(
                $db, $table, $column['col_name']
            );
            //column definition can only be changed if
            //it is not referenced by another column
            if ($column_status['isEditable']) {
                $query .= ' MODIFY ' . PMA_Util::backquote($column['col_name']) . ' '
                    . PMA_Util::sqlAddSlashes($column['col_type']);
                if ($column['col_length']) {
                    $query .= '(' . $column['col_length'] . ')';
                }
                if ($column['col_isNull']) {
                    $query .= ' NULL';
                } else {
                    $query .= ' NOT NULL';
                }
                $query .= ' ' . $column['col_extra'];
                if ($column['col_default']) {
                    if ($column['col_default'] != 'CURRENT_TIMESTAMP') {
                        $query .= ' DEFAULT \'' . PMA_Util::sqlAddSlashes(
                            $column['col_default']
                        ) . '\'';
                    } else {
                        $query .= ' DEFAULT ' . PMA_Util::sqlAddSlashes(
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
                $message = PMA_Message::error(
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
        $cols .= '\'' . PMA_Util::sqlAddSlashes($col_select) . '\',';
    }
    $cols = trim($cols, ',');
    $has_list = PMA_findExistingColNames($db, $cols, $allFields);
    if (isset($has_list) && $has_list) {
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
 * @param string $col_length    new column length
 * @param int    $col_isNull    value 1 if new column isNull is true, 0 othewise
 * @param string $collation     new column collation
 * @param string $col_extra     new column extra property
 * @param string $col_default   new column default value
 *
 * @return true|PMA_Message
 */
function PMA_updateOneColumn($db, $orig_col_name, $col_name, $col_type,
    $col_length, $col_isNull, $collation, $col_extra, $col_default
) {
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $centralTable = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($cfgCentralColumns['db'], $GLOBALS['controllink']);
        $query = 'UPDATE ' . PMA_Util::backquote($centralTable)
                . ' SET col_type = \'' . PMA_Util::sqlAddSlashes($col_type) . '\''
                . ',col_name = \'' . PMA_Util::sqlAddSlashes($col_name) . '\''
                . ', col_length = \'' . PMA_Util::sqlAddSlashes($col_length) . '\''
                . ', col_isNull = ' . $col_isNull
                . ', col_collation = \'' . PMA_Util::sqlAddSlashes($collation) . '\''
                . ', col_extra = \'' . PMA_Util::sqlAddSlashes($col_extra) . '\''
                . ', col_default = \'' . PMA_Util::sqlAddSlashes($col_default) . '\''
                . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\' '
                . 'AND col_name = \'' . PMA_Util::sqlAddSlashes($orig_col_name)
                . '\'';
    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        return PMA_Message::error(
            $GLOBALS['dbi']->getError($GLOBALS['controllink'])
        );
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
 * @return html for table navigation in Central columns page
 */
function PMA_getHTMLforTableNavigation($total_rows, $pos, $db)
{
    $max_rows = $GLOBALS['cfg']['MaxRows'];
    $pageNow = ($pos / $max_rows) + 1;
    $nbTotalPage = ceil($total_rows / $max_rows);
    $table_navigation_html = '<table style="display:inline-block" '
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
        $table_navigation_html .= PMA_Util::pageselector(
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
 * @return html for table header in central columns view/edit page
 */
function PMA_getCentralColumnsTableHeader()
{
    $tableheader = '<thead>';
    $tableheader .= '<tr>'
        . '<th colspan="2">' . __('Action') . '</th>'
        . '<th class="" style="display:none"></th>'
        . '<th class="column_heading" title="Click to sort" data-column="name">'
        . __('Name') . '</th>'
        . '<th class="column_heading" title="Click to sort" data-column="type">'
        . __('Type') . '</th>'
        . '<th class="column_heading" title="Click to sort" data-column="length">'
        . __('Length/Values') . '</th>'
        . '<th class="column_heading" title="Click to sort" data-column="collation">'
        . __('Collation') . '</th>'
        . '<th class="column_heading" title="Click to sort" data-column="isnull">'
        . __('Null') . '</th>'
        . '<th class="column_heading" title="Click to sort" data-column="extra">'
        . __('Extra') . '</th>'
        . '<th class="column_heading" title="Click to sort" data-column="default">'
        . __('Default') . '</th>'
        . '</tr>';
    $tableheader .= '</thead>';
    return $tableheader;
}

/**
 * build the dropdown select html for tables of given database
 *
 * @param string $db current database
 *
 * @return html dropdown for secting table
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
 * buld dropdown select html to select column in selected table,
 * include only columns which are not already in central list
 *
 * @param string $db           current database to which selected table blongs
 * @param string $selected_tbl selected table
 *
 * @return html to select column
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
 * html to display the form that let user to add a column on Cental columns page
 *
 * @param int    $total_rows total number of rows in complete result set
 * @param int    $pos        offset of first result with complete result set
 * @param string $db         current database
 *
 * @return html to add a column in the central list
 */
function PMA_getHTMLforAddCentralColumn($total_rows, $pos, $db)
{
    $columnAdd = '<table style="display:inline-block;margin-left:2%;width:50%" '
        . 'class="navigation nospacing nopadding">'
        . '<tr>'
        . '<td class="navigation_separator"></td>'
        . '<td style="padding:1.5% 0em">'
        . PMA_Util::getIcon(
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
 * @param array   $row     array containg complete information of
 * a particualar row of central list table
 * @param boolean $odd_row set true if the row is at odd number position
 * @param int     $row_num position the row in the table
 * @param string  $db      current database
 *
 * @return html of a particular row in the central columns table.
 */
function PMA_getHTMLforCentralColumnsTableRow($row, $odd_row, $row_num, $db)
{
    $tableHtml = '<tr data-rownum="' . $row_num . '" id="f_' . $row_num . '" '
        . 'class="' . ($odd_row ? 'odd' : 'even') . '">'
        . PMA_URL_getHiddenInputs(
            $db
        )
        . '<input type="hidden" name="edit_save" value="save">'
        . '<td id="edit_' . $row_num . '" class="edit center">'
        . '<a href="#">' . PMA_Util::getIcon('b_edit.png', __('Edit')) . '</a></td>'
        . '<td class="del_row" data-rownum = "' . $row_num . '">'
        . '<a hrf="#">' . PMA_Util::getIcon('b_drop.png', __('Delete')) . '</a>'
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
        . '<input class="edit_box" name="col_name" type="text" '
        . 'value="' . htmlspecialchars($row['col_name']) . '"></td>';
    $tableHtml .=
        '<td name = "col_type" class="nowrap"><span>'
        . htmlspecialchars($row['col_type']) . '</span>'
        . '<select name="col_type">' .
        PMA_Util::getSupportedDatatypes(true, strtoupper($row['col_type']))
        . '</select></td>';
    $tableHtml .=
        '<td class="nowrap" name="col_length">'
        . '<span>' . ($row['col_length']?htmlspecialchars($row['col_length']):"")
        . '</span>'
        . '<input class="edit_box" type="number" name="col_length" '
        . 'value="' . ($row['col_length']?htmlspecialchars($row['col_length']):"")
        . '">'
        . '</td>';

    $tableHtml .=
        '<td name="collation" class="nowrap">'
        . '<span>' . htmlspecialchars($row['col_collation']) . '</span>'
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION, null,
            null, $row['col_collation']
        )
        . '</td>';
    $tableHtml .=
        '<td class="nowrap" name="col_isNull">'
        . '<span>' . ($row['col_isNull'] ? __('Yes') : __('No'))
        . '</span><input type="checkbox" name="col_isNull" '
        . '' . ($row['col_isNull'] ?"checked" : "") . '/>'
        . '</td>';

    $tableHtml .=
        '<td class="nowrap" name="col_extra"><span>'
        . htmlspecialchars($row['col_extra']) . '</span>'
        . '<input type="text" name="col_extra" value="'
        . htmlspecialchars($row['col_extra']) . '"/>'
        . '</td>';

    $tableHtml .=
        '<td class="nowrap" name="col_default"><span>' . (isset($row['col_default'])
        ? htmlspecialchars($row['col_default']) : 'None')
        . '</span><input type="text" name="col_default" '
        . 'value="' . htmlspecialchars($row['col_default']) . '"/>'
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
 * @return encoded list of columns present in central list for the given database
 */
function PMA_getCentralColumnsListRaw($db, $table)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return array();
    }
    $centralTable = $cfgCentralColumns['table'];
    if (empty($table) || $table == '') {
        $query = 'SELECT * FROM ' . PMA_Util::backquote($centralTable) . ' '
                . 'WHERE db_name = \'' . $db . '\';';
    } else {
        $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
        $columns = (array) $GLOBALS['dbi']->getColumnNames(
            $db, $table, $GLOBALS['userlink']
        );
        $cols = '';
        foreach ($columns as $col_select) {
            $cols .= '\'' . PMA_Util::sqlAddSlashes($col_select) . '\',';
        }
        $cols = trim($cols, ',');
        $query = 'SELECT * FROM ' . PMA_Util::backquote($centralTable) . ' '
                . 'WHERE db_name = \'' . $db . '\' '
                . 'AND col_name NOT IN (' . $cols . ');';
    }
    $GLOBALS['dbi']->selectDb($cfgCentralColumns['db'], $GLOBALS['controllink']);
    $columns_list = (array)$GLOBALS['dbi']->fetchResult(
        $query, null, null, $GLOBALS['controllink']
    );
    return json_encode($columns_list);
}

?>
