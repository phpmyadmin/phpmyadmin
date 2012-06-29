<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles database search feature
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Builds the SQL search query
 *
 * @param string  $table                The table name
 * @param string  $criteriaColumnName   Restrict the search to this column
 * @param string  $criteriaSearchString The string to search
 * @param integer $criteriaSearchType   Type of search
 *                                      (1 -> 1 word at least, 2 -> all words,
 *                                      3 -> exact string, 4 -> regexp)
 *
 * @return array 3 SQL querys (for count, display and delete results)
 *
 * @todo    can we make use of fulltextsearch IN BOOLEAN MODE for this?
 * PMA_backquote
 * PMA_DBI_free_result
 * PMA_DBI_fetch_assoc
 * $GLOBALS['db']
 * explode
 * count
 * strlen
 */
function PMA_getSearchSqls($table, $criteriaColumnName, $criteriaSearchString,
    $criteriaSearchType
) {
    // Statement types
    $sqlstr_select = 'SELECT';
    $sqlstr_delete = 'DELETE';
    // Table to use
    $sqlstr_from = ' FROM '
        . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($table);
    // Search words or pattern
    $search_words    = (($criteriaSearchType > 2)
        ? array($criteriaSearchString) : explode(' ', $criteriaSearchString));

    $like_or_regex   = (($criteriaSearchType == 4) ? 'REGEXP' : 'LIKE');
    $automatic_wildcard   = (($criteriaSearchType < 3) ? '%' : '');

    $where_clause = PMA_dbSearchGetWhereClause(
        $table, $search_words, $criteriaSearchType, $criteriaColumnName,
        $like_or_regex, $automatic_wildcard
    );

    // Builds complete queries
    $sql['select_columns'] = $sqlstr_select . ' * ' . $sqlstr_from . $where_clause;
    // here, I think we need to still use the COUNT clause, even for
    // VIEWs, anyway we have a WHERE clause that should limit results
    $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS `count`'
        . $sqlstr_from . $where_clause;
    $sql['delete']        = $sqlstr_delete . $sqlstr_from . $where_clause;

    return $sql;
}

/**
 * Provides where clause for bulding SQL query
 *
 * @param string  $table              the table name
 * @param integer $search_words       Search words or pattern
 * @param integer $criteriaSearchType Type of search
 *                                    (1 -> 1 word at least, 2 -> all words,
 *                                    3 -> exact string, 4 -> regexp)
 * @param string  $criteriaColumnName Restrict the search to this column
 * @param string  $like_or_regex      Whether to use 'LIKE' or 'REGEXP'
 * @param string  $automatic_wildcard Use automatic wildcard
 *
 * @return string The generated where clause
 */
function PMA_dbSearchGetWhereClause($table, $search_words, $criteriaSearchType,
    $criteriaColumnName, $like_or_regex, $automatic_wildcard
) {
    $where_clause = '';
    // Columns to select
    $allColumns = PMA_DBI_get_columns($GLOBALS['db'], $table);
    $likeClauses = array();

    foreach ($search_words as $search_word) {
        // Eliminates empty values
        if (strlen($search_word) === 0) {
            continue;
        }
        $likeClausesPerColumn = array();
        // for each column in the table
        foreach ($allColumns as $column) {
            if (! isset($criteriaColumnName)
                || strlen($criteriaColumnName) == 0
                || $column['Field'] == $criteriaColumnName
            ) {
                // Drizzle has no CONVERT and all text columns are UTF-8
                $column = ((PMA_DRIZZLE)
                    ? PMA_backquote($column['Field'])
                    : 'CONVERT(' . PMA_backquote($column['Field']) . ' USING utf8)');
                $likeClausesPerColumn[] = $column . ' ' . $like_or_regex . ' '
                    . "'"
                    . $automatic_wildcard . $search_word . $automatic_wildcard
                    . "'";
            }
        } // end for
        if (count($likeClausesPerColumn) > 0) {
            $likeClauses[] = implode(' OR ', $likeClausesPerColumn);
        }
    } // end for

    $implode_str  = ($criteriaSearchType == 1 ? ' OR ' : ' AND ');
    if ( empty($likeClauses)) {
        // this could happen when the "inside column" does not exist
        // in any selected tables
        $where_clause = ' WHERE FALSE';
    } else {
        $where_clause = ' WHERE (' . implode(') ' . $implode_str . ' (', $likeClauses) . ')';
    }
    return $where_clause;
}

/**
 * Displays database search results
 *
 * @param array   $criteriaTables       Tables on which search is to be performed
 * @param string  $searched             The search word/phrase/regexp
 * @param string  $option_str           Type of search
 * @param string  $criteriaSearchString The string to search
 * @param integer $criteriaSearchType   Type of search
 *                                      (1 -> 1 word at least, 2 -> all words,
 *                                      3 -> exact string, 4 -> regexp)
 * @param string  $criteriaColumnName   Restrict the search to this column
 *
 * @return string HTML for search results
 */
function PMA_dbSearchGetSearchResults($criteriaTables, $searched, $option_str,
    $criteriaSearchString, $criteriaSearchType, $criteriaColumnName = null
) {
    $html_output = '';
    // Displays search string
    $html_output .= '<br />'
        . '<table class="data">'
        . '<caption class="tblHeaders">'
        . sprintf(
            __('Search results for "<i>%s</i>" %s:'),
            $searched, $option_str
        )
        . '</caption>';

    $num_search_result_total = 0;
    $odd_row = true;
    // For each table selected as search criteria
    foreach ($criteriaTables as $each_table) {
        // Gets the SQL statements
        $newsearchsqls = PMA_getSearchSqls(
            $each_table, (! empty($criteriaColumnName) ? $criteriaColumnName : ''),
            $criteriaSearchString, $criteriaSearchType
        );
        // Executes the "COUNT" statement
        $res_cnt = PMA_DBI_fetch_value($newsearchsqls['select_count']);
        $num_search_result_total += $res_cnt;
        $html_output .= PMA_dbSearchGetResultsRow(
            $each_table, $newsearchsqls, $odd_row
        );
        $odd_row = ! $odd_row;
    } // end for
    $html_output .= '</table>';

    if (count($criteriaTables) > 1) {
        $html_output .= '<p>';
        $html_output .= sprintf(
            _ngettext(
                '<b>Total:</b> <i>%s</i> match',
                '<b>Total:</b> <i>%s</i> matches',
                $num_search_result_total
            ),
            $num_search_result_total
        );
        $html_output .= '</p>';
    }
    return $html_output;
}

/**
 * Provides search results row with browse/delete links.
 * (for a table)
 *
 * @param string $each_table    Tables on which search is to be performed
 * @param array  $newsearchsqls Contains SQL queries
 * @param bool   $odd_row       For displaying contrasting table rows
 *
 * @return string HTML row
 */
function PMA_dbSearchGetResultsRow($each_table, $newsearchsqls, $odd_row)
{
    $this_url_params = array(
        'db'    => $GLOBALS['db'],
        'goto'  => 'db_sql.php',
        'pos'   => 0,
        'is_js_confirmed' => 0,
    );
    $res_cnt = PMA_DBI_fetch_value($newsearchsqls['select_count']);
    // Start forming search results row
    $html_output = '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
    $html_output .= '<td>';
    $html_output .= sprintf(
        _ngettext(
            '%1$s match in <strong>%2$s</strong>',
            '%1$s matches in <strong>%2$s</strong>', $res_cnt
        ),
        $res_cnt, htmlspecialchars($each_table)
    );
    $html_output .= '</td>';

    if ($res_cnt > 0) {
        $this_url_params['sql_query'] = $newsearchsqls['select_columns'];
        $browse_result_path = 'sql.php' . PMA_generate_common_url($this_url_params);
        $html_output .= '<td><a name="browse_search" href="'
            . $browse_result_path . '" onclick="loadResult(\''
            . $browse_result_path . '\',\'' . $each_table . '\',\''
            . PMA_generate_common_url($GLOBALS['db'], $each_table) . '\',\''
            . ($GLOBALS['cfg']['AjaxEnable']) .'\');return false;" >'
            . __('Browse') . '</a></td>';
        $this_url_params['sql_query'] = $newsearchsqls['delete'];
        $delete_result_path = 'sql.php' . PMA_generate_common_url($this_url_params);
        $html_output .= '<td><a name="delete_search" href="'
            . $delete_result_path . '" onclick="deleteResult(\''
            . $delete_result_path . '\' , \''
            . sprintf(
                __('Delete the matches for the %s table?'),
                htmlspecialchars($each_table)
            )
            . '\',\'' . ($GLOBALS['cfg']['AjaxEnable']) . '\');return false;">'
            . __('Delete') . '</a></td>';
    } else {
        $html_output .= '<td>&nbsp;</td>'
            .'<td>&nbsp;</td>';
    }// end if else
    $html_output .= '</tr>';
    return $html_output;
}

/**
 * Provides the main search form's html
 *
 * @param string  $searched           Keyword/Regular expression to be searched
 * @param integer $criteriaSearchType Type of search (one word, phrase etc.)
 * @param array   $tables_names_only  Names of all tables
 * @param array   $criteriaTables     Tables on which search is to be performed
 * @param array   $url_params         URL parameters
 * @param string  $criteriaColumnName Restrict the search to this column
 *
 * @return string HTML for selection form
 */
function PMA_dbSearchGetSelectionForm($searched, $criteriaSearchType,
    $tables_names_only, $criteriaTables, $url_params, $criteriaColumnName = null
) {
    $html_output = '<a id="db_search"></a>';
    $html_output .= '<form id="db_search_form"'
        . ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : '')
        . ' method="post" action="db_search.php" name="db_search">';
    $html_output .= PMA_generate_common_hidden_inputs($GLOBALS['db']);
    $html_output .= '<fieldset>';
    // set legend caption
    $html_output .= '<legend>' . __('Search in database') . '</legend>';
    $html_output .= '<table class="formlayout">';
    // inputbox for search phrase
    $html_output .= '<tr>';
    $html_output .= '<td>' . __('Words or values to search for (wildcard: "%"):')
        . '</td>';
    $html_output .= '<td><input type="text" name="criteriaSearchString" size="60"'
        . ' value="' . $searched . '" /></td>';
    $html_output .= '</tr>';
    // choices for types of search
    $html_output .= '<tr>';
    $html_output .= '<td class="right vtop">' . __('Find:') . '</td>';
    $html_output .= '<td>';
    $choices = array(
        '1' => __('at least one of the words') . PMA_showHint(__('Words are separated by a space character (" ").')),
        '2' => __('all words') . PMA_showHint(__('Words are separated by a space character (" ").')),
        '3' => __('the exact phrase'),
        '4' => __('as regular expression') . ' ' . PMA_showMySQLDocu('Regexp', 'Regexp')
    );
    // 4th parameter set to true to add line breaks
    // 5th parameter set to false to avoid htmlspecialchars() escaping in the label
    //  since we have some HTML in some labels
    $html_output .= PMA_getRadioFields(
        'criteriaSearchType', $choices, $criteriaSearchType, true, false
    );
    $html_output .= '</td></tr>';
    // displays table names as select options 
    $html_output .= '<tr>';
    $html_output .= '<td class="right vtop">' . __('Inside tables:') . '</td>';
    $html_output .= '<td rowspan="2">';
    $html_output .= '<select name="criteriaTables[]" size="6" multiple="multiple">';
    foreach ($tables_names_only as $each_table) {
        if (in_array($each_table, $criteriaTables)) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
        $html_output .= '<option value="' . htmlspecialchars($each_table) . '"'
            . $is_selected . '>'
            . str_replace(' ', '&nbsp;', htmlspecialchars($each_table))
            . '</option>';
    } // end for
    $html_output .= '</select>';
    $alter_select
        = '<a href="db_search.php' . PMA_generate_common_url(array_merge($url_params, array('selectall' => 1))) . '#db_search"'
        . ' onclick="setSelectOptions(\'db_search\', \'criteriaTables[]\', true); return false;">' . __('Select All') . '</a>'
        . '&nbsp;/&nbsp;'
        . '<a href="db_search.php' . PMA_generate_common_url(array_merge($url_params, array('unselectall' => 1))) . '#db_search"'
        . ' onclick="setSelectOptions(\'db_search\', \'criteriaTables[]\', false); return false;">' . __('Unselect All') . '</a>';
    $html_output .= '</td></tr>';
    $html_output .= '<tr><td class="right vbottom">' . $alter_select . '</td></tr>';
    $html_output .= '<tr>';
    $html_output .= '<td class="right">' . __('Inside column:') . '</td>';
    $html_output .= '<td><input type="text" name="criteriaColumnName" size="60"'
        . 'value="' . (! empty($criteriaColumnName) ? htmlspecialchars($criteriaColumnName) : '')
        . '" /></td>';
    $html_output .= '</tr>';
    $html_output .= '</table>';
    $html_output .= '</fieldset>';
    $html_output .= '<fieldset class="tblFooters">';
    $html_output .= '<input type="submit" name="submit_search" value="'
        . __('Go') . '" id="buttonGo" />';
    $html_output .= '</fieldset>';
    $html_output .= '</form>';
    $html_output .= getResultDivs();
    
    return $html_output;
}

/**
 * Provides div tags for browsing search results and sql query form.
 *
 * @return string div tags
 */
function getResultDivs()
{
    $html_output = '<!-- These two table-image and table-link elements display'
        . ' the table name in browse search results  -->';
    $html_output .= '<div id="table-info">';
    $html_output .= '<a class="item" id="table-link" ></a>';
    $html_output .= '</div>';
    // div for browsing results
    $html_output .= '<div id="browse-results">';
    $html_output .= '<!-- this browse-results div is used to load the browse and delete'
        . ' results in the db search -->';
    $html_output .= '</div>';
    $html_output .= '<br class="clearfloat" />';
    $html_output .= '<div id="sqlqueryform">';
    $html_output .= '<!-- this sqlqueryform div is used to load the delete form in'
        . ' the db search -->';
    $html_output .= '</div>';
    $html_output .= '<!--  toggle query box link-->';
    $html_output .= '<a id="togglequerybox"></a>';
    return $html_output;
}
?>
