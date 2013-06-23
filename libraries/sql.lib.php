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
 * @param object $displayResultsObject PMA_DisplayResults object
 * @param string $db                   database name
 * @param array  $sql_data             information about SQL statement
 * @param string $goto                 URL to go back in case of errors
 * @param string $pmaThemeImage        path for theme images  directory
 * @param string $text_dir             text direction
 * @param string $printview            whether printview is enabled
 * @param string $url_query            URL query
 * @param array  $disp_mode            the display mode
 * @param string $sql_limit_to_append  limit clause
 * @param bool   $has_unique           result contains a unique key
 *
 * @return string   $table_html   html content
 */
function getTableHtmlForMultipleQueries(
    $displayResultsObject, $db, $sql_data, $goto, $pmaThemeImage,
    $text_dir, $printview, $url_query, $disp_mode, $sql_limit_to_append,
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
            $url_query = PMA_generate_common_url($db, $table);
            
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
                $text_dir, $is_maint, $is_explain, $is_show, $showtable,
                $printview, $url_query, $editable
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
            $GLOBALS['cfg']['ForeignKeyMaxLimit']
        );
        $dropdown = '<select>' . $dropdown . '</select>';
    }

    return $dropdown;
}

/**
 * Get the HTML for the header of the page in print view
 *
 * @param string $db        current database
 * @param string $sql_query current sql query
 * @param int    $num_rows  the number of rows in result
 *
 * @return string $header html for the header
 */
function PMA_getHtmlForPrintViewHeader($db, $sql_query, $num_rows)
{
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

    $header = '';
    $header .= "<h1>" . __('SQL result') . "</h1>";
    $header .= "<p>";
    $header .= "<strong>" . __('Host:') . "</strong> $hostname<br />";
    $header .= "<strong>" . __('Database:') . "</strong> "
        . htmlspecialchars($db) . "<br />";
    $header .= "<strong>" . __('Generation Time:') . "</strong> "
        . PMA_Util::localisedDate() . "<br />";
    $header .= "<strong>" . __('Generated by:') . "</strong> $versions<br />";
    $header .= "<strong>" . __('SQL query:') . "</strong> "
        . htmlspecialchars($sql_query) . ";";
    if (isset($num_rows)) {
        $header .= "<br />";
        $header .= "<strong>" . __('Rows:') . "</strong> $num_rows";
    }
    $header .= "</p>";

    return $header;
}

/**
 * Get the HTML for the profiling table and accompanying chart
 *
 * @param string $url_query         the url query
 * @param string $pma_token         the pma token
 * @param array  $profiling_results array containing the profiling info
 *
 * @return string $profiling_table html for the profiling table and chart
 */
function PMA_getHtmlForProfilingChart($url_query, $pma_token, $profiling_results)
{
    $profiling_stats = array(
        'total_time' => 0,
        'states' => array(),
    );
    $profiling_table = '';

    $profiling_table .= '<fieldset><legend>' . __('Profiling') . '</legend>' . "\n";
    $profiling_table .= '<div style="float: left;">';
    $profiling_table .= '<h3>' . __('Detailed profile') . '</h3>';
    $profiling_table .= '<table id="profiletable"><thead>' . "\n";
    $profiling_table .= ' <tr>' . "\n";
    $profiling_table .= '  <th>' . __('Order')
        . '<div class="sorticon"></div></th>' . "\n";
    $profiling_table .= '  <th>' . __('State')
        . PMA_Util::showMySQLDocu(
            'general-thread-states', 'general-thread-states'
        )
        . '<div class="sorticon"></div></th>' . "\n";
    $profiling_table .= '  <th>' . __('Time')
        . '<div class="sorticon"></div></th>' . "\n";
    $profiling_table .= ' </tr></thead><tbody>' . "\n";

    $chart_json = Array();
    $i          = 1;
    foreach ($profiling_results as $one_result) {
        if (isset($profiling_stats['states'][ucwords($one_result['Status'])])) {
            $profiling_stats['states'][ucwords($one_result['Status'])]['time'] += $one_result['Duration'];
            $profiling_stats['states'][ucwords($one_result['Status'])]['calls']++;
        } else {
            $profiling_stats['states'][ucwords($one_result['Status'])] = array(
                'total_time' => $one_result['Duration'],
                'calls' => 1,
            );
        }
        $profiling_stats['total_time'] += $one_result['Duration'];

        $profiling_table .= ' <tr>' . "\n";
        $profiling_table .= '<td>' . $i++ . '</td>' . "\n";
        $profiling_table .= '<td>' . ucwords($one_result['Status']) . '</td>' . "\n";
        $profiling_table .= '<td class="right">'
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

    $profiling_table .= '</tbody></table>' . "\n";
    $profiling_table .= '</div>';

    $profiling_table .= '<div style="float: left; margin-left:10px;">';
    $profiling_table .= '<h3>' . __('Summary by state') . '</h3>';
    $profiling_table .= '<table id="profilesummarytable"><thead>' . "\n";
    $profiling_table .= ' <tr>' . "\n";
    $profiling_table .= '  <th>' . __('State')
        . PMA_Util::showMySQLDocu(
            'general-thread-states', 'general-thread-states'
        )
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
    foreach ($profiling_stats['states'] as $name => $stats) {
        $profiling_table .= ' <tr>' . "\n";
        $profiling_table .= '<td>' . $name . '</td>' . "\n";
        $profiling_table .= '<td align="right">'
            . PMA_Util::formatNumber($stats['total_time'], 3, 1)
            . 's<span style="display:none;" class="rawvalue">'
            . $stats['total_time'] . '</span></td>' . "\n";
        $profiling_table .= '<td align="right">'
            . PMA_Util::formatNumber(100 * ($stats['total_time'] / $profiling_stats['total_time']), 0, 2)
            . '%</td>' . "\n";
        $profiling_table .= '<td align="right">' . $stats['calls'] . '</td>' . "\n";
        $profiling_table .= '<td align="right">'
            . PMA_Util::formatNumber($stats['total_time'] / $stats['calls'], 3, 1)
            . 's<span style="display:none;" class="rawvalue">'
            . number_format($stats['total_time'] / $stats['calls'], 8, '.', '')
            . '</span></td>' . "\n";
        $profiling_table .= ' </tr>' . "\n";
    }

    $profiling_table .= '</tbody></table>' . "\n";

    $profiling_table .= <<<EOT
<script type="text/javascript">
    pma_token = '$pma_token';
    url_query = '$url_query';
</script>
EOT;
    $profiling_table .= "</div>";

    //require_once 'libraries/chart.lib.php';
    $profiling_table .= '<div id="profilingChartData" style="display:none;">';
    $profiling_table .= json_encode($chart_json);
    $profiling_table .= '</div>';
    $profiling_table .= '<div id="profilingchart" style="display:none;">';
    $profiling_table .= '</div>';
    $profiling_table .= '<script type="text/javascript">';
    $profiling_table .= 'makeProfilingChart();';
    $profiling_table .= 'initProfilingTables();';
    $profiling_table .= '</script>';
    $profiling_table .= '</fieldset>' . "\n";

    return $profiling_table;
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
 * Get HTML for the Bookmark form
 *
 * @param string   $db             the current database
 * @param string   $goto           goto page url
 * @param string   $bkm_sql_query  the query to be bookmarked
 * @param string   $bkm_user       the user creating the bookmark
 */
function PMA_getHtmlForBookmark($db, $goto, $bkm_sql_query, $bkm_user)
{
    $html = '<form action="sql.php" method="post"'
        . ' onsubmit="return ! emptyFormElements(this, \'bkm_fields[bkm_label]\');"'
        . ' id="bookmarkQueryForm">';
    $html .= PMA_generate_common_hidden_inputs();
    $html .= '<input type="hidden" name="goto" value="' . $goto . '" />';
    $html .= '<input type="hidden" name="bkm_fields[bkm_database]"'
        . ' value="' . htmlspecialchars($db) . '" />';
    $html .= '<input type="hidden" name="bkm_fields[bkm_user]"'
        . ' value="' . $bkm_user . '" />';
    $html .= '<input type="hidden" name="bkm_fields[bkm_sql_query]"' . ' value="'
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

    return $html;
}

/**
 * Function to check whether to remeber the sorting order or not
 * 
 * @param array $analyzed_sql_results    the analyzed qyery and other varibles set
 *                                       after analyzing the query
 * @return boolean
 */
function PMA_isRememberSortingOrder($analyzed_sql_results)
{
    if ($GLOBALS['cfg']['RememberSorting']
        && ! ($analyzed_sql_results['is_count']
            || $analyzed_sql_results['is_export']
            || $analyzed_sql_results['is_func']
            || $analyzed_sql_results['is_analyse']
        )
        && isset($analyzed_sql_results['analyzed_sql'][0]['select_expr'])
        && (count($analyzed_sql_results['analyzed_sql'][0]['select_expr']) == 0)
        && isset(
            $analyzed_sql_results['analyzed_sql'][0]['queryflags']['select_from']
        )
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
 * @param array $analyzed_sql_results    the analyzed qyery and other varibles set
 *                                       after analyzing the query
 * 
 * @return boolean
 */
function PMA_isAppendLimitClause($analyzed_sql_results)
{
    if (($_SESSION['tmp_user_values']['max_rows'] != 'all')
        && ! ($analyzed_sql_results['is_count']
            || $analyzed_sql_results['is_export']
            || $analyzed_sql_results['is_func']
            || $analyzed_sql_results['is_analyse']
        )
        && isset(
            $analyzed_sql_results['analyzed_sql'][0]['queryflags']['select_from']
        )
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
 * @param array   $analyzed_sql_results   the analyzed qyery and other varibles set
 *                                        after analyzing the query
 * @param boolean $find_real_end          whether the real end should be found
 * 
 * @return boolean
 */
function PMA_isJustBrowsing($analyzed_sql_results, $find_real_end)
{
    if (! $analyzed_sql_results['is_group']
        && ! isset($analyzed_sql_results['analyzed_sql'][0]['queryflags']['union'])
        && ! isset(
            $analyzed_sql_results['analyzed_sql'][0]['queryflags']['distinct']
        )
        && ! isset(
            $analyzed_sql_results['analyzed_sql'][0]['table_ref'][1]['table_name']
        )
        && (empty($analyzed_sql_results['analyzed_sql'][0]['where_clause'])
        || $analyzed_sql_results['analyzed_sql'][0]['where_clause'] == '1 ')
        && ! isset($find_real_end)
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to check whether the reated transformation information shoul be deleted
 * 
 * @param array $analyzed_sql_results  the analyzed qyery and other varibles set
 *                                     after analyzing the query 
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
 * @param  array   $analyzed_sql_results   the analyzed qyery and other varibles set
 *                                         after analyzing the query
 * @param boolean  $allowUserDropDatabase  whether the user is allowed to drop db
 * 
 * @return boolean
 */
function PMA_hasNoRightsToDropDatabase($analyzed_sql_results,
    $allowUserDropDatabase
) {
    if (! defined('PMA_CHK_DROP')
        && ! $allowUserDropDatabase
        && isset ($analyzed_sql_results['drop_database'])
        && $analyzed_sql_results['drop_database'] == 1
        && ! $analyzed_sql_results['is_superuser']
    ) {
        return true;
    } else {
        return false;
    }
}
?>
