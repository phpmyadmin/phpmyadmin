<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles Database Search
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Class to handle database search
 *
 * @package PhpMyAdmin
 */
class DbSearch
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;
    /**
     * Table Names
     *
     * @access private
     * @var array
     */
    private $_tables_names_only;
    /**
     * Type of search
     *
     * @access private
     * @var array
     */
    private $_searchTypes;
    /**
     * Already set search type
     *
     * @access private
     * @var integer
     */
    private $_criteriaSearchType;
    /**
     * Already set search type's description
     *
     * @access private
     * @var string
     */
    private $_searchTypeDescription;
    /**
     * Search string/regexp
     *
     * @access private
     * @var string
     */
    private $_criteriaSearchString;
    /**
     * Criteria Tables to search in
     *
     * @access private
     * @var array
     */
    private $_criteriaTables;
    /**
     * Restrict the search to this column
     *
     * @access private
     * @var string
     */
    private $_criteriaColumnName;

    /**
     * Public Constructor
     *
     * @param string $db Database name
     */
    public function __construct($db)
    {
        $this->_db = $db;
        // Sets criteria parameters
        $this->_setSearchParams();
    }

    /**
     * Sets search parameters
     *
     * @return void
     */
    private function _setSearchParams()
    {
        $this->_tables_names_only = $GLOBALS['dbi']->getTables($this->_db);

        $this->_searchTypes = array(
            '1' => __('at least one of the words'),
            '2' => __('all words'),
            '3' => __('the exact phrase'),
            '4' => __('as regular expression'),
        );

        if (empty($_REQUEST['criteriaSearchType'])
            || ! is_string($_REQUEST['criteriaSearchType'])
            || ! array_key_exists(
                $_REQUEST['criteriaSearchType'],
                $this->_searchTypes
            )
        ) {
            $this->_criteriaSearchType = 1;
            unset($_REQUEST['submit_search']);
        } else {
            $this->_criteriaSearchType = (int) $_REQUEST['criteriaSearchType'];
            $this->_searchTypeDescription
                = $this->_searchTypes[$_REQUEST['criteriaSearchType']];
        }

        if (empty($_REQUEST['criteriaSearchString'])
            || ! is_string($_REQUEST['criteriaSearchString'])
        ) {
            $this->_criteriaSearchString = '';
            unset($_REQUEST['submit_search']);
        } else {
            $this->_criteriaSearchString = $_REQUEST['criteriaSearchString'];
        }

        $this->_criteriaTables = array();
        if (empty($_REQUEST['criteriaTables'])
            || ! is_array($_REQUEST['criteriaTables'])
        ) {
            unset($_REQUEST['submit_search']);
        } else {
            $this->_criteriaTables = array_intersect(
                $_REQUEST['criteriaTables'], $this->_tables_names_only
            );
        }

        if (empty($_REQUEST['criteriaColumnName'])
            || ! is_string($_REQUEST['criteriaColumnName'])
        ) {
            unset($this->_criteriaColumnName);
        } else {
            $this->_criteriaColumnName = $GLOBALS['dbi']->escapeString(
                $_REQUEST['criteriaColumnName']
            );
        }
    }

    /**
     * Builds the SQL search query
     *
     * @param string $table The table name
     *
     * @return array 3 SQL queries (for count, display and delete results)
     *
     * @todo    can we make use of fulltextsearch IN BOOLEAN MODE for this?
     * PMA_backquote
     * DatabaseInterface::freeResult
     * DatabaseInterface::fetchAssoc
     * $GLOBALS['db']
     * explode
     * count
     * strlen
     */
    private function _getSearchSqls($table)
    {
        // Statement types
        $sqlstr_select = 'SELECT';
        $sqlstr_delete = 'DELETE';
        // Table to use
        $sqlstr_from = ' FROM '
            . Util::backquote($GLOBALS['db']) . '.'
            . Util::backquote($table);
        // Gets where clause for the query
        $where_clause = $this->_getWhereClause($table);
        // Builds complete queries
        $sql = array();
        $sql['select_columns'] = $sqlstr_select . ' * ' . $sqlstr_from
            . $where_clause;
        // here, I think we need to still use the COUNT clause, even for
        // VIEWs, anyway we have a WHERE clause that should limit results
        $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS `count`'
            . $sqlstr_from . $where_clause;
        $sql['delete']        = $sqlstr_delete . $sqlstr_from . $where_clause;

        return $sql;
    }

    /**
     * Provides where clause for building SQL query
     *
     * @param string $table The table name
     *
     * @return string The generated where clause
     */
    private function _getWhereClause($table)
    {
        // Columns to select
        $allColumns = $GLOBALS['dbi']->getColumns($GLOBALS['db'], $table);
        $likeClauses = array();
        // Based on search type, decide like/regex & '%'/''
        $like_or_regex   = (($this->_criteriaSearchType == 4) ? 'REGEXP' : 'LIKE');
        $automatic_wildcard   = (($this->_criteriaSearchType < 3) ? '%' : '');
        // For "as regular expression" (search option 4), LIKE won't be used
        // Usage example: If user is searching for a literal $ in a regexp search,
        // he should enter \$ as the value.
        $criteriaSearchStringEscaped = $GLOBALS['dbi']->escapeString(
            $this->_criteriaSearchString
        );
        // Extract search words or pattern
        $search_words = (($this->_criteriaSearchType > 2)
            ? array($criteriaSearchStringEscaped)
            : explode(' ', $criteriaSearchStringEscaped));

        foreach ($search_words as $search_word) {
            // Eliminates empty values
            if (mb_strlen($search_word) === 0) {
                continue;
            }
            $likeClausesPerColumn = array();
            // for each column in the table
            foreach ($allColumns as $column) {
                if (! isset($this->_criteriaColumnName)
                    || mb_strlen($this->_criteriaColumnName) == 0
                    || $column['Field'] == $this->_criteriaColumnName
                ) {
                    $column = 'CONVERT(' . Util::backquote($column['Field'])
                            . ' USING utf8)';
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
        // Use 'OR' if 'at least one word' is to be searched, else use 'AND'
        $implode_str  = ($this->_criteriaSearchType == 1 ? ' OR ' : ' AND ');
        if (empty($likeClauses)) {
            // this could happen when the "inside column" does not exist
            // in any selected tables
            $where_clause = ' WHERE FALSE';
        } else {
            $where_clause = ' WHERE ('
                . implode(') ' . $implode_str . ' (', $likeClauses)
                . ')';
        }
        return $where_clause;
    }

    /**
     * Displays database search results
     *
     * @return string HTML for search results
     */
    public function getSearchResults()
    {
        $html_output = '';
        // Displays search string
        $html_output .= '<br />'
            . '<table class="data">'
            . '<caption class="tblHeaders">'
            . sprintf(
                __('Search results for "<i>%s</i>" %s:'),
                htmlspecialchars($this->_criteriaSearchString),
                $this->_searchTypeDescription
            )
            . '</caption>';

        $num_search_result_total = 0;
        $odd_row = true;
        // For each table selected as search criteria
        foreach ($this->_criteriaTables as $each_table) {
            // Gets the SQL statements
            $newsearchsqls = $this->_getSearchSqls($each_table);
            // Executes the "COUNT" statement
            $res_cnt = intval($GLOBALS['dbi']->fetchValue($newsearchsqls['select_count']));
            $num_search_result_total += $res_cnt;
            // Gets the result row's HTML for a table
            $html_output .= $this->_getResultsRow(
                $each_table, $newsearchsqls, $odd_row, $res_cnt
            );
            $odd_row = ! $odd_row;
        } // end for
        $html_output .= '</table>';
        // Displays total number of matches
        if (count($this->_criteriaTables) > 1) {
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
     * @param string  $each_table    One of the tables on which search was performed
     * @param array   $newsearchsqls Contains SQL queries
     * @param bool    $odd_row       For displaying contrasting table rows
     * @param integer $res_cnt       Number of results found
     *
     * @return string HTML row
     */
    private function _getResultsRow($each_table, $newsearchsqls, $odd_row, $res_cnt)
    {
        $this_url_params = array(
            'db'    => $GLOBALS['db'],
            'table' => $each_table,
            'goto'  => 'db_sql.php',
            'pos'   => 0,
            'is_js_confirmed' => 0,
        );
        // Start forming search results row
        $html_output = '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        // Displays results count for a table
        $html_output .= '<td>';
        $html_output .= sprintf(
            _ngettext(
                '%1$s match in <strong>%2$s</strong>',
                '%1$s matches in <strong>%2$s</strong>', $res_cnt
            ),
            $res_cnt, htmlspecialchars($each_table)
        );
        $html_output .= '</td>';
        // Displays browse/delete link if result count > 0
        if ($res_cnt > 0) {
            $this_url_params['db'] = htmlspecialchars($GLOBALS['db']);
            $this_url_params['table'] = htmlspecialchars($each_table);
            $browse_result_path = 'sql.php' . PMA_URL_getCommon($this_url_params);
            $html_output .= '<td><a name="browse_search" '
                . ' class="ajax browse_results" href="'
                . $browse_result_path . '" '
                . 'data-browse-sql="'
                . htmlspecialchars($newsearchsqls['select_columns']). '" '
                . 'data-table-name="' . htmlspecialchars($each_table) . '" >'
                . __('Browse') . '</a></td>';

            $delete_result_path = $browse_result_path;
            $html_output .= '<td><a name="delete_search" class="ajax delete_results"'
                . ' href="' . $delete_result_path . '"'
                . ' data-delete-sql="' . htmlspecialchars($newsearchsqls['delete']) . '"'
                . ' data-table-name="' . htmlspecialchars($each_table) . '" >'
                . __('Delete') . '</a></td>';
        } else {
            $html_output .= '<td>&nbsp;</td>'
                . '<td>&nbsp;</td>';
        }// end if else
        $html_output .= '</tr>';
        return $html_output;
    }

    /**
     * Provides the main search form's html
     *
     * @return string HTML for selection form
     */
    public function getSelectionForm()
    {
        $html_output = '<a id="db_search"></a>';
        $html_output .= '<form id="db_search_form"'
            . ' class="ajax lock-page"'
            . ' method="post" action="db_search.php" name="db_search">';
        $html_output .= PMA_URL_getHiddenInputs($GLOBALS['db']);
        $html_output .= '<fieldset>';
        // set legend caption
        $html_output .= '<legend>' . __('Search in database') . '</legend>';
        $html_output .= '<table class="formlayout">';
        // inputbox for search phrase
        $html_output .= '<tr>';
        $html_output .= '<td>' . __('Words or values to search for (wildcard: "%"):')
            . '</td>';
        $html_output .= '<td><input type="text"'
            . ' name="criteriaSearchString" size="60"'
            . ' value="' . htmlspecialchars($this->_criteriaSearchString) . '" />';
        $html_output .= '</td>';
        $html_output .= '</tr>';
        // choices for types of search
        $html_output .= '<tr>';
        $html_output .= '<td class="right vtop">' . __('Find:') . '</td>';
        $html_output .= '<td>';
        $choices = array(
            '1' => __('at least one of the words')
                . Util::showHint(
                    __('Words are separated by a space character (" ").')
                ),
            '2' => __('all words')
                . Util::showHint(
                    __('Words are separated by a space character (" ").')
                ),
            '3' => __('the exact phrase'),
            '4' => __('as regular expression') . ' '
                . Util::showMySQLDocu('Regexp')
        );
        // 4th parameter set to true to add line breaks
        // 5th parameter set to false to avoid htmlspecialchars() escaping
        // in the label since we have some HTML in some labels
        $html_output .= Util::getRadioFields(
            'criteriaSearchType', $choices, $this->_criteriaSearchType, true, false
        );
        $html_output .= '</td></tr>';
        // displays table names as select options
        $html_output .= '<tr>';
        $html_output .= '<td class="right vtop">' . __('Inside tables:') . '</td>';
        $html_output .= '<td rowspan="2">';
        $html_output .= '<select name="criteriaTables[]" size="6"'
            . ' multiple="multiple">';
        foreach ($this->_tables_names_only as $each_table) {
            if (in_array($each_table, $this->_criteriaTables)) {
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
        $html_output .= '</td></tr>';
        // Displays 'select all' and 'unselect all' links
        $alter_select = '<a href="#" '
            . 'onclick="setSelectOptions(\'db_search\','
            . ' \'criteriaTables[]\', true); return false;">'
            . __('Select all') . '</a> &nbsp;/&nbsp;';
        $alter_select .= '<a href="#" '
            . 'onclick="setSelectOptions(\'db_search\','
            . ' \'criteriaTables[]\', false); return false;">'
            . __('Unselect all') . '</a>';
        $html_output .= '<tr><td class="right vbottom">'
            . $alter_select . '</td></tr>';
        // Inputbox for column name entry
        $html_output .= '<tr>';
        $html_output .= '<td class="right">' . __('Inside column:') . '</td>';
        $html_output .= '<td><input type="text" name="criteriaColumnName" size="60"'
            . 'value="'
            . (! empty($this->_criteriaColumnName)
                ? htmlspecialchars($this->_criteriaColumnName)
                : '')
            . '" /></td>';
        $html_output .= '</tr>';
        $html_output .= '</table>';
        $html_output .= '</fieldset>';
        $html_output .= '<fieldset class="tblFooters">';
        $html_output .= '<input type="submit" name="submit_search" value="'
            . __('Go') . '" id="buttonGo" />';
        $html_output .= '</fieldset>';
        $html_output .= '</form>';
        $html_output .= '<div id="togglesearchformdiv">'
            . '<a id="togglesearchformlink"></a></div>';

        return $html_output;
    }

    /**
     * Provides div tags for browsing search results and sql query form.
     *
     * @return string div tags
     */
    public function getResultDivs()
    {
        $html_output = '<!-- These two table-image and table-link elements display'
            . ' the table name in browse search results  -->';
        $html_output .= '<div id="table-info">';
        $html_output .= '<a class="item" id="table-link" ></a>';
        $html_output .= '</div>';
        // div for browsing results
        $html_output .= '<div id="browse-results">';
        $html_output .= '<!-- this browse-results div is used to load the browse'
            . ' and delete results in the db search -->';
        $html_output .= '</div>';
        $html_output .= '<br class="clearfloat" />';
        $html_output .= '<div id="sqlqueryform">';
        $html_output .= '<!-- this sqlqueryform div is used to load the delete'
            . ' form in the db search -->';
        $html_output .= '</div>';
        $html_output .= '<!--  toggle query box link-->';
        $html_output .= '<a id="togglequerybox"></a>';
        return $html_output;
    }
}
