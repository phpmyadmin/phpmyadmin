<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions for the SQL executor
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Table;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Set of functions for the SQL executor
 *
 * @package PhpMyAdmin
 */
class Sql
{
    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var RelationCleanup
     */
    private $relationCleanup;

    /**
     * @var Transformations
     */
    private $transformations;

    /**
     * @var Operations
     */
    private $operations;

    /**
     * @var Template
     */
    private $template;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relation = new Relation($GLOBALS['dbi']);
        $this->relationCleanup = new RelationCleanup($GLOBALS['dbi'], $this->relation);
        $this->operations = new Operations($GLOBALS['dbi'], $this->relation);
        $this->transformations = new Transformations();
        $this->template = new Template();
    }

    /**
     * Parses and analyzes the given SQL query.
     *
     * @param string $sql_query SQL query
     * @param string $db        DB name
     *
     * @return mixed
     */
    public function parseAndAnalyze($sql_query, $db = null)
    {
        if ($db === null && isset($GLOBALS['db']) && strlen($GLOBALS['db'])) {
            $db = $GLOBALS['db'];
        }
        list($analyzed_sql_results,,) = ParseAnalyze::sqlQuery($sql_query, $db);
        return $analyzed_sql_results;
    }

    /**
     * Handle remembered sorting order, only for single table query
     *
     * @param string $db                   database name
     * @param string $table                table name
     * @param array  $analyzed_sql_results the analyzed query results
     * @param string $full_sql_query       SQL query
     *
     * @return void
     */
    private function handleSortOrder(
        $db,
        $table,
        array &$analyzed_sql_results,
        &$full_sql_query
    ) {
        $pmatable = new Table($table, $db);

        if (empty($analyzed_sql_results['order'])) {
            // Retrieving the name of the column we should sort after.
            $sortCol = $pmatable->getUiProp(Table::PROP_SORTED_COLUMN);
            if (empty($sortCol)) {
                return;
            }

            // Remove the name of the table from the retrieved field name.
            $sortCol = str_replace(
                Util::backquote($table) . '.',
                '',
                $sortCol
            );

            // Create the new query.
            $full_sql_query = Query::replaceClause(
                $analyzed_sql_results['statement'],
                $analyzed_sql_results['parser']->list,
                'ORDER BY ' . $sortCol
            );

            // TODO: Avoid reparsing the query.
            $analyzed_sql_results = Query::getAll($full_sql_query);
        } else {
            // Store the remembered table into session.
            $pmatable->setUiProp(
                Table::PROP_SORTED_COLUMN,
                Query::getClause(
                    $analyzed_sql_results['statement'],
                    $analyzed_sql_results['parser']->list,
                    'ORDER BY'
                )
            );
        }
    }

    /**
     * Append limit clause to SQL query
     *
     * @param array $analyzed_sql_results the analyzed query results
     *
     * @return string limit clause appended SQL query
     */
    private function getSqlWithLimitClause(array &$analyzed_sql_results)
    {
        return Query::replaceClause(
            $analyzed_sql_results['statement'],
            $analyzed_sql_results['parser']->list,
            'LIMIT ' . $_SESSION['tmpval']['pos'] . ', '
            . $_SESSION['tmpval']['max_rows']
        );
    }

    /**
     * Verify whether the result set has columns from just one table
     *
     * @param array $fields_meta meta fields
     *
     * @return boolean whether the result set has columns from just one table
     */
    private function resultSetHasJustOneTable(array $fields_meta)
    {
        $just_one_table = true;
        $prev_table = '';
        foreach ($fields_meta as $one_field_meta) {
            if ($one_field_meta->table != ''
                && $prev_table != ''
                && $one_field_meta->table != $prev_table
            ) {
                $just_one_table = false;
            }
            if ($one_field_meta->table != '') {
                $prev_table = $one_field_meta->table;
            }
        }
        return $just_one_table && $prev_table != '';
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
    private function resultSetContainsUniqueKey($db, $table, array $fields_meta)
    {
        $columns = $GLOBALS['dbi']->getColumns($db, $table);
        $resultSetColumnNames = [];
        foreach ($fields_meta as $oneMeta) {
            $resultSetColumnNames[] = $oneMeta->name;
        }
        foreach (Index::getFromTable($table, $db) as $index) {
            if ($index->isUnique()) {
                $indexColumns = $index->getColumns();
                $numberFound = 0;
                foreach ($indexColumns as $indexColumnName => $dummy) {
                    if (in_array($indexColumnName, $resultSetColumnNames)) {
                        $numberFound++;
                    } elseif (! in_array($indexColumnName, $columns)) {
                        $numberFound++;
                    } elseif (strpos($columns[$indexColumnName]['Extra'], 'INVISIBLE') !== false) {
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
     * @return string html for the dropdown
     */
    private function getHtmlForRelationalColumnDropdown($db, $table, $column, $curr_value)
    {
        $foreigners = $this->relation->getForeigners($db, $table, $column);

        $foreignData = $this->relation->getForeignData(
            $foreigners,
            $column,
            false,
            '',
            ''
        );

        if ($foreignData['disp_row'] == null) {
            //Handle the case when number of values
            //is more than $cfg['ForeignKeyMaxLimit']
            $_url_params = [
                'db' => $db,
                'table' => $table,
                'field' => $column,
            ];

            $dropdown = $this->template->render('sql/relational_column_dropdown', [
                'current_value' => $_POST['curr_value'],
                'params' => $_url_params,
            ]);
        } else {
            $dropdown = $this->relation->foreignDropdown(
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
     * Get the HTML for the profiling table and accompanying chart if profiling is set.
     * Otherwise returns null
     *
     * @param string|null $urlQuery         url query
     * @param string      $database         current database
     * @param array       $profilingResults array containing the profiling info
     *
     * @return string html for the profiling table and chart
     */
    private function getHtmlForProfilingChart($urlQuery, $database, $profilingResults): string
    {
        if (! empty($profilingResults)) {
            $urlQuery = isset($urlQuery) ? $urlQuery : Url::getCommon(['db' => $database]);

            list(
                $detailedTable,
                $chartJson,
                $profilingStats
            ) = $this->analyzeAndGetTableHtmlForProfilingResults($profilingResults);

            return $this->template->render('sql/profiling_chart', [
                'url_query' => $urlQuery,
                'detailed_table' => $detailedTable,
                'states' => $profilingStats['states'],
                'total_time' => $profilingStats['total_time'],
                'chart_json' => $chartJson,
            ]);
        }
        return '';
    }

    /**
     * Function to get HTML for detailed profiling results table, profiling stats, and
     * $chart_json for displaying the chart.
     *
     * @param array $profiling_results profiling results
     *
     * @return mixed
     */
    private function analyzeAndGetTableHtmlForProfilingResults(
        $profiling_results
    ) {
        $profiling_stats = [
            'total_time' => 0,
            'states' => [],
        ];
        $chart_json = [];
        $i = 1;
        $table = '';
        foreach ($profiling_results as $one_result) {
            if (! isset($profiling_stats['states'][ucwords($one_result['Status'])])) {
                $profiling_stats['states'][ucwords($one_result['Status'])] = [
                    'total_time' => $one_result['Duration'],
                    'calls' => 1,
                ];
            }
            $profiling_stats['total_time'] += $one_result['Duration'];

            $table .= $this->template->render('sql/detailed_table', [
                'index' => $i++,
                'status' => $one_result['Status'],
                'duration' => $one_result['Duration'],
            ]);

            if (isset($chart_json[ucwords($one_result['Status'])])) {
                $chart_json[ucwords($one_result['Status'])]
                    += $one_result['Duration'];
            } else {
                $chart_json[ucwords($one_result['Status'])]
                    = $one_result['Duration'];
            }
        }
        return [
            $table,
            $chart_json,
            $profiling_stats,
        ];
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
     * @return string html for the dropdown
     */
    private function getHtmlForEnumColumnDropdown($db, $table, $column, $curr_value)
    {
        $values = $this->getValuesForColumn($db, $table, $column);
        return $this->template->render('sql/enum_column_dropdown', [
            'values' => $values,
            'selected_values' => [$curr_value],
        ]);
    }

    /**
     * Get value of a column for a specific row (marked by $where_clause)
     *
     * @param string $db           current database
     * @param string $table        current table
     * @param string $column       current column
     * @param string $where_clause where clause to select a particular row
     *
     * @return string with value
     */
    private function getFullValuesForSetColumn($db, $table, $column, $where_clause)
    {
        $result = $GLOBALS['dbi']->fetchSingleRow(
            "SELECT `$column` FROM `$db`.`$table` WHERE $where_clause"
        );

        return $result[$column];
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
     * @return string html for the set column
     */
    private function getHtmlForSetColumn($db, $table, $column, $curr_value): string
    {
        $values = $this->getValuesForColumn($db, $table, $column);

        $full_values = isset($_POST['get_full_values']) ? $_POST['get_full_values'] : false;
        $where_clause = isset($_POST['where_clause']) ? $_POST['where_clause'] : null;

        // If the $curr_value was truncated, we should
        // fetch the correct full values from the table
        if ($full_values && ! empty($where_clause)) {
            $curr_value = $this->getFullValuesForSetColumn(
                $db,
                $table,
                $column,
                $where_clause
            );
        }

        //converts characters of $curr_value to HTML entities
        $converted_curr_value = htmlentities(
            $curr_value,
            ENT_COMPAT,
            "UTF-8"
        );

        $selected_values = explode(',', $converted_curr_value);
        $select_size = (count($values) > 10) ? 10 : count($values);

        return $this->template->render('sql/set_column', [
            'size' => $select_size,
            'values' => $values,
            'selected_values' => $selected_values,
        ]);
    }

    /**
     * Get all the values for a enum column or set column in a table
     *
     * @param string $db     current database
     * @param string $table  current table
     * @param string $column current column
     *
     * @return array array containing the value list for the column
     */
    private function getValuesForColumn($db, $table, $column)
    {
        $field_info_query = $GLOBALS['dbi']->getColumnsSql($db, $table, $column);

        $field_info_result = $GLOBALS['dbi']->fetchResult(
            $field_info_query,
            null,
            null,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );

        return Util::parseEnumSetValues($field_info_result[0]['Type']);
    }

    /**
     * Function to get html for bookmark support if bookmarks are enabled. Else will
     * return null
     *
     * @param array       $displayParts   the parts to display
     * @param array       $cfgBookmark    configuration setting for bookmarking
     * @param string      $sql_query      sql query
     * @param string      $db             current database
     * @param string      $table          current table
     * @param string|null $complete_query complete query
     * @param string      $bkm_user       bookmarking user
     *
     * @return string
     */
    public function getHtmlForBookmark(
        array $displayParts,
        array $cfgBookmark,
        $sql_query,
        $db,
        $table,
        ?string $complete_query,
        $bkm_user
    ): string {
        if ($displayParts['bkm_form'] == '1'
            && (! empty($cfgBookmark) && empty($_GET['id_bookmark']))
            && ! empty($sql_query)
        ) {
            return $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => 'sql.php' . Url::getCommon([
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sql_query,
                    'id_bookmark' => 1,
                ]),
                'user' => $bkm_user,
                'sql_query' => isset($complete_query) ? $complete_query : $sql_query,
            ]);
        }
        return '';
    }

    /**
     * Function to check whether to remember the sorting order or not
     *
     * @param array $analyzed_sql_results the analyzed query and other variables set
     *                                    after analyzing the query
     *
     * @return boolean
     */
    private function isRememberSortingOrder(array $analyzed_sql_results)
    {
        return $GLOBALS['cfg']['RememberSorting']
            && ! ($analyzed_sql_results['is_count']
                || $analyzed_sql_results['is_export']
                || $analyzed_sql_results['is_func']
                || $analyzed_sql_results['is_analyse'])
            && $analyzed_sql_results['select_from']
            && isset($analyzed_sql_results['select_expr'])
            && isset($analyzed_sql_results['select_tables'])
            && (empty($analyzed_sql_results['select_expr'])
                || ((count($analyzed_sql_results['select_expr']) === 1)
                    && ($analyzed_sql_results['select_expr'][0] == '*')))
            && count($analyzed_sql_results['select_tables']) === 1;
    }

    /**
     * Function to check whether the LIMIT clause should be appended or not
     *
     * @param array $analyzed_sql_results the analyzed query and other variables set
     *                                    after analyzing the query
     *
     * @return boolean
     */
    private function isAppendLimitClause(array $analyzed_sql_results)
    {
        // Assigning LIMIT clause to an syntactically-wrong query
        // is not needed. Also we would want to show the true query
        // and the true error message to the query executor

        return (isset($analyzed_sql_results['parser'])
            && count($analyzed_sql_results['parser']->errors) === 0)
            && ($_SESSION['tmpval']['max_rows'] != 'all')
            && ! ($analyzed_sql_results['is_export']
            || $analyzed_sql_results['is_analyse'])
            && ($analyzed_sql_results['select_from']
                || $analyzed_sql_results['is_subquery'])
            && empty($analyzed_sql_results['limit']);
    }

    /**
     * Function to check whether this query is for just browsing
     *
     * @param array        $analyzed_sql_results the analyzed query and other variables set
     *                                           after analyzing the query
     * @param boolean|null $find_real_end        whether the real end should be found
     *
     * @return boolean
     */
    public function isJustBrowsing(array $analyzed_sql_results, ?bool $find_real_end): bool
    {
        return ! $analyzed_sql_results['is_group']
            && ! $analyzed_sql_results['is_func']
            && empty($analyzed_sql_results['union'])
            && empty($analyzed_sql_results['distinct'])
            && $analyzed_sql_results['select_from']
            && (count($analyzed_sql_results['select_tables']) === 1)
            && (empty($analyzed_sql_results['statement']->where)
                || (count($analyzed_sql_results['statement']->where) === 1
                    && $analyzed_sql_results['statement']->where[0]->expr === '1'))
            && empty($analyzed_sql_results['group'])
            && ! isset($find_real_end)
            && ! $analyzed_sql_results['is_subquery']
            && ! $analyzed_sql_results['join']
            && empty($analyzed_sql_results['having']);
    }

    /**
     * Function to check whether the related transformation information should be deleted
     *
     * @param array $analyzed_sql_results the analyzed query and other variables set
     *                                    after analyzing the query
     *
     * @return boolean
     */
    private function isDeleteTransformationInfo(array $analyzed_sql_results)
    {
        return ! empty($analyzed_sql_results['querytype'])
            && (($analyzed_sql_results['querytype'] == 'ALTER')
                || ($analyzed_sql_results['querytype'] == 'DROP'));
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
    public function hasNoRightsToDropDatabase(
        array $analyzed_sql_results,
        $allowUserDropDatabase,
        $is_superuser
    ) {
        return ! $allowUserDropDatabase
            && isset($analyzed_sql_results['drop_database'])
            && $analyzed_sql_results['drop_database']
            && ! $is_superuser;
    }

    /**
     * Function to set a column property
     *
     * @param Table  $pmatable      Table instance
     * @param string $request_index col_order|col_visib
     *
     * @return boolean
     */
    private function setColumnProperty($pmatable, $request_index)
    {
        $property_value = array_map('intval', explode(',', $_POST[$request_index]));
        switch ($request_index) {
            case 'col_order':
                $property_to_set = Table::PROP_COLUMN_ORDER;
                break;
            case 'col_visib':
                $property_to_set = Table::PROP_COLUMN_VISIB;
                break;
            default:
                $property_to_set = '';
        }
        $retval = $pmatable->setUiProp(
            $property_to_set,
            $property_value,
            isset($_POST['table_create_time']) ? $_POST['table_create_time'] : null
        );
        if (gettype($retval) != 'boolean') {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $retval->getString());
            exit;
        }

        return $retval;
    }

    /**
     * Function to check the request for setting the column order or visibility
     *
     * @param string $table the current table
     * @param string $db    the current database
     *
     * @return void
     */
    public function setColumnOrderOrVisibility($table, $db)
    {
        $pmatable = new Table($table, $db);
        $retval = false;

        // set column order
        if (isset($_POST['col_order'])) {
            $retval = $this->setColumnProperty($pmatable, 'col_order');
        }

        // set column visibility
        if ($retval === true && isset($_POST['col_visib'])) {
            $retval = $this->setColumnProperty($pmatable, 'col_visib');
        }

        $response = Response::getInstance();
        $response->setRequestStatus($retval === true);
        exit;
    }

    /**
     * Function to add a bookmark
     *
     * @param string $goto goto page URL
     *
     * @return void
     */
    public function addBookmark($goto)
    {
        $bookmark = Bookmark::createBookmark(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            $_POST['bkm_fields'],
            (isset($_POST['bkm_all_users'])
                && $_POST['bkm_all_users'] == 'true' ? true : false
            )
        );
        $result = $bookmark->save();
        $response = Response::getInstance();
        if ($response->isAjax()) {
            if ($result) {
                $msg = Message::success(__('Bookmark %s has been created.'));
                $msg->addParam($_POST['bkm_fields']['bkm_label']);
                $response->addJSON('message', $msg);
            } else {
                $msg = Message::error(__('Bookmark not created!'));
                $response->setRequestStatus(false);
                $response->addJSON('message', $msg);
            }
            exit;
        } else {
            // go back to sql.php to redisplay query; do not use &amp; in this case:
            /**
             * @todo In which scenario does this happen?
             */
            Core::sendHeaderLocation(
                './' . $goto
                . '&label=' . $_POST['bkm_fields']['bkm_label']
            );
        }
    }

    /**
     * Function to find the real end of rows
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return mixed the number of rows if "retain" param is true, otherwise true
     */
    public function findRealEndOfRows($db, $table)
    {
        $unlim_num_rows = $GLOBALS['dbi']->getTable($db, $table)->countRecords(true);
        $_SESSION['tmpval']['pos'] = $this->getStartPosToDisplayRow($unlim_num_rows);

        return $unlim_num_rows;
    }

    /**
     * Function to get values for the relational columns
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return void
     */
    public function getRelationalValues($db, $table)
    {
        $column = $_POST['column'];
        if ($_SESSION['tmpval']['relational_display'] == 'D'
            && isset($_POST['relation_key_or_display_column'])
            && $_POST['relation_key_or_display_column']
        ) {
            $curr_value = $_POST['relation_key_or_display_column'];
        } else {
            $curr_value = $_POST['curr_value'];
        }
        $dropdown = $this->getHtmlForRelationalColumnDropdown(
            $db,
            $table,
            $column,
            $curr_value
        );
        $response = Response::getInstance();
        $response->addJSON('dropdown', $dropdown);
        exit;
    }

    /**
     * Function to get values for Enum or Set Columns
     *
     * @param string $db         the current database
     * @param string $table      the current table
     * @param string $columnType whether enum or set
     *
     * @return void
     */
    public function getEnumOrSetValues($db, $table, $columnType)
    {
        $column = $_POST['column'];
        $curr_value = $_POST['curr_value'];
        $response = Response::getInstance();
        if ($columnType == "enum") {
            $dropdown = $this->getHtmlForEnumColumnDropdown(
                $db,
                $table,
                $column,
                $curr_value
            );
            $response->addJSON('dropdown', $dropdown);
        } else {
            $select = $this->getHtmlForSetColumn(
                $db,
                $table,
                $column,
                $curr_value
            );
            $response->addJSON('select', $select);
        }
        exit;
    }

    /**
     * Function to get the default sql query for browsing page
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return string the default $sql_query for browse page
     */
    public function getDefaultSqlQueryForBrowse($db, $table)
    {
        $bookmark = Bookmark::get(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            $db,
            $table,
            'label',
            false,
            true
        );

        if (! empty($bookmark) && ! empty($bookmark->getQuery())) {
            $GLOBALS['using_bookmark_message'] = Message::notice(
                __('Using bookmark "%s" as default browse query.')
            );
            $GLOBALS['using_bookmark_message']->addParam($table);
            $GLOBALS['using_bookmark_message']->addHtml(
                Util::showDocu('faq', 'faq6-22')
            );
            $sql_query = $bookmark->getQuery();
        } else {
            $defaultOrderByClause = '';

            if (isset($GLOBALS['cfg']['TablePrimaryKeyOrder'])
                && ($GLOBALS['cfg']['TablePrimaryKeyOrder'] !== 'NONE')
            ) {
                $primaryKey     = null;
                $primary        = Index::getPrimary($table, $db);

                if ($primary !== false) {
                    $primarycols    = $primary->getColumns();

                    foreach ($primarycols as $col) {
                        $primaryKey = $col->getName();
                        break;
                    }

                    if ($primaryKey != null) {
                        $defaultOrderByClause = ' ORDER BY '
                            . Util::backquote($table) . '.'
                            . Util::backquote($primaryKey) . ' '
                            . $GLOBALS['cfg']['TablePrimaryKeyOrder'];
                    }
                }
            }

            $sql_query = 'SELECT * FROM ' . Util::backquote($table)
                . $defaultOrderByClause;
        }

        return $sql_query;
    }

    /**
     * Responds an error when an error happens when executing the query
     *
     * @param boolean $is_gotofile    whether goto file or not
     * @param string  $error          error after executing the query
     * @param string  $full_sql_query full sql query
     *
     * @return void
     */
    private function handleQueryExecuteError($is_gotofile, $error, $full_sql_query)
    {
        if ($is_gotofile) {
            $message = Message::rawError($error);
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
        } else {
            Util::mysqlDie($error, $full_sql_query, '', '');
        }
        exit;
    }

    /**
     * Function to store the query as a bookmark
     *
     * @param string       $db                     the current database
     * @param string       $bkm_user               the bookmarking user
     * @param string       $sql_query_for_bookmark the query to be stored in bookmark
     * @param string       $bkm_label              bookmark label
     * @param boolean|null $bkm_replace            whether to replace existing bookmarks
     *
     * @return void
     */
    public function storeTheQueryAsBookmark(
        $db,
        $bkm_user,
        $sql_query_for_bookmark,
        $bkm_label,
        ?bool $bkm_replace
    ) {
        $bfields = [
            'bkm_database' => $db,
            'bkm_user'  => $bkm_user,
            'bkm_sql_query' => $sql_query_for_bookmark,
            'bkm_label' => $bkm_label,
        ];

        // Should we replace bookmark?
        if (isset($bkm_replace)) {
            $bookmarks = Bookmark::getList(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['user'],
                $db
            );
            foreach ($bookmarks as $bookmark) {
                if ($bookmark->getLabel() == $bkm_label) {
                    $bookmark->delete();
                }
            }
        }

        $bookmark = Bookmark::createBookmark(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            $bfields,
            isset($_POST['bkm_all_users'])
        );
        $bookmark->save();
    }

    /**
     * Executes the SQL query and measures its execution time
     *
     * @param string $full_sql_query the full sql query
     *
     * @return array ($result, $querytime)
     */
    private function executeQueryAndMeasureTime($full_sql_query)
    {
        // close session in case the query takes too long
        session_write_close();

        // Measure query time.
        $querytime_before = array_sum(explode(' ', microtime()));

        $result = @$GLOBALS['dbi']->tryQuery(
            $full_sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $querytime_after = array_sum(explode(' ', microtime()));

        // reopen session
        session_start();

        return [
            $result,
            $querytime_after - $querytime_before,
        ];
    }

    /**
     * Function to get the affected or changed number of rows after executing a query
     *
     * @param boolean $is_affected whether the query affected a table
     * @param mixed   $result      results of executing the query
     *
     * @return int    number of rows affected or changed
     */
    private function getNumberOfRowsAffectedOrChanged($is_affected, $result)
    {
        if (! $is_affected) {
            $num_rows = $result ? @$GLOBALS['dbi']->numRows($result) : 0;
        } else {
            $num_rows = @$GLOBALS['dbi']->affectedRows();
        }

        return $num_rows;
    }

    /**
     * Checks if the current database has changed
     * This could happen if the user sends a query like "USE `database`;"
     *
     * @param string $db the database in the query
     *
     * @return bool whether to reload the navigation(1) or not(0)
     */
    private function hasCurrentDbChanged($db): bool
    {
        if (strlen($db) > 0) {
            $current_db = $GLOBALS['dbi']->fetchValue('SELECT DATABASE()');
            // $current_db is false, except when a USE statement was sent
            return ($current_db != false) && ($db !== $current_db);
        }

        return false;
    }

    /**
     * If a table, database or column gets dropped, clean comments.
     *
     * @param string      $db     current database
     * @param string      $table  current table
     * @param string|null $column current column
     * @param bool        $purge  whether purge set or not
     *
     * @return void
     */
    private function cleanupRelations($db, $table, ?string $column, $purge)
    {
        if (! empty($purge) && strlen($db) > 0) {
            if (strlen($table) > 0) {
                if (isset($column) && strlen($column) > 0) {
                    $this->relationCleanup->column($db, $table, $column);
                } else {
                    $this->relationCleanup->table($db, $table);
                }
            } else {
                $this->relationCleanup->database($db);
            }
        }
    }

    /**
     * Function to count the total number of rows for the same 'SELECT' query without
     * the 'LIMIT' clause that may have been programatically added
     *
     * @param int    $num_rows             number of rows affected/changed by the query
     * @param bool   $justBrowsing         whether just browsing or not
     * @param string $db                   the current database
     * @param string $table                the current table
     * @param array  $analyzed_sql_results the analyzed query and other variables set
     *                                     after analyzing the query
     *
     * @return int unlimited number of rows
     */
    private function countQueryResults(
        $num_rows,
        $justBrowsing,
        $db,
        $table,
        array $analyzed_sql_results
    ) {

        /* Shortcut for not analyzed/empty query */
        if (empty($analyzed_sql_results)) {
            return 0;
        }

        if (! $this->isAppendLimitClause($analyzed_sql_results)) {
            // if we did not append a limit, set this to get a correct
            // "Showing rows..." message
            // $_SESSION['tmpval']['max_rows'] = 'all';
            $unlim_num_rows = $num_rows;
        } elseif ($this->isAppendLimitClause($analyzed_sql_results) && $_SESSION['tmpval']['max_rows'] > $num_rows) {
            // When user has not defined a limit in query and total rows in
            // result are less than max_rows to display, there is no need
            // to count total rows for that query again
            $unlim_num_rows = $_SESSION['tmpval']['pos'] + $num_rows;
        } elseif ($analyzed_sql_results['querytype'] == 'SELECT'
            || $analyzed_sql_results['is_subquery']
        ) {
            //    c o u n t    q u e r y

            // If we are "just browsing", there is only one table (and no join),
            // and no WHERE clause (or just 'WHERE 1 '),
            // we do a quick count (which uses MaxExactCount) because
            // SQL_CALC_FOUND_ROWS is not quick on large InnoDB tables

            // However, do not count again if we did it previously
            // due to $find_real_end == true
            if ($justBrowsing) {
                // Get row count (is approximate for InnoDB)
                $unlim_num_rows = $GLOBALS['dbi']->getTable($db, $table)->countRecords();
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
                    $unlim_num_rows = $GLOBALS['dbi']->getTable($db, $table)
                        ->countRecords(true);
                }
            } else {
                // The SQL_CALC_FOUND_ROWS option of the SELECT statement is used.

                // For UNION statements, only a SQL_CALC_FOUND_ROWS is required
                // after the first SELECT.

                $count_query = Query::replaceClause(
                    $analyzed_sql_results['statement'],
                    $analyzed_sql_results['parser']->list,
                    'SELECT SQL_CALC_FOUND_ROWS',
                    null,
                    true
                );

                // Another LIMIT clause is added to avoid long delays.
                // A complete result will be returned anyway, but the LIMIT would
                // stop the query as soon as the result that is required has been
                // computed.

                if (empty($analyzed_sql_results['union'])) {
                    $count_query .= ' LIMIT 1';
                }

                // Running the count query.
                $GLOBALS['dbi']->tryQuery($count_query);

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
     * @param array        $analyzed_sql_results   analyzed sql results
     * @param string       $full_sql_query         full sql query
     * @param boolean      $is_gotofile            whether to go to a file
     * @param string       $db                     current database
     * @param string       $table                  current table
     * @param boolean|null $find_real_end          whether to find the real end
     * @param string       $sql_query_for_bookmark sql query to be stored as bookmark
     * @param array        $extra_data             extra data
     *
     * @return mixed
     */
    private function executeTheQuery(
        array $analyzed_sql_results,
        $full_sql_query,
        $is_gotofile,
        $db,
        $table,
        ?bool $find_real_end,
        $sql_query_for_bookmark,
        $extra_data
    ) {
        $response = Response::getInstance();
        $response->getHeader()->getMenu()->setTable($table);

        // Only if we ask to see the php code
        if (isset($GLOBALS['show_as_php'])) {
            $result = null;
            $num_rows = 0;
            $unlim_num_rows = 0;
        } else { // If we don't ask to see the php code
            if (isset($_SESSION['profiling'])
                && Util::profilingSupported()
            ) {
                $GLOBALS['dbi']->query('SET PROFILING=1;');
            }

            list(
                $result,
                $GLOBALS['querytime']
            ) = $this->executeQueryAndMeasureTime($full_sql_query);

            // Displays an error message if required and stop parsing the script
            $error = $GLOBALS['dbi']->getError();
            if ($error && $GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
                $extra_data['error'] = $error;
            } elseif ($error) {
                $this->handleQueryExecuteError($is_gotofile, $error, $full_sql_query);
            }

            // If there are no errors and bookmarklabel was given,
            // store the query as a bookmark
            if (! empty($_POST['bkm_label']) && ! empty($sql_query_for_bookmark)) {
                $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
                $this->storeTheQueryAsBookmark(
                    $db,
                    $cfgBookmark['user'],
                    $sql_query_for_bookmark,
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace']) ? $_POST['bkm_replace'] : null
                );
            } // end store bookmarks

            // Gets the number of rows affected/returned
            // (This must be done immediately after the query because
            // mysql_affected_rows() reports about the last query done)
            $num_rows = $this->getNumberOfRowsAffectedOrChanged(
                $analyzed_sql_results['is_affected'],
                $result
            );

            // Grabs the profiling results
            if (isset($_SESSION['profiling'])
                && Util::profilingSupported()
            ) {
                $profiling_results = $GLOBALS['dbi']->fetchResult('SHOW PROFILE;');
            }

            $justBrowsing = $this->isJustBrowsing(
                $analyzed_sql_results,
                isset($find_real_end) ? $find_real_end : null
            );

            $unlim_num_rows = $this->countQueryResults(
                $num_rows,
                $justBrowsing,
                $db,
                $table,
                $analyzed_sql_results
            );

            $this->cleanupRelations(
                isset($db) ? $db : '',
                isset($table) ? $table : '',
                isset($_POST['dropped_column']) ? $_POST['dropped_column'] : null,
                isset($_POST['purge']) ? $_POST['purge'] : null
            );

            if (isset($_POST['dropped_column'])
                && strlen($db) > 0
                && strlen($table) > 0
            ) {
                // to refresh the list of indexes (Ajax mode)
                $extra_data['indexes_list'] = Index::getHtmlForIndexes(
                    $table,
                    $db
                );
            }
        }

        return [
            $result,
            $num_rows,
            $unlim_num_rows,
            isset($profiling_results) ? $profiling_results : null,
            $extra_data,
        ];
    }
    /**
     * Delete related transformation information
     *
     * @param string $db                   current database
     * @param string $table                current table
     * @param array  $analyzed_sql_results analyzed sql results
     *
     * @return void
     */
    private function deleteTransformationInfo($db, $table, array $analyzed_sql_results)
    {
        if (! isset($analyzed_sql_results['statement'])) {
            return;
        }
        $statement = $analyzed_sql_results['statement'];
        if ($statement instanceof AlterStatement) {
            if (! empty($statement->altered[0])
                && $statement->altered[0]->options->has('DROP')
            ) {
                if (! empty($statement->altered[0]->field->column)) {
                    $this->transformations->clear(
                        $db,
                        $table,
                        $statement->altered[0]->field->column
                    );
                }
            }
        } elseif ($statement instanceof DropStatement) {
            $this->transformations->clear($db, $table);
        }
    }

    /**
     * Function to get the message for the no rows returned case
     *
     * @param string $message_to_show      message to show
     * @param array  $analyzed_sql_results analyzed sql results
     * @param int    $num_rows             number of rows
     *
     * @return Message
     */
    private function getMessageForNoRowsReturned(
        $message_to_show,
        array $analyzed_sql_results,
        $num_rows
    ) {
        if ($analyzed_sql_results['querytype'] == 'DELETE"') {
            $message = Message::getMessageForDeletedRows($num_rows);
        } elseif ($analyzed_sql_results['is_insert']) {
            if ($analyzed_sql_results['querytype'] == 'REPLACE') {
                // For REPLACE we get DELETED + INSERTED row count,
                // so we have to call it affected
                $message = Message::getMessageForAffectedRows($num_rows);
            } else {
                $message = Message::getMessageForInsertedRows($num_rows);
            }
            $insert_id = $GLOBALS['dbi']->insertId();
            if ($insert_id != 0) {
                // insert_id is id of FIRST record inserted in one insert,
                // so if we inserted multiple rows, we had to increment this
                $message->addText('[br]');
                // need to use a temporary because the Message class
                // currently supports adding parameters only to the first
                // message
                $_inserted = Message::notice(__('Inserted row id: %1$d'));
                $_inserted->addParam($insert_id + $num_rows - 1);
                $message->addMessage($_inserted);
            }
        } elseif ($analyzed_sql_results['is_affected']) {
            $message = Message::getMessageForAffectedRows($num_rows);

            // Ok, here is an explanation for the !$is_select.
            // The form generated by PhpMyAdmin\SqlQueryForm
            // and db_sql.php has many submit buttons
            // on the same form, and some confusion arises from the
            // fact that $message_to_show is sent for every case.
            // The $message_to_show containing a success message and sent with
            // the form should not have priority over errors
        } elseif (! empty($message_to_show)
            && $analyzed_sql_results['querytype'] != 'SELECT'
        ) {
            $message = Message::rawSuccess(htmlspecialchars($message_to_show));
        } elseif (! empty($GLOBALS['show_as_php'])) {
            $message = Message::success(__('Showing as PHP code'));
        } elseif (isset($GLOBALS['show_as_php'])) {
            /* User disable showing as PHP, query is only displayed */
            $message = Message::notice(__('Showing SQL query'));
        } else {
            $message = Message::success(
                __('MySQL returned an empty result set (i.e. zero rows).')
            );
        }

        if (isset($GLOBALS['querytime'])) {
            $_querytime = Message::notice(
                '(' . __('Query took %01.4f seconds.') . ')'
            );
            $_querytime->addParam($GLOBALS['querytime']);
            $message->addMessage($_querytime);
        }

        // In case of ROLLBACK, notify the user.
        if (isset($_POST['rollback_query'])) {
            $message->addText(__('[ROLLBACK occurred.]'));
        }

        return $message;
    }

    /**
     * Function to respond back when the query returns zero rows
     * This method is called
     * 1-> When browsing an empty table
     * 2-> When executing a query on a non empty table which returns zero results
     * 3-> When executing a query on an empty table
     * 4-> When executing an INSERT, UPDATE, DELETE query from the SQL tab
     * 5-> When deleting a row from BROWSE tab
     * 6-> When searching using the SEARCH tab which returns zero results
     * 7-> When changing the structure of the table except change operation
     *
     * @param array          $analyzed_sql_results analyzed sql results
     * @param string         $db                   current database
     * @param string         $table                current table
     * @param string|null    $message_to_show      message to show
     * @param int            $num_rows             number of rows
     * @param DisplayResults $displayResultsObject DisplayResult instance
     * @param array|null     $extra_data           extra data
     * @param string         $pmaThemeImage        uri of the theme image
     * @param array|null     $profiling_results    profiling results
     * @param object         $result               executed query results
     * @param string         $sql_query            sql query
     * @param string|null    $complete_query       complete sql query
     *
     * @return string html
     */
    private function getQueryResponseForNoResultsReturned(
        array $analyzed_sql_results,
        $db,
        $table,
        ?string $message_to_show,
        $num_rows,
        $displayResultsObject,
        ?array $extra_data,
        $pmaThemeImage,
        ?array $profiling_results,
        $result,
        $sql_query,
        ?string $complete_query
    ) {
        global $url_query;
        if ($this->isDeleteTransformationInfo($analyzed_sql_results)) {
            $this->deleteTransformationInfo($db, $table, $analyzed_sql_results);
        }

        if (isset($extra_data['error'])) {
            $message = Message::rawError($extra_data['error']);
        } else {
            $message = $this->getMessageForNoRowsReturned(
                isset($message_to_show) ? $message_to_show : null,
                $analyzed_sql_results,
                $num_rows
            );
        }

        $html_output = '';
        $html_message = Util::getMessage(
            $message,
            $GLOBALS['sql_query'],
            'success'
        );
        $html_output .= $html_message;
        if (! isset($GLOBALS['show_as_php'])) {
            if (! empty($GLOBALS['reload'])) {
                $extra_data['reload'] = 1;
                $extra_data['db'] = $GLOBALS['db'];
            }

            // For ajax requests add message and sql_query as JSON
            if (empty($_REQUEST['ajax_page_request'])) {
                $extra_data['message'] = $message;
                if ($GLOBALS['cfg']['ShowSQL']) {
                    $extra_data['sql_query'] = $html_message;
                }
            }

            $response = Response::getInstance();
            $response->addJSON(isset($extra_data) ? $extra_data : []);

            if (! empty($analyzed_sql_results['is_select']) &&
                    ! isset($extra_data['error'])) {
                $url_query = isset($url_query) ? $url_query : null;

                $displayParts = [
                    'edit_lnk' => null,
                    'del_lnk' => null,
                    'sort_lnk' => '1',
                    'nav_bar'  => '0',
                    'bkm_form' => '1',
                    'text_btn' => '1',
                    'pview_lnk' => '1',
                ];

                $html_output .= $this->getHtmlForSqlQueryResultsTable(
                    $displayResultsObject,
                    $pmaThemeImage,
                    $url_query,
                    $displayParts,
                    false,
                    0,
                    $num_rows,
                    true,
                    $result,
                    $analyzed_sql_results,
                    true
                );

                if (is_array($profiling_results)) {
                    $header   = $response->getHeader();
                    $scripts  = $header->getScripts();
                    $scripts->addFile('sql.js');
                    $html_output .= $this->getHtmlForProfilingChart(
                        $url_query,
                        $db,
                        $profiling_results
                    );
                }

                $html_output .= $displayResultsObject->getCreateViewQueryResultOp(
                    $analyzed_sql_results
                );

                $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
                if ($cfgBookmark) {
                    $html_output .= $this->getHtmlForBookmark(
                        $displayParts,
                        $cfgBookmark,
                        $sql_query,
                        $db,
                        $table,
                        isset($complete_query) ? $complete_query : $sql_query,
                        $cfgBookmark['user']
                    );
                }
            }
        }

        return $html_output;
    }

    /**
     * Function to send response for ajax grid edit
     *
     * @param object $result result of the executed query
     *
     * @return void
     */
    private function sendResponseForGridEdit($result)
    {
        $row = $GLOBALS['dbi']->fetchRow($result);
        $field_flags = $GLOBALS['dbi']->fieldFlags($result, 0);
        if (false !== stripos($field_flags, DisplayResults::BINARY_FIELD)) {
            $row[0] = bin2hex($row[0]);
        }
        $response = Response::getInstance();
        $response->addJSON('value', $row[0]);
        exit;
    }

    /**
     * Returns a message for successful creation of a bookmark or null if a bookmark
     * was not created
     *
     * @return string
     */
    private function getBookmarkCreatedMessage(): string
    {
        $output = '';
        if (isset($_GET['label'])) {
            $message = Message::success(
                __('Bookmark %s has been created.')
            );
            $message->addParam($_GET['label']);
            $output = $message->getDisplay();
        }

        return $output;
    }

    /**
     * Function to get html for the sql query results table
     *
     * @param DisplayResults $displayResultsObject instance of DisplayResult
     * @param string         $pmaThemeImage        theme image uri
     * @param string         $url_query            url query
     * @param array          $displayParts         the parts to display
     * @param bool           $editable             whether the result table is
     *                                             editable or not
     * @param int            $unlim_num_rows       unlimited number of rows
     * @param int            $num_rows             number of rows
     * @param bool           $showtable            whether to show table or not
     * @param object|null    $result               result of the executed query
     * @param array          $analyzed_sql_results analyzed sql results
     * @param bool           $is_limited_display   Show only limited operations or not
     *
     * @return string
     */
    private function getHtmlForSqlQueryResultsTable(
        $displayResultsObject,
        $pmaThemeImage,
        $url_query,
        array $displayParts,
        $editable,
        $unlim_num_rows,
        $num_rows,
        $showtable,
        $result,
        array $analyzed_sql_results,
        $is_limited_display = false
    ) {
        $printview = isset($_POST['printview']) && $_POST['printview'] == '1' ? '1' : null;
        $table_html = '';
        $browse_dist = ! empty($_POST['is_browse_distinct']);

        if ($analyzed_sql_results['is_procedure']) {
            do {
                if (! isset($result)) {
                    $result = $GLOBALS['dbi']->storeResult();
                }
                $num_rows = $GLOBALS['dbi']->numRows($result);

                if ($result !== false && $num_rows > 0) {
                    $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
                    if (! is_array($fields_meta)) {
                        $fields_cnt = 0;
                    } else {
                        $fields_cnt  = count($fields_meta);
                    }

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
                        $editable,
                        $browse_dist
                    );

                    $displayParts = [
                        'edit_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                        'del_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                        'sort_lnk' => '1',
                        'nav_bar'  => '1',
                        'bkm_form' => '1',
                        'text_btn' => '1',
                        'pview_lnk' => '1',
                    ];

                    $table_html .= $displayResultsObject->getTable(
                        $result,
                        $displayParts,
                        $analyzed_sql_results,
                        $is_limited_display
                    );
                }

                $GLOBALS['dbi']->freeResult($result);
            } while ($GLOBALS['dbi']->moreResults() && $GLOBALS['dbi']->nextResult());
        } else {
            $fields_meta = [];
            if (isset($result) && ! is_bool($result)) {
                $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
            }
            $fields_cnt = count($fields_meta);
            $_SESSION['is_multi_query'] = false;
            $displayResultsObject->setProperties(
                $unlim_num_rows,
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
                $editable,
                $browse_dist
            );

            if (! is_bool($result)) {
                $table_html .= $displayResultsObject->getTable(
                    $result,
                    $displayParts,
                    $analyzed_sql_results,
                    $is_limited_display
                );
            }
            $GLOBALS['dbi']->freeResult($result);
        }

        return $table_html;
    }

    /**
     * Function to get html for the previous query if there is such. If not will return
     * null
     *
     * @param string|null    $displayQuery   display query
     * @param bool           $showSql        whether to show sql
     * @param array          $sqlData        sql data
     * @param Message|string $displayMessage display message
     *
     * @return string
     */
    private function getHtmlForPreviousUpdateQuery(
        ?string $displayQuery,
        bool $showSql,
        $sqlData,
        $displayMessage
    ): string {
        $output = '';
        if (isset($displayQuery) && ($showSql === true) && empty($sqlData)) {
            $output = Util::getMessage(
                $displayMessage,
                $displayQuery,
                'success'
            );
        }

        return $output;
    }

    /**
     * To get the message if a column index is missing. If not will return null
     *
     * @param string  $table        current table
     * @param string  $database     current database
     * @param boolean $editable     whether the results table can be editable or not
     * @param boolean $hasUniqueKey whether there is a unique key
     *
     * @return string
     */
    private function getMessageIfMissingColumnIndex($table, $database, $editable, $hasUniqueKey): string
    {
        $output = '';
        if (! empty($table) && ($GLOBALS['dbi']->isSystemSchema($database) || ! $editable)) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, checkbox, Edit, Copy and Delete features'
                        . ' are not available. %s'
                    ),
                    Util::showDocu(
                        'config',
                        'cfg_RowActionLinksWithoutUnique'
                    )
                )
            )->getDisplay();
        } elseif (! empty($table) && ! $hasUniqueKey) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, Edit, Copy and Delete features may result in'
                        . ' undesired behavior. %s'
                    ),
                    Util::showDocu(
                        'config',
                        'cfg_RowActionLinksWithoutUnique'
                    )
                )
            )->getDisplay();
        }

        return $output;
    }

    /**
     * Function to get html to display problems in indexes
     *
     * @param string|null $queryType      query type
     * @param array|null  $selectedTables array of table names selected from the
     *                                    database structure page, for an action
     *                                    like check table, optimize table,
     *                                    analyze table or repair table
     * @param string      $database       current database
     *
     * @return string
     */
    private function getHtmlForIndexesProblems(?string $queryType, ?array $selectedTables, string $database): string
    {
        // BEGIN INDEX CHECK See if indexes should be checked.
        $output = '';
        if (isset($queryType)
            && $queryType == 'check_tbl'
            && isset($selectedTables)
            && is_array($selectedTables)
        ) {
            foreach ($selectedTables as $table) {
                $check = Index::findDuplicates($table, $database);
                if (! empty($check)) {
                    $output .= sprintf(
                        __('Problems with indexes of table `%s`'),
                        $table
                    );
                    $output .= $check;
                }
            }
        }

        return $output;
    }

    /**
     * Function to display results when the executed query returns non empty results
     *
     * @param object|null         $result               executed query results
     * @param array               $analyzed_sql_results analysed sql results
     * @param string              $db                   current database
     * @param string              $table                current table
     * @param string|null         $message              message to show
     * @param array|null          $sql_data             sql data
     * @param DisplayResults      $displayResultsObject Instance of DisplayResults
     * @param string              $pmaThemeImage        uri of the theme image
     * @param int                 $unlim_num_rows       unlimited number of rows
     * @param int                 $num_rows             number of rows
     * @param string|null         $disp_query           display query
     * @param Message|string|null $disp_message         display message
     * @param array|null          $profiling_results    profiling results
     * @param string|null         $query_type           query type
     * @param array|null          $selectedTables       array of table names selected
     *                                                  from the database structure page, for
     *                                                  an action like check table,
     *                                                  optimize table, analyze table or
     *                                                  repair table
     * @param string              $sql_query            sql query
     * @param string|null         $complete_query       complete sql query
     *
     * @return string html
     */
    private function getQueryResponseForResultsReturned(
        $result,
        array $analyzed_sql_results,
        $db,
        $table,
        ?string $message,
        ?array $sql_data,
        $displayResultsObject,
        $pmaThemeImage,
        $unlim_num_rows,
        $num_rows,
        ?string $disp_query,
        $disp_message,
        ?array $profiling_results,
        ?string $query_type,
        $selectedTables,
        $sql_query,
        ?string $complete_query
    ) {
        global $showtable, $url_query;
        // If we are retrieving the full value of a truncated field or the original
        // value of a transformed field, show it here
        if (isset($_POST['grid_edit']) && $_POST['grid_edit'] == true) {
            $this->sendResponseForGridEdit($result);
            // script has exited at this point
        }

        // Gets the list of fields properties
        if (isset($result) && $result) {
            $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
        } else {
            $fields_meta = [];
        }

        // Should be initialized these parameters before parsing
        $showtable = isset($showtable) ? $showtable : null;
        $url_query = isset($url_query) ? $url_query : null;

        $response = Response::getInstance();
        $header   = $response->getHeader();
        $scripts  = $header->getScripts();

        $just_one_table = $this->resultSetHasJustOneTable($fields_meta);

        // hide edit and delete links:
        // - for information_schema
        // - if the result set does not contain all the columns of a unique key
        //   (unless this is an updatable view)
        // - if the SELECT query contains a join or a subquery

        $updatableView = false;

        $statement = isset($analyzed_sql_results['statement']) ? $analyzed_sql_results['statement'] : null;
        if ($statement instanceof SelectStatement) {
            if (! empty($statement->expr)) {
                if ($statement->expr[0]->expr === '*') {
                    $_table = new Table($table, $db);
                    $updatableView = $_table->isUpdatableView();
                }
            }

            if ($analyzed_sql_results['join']
                || $analyzed_sql_results['is_subquery']
                || count($analyzed_sql_results['select_tables']) !== 1
            ) {
                $just_one_table = false;
            }
        }

        $has_unique = $this->resultSetContainsUniqueKey(
            $db,
            $table,
            $fields_meta
        );

        $editable = ($has_unique
            || $GLOBALS['cfg']['RowActionLinksWithoutUnique']
            || $updatableView)
            && $just_one_table;

        $_SESSION['tmpval']['possible_as_geometry'] = $editable;

        $displayParts = [
            'edit_lnk' => $displayResultsObject::UPDATE_ROW,
            'del_lnk' => $displayResultsObject::DELETE_ROW,
            'sort_lnk' => '1',
            'nav_bar'  => '1',
            'bkm_form' => '1',
            'text_btn' => '0',
            'pview_lnk' => '1',
        ];

        if ($GLOBALS['dbi']->isSystemSchema($db) || ! $editable) {
            $displayParts = [
                'edit_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'del_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'sort_lnk' => '1',
                'nav_bar'  => '1',
                'bkm_form' => '1',
                'text_btn' => '1',
                'pview_lnk' => '1',
            ];
        }
        if (isset($_POST['printview']) && $_POST['printview'] == '1') {
            $displayParts = [
                'edit_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'del_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'sort_lnk' => '0',
                'nav_bar'  => '0',
                'bkm_form' => '0',
                'text_btn' => '0',
                'pview_lnk' => '0',
            ];
        }

        $tableMaintenanceHtml = '';
        if (isset($_POST['table_maintenance'])) {
            $scripts->addFile('makegrid.js');
            $scripts->addFile('sql.js');
            if (isset($message)) {
                $message = Message::success($message);
                $tableMaintenanceHtml = Util::getMessage(
                    $message,
                    $GLOBALS['sql_query'],
                    'success'
                );
            }
            $tableMaintenanceHtml .= $this->getHtmlForSqlQueryResultsTable(
                $displayResultsObject,
                $pmaThemeImage,
                $url_query,
                $displayParts,
                false,
                $unlim_num_rows,
                $num_rows,
                $showtable,
                $result,
                $analyzed_sql_results
            );
            if (empty($sql_data) || ($sql_data['valid_queries'] = 1)) {
                $response->addHTML($tableMaintenanceHtml);
                exit;
            }
        }

        if (! isset($_POST['printview']) || $_POST['printview'] != '1') {
            $scripts->addFile('makegrid.js');
            $scripts->addFile('sql.js');
            unset($GLOBALS['message']);
            //we don't need to buffer the output in getMessage here.
            //set a global variable and check against it in the function
            $GLOBALS['buffer_message'] = false;
        }

        $previousUpdateQueryHtml = $this->getHtmlForPreviousUpdateQuery(
            isset($disp_query) ? $disp_query : null,
            (bool) $GLOBALS['cfg']['ShowSQL'],
            isset($sql_data) ? $sql_data : null,
            isset($disp_message) ? $disp_message : null
        );

        $profilingChartHtml = $this->getHtmlForProfilingChart(
            $url_query,
            $db,
            isset($profiling_results) ? $profiling_results : []
        );

        $missingUniqueColumnMessage = $this->getMessageIfMissingColumnIndex(
            $table,
            $db,
            $editable,
            $has_unique
        );

        $bookmarkCreatedMessage = $this->getBookmarkCreatedMessage();

        $tableHtml = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $pmaThemeImage,
            $url_query,
            $displayParts,
            $editable,
            $unlim_num_rows,
            $num_rows,
            $showtable,
            $result,
            $analyzed_sql_results
        );

        $indexesProblemsHtml = $this->getHtmlForIndexesProblems(
            isset($query_type) ? $query_type : null,
            isset($selectedTables) ? $selectedTables : null,
            $db
        );

        $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
        $bookmarkSupportHtml = '';
        if ($cfgBookmark) {
            $bookmarkSupportHtml = $this->getHtmlForBookmark(
                $displayParts,
                $cfgBookmark,
                $sql_query,
                $db,
                $table,
                isset($complete_query) ? $complete_query : $sql_query,
                $cfgBookmark['user']
            );
        }

        return $this->template->render('sql/sql_query_results', [
            'table_maintenance' => $tableMaintenanceHtml,
            'previous_update_query' => $previousUpdateQueryHtml,
            'profiling_chart' => $profilingChartHtml,
            'missing_unique_column_message' => $missingUniqueColumnMessage,
            'bookmark_created_message' => $bookmarkCreatedMessage,
            'table' => $tableHtml,
            'indexes_problems' => $indexesProblemsHtml,
            'bookmark_support' => $bookmarkSupportHtml,
        ]);
    }

    /**
     * Function to execute the query and send the response
     *
     * @param array          $analyzed_sql_results   analysed sql results
     * @param bool           $is_gotofile            whether goto file or not
     * @param string         $db                     current database
     * @param string         $table                  current table
     * @param bool|null      $find_real_end          whether to find real end or not
     * @param string         $sql_query_for_bookmark the sql query to be stored as bookmark
     * @param array|null     $extra_data             extra data
     * @param string         $message_to_show        message to show
     * @param string         $message                message
     * @param array|null     $sql_data               sql data
     * @param string         $goto                   goto page url
     * @param string         $pmaThemeImage          uri of the PMA theme image
     * @param string         $disp_query             display query
     * @param Message|string $disp_message           display message
     * @param string         $query_type             query type
     * @param string         $sql_query              sql query
     * @param array|null     $selectedTables         array of table names selected from the
     *                                               database structure page, for an action
     *                                               like check table, optimize table,
     *                                               analyze table or repair table
     * @param string         $complete_query         complete query
     *
     * @return void
     */
    public function executeQueryAndSendQueryResponse(
        $analyzed_sql_results,
        $is_gotofile,
        $db,
        $table,
        $find_real_end,
        $sql_query_for_bookmark,
        $extra_data,
        $message_to_show,
        $message,
        $sql_data,
        $goto,
        $pmaThemeImage,
        $disp_query,
        $disp_message,
        $query_type,
        $sql_query,
        $selectedTables,
        $complete_query
    ) {
        if ($analyzed_sql_results == null) {
            // Parse and analyze the query
            list(
                $analyzed_sql_results,
                $db,
                $table_from_sql
            ) = ParseAnalyze::sqlQuery($sql_query, $db);
            // @todo: possibly refactor
            extract($analyzed_sql_results);

            if ($table != $table_from_sql && ! empty($table_from_sql)) {
                $table = $table_from_sql;
            }
        }

        $html_output = $this->executeQueryAndGetQueryResponse(
            $analyzed_sql_results, // analyzed_sql_results
            $is_gotofile, // is_gotofile
            $db, // db
            $table, // table
            $find_real_end, // find_real_end
            $sql_query_for_bookmark, // sql_query_for_bookmark
            $extra_data, // extra_data
            $message_to_show, // message_to_show
            $message, // message
            $sql_data, // sql_data
            $goto, // goto
            $pmaThemeImage, // pmaThemeImage
            $disp_query, // disp_query
            $disp_message, // disp_message
            $query_type, // query_type
            $sql_query, // sql_query
            $selectedTables, // selectedTables
            $complete_query // complete_query
        );

        $response = Response::getInstance();
        $response->addHTML($html_output);
    }

    /**
     * Function to execute the query and send the response
     *
     * @param array               $analyzed_sql_results   analysed sql results
     * @param bool                $is_gotofile            whether goto file or not
     * @param string              $db                     current database
     * @param string              $table                  current table
     * @param bool|null           $find_real_end          whether to find real end or not
     * @param string|null         $sql_query_for_bookmark the sql query to be stored as bookmark
     * @param array|null          $extra_data             extra data
     * @param string|null         $message_to_show        message to show
     * @param Message|string|null $message                message
     * @param array|null          $sql_data               sql data
     * @param string              $goto                   goto page url
     * @param string              $pmaThemeImage          uri of the PMA theme image
     * @param string|null         $disp_query             display query
     * @param Message|string|null $disp_message           display message
     * @param string|null         $query_type             query type
     * @param string              $sql_query              sql query
     * @param array|null          $selectedTables         array of table names selected from the
     *                                                    database structure page, for an action
     *                                                    like check table, optimize table,
     *                                                    analyze table or repair table
     * @param string|null         $complete_query         complete query
     *
     * @return string html
     */
    public function executeQueryAndGetQueryResponse(
        array $analyzed_sql_results,
        $is_gotofile,
        $db,
        $table,
        $find_real_end,
        ?string $sql_query_for_bookmark,
        $extra_data,
        ?string $message_to_show,
        $message,
        $sql_data,
        $goto,
        $pmaThemeImage,
        ?string $disp_query,
        $disp_message,
        ?string $query_type,
        $sql_query,
        $selectedTables,
        ?string $complete_query
    ) {
        // Handle disable/enable foreign key checks
        $default_fk_check = Util::handleDisableFKCheckInit();

        // Handle remembered sorting order, only for single table query.
        // Handling is not required when it's a union query
        // (the parser never sets the 'union' key to 0).
        // Handling is also not required if we came from the "Sort by key"
        // drop-down.
        if (! empty($analyzed_sql_results)
            && $this->isRememberSortingOrder($analyzed_sql_results)
            && empty($analyzed_sql_results['union'])
            && ! isset($_POST['sort_by_key'])
        ) {
            if (! isset($_SESSION['sql_from_query_box'])) {
                $this->handleSortOrder($db, $table, $analyzed_sql_results, $sql_query);
            } else {
                unset($_SESSION['sql_from_query_box']);
            }
        }

        $displayResultsObject = new DisplayResults(
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['server'],
            $goto,
            $sql_query
        );
        $displayResultsObject->setConfigParamsForDisplayTable();

        // assign default full_sql_query
        $full_sql_query = $sql_query;

        // Do append a "LIMIT" clause?
        if ($this->isAppendLimitClause($analyzed_sql_results)) {
            $full_sql_query = $this->getSqlWithLimitClause($analyzed_sql_results);
        }

        $GLOBALS['reload'] = $this->hasCurrentDbChanged($db);
        $GLOBALS['dbi']->selectDb($db);

        list(
            $result,
            $num_rows,
            $unlim_num_rows,
            $profiling_results,
            $extra_data
        ) = $this->executeTheQuery(
            $analyzed_sql_results,
            $full_sql_query,
            $is_gotofile,
            $db,
            $table,
            isset($find_real_end) ? $find_real_end : null,
            isset($sql_query_for_bookmark) ? $sql_query_for_bookmark : null,
            isset($extra_data) ? $extra_data : null
        );

        if ($GLOBALS['dbi']->moreResults()) {
            $GLOBALS['dbi']->nextResult();
        }

        $warning_messages = $this->operations->getWarningMessagesArray();

        // No rows returned -> move back to the calling page
        if ((0 == $num_rows && 0 == $unlim_num_rows)
            || $analyzed_sql_results['is_affected']
        ) {
            $html_output = $this->getQueryResponseForNoResultsReturned(
                $analyzed_sql_results,
                $db,
                $table,
                isset($message_to_show) ? $message_to_show : null,
                $num_rows,
                $displayResultsObject,
                $extra_data,
                $pmaThemeImage,
                $profiling_results,
                isset($result) ? $result : null,
                $sql_query,
                isset($complete_query) ? $complete_query : null
            );
        } else {
            // At least one row is returned -> displays a table with results
            $html_output = $this->getQueryResponseForResultsReturned(
                isset($result) ? $result : null,
                $analyzed_sql_results,
                $db,
                $table,
                isset($message) ? $message : null,
                isset($sql_data) ? $sql_data : null,
                $displayResultsObject,
                $pmaThemeImage,
                $unlim_num_rows,
                $num_rows,
                isset($disp_query) ? $disp_query : null,
                isset($disp_message) ? $disp_message : null,
                $profiling_results,
                isset($query_type) ? $query_type : null,
                isset($selectedTables) ? $selectedTables : null,
                $sql_query,
                isset($complete_query) ? $complete_query : null
            );
        }

        // Handle disable/enable foreign key checks
        Util::handleDisableFKCheckCleanup($default_fk_check);

        foreach ($warning_messages as $warning) {
            $message = Message::notice(Message::sanitize($warning));
            $html_output .= $message->getDisplay();
        }

        return $html_output;
    }

    /**
     * Function to define pos to display a row
     *
     * @param int $number_of_line Number of the line to display
     * @param int $max_rows       Number of rows by page
     *
     * @return int Start position to display the line
     */
    private function getStartPosToDisplayRow($number_of_line, $max_rows = null)
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
     * @param string   $db    Database name
     * @param string   $table Table name
     * @param int|null $pos   Initial position
     *
     * @return int Number of pos to display last page
     */
    public function calculatePosForLastPage($db, $table, $pos)
    {
        if (null === $pos) {
            $pos = $_SESSION['tmpval']['pos'];
        }

        $_table = new Table($table, $db);
        $unlim_num_rows = $_table->countRecords(true);
        //If position is higher than number of rows
        if ($unlim_num_rows <= $pos && 0 != $pos) {
            $pos = $this->getStartPosToDisplayRow($unlim_num_rows);
        }

        return $pos;
    }
}
