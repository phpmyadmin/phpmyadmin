<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions for the sql executor
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Initialize some parameters needed to display results
 *
 * @param string  $sql_query SQL statement
 * @param boolean $is_select select query or not
 *
 * @return  array set of parameters
 *
 * @access  public
 */
function PMA_getDisplayPropertyParams($sql_query, $is_select)
{
    $is_explain = $is_count = $is_export = $is_delete = $is_insert = $is_affected = $is_show = $is_maint = $is_analyse = $is_group = $is_func = $is_replace = false;

    if ($is_select) {
        $is_group = preg_match('@(GROUP[[:space:]]+BY|HAVING|SELECT[[:space:]]+DISTINCT)[[:space:]]+@i', $sql_query);
        $is_func =  ! $is_group && (preg_match('@[[:space:]]+(SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND)\s*\(@i', $sql_query));
        $is_count = ! $is_group && (preg_match('@^SELECT[[:space:]]+COUNT\((.*\.+)?.*\)@i', $sql_query));
        $is_export   = preg_match('@[[:space:]]+INTO[[:space:]]+OUTFILE[[:space:]]+@i', $sql_query);
        $is_analyse  = preg_match('@[[:space:]]+PROCEDURE[[:space:]]+ANALYSE@i', $sql_query);
    } elseif (preg_match('@^EXPLAIN[[:space:]]+@i', $sql_query)) {
        $is_explain  = true;
    } elseif (preg_match('@^DELETE[[:space:]]+@i', $sql_query)) {
        $is_delete   = true;
        $is_affected = true;
    } elseif (preg_match('@^(INSERT|LOAD[[:space:]]+DATA|REPLACE)[[:space:]]+@i', $sql_query)) {
        $is_insert   = true;
        $is_affected = true;
        if (preg_match('@^(REPLACE)[[:space:]]+@i', $sql_query)) {
            $is_replace = true;
        }
    } elseif (preg_match('@^UPDATE[[:space:]]+@i', $sql_query)) {
        $is_affected = true;
    } elseif (preg_match('@^[[:space:]]*SHOW[[:space:]]+@i', $sql_query)) {
        $is_show     = true;
    } elseif (preg_match('@^(CHECK|ANALYZE|REPAIR|OPTIMIZE)[[:space:]]+TABLE[[:space:]]+@i', $sql_query)) {
        $is_maint    = true;
    }

    return array(
        $is_group, $is_func, $is_count, $is_export, $is_analyse, $is_explain,
        $is_delete, $is_affected, $is_insert, $is_replace,$is_show, $is_maint
    );
}

/**
 * Get the database name inside a USE query
 *
 * @param string $sql       SQL query
 * @param array  $databases array with all databases
 *
 * @return strin $db new database name
 */
function PMA_getNewDatabase($sql, $databases)
{
    $db = '';
    // loop through all the databases
    foreach ($databases as $database) {
        if (strpos($sql, $database['SCHEMA_NAME']) !== false) {
            $db = $database;
            break;
        }
    }
    return $db;
}

/**
 * Get the table name in a sql query
 * If there are several tables in the SQL query,
 * first table wil lreturn
 *
 * @param string $sql    SQL query
 * @param array  $tables array of names in current database
 *
 * @return string $table table name
 */
function PMA_getTableNameBySQL($sql, $tables)
{
    $table = '';

    // loop through all the tables in the database
    foreach ($tables as $tbl) {
        if (strpos($sql, $tbl)) {
            $table .= ' ' . $tbl;
        }
    }

    if (count(explode(' ', trim($table))) > 1) {
        $tmp_array = explode(' ', trim($table));
        return $tmp_array[0];
    }

    return trim($table);
}


/**
 * Generate table html when SQL statement have multiple queries
 * which return displayable results
 *
 * @param PMA_DisplayResults $displayResultsObject       object
 * @param string             $db                         database name
 * @param array              $sql_data                   information about
 *                                                        SQL statement
 * @param string             $goto                       URL to go back in case
 *                                                        of errors
 * @param string             $pmaThemeImage              path for theme images
 *                                                        directory
 * @param string             $text_dir                   text direction
 * @param string             $printview                  whether printview is enabled
 * @param string             $url_query                  URL query
 * @param array              $disp_mode                  the display mode
 * @param string             $sql_limit_to_append        limit clause
 * @param bool               $resultSetContainsUniqueKey result contains a unique key
 *
 * @return string   $table_html   html content
 */
function getTableHtmlForMultipleQueries(
    $displayResultsObject, $db, $sql_data, $goto, $pmaThemeImage,
    $text_dir, $printview, $url_query, $disp_mode, $sql_limit_to_append,
    $resultSetContainsUniqueKey
) {
    $table_html = '';

    $tables_array = PMA_DBI_getTables($db);
    $databases_array = PMA_DBI_getDatabasesFull();
    $multi_sql = implode(";", $sql_data['valid_sql']);
    $querytime_before = array_sum(explode(' ', microtime()));

    // Assignment for variable is not needed since the results are
    // looiping using the connection
    @PMA_DBI_tryMultiQuery($multi_sql);

    $querytime_after = array_sum(explode(' ', microtime()));
    $querytime = $querytime_after - $querytime_before;
    $sql_no = 0;

    do {
        $analyzed_sql = array();
        $is_affected = false;

        $result = PMA_DBI_storeResult();
        $fields_meta = ($result !== false)
            ? PMA_DBI_getFieldsMeta($result)
            : array();
        $fields_cnt  = count($fields_meta);

        // Initialize needed params related to each query in multiquery statement
        if (isset($sql_data['valid_sql'][$sql_no])) {
            // 'Use' query can change the database
            if (stripos($sql_data['valid_sql'][$sql_no], "use ")) {
                $db = PMA_getNewDatabase(
                    $sql_data['valid_sql'][$sql_no],
                    $databases_array
                );
            }
            $parsed_sql = PMA_SQP_parse($sql_data['valid_sql'][$sql_no]);
            $table = PMA_getTableNameBySQL(
                $sql_data['valid_sql'][$sql_no],
                $tables_array
            );

            $analyzed_sql = PMA_SQP_analyze($parsed_sql);
            $is_select = isset($analyzed_sql[0]['queryflags']['select_from']);
            $unlim_num_rows = PMA_Table::countRecords($db, $table, true);
            $showtable = PMA_Table::sGetStatusInfo($db, $table, null, true);
            $url_query = PMA_generate_common_url($db, $table);

            list($is_group, $is_func, $is_count, $is_export, $is_analyse,
                $is_explain, $is_delete, $is_affected, $is_insert, $is_replace,
                $is_show, $is_maint)
                    = PMA_getDisplayPropertyParams(
                        $sql_data['valid_sql'][$sql_no], $is_select
                    );

            // Handle remembered sorting order, only for single table query
            if ($GLOBALS['cfg']['RememberSorting']
                && ! ($is_count || $is_export || $is_func || $is_analyse)
                && isset($analyzed_sql[0]['select_expr'])
                && (count($analyzed_sql[0]['select_expr']) == 0)
                && isset($analyzed_sql[0]['queryflags']['select_from'])
                && count($analyzed_sql[0]['table_ref']) == 1
            ) {
                PMA_handleSortOrder(
                    $db,
                    $table,
                    $analyzed_sql,
                    $sql_data['valid_sql'][$sql_no]
                );
            }

            // Do append a "LIMIT" clause?
            if (($_SESSION['tmp_user_values']['max_rows'] != 'all')
                && ! ($is_count || $is_export || $is_func || $is_analyse)
                && isset($analyzed_sql[0]['queryflags']['select_from'])
                && ! isset($analyzed_sql[0]['queryflags']['offset'])
                && empty($analyzed_sql[0]['limit_clause'])
            ) {
                $sql_limit_to_append = ' LIMIT '
                    . $_SESSION['tmp_user_values']['pos']
                    . ', ' . $_SESSION['tmp_user_values']['max_rows'] . " ";
                $sql_data['valid_sql'][$sql_no] = PMA_getSqlWithLimitClause(
                    $sql_data['valid_sql'][$sql_no],
                    $analyzed_sql,
                    $sql_limit_to_append
                );
            }

            // Set the needed properties related to executing sql query
            $displayResultsObject->__set('db', $db);
            $displayResultsObject->__set('table', $table);
            $displayResultsObject->__set('goto', $goto);
        }

        if (! $is_affected) {
            $num_rows = ($result) ? @PMA_DBI_numRows($result) : 0;
        } elseif (! isset($num_rows)) {
            $num_rows = @PMA_DBI_affectedRows();
        }

        if (isset($sql_data['valid_sql'][$sql_no])) {

            $displayResultsObject->__set(
                'sql_query',
                $sql_data['valid_sql'][$sql_no]
            );
            $displayResultsObject->setProperties(
                $unlim_num_rows, $fields_meta, $is_count, $is_export, $is_func,
                $is_analyse, $num_rows, $fields_cnt, $querytime, $pmaThemeImage,
                $text_dir, $is_maint, $is_explain, $is_show, $showtable,
                $printview, $url_query, $resultSetContainsUniqueKey
            );
        }

        if ($num_rows == 0) {
            continue;
        }

        // With multiple results, operations are limied
        $disp_mode = 'nnnn000000';
        $is_limited_display = true;

        // Collect the tables
        $table_html .= $displayResultsObject->getTable(
            $result, $disp_mode, $analyzed_sql, $is_limited_display
        );

        // Free the result to save the memory
        PMA_DBI_freeResult($result);

        $sql_no++;

    } while (PMA_DBI_moreResults() && PMA_DBI_nextResult());

    return $table_html;
}

/**
 * Handle remembered sorting order, only for single table query
 *
 * @param string $db              database name
 * @param string $table           table name
 * @param array  &$analyzed_sql   the analyzed query
 * @param string &$full_sql_query SQL query
 *
 * @return void
 */
function PMA_handleSortOrder($db, $table, &$analyzed_sql, &$full_sql_query)
{
    $pmatable = new PMA_Table($table, $db);
    if (empty($analyzed_sql[0]['order_by_clause'])) {
        $sorted_col = $pmatable->getUiProp(PMA_Table::PROP_SORTED_COLUMN);
        if ($sorted_col) {
            // retrieve the remembered sorting order for current table
            $sql_order_to_append = ' ORDER BY ' . $sorted_col . ' ';
            $full_sql_query = $analyzed_sql[0]['section_before_limit']
                . $sql_order_to_append . $analyzed_sql[0]['limit_clause']
                . ' ' . $analyzed_sql[0]['section_after_limit'];

            // update the $analyzed_sql
            $analyzed_sql[0]['section_before_limit'] .= $sql_order_to_append;
            $analyzed_sql[0]['order_by_clause'] = $sorted_col;
        }
    } else {
        // store the remembered table into session
        $pmatable->setUiProp(
            PMA_Table::PROP_SORTED_COLUMN,
            $analyzed_sql[0]['order_by_clause']
        );
    }
}

/**
 * Append limit clause to SQL query
 *
 * @param string $full_sql_query      SQL query
 * @param array  $analyzed_sql        the analyzed query
 * @param string $sql_limit_to_append clause to append
 *
 * @return string limit clause appended SQL query
 */
function PMA_getSqlWithLimitClause($full_sql_query, $analyzed_sql,
    $sql_limit_to_append
) {
    return $analyzed_sql[0]['section_before_limit'] . "\n"
        . $sql_limit_to_append . $analyzed_sql[0]['section_after_limit'];
}


/**
 * Get column name from a drop SQL statement
 *
 * @param string $sql SQL query
 *
 * @return string $drop_column Name of the column
 */
function PMA_getColumnNameInColumnDropSql($sql)
{
    $tmpArray1 = explode('DROP', $sql);
    $str_to_check = trim($tmpArray1[1]);

    if (stripos($str_to_check, 'COLUMN') !== false) {
        $tmpArray2 = explode('COLUMN', $str_to_check);
        $str_to_check = trim($tmpArray2[1]);
    }

    $tmpArray3 = explode(' ', $str_to_check);
    $str_to_check = trim($tmpArray3[0]);

    $drop_column = str_replace(';', '', trim($str_to_check));
    $drop_column = str_replace('`', '', $drop_column);

    return $drop_column;
}

/**
 * Verify whether the result set contains all the columns
 * of at least one unique key
 *
 * @param string $db          database name
 * @param string $table       table name
 * @param string $fields_meta meta fields
 *
 * @return boolean whether the result set contains a unique key
 */
function PMA_resultSetContainsUniqueKey($db, $table, $fields_meta)
{
    $resultSetColumnNames = array();
    foreach ($fields_meta as $oneMeta) {
        $resultSetColumnNames[] = $oneMeta->name;
    }
    foreach (PMA_Index::getFromTable($table, $db) as $index) {
        if ($index->isUnique()) {
            $indexColumns = $index->getColumns();
            $numberFound = 0;
            foreach ($indexColumns as $indexColumnName => $dummy) {
                if (in_array($indexColumnName, $resultSetColumnNames)) {
                    $numberFound++;
                }
            }
            if ($numberFound == count($indexColumns)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Get the HTML for relational column dropdown
 * During grid edit, if we have a relational field, returns the html for the
 * dropdown
 *
 * @param string  $db                       current database
 * @param string  $table                    current table
 * @param string  $column                   current column
 * @param string  $curr_value               current selected value
 *
 * @return string $dropdown                 html for the dropdown
 */
function PMA_getHtmlForRelationalColumnDropdown($db, $table, $column, $curr_value)
{
    $foreigners = PMA_getForeigners($db, $table, $column);

    $display_field = PMA_getDisplayField(
        $foreigners[$column]['foreign_db'],
        $foreigners[$column]['foreign_table']
    );

    $foreignData = PMA_getForeignData($foreigners, $column, false, '', '');

    if ($foreignData['disp_row'] == null) {
        //Handle the case when number of values
        //is more than $cfg['ForeignKeyMaxLimit']
        $_url_params = array(
                'db' => $db,
                'table' => $table,
                'field' => $column
        );

        $dropdown = '<span class="curr_value">'
            . htmlspecialchars($_REQUEST['curr_value'])
            . '</span>'
            . '<a href="browse_foreigners.php'
            . PMA_generate_common_url($_url_params) . '"'
            . ' target="_blank" class="browse_foreign" ' .'>'
            . __('Browse foreign values')
            . '</a>';
    } else {
        $dropdown = PMA_foreignDropdown(
            $foreignData['disp_row'],
            $foreignData['foreign_field'],
            $foreignData['foreign_display'],
            $curr_value,
            $cfg['ForeignKeyMaxLimit']
        );
        $dropdown = '<select>' . $dropdown . '</select>';
    }

    return $dropdown;
}

/**
 * Get the HTML for the enum column dropdown
 * During grid edit, if we have a enum field, returns the html for the
 * dropdown
 *
 * @param string  $db                       current database
 * @param string  $table                    current table
 * @param string  $column                   current column
 * @param string  $curr_value               currently selected value
 *
 * @return string $dropdown                 html for the dropdown
 */
function PMA_getHtmlForEnumColumnDropdown($db, $table, $column, $curr_value)
{
    $values = PMA_getValuesForColumn($db, $table, $column);
    $dropdown = '<option value="">&nbsp;</option>';
    $dropdown .= PMA_getHtmlForOptionsList($values, array($curr_value));
    $dropdown = '<select>' . $dropdown . '</select>';
    return $dropdown;
}

/**
 * Get the HTML for the set column dropdown
 * During grid edit, if we have a set field, returns the html for the
 * dropdown
 *
 * @param string  $db                       current database
 * @param string  $table                    current table
 * @param string  $column                   current column
 * @param string  $curr_value               currently selected value
 *
 * @return string $dropdown                 html for the set column
 */
function PMA_getHtmlForSetColumn($db, $table, $column, $curr_value)
{
    $values = PMA_getValuesForColumn($db, $table, $column);
    $dropdown = '';

    //converts characters of $curr_value to HTML entities
    $converted_curr_value = htmlentities(
        $curr_value, ENT_COMPAT, "UTF-8"
    );

    $selected_values = explode(',', $converted_curr_value);
    $dropdown .= PMA_getHtmlForOptionsList($values, $selected_values);

    $select_size = (sizeof($values) > 10) ? 10 : sizeof($values);
    $dropdown = '<select multiple="multiple" size="' . $select_size . '">'
        . $dropdown . '</select>';

    return $dropdown;
}

/**
 * Get all the values for a enum column or set column in a table
 *
 * @param string  $db             current database
 * @param string  $table          current table
 * @param string  $column         current column
 *
 * @return array  $values         array containing the value list for the column
 */
function PMA_getValuesForColumn($db, $table, $column)
{
    $field_info_query = PMA_DBI_getColumnsSql($db, $table, $column);

    $field_info_result = PMA_DBI_fetchResult(
        $field_info_query, null, null, null, PMA_DBI_QUERY_STORE
    );

    $values = PMA_Util::parseEnumSetValues($field_info_result[0]['Type']);

    return $values;
}

/**
 * Get HTML for options list
 *
 * @param array   $values           set of values
 * @param array   $selected_values  currently selected values
 *
 * @return string $options          HTML for options list
 */
function PMA_getHtmlForOptionsList($values, $selected_values)
{
    $options = '';
    foreach ($values as $value) {
        $options .= '<option value="' . $value . '"';
        if (in_array($value, $selected_values, true)) {
            $options .= ' selected="selected" ';
        }
        $options .= '>' . $value . '</option>';
    }
    return $options;
}
?>
