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
 * Get the database name inside a query
 *
 * @param string $sql       SQL query
 * @param array  $databases array with all databases
 *
 * @return string $db new database name
 */
function PMA_getNewDatabase($sql, $databases)
{
    $db = '';
    // loop through all the databases
    foreach ($databases as $database) {
        if (strpos($sql, $database['SCHEMA_NAME']) !== false) {
            $db = $database['SCHEMA_NAME'];
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
 * @param object $displayResultsObject PMA_DisplayResults object
 * @param string $db                   database name
 * @param array  $sql_data             information about SQL statement
 * @param string $goto                 URL to go back in case of errors
 * @param string $pmaThemeImage        path for theme images  directory
 * @param string $printview            whether printview is enabled
 * @param string $url_query            URL query
 * @param array  $disp_mode            the display mode
 * @param string $sql_limit_to_append  limit clause
 * @param bool   $editable             whether editable or not
 *
 * @return string   $table_html   html content
 */
function PMA_getTableHtmlForMultipleQueries(
    $displayResultsObject, $db, $sql_data, $goto, $pmaThemeImage,
    $printview, $url_query, $disp_mode, $sql_limit_to_append,
    $editable
) {
    $table_html = '';

    $tables_array = $GLOBALS['dbi']->getTables($db);
    $databases_array = $GLOBALS['dbi']->getDatabasesFull();
    $multi_sql = implode(";", $sql_data['valid_sql']);
    $querytime_before = array_sum(explode(' ', microtime()));

    // Assignment for variable is not needed since the results are
    // looping using the connection
    @$GLOBALS['dbi']->tryMultiQuery($multi_sql);

    $querytime_after = array_sum(explode(' ', microtime()));
    $querytime = $querytime_after - $querytime_before;
    $sql_no = 0;

    do {
        $analyzed_sql = array();
        $is_affected = false;

        $result = $GLOBALS['dbi']->storeResult();
        $fields_meta = ($result !== false)
            ? $GLOBALS['dbi']->getFieldsMeta($result)
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

            $table = PMA_getTableNameBySQL(
                $sql_data['valid_sql'][$sql_no],
                $tables_array
            );

            // for the use of the parse_analyze.inc.php
            $sql_query = $sql_data['valid_sql'][$sql_no];

            // Parse and analyze the query
            include 'libraries/parse_analyze.inc.php';

            $unlim_num_rows = PMA_Table::countRecords($db, $table, true);
            $showtable = PMA_Table::sGetStatusInfo($db, $table, null, true);
            $url_query = PMA_URL_getCommon($db, $table);

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
            if (($_SESSION['tmpval']['max_rows'] != 'all')
                && ! ($is_count || $is_export || $is_func || $is_analyse)
                && isset($analyzed_sql[0]['queryflags']['select_from'])
                && ! isset($analyzed_sql[0]['queryflags']['offset'])
                && empty($analyzed_sql[0]['limit_clause'])
            ) {
                $sql_limit_to_append = ' LIMIT '
                    . $_SESSION['tmpval']['pos']
                    . ', ' . $_SESSION['tmpval']['max_rows'] . " ";
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
            $num_rows = ($result) ? @$GLOBALS['dbi']->numRows($result) : 0;
        } elseif (! isset($num_rows)) {
            $num_rows = @$GLOBALS['dbi']->affectedRows();
        }

        if (isset($sql_data['valid_sql'][$sql_no])) {

            $displayResultsObject->__set(
                'sql_query',
                $sql_data['valid_sql'][$sql_no]
            );
            $displayResultsObject->setProperties(
                $unlim_num_rows, $fields_meta, $is_count, $is_export, $is_func,
                $is_analyse, $num_rows, $fields_cnt, $querytime, $pmaThemeImage,
                $GLOBALS['text_dir'], $is_maint, $is_explain, $is_show,
                $showtable, $printview, $url_query, $editable
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
        $GLOBALS['dbi']->freeResult($result);

        $sql_no++;

    } while ($GLOBALS['dbi']->moreResults() && $GLOBALS['dbi']->nextResult());

    return $table_html;
}

/**
 * Handle remembered sorting order, only for single table query
 *
 * @param string $db                    database name
 * @param string $table                 table name
 * @param array  &$analyzed_sql_results the analyzed query results
 * @param string &$full_sql_query       SQL query
 *
 * @return void
 */
function PMA_handleSortOrder(
    $db, $table, &$analyzed_sql_results, &$full_sql_query
) {
    $pmatable = new PMA_Table($table, $db);
    if (empty($analyzed_sql_results['analyzed_sql'][0]['order_by_clause'])) {
        $sorted_col = $pmatable->getUiProp(PMA_Table::PROP_SORTED_COLUMN);
        if ($sorted_col) {
            //remove the tablename from retrieved preference
            //to get just the column name and the sort order
            $sorted_col = str_replace(
                PMA_Util::backquote($table) . '.', '', $sorted_col
            );
            // retrieve the remembered sorting order for current table
            $sql_order_to_append = ' ORDER BY ' . $sorted_col . ' ';
            $full_sql_query
                = $analyzed_sql_results['analyzed_sql'][0]['section_before_limit']
                . $sql_order_to_append
                . $analyzed_sql_results['analyzed_sql'][0]['limit_clause']
                . ' '
                . $analyzed_sql_results['analyzed_sql'][0]['section_after_limit'];

            // update the $analyzed_sql
            $analyzed_sql_results['analyzed_sql'][0]['section_before_limit']
                .= $sql_order_to_append;
            $analyzed_sql_results['analyzed_sql'][0]['order_by_clause']
                = $sorted_col;
        }
    } else {
        // store the remembered table into session
        $pmatable->setUiProp(
            PMA_Table::PROP_SORTED_COLUMN,
            $analyzed_sql_results['analyzed_sql'][0]['order_by_clause']
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
 * Verify whether the result set has columns from just one table
 *
 * @param array $fields_meta meta fields
 *
 * @return boolean whether the result set has columns from just one table
 */
function PMA_resultSetHasJustOneTable($fields_meta)
{
    $just_one_table = true;
    $prev_table = $fields_meta[0]->table;
    foreach ($fields_meta as $one_field_meta) {
        if (! empty($one_field_meta->table)
            && $one_field_meta->table != $prev_table
        ) {
            $just_one_table = false;
            break;
        }
    }
    return $just_one_table;
}

/**
 * Verify whether the result set contains all the columns
 * of at least one unique key
 *
 * @param string $db          database name
 * @param string $table       table name
 * @param array  $fields_meta meta fields
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
 * @param string $db         current database
 * @param string $table      current table
 * @param string $column     current column
 * @param string $curr_value current selected value
 *
 * @return string $dropdown html for the dropdown
 */
function PMA_getHtmlForRelationalColumnDropdown($db, $table, $column, $curr_value)
{
    $foreigners = PMA_getForeigners($db, $table, $column);

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
            . PMA_URL_getCommon($_url_params) . '"'
            . ' target="_blank" class="browse_foreign" ' . '>'
            . __('Browse foreign values')
            . '</a>';
    } else {
        $dropdown = PMA_foreignDropdown(
            $foreignData['disp_row'],
            $foreignData['foreign_field'],
            $foreignData['foreign_display'],
            $curr_value,
            $GLOBALS['cfg']['ForeignKeyMaxLimit']
        );
        $dropdown = '<select>' . $dropdown . '</select>';
    }

    return $dropdown;
}

/**
 * Get the HTML for the header of the page in print view if print view is selected.
 * Otherwise returns null.
 *
 * @param string $db        current database
 * @param string $sql_query current sql query
 * @param int    $num_rows  the number of rows in result
 *
 * @return string $header html for the header
 */
function PMA_getHtmlForPrintViewHeader($db, $sql_query, $num_rows)
{
    $response = PMA_Response::getInstance();
    $header = $response->getHeader();
    if (isset($_REQUEST['printview']) && $_REQUEST['printview'] == '1') {
        PMA_Util::checkParameters(array('db', 'sql_query'));
        $header->enablePrintView();
        $hostname = '';
        if ( $GLOBALS['cfg']['Server']['verbose']) {
            $hostname =  $GLOBALS['cfg']['Server']['verbose'];
        } else {
            $hostname =  $GLOBALS['cfg']['Server']['host'];
            if (! empty( $GLOBALS['cfg']['Server']['port'])) {
                $hostname .=  $GLOBALS['cfg']['Server']['port'];
            }
        }

        $versions  = "phpMyAdmin&nbsp;" . PMA_VERSION;
        $versions .= "&nbsp;/&nbsp;";
        $versions .= "MySQL&nbsp;" . PMA_MYSQL_STR_VERSION;

        $print_view_header = '';
        $print_view_header .= "<h1>" . __('SQL result') . "</h1>";
        $print_view_header .= "<p>";
        $print_view_header .= "<strong>" . __('Host:')
            . "</strong> $hostname<br />";
        $print_view_header .= "<strong>" . __('Database:') . "</strong> "
            . htmlspecialchars($db) . "<br />";
        $print_view_header .= "<strong>" . __('Generation Time:') . "</strong> "
            . PMA_Util::localisedDate() . "<br />";
        $print_view_header .= "<strong>" . __('Generated by:')
            . "</strong> $versions<br />";
        $print_view_header .= "<strong>" . __('SQL query:') . "</strong> "
            . htmlspecialchars($sql_query) . ";";
        if (isset($num_rows)) {
            $print_view_header .= "<br />";
            $print_view_header .= "<strong>" . __('Rows:') . "</strong> $num_rows";
        }
        $print_view_header .= "</p>";
    } else {
        $print_view_header = null;
    }

    return $print_view_header;
}

/**
 * Get the HTML for the profiling table and accompanying chart if profiling is set.
 * Otherwise returns null
 *
 * @param string $url_query         url query
 * @param string $db                current database
 * @param array  $profiling_results array containing the profiling info
 *
 * @return string $profiling_table html for the profiling table and chart
 */
function PMA_getHtmlForProfilingChart($url_query, $db, $profiling_results)
{
    if (isset($profiling_results)) {
        $pma_token = $_SESSION[' PMA_token '];
        $url_query = (isset($url_query) ? $url_query : PMA_URL_getCommon($db));

        $profiling_table = '';
        $profiling_table .= '<fieldset><legend>' . __('Profiling')
            . '</legend>' . "\n";
        $profiling_table .= '<div style="float: left;">';
        $profiling_table .= '<h3>' . __('Detailed profile') . '</h3>';
        $profiling_table .= '<table id="profiletable"><thead>' . "\n";
        $profiling_table .= ' <tr>' . "\n";
        $profiling_table .= '  <th>' . __('Order')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= '  <th>' . __('State')
            . PMA_Util::showMySQLDocu('general-thread-states')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= '  <th>' . __('Time')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= ' </tr></thead><tbody>' . "\n";
        list($detailed_table, $chart_json, $profiling_stats)
            = PMA_analyzeAndGetTableHtmlForProfilingResults($profiling_results);
        $profiling_table .= $detailed_table;
        $profiling_table .= '</tbody></table>' . "\n";
        $profiling_table .= '</div>';

        $profiling_table .= '<div style="float: left; margin-left:10px;">';
        $profiling_table .= '<h3>' . __('Summary by state') . '</h3>';
        $profiling_table .= '<table id="profilesummarytable"><thead>' . "\n";
        $profiling_table .= ' <tr>' . "\n";
        $profiling_table .= '  <th>' . __('State')
            . PMA_Util::showMySQLDocu('general-thread-states')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= '  <th>' . __('Total Time')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= '  <th>' . __('% Time')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= '  <th>' . __('Calls')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= '  <th>' . __('ø Time')
            . '<div class="sorticon"></div></th>' . "\n";
        $profiling_table .= ' </tr></thead><tbody>' . "\n";
        $profiling_table .= PMA_getTableHtmlForProfilingSummaryByState(
            $profiling_stats
        );
        $profiling_table .= '</tbody></table>' . "\n";

        $profiling_table .= <<<EOT
<script type="text/javascript">
    pma_token = '$pma_token';
    url_query = '$url_query';
</script>
EOT;
        $profiling_table .= "</div>";
        $profiling_table .= "<div class='clearfloat'></div>";

        //require_once 'libraries/chart.lib.php';
        $profiling_table .= '<div id="profilingChartData" style="display:none;">';
        $profiling_table .= json_encode($chart_json);
        $profiling_table .= '</div>';
        $profiling_table .= '<div id="profilingchart" style="display:none;">';
        $profiling_table .= '</div>';
        $profiling_table .= '<script type="text/javascript">';
        $profiling_table .= "AJAX.registerOnload('sql.js', function () {";
        $profiling_table .= 'makeProfilingChart();';
        $profiling_table .= 'initProfilingTables();';
        $profiling_table .= '});';
        $profiling_table .= '</script>';
        $profiling_table .= '</fieldset>' . "\n";
    } else {
        $profiling_table = null;
    }
    return $profiling_table;
}

/**
 * Function to get HTML for detailed profiling results table, profiling stats, and
 * $chart_json for displaying the chart.
 *
 * @param array $profiling_results profiling results
 *
 * @return mixed
 */
function PMA_analyzeAndGetTableHtmlForProfilingResults(
    $profiling_results
) {
    $profiling_stats = array(
        'total_time' => 0,
        'states' => array(),
    );
    $chart_json = Array();
    $i = 1;
    $table = '';
    foreach ($profiling_results as $one_result) {
        if (isset($profiling_stats['states'][ucwords($one_result['Status'])])) {
            $states = $profiling_stats['states'];
            $states[ucwords($one_result['Status'])]['time']
                += $one_result['Duration'];
            $states[ucwords($one_result['Status'])]['calls']++;
        } else {
            $profiling_stats['states'][ucwords($one_result['Status'])] = array(
                'total_time' => $one_result['Duration'],
                'calls' => 1,
            );
        }
        $profiling_stats['total_time'] += $one_result['Duration'];

        $table .= ' <tr>' . "\n";
        $table .= '<td>' . $i++ . '</td>' . "\n";
        $table .= '<td>' . ucwords($one_result['Status'])
            . '</td>' . "\n";
        $table .= '<td class="right">'
            . (PMA_Util::formatNumber($one_result['Duration'], 3, 1))
            . 's<span style="display:none;" class="rawvalue">'
            . $one_result['Duration'] . '</span></td>' . "\n";
        if (isset($chart_json[ucwords($one_result['Status'])])) {
            $chart_json[ucwords($one_result['Status'])]
                += $one_result['Duration'];
        } else {
            $chart_json[ucwords($one_result['Status'])]
                = $one_result['Duration'];
        }
    }
    return array($table, $chart_json, $profiling_stats);
}

/**
 * Function to get HTML for summary by state table
 *
 * @param array $profiling_stats profiling stats
 *
 * @return string $table html for the table
 */
function PMA_getTableHtmlForProfilingSummaryByState($profiling_stats)
{
    $table = '';
    foreach ($profiling_stats['states'] as $name => $stats) {
        $table .= ' <tr>' . "\n";
        $table .= '<td>' . $name . '</td>' . "\n";
        $table .= '<td align="right">'
            . PMA_Util::formatNumber($stats['total_time'], 3, 1)
            . 's<span style="display:none;" class="rawvalue">'
            . $stats['total_time'] . '</span></td>' . "\n";
        $table .= '<td align="right">'
            . PMA_Util::formatNumber(
                100 * ($stats['total_time'] / $profiling_stats['total_time']),
                0, 2
            )
        . '%</td>' . "\n";
        $table .= '<td align="right">' . $stats['calls'] . '</td>'
            . "\n";
        $table .= '<td align="right">'
            . PMA_Util::formatNumber(
                $stats['total_time'] / $stats['calls'], 3, 1
            )
            . 's<span style="display:none;" class="rawvalue">'
            . number_format($stats['total_time'] / $stats['calls'], 8, '.', '')
            . '</span></td>' . "\n";
        $table .= ' </tr>' . "\n";
    }
    return $table;
}

/**
 * Get the HTML for the enum column dropdown
 * During grid edit, if we have a enum field, returns the html for the
 * dropdown
 *
 * @param string $db         current database
 * @param string $table      current table
 * @param string $column     current column
 * @param string $curr_value currently selected value
 *
 * @return string $dropdown html for the dropdown
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
 * @param string $db         current database
 * @param string $table      current table
 * @param string $column     current column
 * @param string $curr_value currently selected value
 *
 * @return string $dropdown html for the set column
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
 * @param string $db     current database
 * @param string $table  current table
 * @param string $column current column
 *
 * @return array $values array containing the value list for the column
 */
function PMA_getValuesForColumn($db, $table, $column)
{
    $field_info_query = $GLOBALS['dbi']->getColumnsSql($db, $table, $column);

    $field_info_result = $GLOBALS['dbi']->fetchResult(
        $field_info_query, null, null, null, PMA_DatabaseInterface::QUERY_STORE
    );

    $values = PMA_Util::parseEnumSetValues($field_info_result[0]['Type']);

    return $values;
}

/**
 * Get HTML for options list
 *
 * @param array $values          set of values
 * @param array $selected_values currently selected values
 *
 * @return string $options HTML for options list
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

/**
 * Function to get html for bookmark support if bookmarks are enabled. Else will
 * return null
 *
 * @param string $disp_mode      display mode
 * @param bool   $cfgBookmark    configuration setting for bookmarking
 * @param string $sql_query      sql query
 * @param string $db             current database
 * @param string $table          current table
 * @param string $complete_query complete query
 * @param string $bkm_user       bookmarking user
 *
 * @return string $html
 */
function PMA_getHtmlForBookmark($disp_mode, $cfgBookmark, $sql_query, $db, $table,
    $complete_query, $bkm_user
) {
    if ($disp_mode[7] == '1'
        && (! empty($cfgBookmark) && empty($_GET['id_bookmark']))
        && ! empty($sql_query)
    ) {
        $html = "\n";
        $goto = 'sql.php'
            . PMA_URL_getCommon(
                array(
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sql_query,
                    'id_bookmark'=> 1,
                )
            );
        $bkm_sql_query = urlencode(
            isset($complete_query) ? $complete_query : $sql_query
        );
        $html = '<form action="sql.php" method="post"'
            . ' onsubmit="return ! emptyFormElements(this,'
            . '\'bkm_fields[bkm_label]\');"'
            . ' id="bookmarkQueryForm">';
        $html .= PMA_URL_getHiddenInputs();
        $html .= '<input type="hidden" name="goto" value="' . $goto . '" />';
        $html .= '<input type="hidden" name="bkm_fields[bkm_database]"'
            . ' value="' . htmlspecialchars($db) . '" />';
        $html .= '<input type="hidden" name="bkm_fields[bkm_user]"'
            . ' value="' . $bkm_user . '" />';
        $html .= '<input type="hidden" name="bkm_fields[bkm_sql_query]"'
            . ' value="'
            . $bkm_sql_query
            . '" />';
        $html .= '<fieldset>';
        $html .= '<legend>';
        $html .= PMA_Util::getIcon(
            'b_bookmark.png', __('Bookmark this SQL query'), true
        );
        $html .= '</legend>';
        $html .= '<div class="formelement">';
        $html .= '<label for="fields_label_">' . __('Label:') . '</label>';
        $html .= '<input type="text" id="fields_label_"'
            . ' name="bkm_fields[bkm_label]" value="" />';
        $html .= '</div>';
        $html .= '<div class="formelement">';
        $html .= '<input type="checkbox" name="bkm_all_users"'
            . ' id="bkm_all_users" value="true" />';
        $html .= '<label for="bkm_all_users">'
            . __('Let every user access this bookmark')
            . '</label>';
        $html .= '</div>';
        $html .= '<div class="clearfloat"></div>';
        $html .= '</fieldset>';
        $html .= '<fieldset class="tblFooters">';
        $html .= '<input type="hidden" name="store_bkm" value="1" />';
        $html .= '<input type="submit"'
            . ' value="' . __('Bookmark this SQL query') . '" />';
        $html .= '</fieldset>';
        $html .= '</form>';

    } else {
        $html = null;
    }

    return $html;
}

/**
 * Function to check whether to remember the sorting order or not
 *
 * @param array $analyzed_sql_results the analyzed query and other variables set
 *                                    after analyzing the query
 *
 * @return boolean
 */
function PMA_isRememberSortingOrder($analyzed_sql_results)
{
    $select_from = isset(
        $analyzed_sql_results['analyzed_sql'][0]['queryflags']['select_from']
    );
    if ($GLOBALS['cfg']['RememberSorting']
        && ! ($analyzed_sql_results['is_count']
        || $analyzed_sql_results['is_export']
        || $analyzed_sql_results['is_func']
        || $analyzed_sql_results['is_analyse'])
        && isset($analyzed_sql_results['analyzed_sql'][0]['select_expr'])
        && (count($analyzed_sql_results['analyzed_sql'][0]['select_expr']) == 0)
        && $select_from
        && count($analyzed_sql_results['analyzed_sql'][0]['table_ref']) == 1
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to check whether the LIMIT clause should be appended or not
 *
 * @param array $analyzed_sql_results the analyzed query and other variables set
 *                                    after analyzing the query
 *
 * @return boolean
 */
function PMA_isAppendLimitClause($analyzed_sql_results)
{
    $select_from = isset(
        $analyzed_sql_results['analyzed_sql'][0]['queryflags']['select_from']
    );
    if (($_SESSION['tmpval']['max_rows'] != 'all')
        && ! ($analyzed_sql_results['is_export']
        || $analyzed_sql_results['is_analyse'])
        && ($select_from || $analyzed_sql_results['is_subquery'])
        && ! isset($analyzed_sql_results['analyzed_sql'][0]['queryflags']['offset'])
        && empty($analyzed_sql_results['analyzed_sql'][0]['limit_clause'])
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to check whether this query is for just browsing
 *
 * @param array   $analyzed_sql_results the analyzed query and other variables set
 *                                      after analyzing the query
 * @param boolean $find_real_end        whether the real end should be found
 *
 * @return boolean
 */
function PMA_isJustBrowsing($analyzed_sql_results, $find_real_end)
{
    $distinct = isset(
        $analyzed_sql_results['analyzed_sql'][0]['queryflags']['distinct']
    );

    $table_name = isset(
        $analyzed_sql_results['analyzed_sql'][0]['table_ref'][1]['table_name']
    );
    if (! $analyzed_sql_results['is_group']
        && ! isset($analyzed_sql_results['analyzed_sql'][0]['queryflags']['union'])
        && ! $distinct
        && ! $table_name
        && (empty($analyzed_sql_results['analyzed_sql'][0]['where_clause'])
        || $analyzed_sql_results['analyzed_sql'][0]['where_clause'] == '1 ')
        && empty($analyzed_sql_results['analyzed_sql'][0]['group_by_clause'])
        && ! isset($find_real_end)
        && !$analyzed_sql_results['is_subquery']
        && empty($analyzed_sql_results['analyzed_sql'][0]['having_clause'])
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to check whether the reated transformation information shoul be deleted
 *
 * @param array $analyzed_sql_results the analyzed query and other variables set
 *                                    after analyzing the query
 *
 * @return boolean
 */
function PMA_isDeleteTransformationInfo($analyzed_sql_results)
{
    if (!empty($analyzed_sql_results['analyzed_sql'][0]['querytype'])
        && (($analyzed_sql_results['analyzed_sql'][0]['querytype'] == 'ALTER')
        || ($analyzed_sql_results['analyzed_sql'][0]['querytype'] == 'DROP'))
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to check whether the user has rights to drop the database
 *
 * @param array   $analyzed_sql_results  the analyzed query and other variables set
 *                                       after analyzing the query
 * @param boolean $allowUserDropDatabase whether the user is allowed to drop db
 * @param boolean $is_superuser          whether this user is a superuser
 *
 * @return boolean
 */
function PMA_hasNoRightsToDropDatabase($analyzed_sql_results,
    $allowUserDropDatabase, $is_superuser
) {
    if (! defined('PMA_CHK_DROP')
        && ! $allowUserDropDatabase
        && isset ($analyzed_sql_results['drop_database'])
        && $analyzed_sql_results['drop_database'] == 1
        && ! $is_superuser
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to set the column order
 *
 * @param PMA_Table $pmatable PMA_Table instance
 *
 * @return boolean $retval
 */
function PMA_setColumnOrder($pmatable)
{
    $col_order = explode(',', $_REQUEST['col_order']);
    $retval = $pmatable->setUiProp(
        PMA_Table::PROP_COLUMN_ORDER,
        $col_order,
        $_REQUEST['table_create_time']
    );
    if (gettype($retval) != 'boolean') {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $retval->getString());
        exit;
    }

    return $retval;
}

/**
 * Function to set the column visibility
 *
 * @param PMA_Table $pmatable PMA_Table instance
 *
 * @return boolean $retval
 */
function PMA_setColumnVisibility($pmatable)
{
    $col_visib = explode(',', $_REQUEST['col_visib']);
    $retval = $pmatable->setUiProp(
        PMA_Table::PROP_COLUMN_VISIB, $col_visib,
        $_REQUEST['table_create_time']
    );
    if (gettype($retval) != 'boolean') {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $retval->getString());
        exit;
    }
    return $retval;
}

/**
 * Function to check the request for setting the column order or visibility
 *
 * @param String $table the current table
 * @param String $db    the current database
 *
 * @return void
 */
function PMA_setColumnOrderOrVisibility($table, $db)
{
    $pmatable = new PMA_Table($table, $db);
    $retval = false;

    // set column order
    if (isset($_REQUEST['col_order'])) {
        $retval = PMA_setColumnOrder($pmatable);
    }

    // set column visibility
    if ($retval === true && isset($_REQUEST['col_visib'])) {
        $retval = PMA_setColumnVisibility($pmatable);
    }

    $response = PMA_Response::getInstance();
    $response->isSuccess($retval == true);
    exit;
}

/**
 * Function to add a bookmark
 *
 * @param String $pmaAbsoluteUri absolute URI
 * @param String $goto           goto page URL
 *
 * @return void
 */
function PMA_addBookmark($pmaAbsoluteUri, $goto)
{
    $result = PMA_Bookmark_save(
        $_POST['bkm_fields'],
        (isset($_POST['bkm_all_users'])
            && $_POST['bkm_all_users'] == 'true' ? true : false
        )
    );
    $response = PMA_Response::getInstance();
    if ($response->isAjax()) {
        if ($result) {
            $msg = PMA_message::success(__('Bookmark %s has been created.'));
            $msg->addParam($_POST['bkm_fields']['bkm_label']);
            $response->addJSON('message', $msg);
        } else {
            $msg = PMA_message::error(__('Bookmark not created!'));
            $response->isSuccess(false);
            $response->addJSON('message', $msg);
        }
        exit;
    } else {
        // go back to sql.php to redisplay query; do not use &amp; in this case:
        /**
         * @todo In which scenario does this happen?
         */
        PMA_sendHeaderLocation(
            $pmaAbsoluteUri . $goto
            . '&label=' . $_POST['bkm_fields']['bkm_label']
        );
    }
}

/**
 * Function to find the real end of rows
 *
 * @param String $db    the current database
 * @param String $table the current table
 *
 * @return mixed the number of rows if "retain" param is true, otherwise true
 */
function PMA_findRealEndOfRows($db, $table)
{
    $unlim_num_rows = PMA_Table::countRecords($db, $table, true);
    $_SESSION['tmpval']['pos'] = PMA_getStartPosToDisplayRow($unlim_num_rows);

    return $unlim_num_rows;
}

/**
 * Function to get values for the relational columns
 *
 * @param String $db            the current database
 * @param String $table         the current table
 *
 * @return void
 */
function PMA_getRelationalValues($db, $table)
{
    $column = $_REQUEST['column'];
    if ($_SESSION['tmpval']['relational_display'] == 'D'
        && isset($_REQUEST['relation_key_or_display_column'])
        && $_REQUEST['relation_key_or_display_column']
    ) {
        $curr_value = $_REQUEST['relation_key_or_display_column'];
    } else {
        $curr_value = $_REQUEST['curr_value'];
    }
    $dropdown = PMA_getHtmlForRelationalColumnDropdown(
        $db, $table, $column, $curr_value
    );
    $response = PMA_Response::getInstance();
    $response->addJSON('dropdown', $dropdown);
    exit;
}

/**
 * Function to get values for Enum or Set Columns
 *
 * @param String $db         the current database
 * @param String $table      the current table
 * @param String $columnType whether enum or set
 *
 * @return void
 */
function PMA_getEnumOrSetValues($db, $table, $columnType)
{
    $column = $_REQUEST['column'];
    $curr_value = $_REQUEST['curr_value'];
    $response = PMA_Response::getInstance();
    if ($columnType == "enum") {
        $dropdown = PMA_getHtmlForEnumColumnDropdown(
            $db, $table, $column, $curr_value
        );
        $response->addJSON('dropdown', $dropdown);
    } else {
        $select = PMA_getHtmlForSetColumn($db, $table, $column, $curr_value);
        $response->addJSON('select', $select);
    }
    exit;
}

/**
 * Function to append the limit clause
 *
 * @param String $full_sql_query full sql query
 * @param array  $analyzed_sql   analyzed sql query
 * @param String $display_query  display query
 *
 * @return array
 */
function PMA_appendLimitClause($full_sql_query, $analyzed_sql, $display_query)
{
    $sql_limit_to_append = ' LIMIT ' . $_SESSION['tmpval']['pos']
        . ', ' . $_SESSION['tmpval']['max_rows'] . " ";
    $full_sql_query = PMA_getSqlWithLimitClause(
        $full_sql_query,
        $analyzed_sql,
        $sql_limit_to_append
    );

    /**
     * @todo pretty printing of this modified query
     */
    if ($display_query) {
        // if the analysis of the original query revealed that we found
        // a section_after_limit, we now have to analyze $display_query
        // to display it correctly

        if (! empty($analyzed_sql[0]['section_after_limit'])
            && trim($analyzed_sql[0]['section_after_limit']) != ';'
        ) {
            $analyzed_display_query = PMA_SQP_analyze(
                PMA_SQP_parse($display_query)
            );
            $display_query  = $analyzed_display_query[0]['section_before_limit']
                . "\n" . $sql_limit_to_append
                . $analyzed_display_query[0]['section_after_limit'];
        }
    }

    return array($sql_limit_to_append, $full_sql_query, isset(
        $analyzed_display_query)
        ? $analyzed_display_query : null,
        isset($display_query) ? $display_query : null
    );
}

/**
 * Function to get the default sql query for browsing page
 *
 * @param String $db    the current database
 * @param String $table the current table
 *
 * @return String $sql_query the default $sql_query for browse page
 */
function PMA_getDefaultSqlQueryForBrowse($db, $table)
{
    include_once 'libraries/bookmark.lib.php';
    $book_sql_query = PMA_Bookmark_get(
        $db,
        '\'' . PMA_Util::sqlAddSlashes($table) . '\'',
        'label',
        false,
        true
    );

    if (! empty($book_sql_query)) {
        $GLOBALS['using_bookmark_message'] = PMA_message::notice(
            __('Using bookmark "%s" as default browse query.')
        );
        $GLOBALS['using_bookmark_message']->addParam($table);
        $GLOBALS['using_bookmark_message']->addMessage(
            PMA_Util::showDocu('faq', 'faq6-22')
        );
        $sql_query = $book_sql_query;
    } else {
        $sql_query = 'SELECT * FROM ' . PMA_Util::backquote($table);
    }
    unset($book_sql_query);

    return $sql_query;
}

/**
 * Responds an error when an error happens when executing the query
 *
 * @param boolean $is_gotofile    whether goto file or not
 * @param String  $error          error after executing the query
 * @param String  $full_sql_query full sql query
 *
 * @return void
 */
function PMA_handleQueryExecuteError($is_gotofile, $error, $full_sql_query)
{
    if ($is_gotofile) {
        $message = PMA_Message::rawError($error);
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $message);
    } else {
        PMA_Util::mysqlDie($error, $full_sql_query, '', '');
    }
    exit;
}

/**
 * Function to store the query as a bookmark
 *
 * @param String  $db                     the current database
 * @param String  $bkm_user               the bookmarking user
 * @param String  $sql_query_for_bookmark the query to be stored in bookmark
 * @param String  $bkm_label              bookmark label
 * @param boolean $bkm_replace            whether to replace existing bookmarks
 *
 * @return void
 */
function PMA_storeTheQueryAsBookmark($db, $bkm_user, $sql_query_for_bookmark,
    $bkm_label, $bkm_replace
) {
    include_once 'libraries/bookmark.lib.php';
    $bfields = array(
                 'bkm_database' => $db,
                 'bkm_user'  => $bkm_user,
                 'bkm_sql_query' => urlencode($sql_query_for_bookmark),
                 'bkm_label' => $bkm_label
    );

    // Should we replace bookmark?
    if (isset($bkm_replace)) {
        $bookmarks = PMA_Bookmark_getList($db);
        foreach ($bookmarks as $key => $val) {
            if ($val == $bkm_label) {
                PMA_Bookmark_delete($key);
            }
        }
    }

    PMA_Bookmark_save($bfields, isset($_POST['bkm_all_users']));

}

/**
 * Function to execute the SQL query and set the execution time
 *
 * @param String $full_sql_query the full sql query
 *
 * @return mixed  $result the results after running the query
 */
function PMA_executeQueryAndStoreResults($full_sql_query)
{
    // Measure query time.
    $querytime_before = array_sum(explode(' ', microtime()));

    $result = @$GLOBALS['dbi']->tryQuery(
        $full_sql_query, null, PMA_DatabaseInterface::QUERY_STORE
    );
    $querytime_after = array_sum(explode(' ', microtime()));

    $GLOBALS['querytime'] = $querytime_after - $querytime_before;

    return $result;
}

/**
 * Function to get the affected or changed number of rows after executing a query
 *
 * @param boolean $is_affected whether the query affected a table
 * @param mixed   $result      results of executing the query
 * @param int     $num_rows    number of rows affected or changed
 *
 * @return int    $num_rows    number of rows affected or changed
 */
function PMA_getNumberOfRowsAffectedOrChanged($is_affected, $result, $num_rows)
{
    if (! $is_affected) {
        $num_rows = ($result) ? @$GLOBALS['dbi']->numRows($result) : 0;
    } elseif (! isset($num_rows)) {
        $num_rows = @$GLOBALS['dbi']->affectedRows();
    }

    return $num_rows;
}

/**
 * Checks if the current database has changed
 * This could happen if the user sends a query like "USE `database`;"
 *
 * @param String $db the database in the query
 *
 * @return int $reload whether to reload the navigation(1) or not(0)
 */
function PMA_hasCurrentDbChanged($db)
{
    // Checks if the current database has changed
    // This could happen if the user sends a query like "USE `database`;"
    $reload = 0;
    if (strlen($db)) {
        $current_db = $GLOBALS['dbi']->fetchValue('SELECT DATABASE()');
        // $current_db is false, except when a USE statement was sent
        if ($current_db != false && $db !== $current_db) {
            $reload = 1;
        }
    }

    return $reload;
}

/**
 * If a table, database or column gets dropped, clean comments.
 *
 * @param String $db             current database
 * @param String $table          current table
 * @param String $dropped_column dropped column if any
 * @param bool   $purge          whether purge set or not
 * @param array  $extra_data     extra data
 *
 * @return array $extra_data
 */
function PMA_cleanupRelations($db, $table, $dropped_column, $purge, $extra_data)
{
    include_once 'libraries/relation_cleanup.lib.php';

    if (isset($purge) && $purge == 1) {
        if (strlen($table) && strlen($db)) {
            PMA_relationsCleanupTable($db, $table);
        } elseif (strlen($db)) {
            PMA_relationsCleanupDatabase($db);
        }
    }

    if (isset($dropped_column)
        && !empty($dropped_column)
        && strlen($db)
        && strlen($table)
    ) {
        PMA_relationsCleanupColumn($db, $table, $dropped_column);
        // to refresh the list of indexes (Ajax mode)
        $extra_data['indexes_list'] = PMA_Index::getView($table, $db);
    }

    return $extra_data;
}

/**
 * Function to count the total number of rows for the same 'SELECT' query without
 * the 'LIMIT' clause that may have been programatically added
 *
 * @param int    $num_rows             number of rows affected/changed by the query
 * @param bool   $is_select            whether the query is SELECT or not
 * @param bool   $justBrowsing         whether just browsing or not
 * @param string $db                   the current database
 * @param string $table                the current table
 * @param array  $parsed_sql           parsed sql
 * @param array  $analyzed_sql_results the analyzed query and other variables set
 *                                     after analyzing the query
 *
 * @return int $unlim_num_rows unlimited number of rows
 */
function PMA_countQueryResults(
    $num_rows, $is_select, $justBrowsing,
    $db, $table, $parsed_sql, $analyzed_sql_results
) {
    if (!PMA_isAppendLimitClause($analyzed_sql_results)) {
        // if we did not append a limit, set this to get a correct
        // "Showing rows..." message
        // $_SESSION['tmpval']['max_rows'] = 'all';
        $unlim_num_rows         = $num_rows;
    } elseif ($is_select || $analyzed_sql_results['is_subquery']) {
        //    c o u n t    q u e r y

        // If we are "just browsing", there is only one table,
        // and no WHERE clause (or just 'WHERE 1 '),
        // we do a quick count (which uses MaxExactCount) because
        // SQL_CALC_FOUND_ROWS is not quick on large InnoDB tables

        // However, do not count again if we did it previously
        // due to $find_real_end == true
        if ($justBrowsing) {
            // Get row count (is approximate for InnoDB)
            $unlim_num_rows = PMA_Table::countRecords(
                $db,
                $table,
                false
            );
            /**
             * @todo Can we know at this point that this is InnoDB,
             *       (in this case there would be no need for getting
             *       an exact count)?
             */
            if ($unlim_num_rows < $GLOBALS['cfg']['MaxExactCount']) {
                // Get the exact count if approximate count
                // is less than MaxExactCount
                /**
                 * @todo In countRecords(), MaxExactCount is also verified,
                 *       so can we avoid checking it twice?
                 */
                $unlim_num_rows = PMA_Table::countRecords(
                    $db,
                    $table,
                    true
                );
            }

        } else {
            // add select expression after the SQL_CALC_FOUND_ROWS

            // for UNION, just adding SQL_CALC_FOUND_ROWS
            // after the first SELECT works.

            // take the left part, could be:
            // SELECT
            // (SELECT

            $analyzed_sql = $analyzed_sql_results['analyzed_sql'];

            $count_query = PMA_SQP_format(
                $parsed_sql,
                'query_only',
                0,
                $analyzed_sql[0]['position_of_first_select'] + 1
            );
            $count_query .= ' SQL_CALC_FOUND_ROWS ';
            // add everything that was after the first SELECT
            $count_query .= PMA_SQP_format(
                $parsed_sql,
                'query_only',
                $analyzed_sql[0]['position_of_first_select'] + 1
            );
            // ensure there is no semicolon at the end of the
            // count query because we'll probably add
            // a LIMIT 1 clause after it
            $count_query = rtrim($count_query);
            $count_query = rtrim($count_query, ';');

            // if using SQL_CALC_FOUND_ROWS, add a LIMIT to avoid
            // long delays. Returned count will be complete anyway.
            // (but a LIMIT would disrupt results in an UNION)

            if (! isset($analyzed_sql[0]['queryflags']['union'])) {
                $count_query .= ' LIMIT 1';
            }

            // run the count query

            $GLOBALS['dbi']->tryQuery($count_query);
            // if (mysql_error()) {
            // void.
            // I tried the case
            // (SELECT `User`, `Host`, `Db`, `Select_priv` FROM `db`)
            // UNION (SELECT `User`, `Host`, "%" AS "Db",
            // `Select_priv`
            // FROM `user`) ORDER BY `User`, `Host`, `Db`;
            // and although the generated count_query is wrong
            // the SELECT FOUND_ROWS() work! (maybe it gets the
            // count from the latest query that worked)
            //
            // another case where the count_query is wrong:
            // SELECT COUNT(*), f1 from t1 group by f1
            // and you click to sort on count(*)
            // }
            $unlim_num_rows = $GLOBALS['dbi']->fetchValue('SELECT FOUND_ROWS()');
        } // end else "just browsing"
    } else {// not $is_select
        $unlim_num_rows = 0;
    }

    return $unlim_num_rows;
}

/**
 * Function to handle all aspects relating to executing the query
 *
 * @param array   $analyzed_sql_results   analyzed sql results
 * @param String  $full_sql_query         full sql query
 * @param boolean $is_gotofile            whether to go to a file
 * @param String  $db                     current database
 * @param String  $table                  current table
 * @param boolean $find_real_end          whether to find the real end
 * @param String  $sql_query_for_bookmark sql query to be stored as bookmark
 * @param array   $extra_data             extra data
 *
 * @return mixed
 */
function PMA_executeTheQuery($analyzed_sql_results, $full_sql_query, $is_gotofile,
    $db, $table, $find_real_end, $sql_query_for_bookmark, $extra_data
) {
    // Only if we ask to see the php code
    if (isset($GLOBALS['show_as_php'])) {
        $result = null;
        $num_rows = 0;
        $unlim_num_rows = 0;
    } else { // If we don't ask to see the php code
        if (isset($_SESSION['profiling']) && PMA_Util::profilingSupported()) {
            $GLOBALS['dbi']->query('SET PROFILING=1;');
        }

        $result = PMA_executeQueryAndStoreResults($full_sql_query);

        // Displays an error message if required and stop parsing the script
        $error = $GLOBALS['dbi']->getError();
        if ($error) {
            PMA_handleQueryExecuteError($is_gotofile, $error, $full_sql_query);
        }

        // If there are no errors and bookmarklabel was given,
        // store the query as a bookmark
        if (! empty($_POST['bkm_label']) && ! empty($sql_query_for_bookmark)) {
            PMA_storeTheQueryAsBookmark(
                $db, $GLOBALS['cfg']['Bookmark']['user'],
                $sql_query_for_bookmark, $_POST['bkm_label'],
                isset($_POST['bkm_replace']) ? $_POST['bkm_replace'] : null
            );
        } // end store bookmarks

        // Gets the number of rows affected/returned
        // (This must be done immediately after the query because
        // mysql_affected_rows() reports about the last query done)
        $num_rows = PMA_getNumberOfRowsAffectedOrChanged(
            $analyzed_sql_results['is_affected'], $result,
            isset($num_rows) ? $num_rows : null
        );

        // Grabs the profiling results
        if (isset($_SESSION['profiling']) && PMA_Util::profilingSupported()) {
            $profiling_results = $GLOBALS['dbi']->fetchResult('SHOW PROFILE;');
        }

        $justBrowsing = PMA_isJustBrowsing(
            $analyzed_sql_results, isset($find_real_end) ? $find_real_end : null
        );

        $unlim_num_rows = PMA_countQueryResults(
            $num_rows, $analyzed_sql_results['is_select'], $justBrowsing, $db,
            $table, $analyzed_sql_results['parsed_sql'], $analyzed_sql_results
        );

        $extra_data = PMA_cleanupRelations(
            isset($db) ? $db : '', isset($table) ? $table : '',
            isset($_REQUEST['dropped_column']) ? $_REQUEST['dropped_column'] : null,
            isset($_REQUEST['purge']) ? $_REQUEST['purge'] : null,
            isset($extra_data) ? $extra_data : null
        );

        // Update Indexes list.
        if (isset($_REQUEST['index_change'])) {
            $extra_data['indexes_list'] = PMA_Index::getView($table, $db);
        }
    }

    return array($result, $num_rows, $unlim_num_rows,
        isset($profiling_results) ? $profiling_results : null,
        isset($justBrowsing) ? $justBrowsing : null, $extra_data
    );
}
/**
 * Delete related tranformatioinformationn information
 *
 * @param String $db           current database
 * @param String $table        current table
 * @param array  $analyzed_sql analyzed sql query
 *
 * @return void
 */
function PMA_deleteTransformationInfo($db, $table, $analyzed_sql)
{
    include_once 'libraries/transformations.lib.php';
    if ($analyzed_sql[0]['querytype'] == 'ALTER') {
        if (stripos($analyzed_sql[0]['unsorted_query'], 'DROP') !== false) {
            $drop_column = PMA_getColumnNameInColumnDropSql(
                $analyzed_sql[0]['unsorted_query']
            );

            if ($drop_column != '') {
                PMA_clearTransformations($db, $table, $drop_column);
            }
        }

    } else if (($analyzed_sql[0]['querytype'] == 'DROP') && ($table != '')) {
        PMA_clearTransformations($db, $table);
    }
}

/**
 * Function to get the message for the no rows returned case
 *
 * @param string $message_to_show      message to show
 * @param array  $analyzed_sql_results analyzed sql results
 * @param int    $num_rows             number of rows
 *
 * @return string $message
 */
function PMA_getMessageForNoRowsReturned($message_to_show, $analyzed_sql_results,
    $num_rows
) {
    if ($analyzed_sql_results['is_delete']) {
        $message = PMA_Message::getMessageForDeletedRows($num_rows);
    } elseif ($analyzed_sql_results['is_insert']) {
        if ($analyzed_sql_results['is_replace']) {
            // For replace we get DELETED + INSERTED row count,
            // so we have to call it affected
            $message = PMA_Message::getMessageForAffectedRows($num_rows);
        } else {
            $message = PMA_Message::getMessageForInsertedRows($num_rows);
        }
        $insert_id = $GLOBALS['dbi']->insertId();
        if ($insert_id != 0) {
            // insert_id is id of FIRST record inserted in one insert,
            // so if we inserted multiple rows, we had to increment this
            $message->addMessage('[br]');
            // need to use a temporary because the Message class
            // currently supports adding parameters only to the first
            // message
            $_inserted = PMA_Message::notice(__('Inserted row id: %1$d'));
            $_inserted->addParam($insert_id + $num_rows - 1);
            $message->addMessage($_inserted);
        }
    } elseif ($analyzed_sql_results['is_affected']) {
        $message = PMA_Message::getMessageForAffectedRows($num_rows);

        // Ok, here is an explanation for the !$is_select.
        // The form generated by sql_query_form.lib.php
        // and db_sql.php has many submit buttons
        // on the same form, and some confusion arises from the
        // fact that $message_to_show is sent for every case.
        // The $message_to_show containing a success message and sent with
        // the form should not have priority over errors
    } elseif (! empty($message_to_show) && ! $analyzed_sql_results['is_select']) {
        $message = PMA_Message::rawSuccess(htmlspecialchars($message_to_show));
    } elseif (! empty($GLOBALS['show_as_php'])) {
        $message = PMA_Message::success(__('Showing as PHP code'));
    } elseif (isset($GLOBALS['show_as_php'])) {
        /* User disable showing as PHP, query is only displayed */
        $message = PMA_Message::notice(__('Showing SQL query'));
    } else {
        $message = PMA_Message::success(
            __('MySQL returned an empty result set (i.e. zero rows).')
        );
    }

    if (isset($GLOBALS['querytime'])) {
        $_querytime = PMA_Message::notice(
            '(' . __('Query took %01.4f seconds.') . ')'
        );
        $_querytime->addParam($GLOBALS['querytime']);
        $message->addMessage($_querytime);
    }

    return $message;
}

/**
 * Function to send the Ajax response when no rows returned
 *
 * @param string $message              message to be send
 * @param array  $analyzed_sql         analyzed sql
 * @param object $displayResultsObject DisplayResult instance
 * @param array  $extra_data           extra data
 *
 * @return void
 */
function PMA_sendAjaxResponseForNoResultsReturned($message, $analyzed_sql,
    $displayResultsObject, $extra_data
) {
    /**
     * @todo find a better way to make getMessage() in Header.class.php
     *       output the intended message
     */
    $GLOBALS['message'] = $message;

    if ($GLOBALS['cfg']['ShowSQL']) {
        $extra_data['sql_query'] = PMA_Util::getMessage(
            $message, $GLOBALS['sql_query'], 'success'
        );
    }
    if (isset($GLOBALS['reload']) && $GLOBALS['reload'] == 1) {
        $extra_data['reload'] = 1;
        $extra_data['db'] = $GLOBALS['db'];
    }
    $response = PMA_Response::getInstance();
    $response->isSuccess($message->isSuccess());
    // No need to manually send the message
    // The Response class will handle that automatically
    $query__type = PMA_DisplayResults::QUERY_TYPE_SELECT;
    if ($analyzed_sql[0]['querytype'] == $query__type) {
        $createViewHTML = $displayResultsObject->getCreateViewQueryResultOp(
            $analyzed_sql
        );
        $response->addHTML($createViewHTML . '<br />');
    }

    $response->addJSON(isset($extra_data) ? $extra_data : array());
    if (empty($_REQUEST['ajax_page_request'])) {
        $response->addJSON('message', $message);
        exit;
    }
}

/**
 * Function to respond back when the query returns zero rows
 * This method is called
 * 1-> When browsing an empty table
 * 2-> When executing a query on a non empty table which returns zero results
 * 3-> When executing a query on an empty table
 * 4-> When executing an INSERT, UPDATE, DEDETE query from the SQL  tab
 * 5-> When deleting a row from BROWSE tab
 * 6-> When searching using the SEARCH tab which returns zero results
 * 7-> When changing the structure of the table except change operation
 *
 * @param array  $analyzed_sql_results analyzed sql results
 * @param string $db                   current database
 * @param string $table                current table
 * @param string $message_to_show      message to show
 * @param int    $num_rows             number of rows
 * @param object $displayResultsObject DisplayResult instance
 * @param array  $extra_data           extra data
 *
 * @return void
 */
function PMA_sendQueryResponseForNoResultsReturned($analyzed_sql_results, $db,
    $table, $message_to_show, $num_rows, $displayResultsObject, $extra_data
) {
    if (PMA_isDeleteTransformationInfo($analyzed_sql_results)) {
        PMA_deleteTransformationInfo(
            $db, $table, $analyzed_sql_results['analyzed_sql']
        );
    }

    $message = PMA_getMessageForNoRowsReturned(
        isset($message_to_show) ? $message_to_show : null, $analyzed_sql_results,
        $num_rows
    );
    if (!isset($GLOBALS['show_as_php'])) {
        PMA_sendAjaxResponseForNoResultsReturned(
            $message, $analyzed_sql_results['analyzed_sql'],
            $displayResultsObject,
            isset($extra_data) ? $extra_data : null
        );
    }
    exit();
}

/**
 * Function to send response for ajax grid edit
 *
 * @param object $result result of the executed query
 *
 * @return void
 */
function PMA_sendResponseForGridEdit($result)
{
    $row = $GLOBALS['dbi']->fetchRow($result);
    $response = PMA_Response::getInstance();
    $response->addJSON('value', $row[0]);
    exit;
}

/**
 * Function to get html for the sql query results div
 *
 * @param string $previous_update_query_html html for the previously executed query
 * @param string $profiling_chart_html       html for profiling
 * @param object $missing_unique_column_msg  message for the missing unique column
 * @param object $bookmark_created_msg       message for bookmark creation
 * @param string $table_html                 html for the table for displaying sql
 *                                           results
 * @param string $indexes_problems_html      html for displaying errors in indexes
 * @param string $bookmark_support_html      html for displaying bookmark form
 * @param string $print_button_html          html for the print button in printview
 *
 * @return string $html_output
 */
function PMA_getHtmlForSqlQueryResults($previous_update_query_html,
    $profiling_chart_html, $missing_unique_column_msg, $bookmark_created_msg,
    $table_html, $indexes_problems_html, $bookmark_support_html, $print_button_html
) {
    //begin the sqlqueryresults div here. container div
    $html_output = '<div id="sqlqueryresults" class="ajax">';
    $html_output .= isset($previous_update_query_html)
        ? $previous_update_query_html : '';
    $html_output .= isset($profiling_chart_html) ? $profiling_chart_html : '';
    $html_output .= isset($missing_unique_column_msg)
        ? $missing_unique_column_msg->getDisplay() : '';
    $html_output .= isset($bookmark_created_msg)
        ? $bookmark_created_msg->getDisplay() : '';
    $html_output .= $table_html;
    $html_output .= isset($indexes_problems_html) ? $indexes_problems_html : '';
    $html_output .= isset($bookmark_support_html) ? $bookmark_support_html : '';
    $html_output .= isset($print_button_html) ? $print_button_html : '';
    $html_output .= '</div>'; // end sqlqueryresults div

    return $html_output;
}

/**
 * Returns a message for successful creation of a bookmark or null if a bookmark
 * was not created
 *
 * @return PMA_message $bookmark_created_msg
 */
function PMA_getBookmarkCreatedMessage()
{
    if (isset($_GET['label'])) {
        $bookmark_created_msg = PMA_message::success(__('Bookmark %s has been created.'));
        $bookmark_created_msg->addParam($_GET['label']);
    } else {
        $bookmark_created_msg = null;
    }

    return $bookmark_created_msg;
}

/**
 * Function to get html for the sql query results table
 *
 * @param array  $sql_data             sql data
 * @param object $displayResultsObject instance of DisplayResult.class
 * @param string $db                   current database
 * @param string $goto                 goto page url
 * @param string $pmaThemeImage        theme image uri
 * @param string $url_query            url query
 * @param string $disp_mode            display mode
 * @param string $sql_limit_to_append  sql limit to append
 * @param bool   $editable             whether the result table is editable or not
 * @param int    $unlim_num_rows       unlimited number of rows
 * @param int    $num_rows             number of rows
 * @param bool   $showtable            whether to show table or not
 * @param object $result               result of the executed query
 * @param array  $analyzed_sql_results analyzed sql results
 *
 * @return String
 */
function PMA_getHtmlForSqlQueryResultsTable($sql_data, $displayResultsObject, $db,
    $goto, $pmaThemeImage, $url_query, $disp_mode, $sql_limit_to_append,
    $editable, $unlim_num_rows, $num_rows, $showtable, $result,
    $analyzed_sql_results
) {
    $printview = isset($_REQUEST['printview']) ? $_REQUEST['printview'] : null;
    $table_html = '';
    if (! empty($sql_data) && ($sql_data['valid_queries'] > 1)) {
        $_SESSION['is_multi_query'] = true;
        $table_html .= PMA_getTableHtmlForMultipleQueries(
            $displayResultsObject, $db, $sql_data, $goto,
            $pmaThemeImage, $printview, $url_query,
            $disp_mode, $sql_limit_to_append, $editable
        );
    } elseif ($analyzed_sql_results['is_procedure']) {

        do {
            if (! isset($result)) {
                $result = $GLOBALS['dbi']->storeResult();
            }
            $num_rows = $GLOBALS['dbi']->numRows($result);

            if ($result !== false && $num_rows > 0) {

                $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
                $fields_cnt  = count($fields_meta);

                $displayResultsObject->setProperties(
                    $num_rows,
                    $fields_meta,
                    $analyzed_sql_results['is_count'],
                    $analyzed_sql_results['is_export'],
                    $analyzed_sql_results['is_func'],
                    $analyzed_sql_results['is_analyse'],
                    $num_rows,
                    $fields_cnt,
                    $GLOBALS['querytime'],
                    $pmaThemeImage,
                    $GLOBALS['text_dir'],
                    $analyzed_sql_results['is_maint'],
                    $analyzed_sql_results['is_explain'],
                    $analyzed_sql_results['is_show'],
                    $showtable,
                    $printview,
                    $url_query,
                    $editable
                );

                $disp_mode = 'nnnn110111'; // uneditable
                $table_html .= $displayResultsObject->getTable(
                    $result,
                    $disp_mode,
                    $analyzed_sql_results['analyzed_sql']
                );
            }

            $GLOBALS['dbi']->freeResult($result);
            unset($result);

        } while ($GLOBALS['dbi']->moreResults() && $GLOBALS['dbi']->nextResult());

    } else {
        if (isset($result) && $result) {
            $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
            $fields_cnt  = count($fields_meta);
        }
        $_SESSION['is_multi_query'] = false;
        $displayResultsObject->setProperties(
            $unlim_num_rows, $fields_meta, $analyzed_sql_results['is_count'],
            $analyzed_sql_results['is_export'], $analyzed_sql_results['is_func'],
            $analyzed_sql_results['is_analyse'], $num_rows,
            $fields_cnt, $GLOBALS['querytime'], $pmaThemeImage, $GLOBALS['text_dir'],
            $analyzed_sql_results['is_maint'], $analyzed_sql_results['is_explain'],
            $analyzed_sql_results['is_show'], $showtable, $printview, $url_query,
            $editable
        );

        $table_html .= $displayResultsObject->getTable(
            $result, $disp_mode, $analyzed_sql_results['analyzed_sql']
        );
        $GLOBALS['dbi']->freeResult($result);
    }

    return $table_html;
}

/**
 * Function to get html for the previous query if there is such. If not will return
 * null
 *
 * @param string $disp_query   display query
 * @param bool   $showSql      whether to show sql
 * @param array  $sql_data     sql data
 * @param string $disp_message display message
 *
 * @return string $previous_update_query_html
 */
function PMA_getHtmlForPreviousUpdateQuery($disp_query, $showSql, $sql_data,
    $disp_message
) {
    // previous update query (from tbl_replace)
    if (isset($disp_query) && ($showSql == true) && empty($sql_data)) {
        $previous_update_query_html = PMA_Util::getMessage(
            $disp_message, $disp_query, 'success'
        );
    } else {
        $previous_update_query_html = null;
    }

    return $previous_update_query_html;
}

/**
 * To get the message if a column index is missing. If not will return null
 *
 * @param string  $table     current table
 * @param string  $db        current database
 * @param boolean $editable  whether the results table can be editable or not
 * @param string  $disp_mode display mode
 *
 * @return PMA_message $message
 */
function PMA_getMessageIfMissingColumnIndex($table, $db, $editable, $disp_mode)
{
    if (!empty($table) && ($GLOBALS['dbi']->isSystemSchema($db) || !$editable)) {
        $missing_unique_column_msg = PMA_message::notice(
            __(
                'Current selection does not contain a unique column.'
                . ' Grid edit, checkbox, Edit, Copy and Delete features'
                . ' are not available.'
            )
        );
    } else {
        $missing_unique_column_msg = null;
    }

    return $missing_unique_column_msg;
}

/**
 * Function to get html to display problems in indexes
 *
 * @param string $query_type query type
 * @param bool   $selected   whether check table, optimize table, analyze
 *                            table or repair table has been selected with
 *                            respect to the selected tables from the
 *                            database structure page.
 * @param string $db         current database
 *
 * @return string
 */
function PMA_getHtmlForIndexesProblems($query_type, $selected, $db)
{
    // BEGIN INDEX CHECK See if indexes should be checked.
    if (isset($query_type)
        && $query_type == 'check_tbl'
        && isset($selected)
        && is_array($selected)
    ) {
        $indexes_problems_html = '';
        foreach ($selected as $tbl_name) {
            $check = PMA_Index::findDuplicates($tbl_name, $db);
            if (! empty($check)) {
                $indexes_problems_html .= sprintf(
                    __('Problems with indexes of table `%s`'), $tbl_name
                );
                $indexes_problems_html .= $check;
            }
        }
    } else {
        $indexes_problems_html = null;
    }

    return $indexes_problems_html;
}

/**
 * Function to get the html for the print button in printview
 *
 * @return string $print_button_html html for the print button
 */
function PMA_getHtmlForPrintButton()
{
    // Do print the page if required
    if (isset($_REQUEST['printview']) && $_REQUEST['printview'] == '1') {
        $print_button_html = PMA_Util::getButton();
    } else {
        $print_button_html = null;
    }

    return $print_button_html;
}

/**
 * Function to display results when the executed query returns non empty results
 *
 * @param array  $result               executed query results
 * @param bool   $justBrowsing         whether just browsing or not
 * @param array  $analyzed_sql_results analysed sql results
 * @param string $db                   current database
 * @param string $table                current table
 * @param string $disp_mode            display mode
 * @param string $message              message to show
 * @param array  $sql_data             sql data
 * @param object $displayResultsObject Instance of DisplyResults.class
 * @param string $goto                 goto page url
 * @param string $pmaThemeImage        uri of the theme image
 * @param string $sql_limit_to_append  sql limit to append
 * @param int    $unlim_num_rows       unlimited number of rows
 * @param int    $num_rows             number of rows
 * @param string $full_sql_query       full sql query
 * @param string $disp_query           display query
 * @param string $disp_message         display message
 * @param array  $profiling_results    profiling results
 * @param string $query_type           query type
 * @param bool   $selected             whether check table, optimize table, analyze
 *                                     table or repair table has been selected with
 *                                     respect to the selected tables from the
 *                                     database structure page.
 * @param string $sql_query            sql query
 * @param string $complete_query       complete sql query
 *
 * @return void
 */
function PMA_sendQueryResponseForResultsReturned($result, $justBrowsing,
    $analyzed_sql_results, $db, $table, $disp_mode, $message, $sql_data,
    $displayResultsObject, $goto, $pmaThemeImage, $sql_limit_to_append,
    $unlim_num_rows, $num_rows,  $full_sql_query, $disp_query,
    $disp_message, $profiling_results, $query_type, $selected, $sql_query,
    $complete_query
) {
    // If we are retrieving the full value of a truncated field or the original
    // value of a transformed field, show it here
    if (isset($_REQUEST['grid_edit']) && $_REQUEST['grid_edit'] == true) {
        PMA_sendResponseForGridEdit($result);
        // script has exited at this point
    }

    // Gets the list of fields properties
    if (isset($result) && $result) {
        $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
    }

    // Should be initialized these parameters before parsing
    $showtable = isset($showtable) ? $showtable : null;
    $url_query = isset($url_query) ? $url_query : null;

    $response = PMA_Response::getInstance();
    $header   = $response->getHeader();
    $scripts  = $header->getScripts();

    // hide edit and delete links:
    // - for information_schema
    // - if the result set does not contain all the columns of a unique key
    //   (unless this is an updatable view)

    $sele_exp_cls = $analyzed_sql_results['analyzed_sql'][0]['select_expr_clause'];
    $updatableView
        = trim($sele_exp_cls) == '*'
        && PMA_Table::isUpdatableView($db, $table);

    $has_unique = PMA_resultSetContainsUniqueKey(
        $db, $table, $fields_meta
    );

    $just_one_table = PMA_resultSetHasJustOneTable($fields_meta);

    $editable = ($has_unique || $updatableView) && $just_one_table;

    // Displays the results in a table
    if (empty($disp_mode)) {
        // see the "PMA_setDisplayMode()" function in
        // libraries/DisplayResults.class.php
        $disp_mode = 'urdr111101';
    }
    if (!empty($table) && ($GLOBALS['dbi']->isSystemSchema($db) || !$editable)) {
        $disp_mode = 'nnnn110111';
    }
    if ( isset($_REQUEST['printview']) && $_REQUEST['printview'] == '1') {
        $disp_mode = 'nnnn000000';
    }

    if (isset($_REQUEST['table_maintenance'])) {
        $scripts->addFile('makegrid.js');
        $scripts->addFile('sql.js');
        $table_maintenance_html = '';
        if (isset($message)) {
            $message = PMA_Message::success($message);
            $table_maintenance_html = PMA_Util::getMessage(
                $message, $GLOBALS['sql_query'], 'success'
            );
        }
        $table_maintenance_html .= PMA_getHtmlForSqlQueryResultsTable(
            isset($sql_data) ? $sql_data : null, $displayResultsObject, $db, $goto,
            $pmaThemeImage, $url_query, $disp_mode, $sql_limit_to_append,
            false, $unlim_num_rows, $num_rows, $showtable, $result,
            $analyzed_sql_results
        );
        if (empty($sql_data) || ($sql_data['valid_queries'] = 1)) {
            $response->addHTML($table_maintenance_html);
            exit();
        }
    }

    if (!isset($_REQUEST['printview']) || $_REQUEST['printview'] != '1') {
        $scripts->addFile('makegrid.js');
        $scripts->addFile('sql.js');
        unset($GLOBALS['message']);
        //we don't need to buffer the output in getMessage here.
        //set a global variable and check against it in the function
        $GLOBALS['buffer_message'] = false;
    }

    $print_view_header_html = PMA_getHtmlForPrintViewHeader(
        $db, $full_sql_query, $num_rows
    );

    $previous_update_query_html = PMA_getHtmlForPreviousUpdateQuery(
        isset($disp_query) ? $disp_query : null,
        $GLOBALS['cfg']['ShowSQL'], isset($sql_data) ? $sql_data : null,
        isset($disp_message) ? $disp_message : null
    );

    $profiling_chart_html = PMA_getHtmlForProfilingChart(
        $disp_mode, $db, isset($profiling_results) ? $profiling_results : null
    );

    $missing_unique_column_msg = PMA_getMessageIfMissingColumnIndex(
        $table, $db, $editable, $disp_mode
    );

    $bookmark_created_msg = PMA_getBookmarkCreatedMessage();

    $table_html = PMA_getHtmlForSqlQueryResultsTable(
        isset($sql_data) ? $sql_data : null, $displayResultsObject, $db, $goto,
        $pmaThemeImage, $url_query, $disp_mode, $sql_limit_to_append,
        $editable, $unlim_num_rows, $num_rows, $showtable, $result,
        $analyzed_sql_results
    );

    $indexes_problems_html = PMA_getHtmlForIndexesProblems(
        isset($query_type) ? $query_type : null,
        isset($selected) ? $selected : null, $db
    );

    if (isset($GLOBALS['cfg']['Bookmark'])) {
        $bookmark_support_html = PMA_getHtmlForBookmark(
            $disp_mode,
            $GLOBALS['cfg']['Bookmark'],
            $sql_query, $db, $table,
            isset($complete_query) ? $complete_query : $sql_query,
            $GLOBALS['cfg']['Bookmark']['user']
        );
    } else {
        $bookmark_support_html = '';
    }

    $print_button_html = PMA_getHtmlForPrintButton();

    $html_output = isset($table_maintenance_html) ? $table_maintenance_html : '';

    $html_output .= isset($print_view_header_html) ? $print_view_header_html : '';

    $html_output .= PMA_getHtmlForSqlQueryResults(
        $previous_update_query_html, $profiling_chart_html,
        $missing_unique_column_msg, $bookmark_created_msg,
        $table_html, $indexes_problems_html, $bookmark_support_html,
        $print_button_html
    );

    $response->addHTML($html_output);

    exit();
}

/**
 * Function to send response for both empty results and non empty results
 *
 * @param int    $num_rows             number of rows returned by the executed query
 * @param int    $unlim_num_rows       unlimited number of rows
 * @param bool   $is_affected          is affected
 * @param string $db                   current database
 * @param string $table                current table
 * @param string $message_to_show      message to show
 * @param array  $analyzed_sql_results analyzed Sql Results
 * @param object $displayResultsObject Instance of DisplayResult class
 * @param array  $extra_data           extra data
 * @param array  $result               executed query results
 * @param bool   $justBrowsing         whether just browsing or not
 * @param string $disp_mode            disply mode
 * @param object $message              message
 * @param array  $sql_data             sql data
 * @param string $goto                 goto page url
 * @param string $pmaThemeImage        uri of the PMA theme image
 * @param string $sql_limit_to_append  sql limit to append
 * @param string $full_sql_query       full sql query
 * @param string $disp_query           display query
 * @param string $disp_message         display message
 * @param array  $profiling_results    profiling results
 * @param string $query_type           query type
 * @param bool   $selected             whether check table, optimize table, analyze
 *                                     table or repair table has been selected with
 *                                     respect to the selected tables from the
 *                                     database structure page.
 * @param string $sql_query            sql query
 * @param string $complete_query       complete query
 *
 * @return void
 */
function PMA_sendQueryResponse($num_rows, $unlim_num_rows, $is_affected,
    $db, $table, $message_to_show, $analyzed_sql_results, $displayResultsObject,
    $extra_data, $result, $justBrowsing, $disp_mode,$message, $sql_data,
    $goto, $pmaThemeImage, $sql_limit_to_append, $full_sql_query,
    $disp_query, $disp_message, $profiling_results, $query_type, $selected,
    $sql_query, $complete_query
) {
    // No rows returned -> move back to the calling page
    if ((0 == $num_rows && 0 == $unlim_num_rows) || $is_affected) {
        PMA_sendQueryResponseForNoResultsReturned(
            $analyzed_sql_results, $db, $table,
            isset($message_to_show) ? $message_to_show : null,
            $num_rows, $displayResultsObject, $extra_data
        );

    } else {
        // At least one row is returned -> displays a table with results
        PMA_sendQueryResponseForResultsReturned(
            isset($result) ? $result : null, $justBrowsing, $analyzed_sql_results,
            $db, $table, isset($disp_mode) ? $disp_mode : null,
            isset($message) ? $message : null, isset($sql_data) ? $sql_data : null,
            $displayResultsObject, $goto, $pmaThemeImage,
            $sql_limit_to_append, $unlim_num_rows,
            $num_rows, $full_sql_query,
            isset($disp_query) ? $disp_query : null,
            isset($disp_message) ? $disp_message : null, $profiling_results,
            isset($query_type) ? $query_type : null,
            isset($selected) ? $selected : null, $sql_query,
            isset($complete_query) ? $complete_query : null
        );
    } // end rows returned
}

/**
 * Function to execute the query and send the response
 *
 * @param array      $analyzed_sql_results   analysed sql results
 * @param bool       $is_gotofile            whether goto file or not
 * @param string     $db                     current database
 * @param string     $table                  current table
 * @param bool|null  $find_real_end          whether to find real end or not
 * @param string     $sql_query_for_bookmark the sql query to be stored as bookmark
 * @param array|null $extra_data             extra data
 * @param bool       $is_affected            whether affected or not
 * @param string     $message_to_show        message to show
 * @param string     $disp_mode              display mode
 * @param string     $message                message
 * @param array|null $sql_data               sql data
 * @param string     $goto                   goto page url
 * @param string     $pmaThemeImage          uri of the PMA theme image
 * @param string     $disp_query             display query
 * @param string     $disp_message           display message
 * @param string     $query_type             query type
 * @param string     $sql_query              sql query
 * @param bool|null  $selected               whether check table, optimize table,
 *                                           analyze table or repair table has been
 *                                           selected with respect to the selected
 *                                           tables from the database structure page
 * @param string     $complete_query         complete query
 *
 * @return void
 */
function PMA_executeQueryAndSendQueryResponse($analyzed_sql_results,
    $is_gotofile, $db, $table, $find_real_end, $sql_query_for_bookmark,
    $extra_data, $is_affected, $message_to_show, $disp_mode, $message,
    $sql_data, $goto, $pmaThemeImage, $disp_query, $disp_message,
    $query_type, $sql_query, $selected, $complete_query
) {
    // Include PMA_Index class for use in PMA_DisplayResults class
    include_once './libraries/Index.class.php';

    include 'libraries/DisplayResults.class.php';

    // Handle remembered sorting order, only for single table query
    // Handling is not required when it's a union query
    // (the parser never sets the 'union' key to 0)
    if (PMA_isRememberSortingOrder($analyzed_sql_results)
        && ! isset($analyzed_sql_results['analyzed_sql'][0]['queryflags']['union'])
    ) {
        PMA_handleSortOrder($db, $table, $analyzed_sql_results, $sql_query);
    }

    $displayResultsObject = new PMA_DisplayResults(
        $GLOBALS['db'], $GLOBALS['table'], $GLOBALS['goto'], $sql_query
    );
    $displayResultsObject->setConfigParamsForDisplayTable();

    // assign default full_sql_query
    $full_sql_query = $sql_query;

    // Do append a "LIMIT" clause?
    if (PMA_isAppendLimitClause($analyzed_sql_results)) {
        list($sql_limit_to_append,
            $full_sql_query, $analyzed_display_query, $display_query
        ) = PMA_appendLimitClause(
            $full_sql_query, $analyzed_sql_results['analyzed_sql'],
            isset($display_query)
        );
    } else {
        $sql_limit_to_append = '';
    }

    $GLOBALS['reload'] = PMA_hasCurrentDbChanged($db);
    $GLOBALS['dbi']->selectDb($db);

    // Execute the query
    list($result, $num_rows, $unlim_num_rows, $profiling_results,
        $justBrowsing, $extra_data
    ) = PMA_executeTheQuery(
        $analyzed_sql_results,
        $full_sql_query,
        $is_gotofile,
        $db,
        $table,
        isset($find_real_end) ? $find_real_end : null,
        isset($sql_query_for_bookmark) ? $sql_query_for_bookmark : null,
        isset($extra_data) ? $extra_data : null
    );

    PMA_sendQueryResponse(
        $num_rows,
        $unlim_num_rows,
        $is_affected,
        $db,
        $table,
        isset($message_to_show) ? $message_to_show : null,
        $analyzed_sql_results,
        $displayResultsObject,
        $extra_data,
        isset($result) ? $result : null,
        $justBrowsing,
        isset($disp_mode) ? $disp_mode : null,
        isset($message) ? $message : null,
        isset($sql_data) ? $sql_data : null,
        $goto,
        $pmaThemeImage,
        $sql_limit_to_append,
        $full_sql_query,
        isset($disp_query) ? $disp_query : null,
        isset($disp_message) ? $disp_message : null,
        $profiling_results,
        isset($query_type) ? $query_type : null,
        isset($selected) ? $selected : null,
        $sql_query,
        isset($complete_query) ? $complete_query : null
    );
}

/**
 * Function to define pos to display a row
 *
 * @param Int $number_of_line Number of the line to display
 * @param Int $max_rows       Number of rows by page
 *
 * @return Int Start position to display the line
 */
function PMA_getStartPosToDisplayRow($number_of_line, $max_rows = null)
{
    if (null === $max_rows) {
        $max_rows = $_SESSION['tmpval']['max_rows'];
    }

    return @((ceil($number_of_line / $max_rows) - 1) * $max_rows);
}

/**
 * Function to calculate new pos if pos is higher than number of rows
 * of displayed table
 *
 * @param String   $db    Database name
 * @param String   $table Table name
 * @param Int|null $pos   Initial position
 *
 * @return Int Number of pos to display last page
 */
function PMA_calculatePosForLastPage($db, $table, $pos)
{
    if (null === $pos) {
        $pos = $_SESSION['tmpval']['pos'];
    }

    $unlim_num_rows = PMA_Table::countRecords($db, $table, true);
    //If position is higher than number of rows
    if ($unlim_num_rows <= $pos && 0 != $pos) {
        $pos = PMA_getStartPosToDisplayRow($unlim_num_rows);
    }

    return $pos;
}

?>
