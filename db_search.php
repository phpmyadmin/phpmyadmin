<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * searchs the entire database
 *
 * @todo    make use of UNION when searching multiple tables
 * @todo    display executed query, optional?
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_search.js');
$scripts->addFile('sql.js');
$scripts->addFile('makegrid.js');
$scripts->addFile('jquery/timepicker.js');

/**
 * Gets some core libraries and send headers
 */
require 'libraries/db_common.inc.php';

/**
 * init
 */
// If config variable $GLOBALS['cfg']['Usedbsearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    PMA_mysqlDie(__('Access denied'), '', false, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

/**
 * @global array list of tables from the current database
 * but do not clash with $tables coming from db_info.inc.php
 */
$tables_names_only = PMA_DBI_get_tables($GLOBALS['db']);

$search_options = array(
    '1' => __('at least one of the words'),
    '2' => __('all words'),
    '3' => __('the exact phrase'),
    '4' => __('as regular expression'),
);

if (empty($_REQUEST['search_option'])
    || ! is_string($_REQUEST['search_option'])
    || ! array_key_exists($_REQUEST['search_option'], $search_options)
) {
    $search_option = 1;
    unset($_REQUEST['submit_search']);
} else {
    $search_option = (int) $_REQUEST['search_option'];
    $option_str = $search_options[$_REQUEST['search_option']];
}

if (empty($_REQUEST['search_str']) || ! is_string($_REQUEST['search_str'])) {
    unset($_REQUEST['submit_search']);
    $searched = '';
} else {
    $searched = htmlspecialchars($_REQUEST['search_str']);
    // For "as regular expression" (search option 4), we should not treat
    // this as an expression that contains a LIKE (second parameter of
    // PMA_sqlAddSlashes()).
    //
    // Usage example: If user is seaching for a literal $ in a regexp search,
    // he should enter \$ as the value.
    $search_str = PMA_sqlAddSlashes(
        $_REQUEST['search_str'], ($search_option == 4 ? false : true)
    );
}

$tables_selected = array();
if (empty($_REQUEST['table_select']) || ! is_array($_REQUEST['table_select'])) {
    unset($_REQUEST['submit_search']);
} elseif (! isset($_REQUEST['selectall']) && ! isset($_REQUEST['unselectall'])) {
    $tables_selected = array_intersect($_REQUEST['table_select'], $tables_names_only);
}

if (isset($_REQUEST['selectall'])) {
    $tables_selected = $tables_names_only;
} elseif (isset($_REQUEST['unselectall'])) {
    $tables_selected = array();
}

if (empty($_REQUEST['field_str']) || ! is_string($_REQUEST['field_str'])) {
    unset($field_str);
} else {
    $field_str = PMA_sqlAddSlashes($_REQUEST['field_str'], true);
}

/**
 * Displays top links if we are not in an Ajax request
 */
$sub_part = '';

if ( $GLOBALS['is_ajax_request'] != true) {
    include 'libraries/db_info.inc.php';
    $response->addHTML('<div id="searchresults">');
}

/**
 * 1. Main search form has been submitted
 */
if (isset($_REQUEST['submit_search'])) {

    /**
     * Builds the SQL search query
     *
     * @param string  $table         the table name
     * @param string  $field         restrict the search to this field
     * @param string  $search_str    the string to search
     * @param integer $search_option type of search
     *                               (1 -> 1 word at least, 2 -> all words,
     *                                3 -> exact string, 4 -> regexp)
     *
     * @return array    3 SQL querys (for count, display and delete results)
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
    function PMA_getSearchSqls($table, $field, $search_str, $search_option)
    {
        // Statement types
        $sqlstr_select = 'SELECT';
        $sqlstr_delete = 'DELETE';

        // Fields to select
        $tblfields = PMA_DBI_get_columns($GLOBALS['db'], $table);

        // Table to use
        $sqlstr_from = ' FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($table);

        $search_words    = (($search_option > 2) ? array($search_str) : explode(' ', $search_str));

        $like_or_regex   = (($search_option == 4) ? 'REGEXP' : 'LIKE');
        $automatic_wildcard   = (($search_option < 3) ? '%' : '');

        $fieldslikevalues = array();
        foreach ($search_words as $search_word) {
            // Eliminates empty values
            if (strlen($search_word) === 0) {
                continue;
            }

            $thefieldlikevalue = array();
            foreach ($tblfields as $tblfield) {
                if (! isset($field) || strlen($field) == 0 || $tblfield['Field'] == $field) {
                    // Drizzle has no CONVERT and all text columns are UTF-8
                    if (PMA_DRIZZLE) {
                        $thefieldlikevalue[] = PMA_backquote($tblfield['Field'])
                            . ' ' . $like_or_regex . ' '
                            . "'" . $automatic_wildcard
                            . $search_word
                            . $automatic_wildcard . "'";
                    } else {
                        $thefieldlikevalue[] = 'CONVERT(' . PMA_backquote($tblfield['Field']) . ' USING utf8)'
                            . ' ' . $like_or_regex . ' '
                            . "'" . $automatic_wildcard
                            . $search_word
                            . $automatic_wildcard . "'";
                    }
                }
            } // end for

            if (count($thefieldlikevalue) > 0) {
                $fieldslikevalues[]      = implode(' OR ', $thefieldlikevalue);
            }
        } // end for

        $implode_str  = ($search_option == 1 ? ' OR ' : ' AND ');
        if ( empty($fieldslikevalues)) {
            // this could happen when the "inside field" does not exist
            // in any selected tables
            $sqlstr_where = ' WHERE FALSE';
        } else {
            $sqlstr_where = ' WHERE (' . implode(') ' . $implode_str . ' (', $fieldslikevalues) . ')';
        }
        unset($fieldslikevalues);

        // Builds complete queries
        $sql['select_fields'] = $sqlstr_select . ' * ' . $sqlstr_from . $sqlstr_where;
        // here, I think we need to still use the COUNT clause, even for
        // VIEWs, anyway we have a WHERE clause that should limit results
        $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS `count`' . $sqlstr_from . $sqlstr_where;
        $sql['delete']        = $sqlstr_delete . $sqlstr_from . $sqlstr_where;

        return $sql;
    } // end of the "PMA_getSearchSqls()" function
    $response->addHTML(
        PMA_dbSearchGetSearchResults(
            $tables_selected, $searched, $option_str,
            $search_str, $search_option, (! empty($field_str) ? $field_str : '')
        )
    );
} // end 1.

/**
 * Displays database search results
 *
 * @param array   $tables_selected Tables on which search is to be performed
 * @param string  $searched        The search word/phrase/regexp
 * @param string  $option_str      Type of search
 * @param string  $search_str      the string to search
 * @param integer $search_option   type of search
 *                                 (1 -> 1 word at least, 2 -> all words,
 *                                 3 -> exact string, 4 -> regexp)
 * @param string  $field_str       Restrict the search to this field
 *
 * @return string HTML for search results
 */
function PMA_dbSearchGetSearchResults($tables_selected, $searched, $option_str,
    $search_str, $search_option, $field_str = null
) {
    $this_url_params = array(
        'db'    => $GLOBALS['db'],
        'goto'  => 'db_sql.php',
        'pos'   => 0,
        'is_js_confirmed' => 0,
    );
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
    foreach ($tables_selected as $each_table) {
        // Gets the SQL statements
        $newsearchsqls = PMA_getSearchSqls(
            $each_table, (! empty($field_str) ? $field_str : ''),
            $search_str, $search_option
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

    if (count($tables_selected) > 1) {
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
        $this_url_params['sql_query'] = $newsearchsqls['select_fields'];
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
 * If we are in an Ajax request, we need to exit after displaying all the HTML
 */
if ($GLOBALS['is_ajax_request'] == true) {
    exit;
} else {
    $response->addHTML('</div>');//end searchresults div
}

/**
 * Provides the main search form's html
 *
 * @param string  $searched          Keyword/Regular expression to be searched
 * @param integer $search_option     Type of search (one word, phrase etc.)
 * @param array   $tables_names_only Names of all tables
 * @param array   $tables_selected   Tables on which search is to be performed
 * @param array   $url_params        URL parameters
 * @param string  $field_str         Restrict the search to this field
 *
 * @return string HTML for selection form
 */
function PMA_dbSearchGetSelectionForm($searched, $search_option, $tables_names_only,
    $tables_selected, $url_params, $field_str = null
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
    $html_output .= '<td><input type="text" name="search_str" size="60"'
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
        'search_option', $choices, $search_option, true, false
    );
    $html_output .= '</td></tr>';
    // displays table names as select options 
    $html_output .= '<tr>';
    $html_output .= '<td class="right vtop">' . __('Inside tables:') . '</td>';
    $html_output .= '<td rowspan="2">';
    $html_output .= '<select name="table_select[]" size="6" multiple="multiple">';
    foreach ($tables_names_only as $each_table) {
        if (in_array($each_table, $tables_selected)) {
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
        . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', true); return false;">' . __('Select All') . '</a>'
        . '&nbsp;/&nbsp;'
        . '<a href="db_search.php' . PMA_generate_common_url(array_merge($url_params, array('unselectall' => 1))) . '#db_search"'
        . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', false); return false;">' . __('Unselect All') . '</a>';
    $html_output .= '</td></tr>';
    $html_output .= '<tr><td class="right vbottom">' . $alter_select . '</td></tr>';
    $html_output .= '<tr>';
    $html_output .= '<td class="right">' . __('Inside column:') . '</td>';
    $html_output .= '<td><input type="text" name="field_str" size="60"'
        . 'value="' . (! empty($field_str) ? htmlspecialchars($field_str) : '')
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

$response->addHTML(
    PMA_dbSearchGetSelectionForm(
        $searched, $search_option, $tables_names_only, $tables_selected, $url_params,
        (! empty($field_str) ? $field_str : '')
    )
);
?>
