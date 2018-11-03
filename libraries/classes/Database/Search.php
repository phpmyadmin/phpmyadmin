<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles Database Search
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Class to handle database search
 *
 * @package PhpMyAdmin
 */
class Search
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $db;

    /**
     * Table Names
     *
     * @access private
     * @var array
     */
    private $tablesNamesOnly;

    /**
     * Type of search
     *
     * @access private
     * @var array
     */
    private $searchTypes;

    /**
     * Already set search type
     *
     * @access private
     * @var integer
     */
    private $criteriaSearchType;

    /**
     * Already set search type's description
     *
     * @access private
     * @var string
     */
    private $searchTypeDescription;

    /**
     * Search string/regexp
     *
     * @access private
     * @var string
     */
    private $criteriaSearchString;

    /**
     * Criteria Tables to search in
     *
     * @access private
     * @var array
     */
    private $criteriaTables;

    /**
     * Restrict the search to this column
     *
     * @access private
     * @var string
     */
    private $criteriaColumnName;

    /**
     * Public Constructor
     *
     * @param string $db Database name
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->searchTypes = array(
            '1' => __('at least one of the words'),
            '2' => __('all of the words'),
            '3' => __('the exact phrase as substring'),
            '4' => __('the exact phrase as whole field'),
            '5' => __('as regular expression'),
        );
        // Sets criteria parameters
        $this->setSearchParams();
    }

    /**
     * Sets search parameters
     *
     * @return void
     */
    private function setSearchParams()
    {
        $this->tablesNamesOnly = $GLOBALS['dbi']->getTables($this->db);

        if (empty($_POST['criteriaSearchType'])
            || ! is_string($_POST['criteriaSearchType'])
            || ! array_key_exists(
                $_POST['criteriaSearchType'],
                $this->searchTypes
            )
        ) {
            $this->criteriaSearchType = 1;
            unset($_POST['submit_search']);
        } else {
            $this->criteriaSearchType = (int) $_POST['criteriaSearchType'];
            $this->searchTypeDescription
                = $this->searchTypes[$_POST['criteriaSearchType']];
        }

        if (empty($_POST['criteriaSearchString'])
            || ! is_string($_POST['criteriaSearchString'])
        ) {
            $this->criteriaSearchString = '';
            unset($_POST['submit_search']);
        } else {
            $this->criteriaSearchString = $_POST['criteriaSearchString'];
        }

        $this->criteriaTables = array();
        if (empty($_POST['criteriaTables'])
            || ! is_array($_POST['criteriaTables'])
        ) {
            unset($_POST['submit_search']);
        } else {
            $this->criteriaTables = array_intersect(
                $_POST['criteriaTables'], $this->tablesNamesOnly
            );
        }

        if (empty($_POST['criteriaColumnName'])
            || ! is_string($_POST['criteriaColumnName'])
        ) {
            unset($this->criteriaColumnName);
        } else {
            $this->criteriaColumnName = $GLOBALS['dbi']->escapeString(
                $_POST['criteriaColumnName']
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
    private function getSearchSqls($table)
    {
        // Statement types
        $sqlstr_select = 'SELECT';
        $sqlstr_delete = 'DELETE';
        // Table to use
        $sqlstr_from = ' FROM '
            . Util::backquote($GLOBALS['db']) . '.'
            . Util::backquote($table);
        // Gets where clause for the query
        $where_clause = $this->getWhereClause($table);
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
    private function getWhereClause($table)
    {
        // Columns to select
        $allColumns = $GLOBALS['dbi']->getColumns($GLOBALS['db'], $table);
        $likeClauses = array();
        // Based on search type, decide like/regex & '%'/''
        $like_or_regex   = (($this->criteriaSearchType == 5) ? 'REGEXP' : 'LIKE');
        $automatic_wildcard   = (($this->criteriaSearchType < 4) ? '%' : '');
        // For "as regular expression" (search option 5), LIKE won't be used
        // Usage example: If user is searching for a literal $ in a regexp search,
        // he should enter \$ as the value.
        $criteriaSearchStringEscaped = $GLOBALS['dbi']->escapeString(
            $this->criteriaSearchString
        );
        // Extract search words or pattern
        $search_words = (($this->criteriaSearchType > 2)
            ? array($criteriaSearchStringEscaped)
            : explode(' ', $criteriaSearchStringEscaped));

        foreach ($search_words as $search_word) {
            // Eliminates empty values
            if (strlen($search_word) === 0) {
                continue;
            }
            $likeClausesPerColumn = array();
            // for each column in the table
            foreach ($allColumns as $column) {
                if (! isset($this->criteriaColumnName)
                    || strlen($this->criteriaColumnName) === 0
                    || $column['Field'] == $this->criteriaColumnName
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
        $implode_str  = ($this->criteriaSearchType == 1 ? ' OR ' : ' AND ');
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
        $resultTotal = 0;
        $rows = [];
        // For each table selected as search criteria
        foreach ($this->criteriaTables as $eachTable) {
            // Gets the SQL statements
            $newSearchSqls = $this->getSearchSqls($eachTable);
            // Executes the "COUNT" statement
            $resultCount = intval($GLOBALS['dbi']->fetchValue(
                $newSearchSqls['select_count']
            ));
            $resultTotal += $resultCount;
            // Gets the result row's HTML for a table
            $rows[] = [
                'table' => htmlspecialchars($eachTable),
                'new_search_sqls' => $newSearchSqls,
                'result_count' => $resultCount,
            ];
        }

        return Template::get('database/search/results')->render([
            'db' => $this->db,
            'rows' => $rows,
            'result_total' => $resultTotal,
            'criteria_tables' => $this->criteriaTables,
            'criteria_search_string' => htmlspecialchars($this->criteriaSearchString),
            'search_type_description' => $this->searchTypeDescription,
        ]);
    }

    /**
     * Provides the main search form's html
     *
     * @return string HTML for selection form
     */
    public function getSelectionForm()
    {
        $choices = array(
            '1' => $this->searchTypes[1] . ' '
                . Util::showHint(
                    __('Words are separated by a space character (" ").')
                ),
            '2' => $this->searchTypes[2] . ' '
                . Util::showHint(
                    __('Words are separated by a space character (" ").')
                ),
            '3' => $this->searchTypes[3],
            '4' => $this->searchTypes[4],
            '5' => $this->searchTypes[5] . ' ' . Util::showMySQLDocu('Regexp')
        );
        return Template::get('database/search/selection_form')->render([
            'db' => $this->db,
            'choices' => $choices,
            'criteria_search_string' => $this->criteriaSearchString,
            'criteria_search_type' => $this->criteriaSearchType,
            'criteria_tables' => $this->criteriaTables,
            'tables_names_only' => $this->tablesNamesOnly,
            'criteria_column_name' => isset($this->criteriaColumnName)
                ? $this->criteriaColumnName : null,
        ]);
    }

    /**
     * Provides div tags for browsing search results and sql query form.
     *
     * @return string div tags
     */
    public function getResultDivs()
    {
        return Template::get('database/search/result_divs')->render();
    }
}
