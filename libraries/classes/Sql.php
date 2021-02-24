<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use function array_map;
use function array_sum;
use function bin2hex;
use function ceil;
use function count;
use function explode;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_bool;
use function microtime;
use function session_start;
use function session_write_close;
use function sprintf;
use function str_replace;
use function stripos;
use function strlen;
use function strpos;
use function ucwords;

/**
 * Set of functions for the SQL executor
 */
class Sql
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Relation */
    private $relation;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var Transformations */
    private $transformations;

    /** @var Operations */
    private $operations;

    /** @var Template */
    private $template;

    public function __construct(
        DatabaseInterface $dbi,
        Relation $relation,
        RelationCleanup $relationCleanup,
        Operations $operations,
        Transformations $transformations,
        Template $template
    ) {
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->relationCleanup = $relationCleanup;
        $this->operations = $operations;
        $this->transformations = $transformations;
        $this->template = $template;
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
     * @return bool whether the result set has columns from just one table
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
            if ($one_field_meta->table == '') {
                continue;
            }

            $prev_table = $one_field_meta->table;
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
     * @return bool whether the result set contains a unique key
     */
    private function resultSetContainsUniqueKey($db, $table, array $fields_meta)
    {
        $columns = $this->dbi->getColumns($db, $table);
        $resultSetColumnNames = [];
        foreach ($fields_meta as $oneMeta) {
            $resultSetColumnNames[] = $oneMeta->name;
        }
        foreach (Index::getFromTable($table, $db) as $index) {
            if (! $index->isUnique()) {
                continue;
            }

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
    public function getHtmlForRelationalColumnDropdown($db, $table, $column, $curr_value)
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

    /** @return array<string, int|array> */
    private function getDetailedProfilingStats(array $profilingResults): array
    {
        $profiling = [
            'total_time' => 0,
            'states' => [],
            'chart' => [],
            'profile' => [],
        ];

        foreach ($profilingResults as $oneResult) {
            $status = ucwords($oneResult['Status']);
            $profiling['total_time'] += $oneResult['Duration'];
            $profiling['profile'][] = [
                'status' => $status,
                'duration' => Util::formatNumber($oneResult['Duration'], 3, 1),
                'duration_raw' => $oneResult['Duration'],
            ];

            if (! isset($profiling['states'][$status])) {
                $profiling['states'][$status] = [
                    'total_time' => $oneResult['Duration'],
                    'calls' => 1,
                ];
                $profiling['chart'][$status] = $oneResult['Duration'];
            } else {
                $profiling['states'][$status]['calls']++;
                $profiling['chart'][$status] += $oneResult['Duration'];
            }
        }

        return $profiling;
    }

    /**
     * Get value of a column for a specific row (marked by $whereClause)
     */
    public function getFullValuesForSetColumn(
        string $db,
        string $table,
        string $column,
        string $whereClause
    ): string {
        $row = $this->dbi->fetchSingleRow(sprintf(
            'SELECT `%s` FROM `%s`.`%s` WHERE %s',
            $column,
            $db,
            $table,
            $whereClause
        ));

        if ($row === null) {
            return '';
        }

        return $row[$column];
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
    public function getValuesForColumn($db, $table, $column)
    {
        $field_info_query = QueryGenerator::getColumnsSql($db, $table, $this->dbi->escapeString($column));

        $field_info_result = $this->dbi->fetchResult(
            $field_info_query,
            null,
            null,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );

        return Util::parseEnumSetValues($field_info_result[0]['Type']);
    }

    /**
     * Function to check whether to remember the sorting order or not
     *
     * @param array $analyzed_sql_results the analyzed query and other variables set
     *                                    after analyzing the query
     *
     * @return bool
     */
    private function isRememberSortingOrder(array $analyzed_sql_results)
    {
        return isset($analyzed_sql_results['select_expr'], $analyzed_sql_results['select_tables'])
            && $GLOBALS['cfg']['RememberSorting']
            && ! ($analyzed_sql_results['is_count']
                || $analyzed_sql_results['is_export']
                || $analyzed_sql_results['is_func']
                || $analyzed_sql_results['is_analyse'])
            && $analyzed_sql_results['select_from']
            && (empty($analyzed_sql_results['select_expr'])
                || ((count($analyzed_sql_results['select_expr']) === 1)
                    && ($analyzed_sql_results['select_expr'][0] === '*')))
            && count($analyzed_sql_results['select_tables']) === 1;
    }

    /**
     * Function to check whether the LIMIT clause should be appended or not
     *
     * @param array $analyzed_sql_results the analyzed query and other variables set
     *                                    after analyzing the query
     *
     * @return bool
     */
    private function isAppendLimitClause(array $analyzed_sql_results)
    {
        // Assigning LIMIT clause to an syntactically-wrong query
        // is not needed. Also we would want to show the true query
        // and the true error message to the query executor

        return (isset($analyzed_sql_results['parser'])
            && count($analyzed_sql_results['parser']->errors) === 0)
            && ($_SESSION['tmpval']['max_rows'] !== 'all')
            && ! ($analyzed_sql_results['is_export']
            || $analyzed_sql_results['is_analyse'])
            && ($analyzed_sql_results['select_from']
                || $analyzed_sql_results['is_subquery'])
            && empty($analyzed_sql_results['limit']);
    }

    /**
     * Function to check whether this query is for just browsing
     *
     * @param array<string, mixed> $analyzed_sql_results the analyzed query and other variables set
     *                                                   after analyzing the query
     * @param bool|null            $find_real_end        whether the real end should be found
     */
    public static function isJustBrowsing(array $analyzed_sql_results, ?bool $find_real_end): bool
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
     * @return bool
     */
    private function isDeleteTransformationInfo(array $analyzed_sql_results)
    {
        return ! empty($analyzed_sql_results['querytype'])
            && (($analyzed_sql_results['querytype'] === 'ALTER')
                || ($analyzed_sql_results['querytype'] === 'DROP'));
    }

    /**
     * Function to check whether the user has rights to drop the database
     *
     * @param array $analyzed_sql_results  the analyzed query and other variables set
     *                                     after analyzing the query
     * @param bool  $allowUserDropDatabase whether the user is allowed to drop db
     * @param bool  $is_superuser          whether this user is a superuser
     *
     * @return bool
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
     * @return bool|Message
     */
    public function setColumnProperty($pmatable, $request_index)
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

        return $pmatable->setUiProp(
            $property_to_set,
            $property_value,
            $_POST['table_create_time'] ?? null
        );
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
        $unlim_num_rows = $this->dbi->getTable($db, $table)->countRecords(true);
        $_SESSION['tmpval']['pos'] = $this->getStartPosToDisplayRow($unlim_num_rows);

        return $unlim_num_rows;
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
            $this->dbi,
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
                MySQLDocumentation::showDocumentation('faq', 'faq6-22')
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
     * @param bool   $is_gotofile    whether goto file or not
     * @param string $error          error after executing the query
     * @param string $full_sql_query full sql query
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
            Generator::mysqlDie($error, $full_sql_query, '', '');
        }
        exit;
    }

    /**
     * Function to store the query as a bookmark
     *
     * @param string $db                     the current database
     * @param string $bkm_user               the bookmarking user
     * @param string $sql_query_for_bookmark the query to be stored in bookmark
     * @param string $bkm_label              bookmark label
     * @param bool   $bkm_replace            whether to replace existing bookmarks
     *
     * @return void
     */
    public function storeTheQueryAsBookmark(
        $db,
        $bkm_user,
        $sql_query_for_bookmark,
        $bkm_label,
        bool $bkm_replace
    ) {
        $bfields = [
            'bkm_database' => $db,
            'bkm_user'  => $bkm_user,
            'bkm_sql_query' => $sql_query_for_bookmark,
            'bkm_label' => $bkm_label,
        ];

        // Should we replace bookmark?
        if ($bkm_replace) {
            $bookmarks = Bookmark::getList(
                $this->dbi,
                $GLOBALS['cfg']['Server']['user'],
                $db
            );
            foreach ($bookmarks as $bookmark) {
                if ($bookmark->getLabel() != $bkm_label) {
                    continue;
                }

                $bookmark->delete();
            }
        }

        $bookmark = Bookmark::createBookmark(
            $this->dbi,
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

        $result = @$this->dbi->tryQuery(
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
     * @param bool  $is_affected whether the query affected a table
     * @param mixed $result      results of executing the query
     *
     * @return int    number of rows affected or changed
     */
    private function getNumberOfRowsAffectedOrChanged($is_affected, $result)
    {
        if (! $is_affected) {
            $num_rows = $result ? @$this->dbi->numRows($result) : 0;
        } else {
            $num_rows = @$this->dbi->affectedRows();
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
            $current_db = $this->dbi->fetchValue('SELECT DATABASE()');

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
        if (empty($purge) || strlen($db) <= 0) {
            return;
        }

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
        } elseif ($analyzed_sql_results['querytype'] === 'SELECT'
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
                $unlim_num_rows = $this->dbi->getTable($db, $table)->countRecords();
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
                    $unlim_num_rows = $this->dbi->getTable($db, $table)
                        ->countRecords(true);
                }
            } else {
                $statement = $analyzed_sql_results['statement'];
                $token_list = $analyzed_sql_results['parser']->list;
                $replaces = [
                    // Remove ORDER BY to decrease unnecessary sorting time
                    [
                        'ORDER BY',
                        '',
                    ],
                    // Removes LIMIT clause that might have been added
                    [
                        'LIMIT',
                        '',
                    ],
                ];
                $count_query = Query::replaceClauses(
                    $statement,
                    $token_list,
                    $replaces
                );
                $unlim_num_rows = $this->dbi->numRows($this->dbi->tryQuery($count_query));
            }
        } else {// not $is_select
            $unlim_num_rows = 0;
        }

        return $unlim_num_rows;
    }

    /**
     * Function to handle all aspects relating to executing the query
     *
     * @param array       $analyzed_sql_results   analyzed sql results
     * @param string      $full_sql_query         full sql query
     * @param bool        $is_gotofile            whether to go to a file
     * @param string|null $db                     current database
     * @param string|null $table                  current table
     * @param bool|null   $find_real_end          whether to find the real end
     * @param string      $sql_query_for_bookmark sql query to be stored as bookmark
     * @param array       $extra_data             extra data
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
            Profiling::enable($this->dbi);

            [
                $result,
                $GLOBALS['querytime'],
            ] = $this->executeQueryAndMeasureTime($full_sql_query);

            // Displays an error message if required and stop parsing the script
            $error = $this->dbi->getError();
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
                    is_array($cfgBookmark) ? $cfgBookmark['user'] : '',
                    $sql_query_for_bookmark,
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace'])
                );
            }

            // Gets the number of rows affected/returned
            // (This must be done immediately after the query because
            // mysql_affected_rows() reports about the last query done)
            $num_rows = $this->getNumberOfRowsAffectedOrChanged(
                $analyzed_sql_results['is_affected'],
                $result
            );

            $profiling_results = Profiling::getInformation($this->dbi);

            $justBrowsing = self::isJustBrowsing(
                $analyzed_sql_results,
                $find_real_end ?? null
            );

            $unlim_num_rows = $this->countQueryResults(
                $num_rows,
                $justBrowsing,
                $db,
                $table,
                $analyzed_sql_results
            );

            $this->cleanupRelations(
                $db ?? '',
                $table ?? '',
                $_POST['dropped_column'] ?? null,
                $_POST['purge'] ?? null
            );

            if (isset($_POST['dropped_column'])
                && isset($db) && strlen($db) > 0
                && isset($table) && strlen($table) > 0
            ) {
                // to refresh the list of indexes (Ajax mode)

                $indexes = Index::getFromTable($table, $db);
                $indexesDuplicates = Index::findDuplicates($table, $db);
                $template = new Template();

                $extra_data['indexes_list'] = $template->render('indexes', [
                    'url_params' => $GLOBALS['url_params'],
                    'indexes' => $indexes,
                    'indexes_duplicates' => $indexesDuplicates,
                ]);
            }
        }

        return [
            $result,
            $num_rows,
            $unlim_num_rows,
            $profiling_results ?? null,
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
        if ($analyzed_sql_results['querytype'] === 'DELETE"') {
            $message = Message::getMessageForDeletedRows($num_rows);
        } elseif ($analyzed_sql_results['is_insert']) {
            if ($analyzed_sql_results['querytype'] === 'REPLACE') {
                // For REPLACE we get DELETED + INSERTED row count,
                // so we have to call it affected
                $message = Message::getMessageForAffectedRows($num_rows);
            } else {
                $message = Message::getMessageForInsertedRows($num_rows);
            }
            $insert_id = $this->dbi->insertId();
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
            // and /database/sql has many submit buttons
            // on the same form, and some confusion arises from the
            // fact that $message_to_show is sent for every case.
            // The $message_to_show containing a success message and sent with
            // the form should not have priority over errors
        } elseif (! empty($message_to_show)
            && $analyzed_sql_results['querytype'] !== 'SELECT'
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
     * @param string         $themeImagePath       uri of the theme image
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
        $themeImagePath,
        ?array $profiling_results,
        $result,
        $sql_query,
        ?string $complete_query
    ) {
        if ($this->isDeleteTransformationInfo($analyzed_sql_results)) {
            $this->deleteTransformationInfo($db, $table, $analyzed_sql_results);
        }

        if (isset($extra_data['error'])) {
            $message = Message::rawError($extra_data['error']);
        } else {
            $message = $this->getMessageForNoRowsReturned(
                $message_to_show ?? null,
                $analyzed_sql_results,
                $num_rows
            );
        }

        $queryMessage = Generator::getMessage(
            $message,
            $GLOBALS['sql_query'],
            'success'
        );

        if (isset($GLOBALS['show_as_php'])) {
            return $queryMessage;
        }

        if (! empty($GLOBALS['reload'])) {
            $extra_data['reload'] = 1;
            $extra_data['db'] = $GLOBALS['db'];
        }

        // For ajax requests add message and sql_query as JSON
        if (empty($_REQUEST['ajax_page_request'])) {
            $extra_data['message'] = $message;
            if ($GLOBALS['cfg']['ShowSQL']) {
                $extra_data['sql_query'] = $queryMessage;
            }
        }

        $response = Response::getInstance();
        $response->addJSON($extra_data ?? []);

        if (empty($analyzed_sql_results['is_select']) || isset($extra_data['error'])) {
            return $queryMessage;
        }

        $displayParts = [
            'edit_lnk' => null,
            'del_lnk' => null,
            'sort_lnk' => '1',
            'nav_bar' => '0',
            'bkm_form' => '1',
            'text_btn' => '1',
            'pview_lnk' => '1',
        ];

        $sqlQueryResultsTable = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $themeImagePath,
            $displayParts,
            false,
            0,
            $num_rows,
            true,
            $result,
            $analyzed_sql_results,
            true
        );

        $profilingChart = '';
        if ($profiling_results !== null) {
            $header = $response->getHeader();
            $scripts = $header->getScripts();
            $scripts->addFile('vendor/stickyfill.min.js');
            $scripts->addFile('sql.js');

            $profiling = $this->getDetailedProfilingStats($profiling_results);
            $profilingChart = $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
        }

        $bookmark = '';
        $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
        if (is_array($cfgBookmark)
            && $displayParts['bkm_form'] == '1'
            && (! empty($cfgBookmark) && empty($_GET['id_bookmark']))
            && ! empty($sql_query)
        ) {
            $bookmark = $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => Url::getFromRoute('/sql', [
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sql_query,
                    'id_bookmark' => 1,
                ]),
                'user' => $cfgBookmark['user'],
                'sql_query' => $complete_query ?? $sql_query,
            ]);
        }

        return $this->template->render('sql/no_results_returned', [
            'message' => $queryMessage,
            'sql_query_results_table' => $sqlQueryResultsTable,
            'profiling_chart' => $profilingChart,
            'bookmark' => $bookmark,
            'db' => $db,
            'table' => $table,
            'sql_query' => $sql_query,
            'is_procedure' => ! empty($analyzed_sql_results['procedure']),
        ]);
    }

    /**
     * Function to send response for ajax grid edit
     *
     * @param object $result result of the executed query
     */
    private function getResponseForGridEdit($result): void
    {
        $row = $this->dbi->fetchRow($result);
        $field_flags = $this->dbi->fieldFlags($result, 0);
        if (stripos($field_flags, DisplayResults::BINARY_FIELD) !== false) {
            $row[0] = bin2hex($row[0]);
        }
        $response = Response::getInstance();
        $response->addJSON('value', $row[0]);
    }

    /**
     * Returns a message for successful creation of a bookmark or null if a bookmark
     * was not created
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
     * @param DisplayResults   $displayResultsObject instance of DisplayResult
     * @param string           $themeImagePath       theme image uri
     * @param array            $displayParts         the parts to display
     * @param bool             $editable             whether the result table is
     *                                               editable or not
     * @param int              $unlim_num_rows       unlimited number of rows
     * @param int              $num_rows             number of rows
     * @param bool             $showtable            whether to show table or not
     * @param object|bool|null $result               result of the executed query
     * @param array            $analyzed_sql_results analyzed sql results
     * @param bool             $is_limited_display   Show only limited operations or not
     *
     * @return string
     */
    private function getHtmlForSqlQueryResultsTable(
        $displayResultsObject,
        $themeImagePath,
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
                    $result = $this->dbi->storeResult();
                }
                $num_rows = $this->dbi->numRows($result);

                if ($result !== false && $num_rows > 0) {
                    $fields_meta = $this->dbi->getFieldsMeta($result);
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
                        $themeImagePath,
                        $GLOBALS['text_dir'],
                        $analyzed_sql_results['is_maint'],
                        $analyzed_sql_results['is_explain'],
                        $analyzed_sql_results['is_show'],
                        $showtable,
                        $printview,
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

                $this->dbi->freeResult($result);
            } while ($this->dbi->moreResults() && $this->dbi->nextResult());
        } else {
            $fields_meta = [];
            if (isset($result) && ! is_bool($result)) {
                $fields_meta = $this->dbi->getFieldsMeta($result);
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
                $themeImagePath,
                $GLOBALS['text_dir'],
                $analyzed_sql_results['is_maint'],
                $analyzed_sql_results['is_explain'],
                $analyzed_sql_results['is_show'],
                $showtable,
                $printview,
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
            $this->dbi->freeResult($result);
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
     */
    private function getHtmlForPreviousUpdateQuery(
        ?string $displayQuery,
        bool $showSql,
        $sqlData,
        $displayMessage
    ): string {
        $output = '';
        if (isset($displayQuery) && ($showSql === true) && empty($sqlData)) {
            $output = Generator::getMessage(
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
     * @param string $table        current table
     * @param string $database     current database
     * @param bool   $editable     whether the results table can be editable or not
     * @param bool   $hasUniqueKey whether there is a unique key
     */
    private function getMessageIfMissingColumnIndex($table, $database, $editable, $hasUniqueKey): string
    {
        $output = '';
        if (! empty($table) && (Utilities::isSystemSchema($database) || ! $editable)) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, checkbox, Edit, Copy and Delete features'
                        . ' are not available. %s'
                    ),
                    MySQLDocumentation::showDocumentation(
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
                    MySQLDocumentation::showDocumentation(
                        'config',
                        'cfg_RowActionLinksWithoutUnique'
                    )
                )
            )->getDisplay();
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
     * @param array|null          $sql_data             sql data
     * @param DisplayResults      $displayResultsObject Instance of DisplayResults
     * @param string              $themeImagePath       uri of the theme image
     * @param int                 $unlim_num_rows       unlimited number of rows
     * @param int                 $num_rows             number of rows
     * @param string|null         $disp_query           display query
     * @param Message|string|null $disp_message         display message
     * @param array|null          $profiling_results    profiling results
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
        ?array $sql_data,
        $displayResultsObject,
        $themeImagePath,
        $unlim_num_rows,
        $num_rows,
        ?string $disp_query,
        $disp_message,
        ?array $profiling_results,
        $sql_query,
        ?string $complete_query
    ) {
        global $showtable;

        // If we are retrieving the full value of a truncated field or the original
        // value of a transformed field, show it here
        if (isset($_POST['grid_edit']) && $_POST['grid_edit'] == true) {
            $this->getResponseForGridEdit($result);
            exit;
        }

        // Gets the list of fields properties
        if (isset($result) && $result) {
            $fields_meta = $this->dbi->getFieldsMeta($result);
        } else {
            $fields_meta = [];
        }

        // Should be initialized these parameters before parsing
        $showtable = $showtable ?? null;

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

        $statement = $analyzed_sql_results['statement'] ?? null;
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

        if (Utilities::isSystemSchema($db) || ! $editable) {
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

        if (! isset($_POST['printview']) || $_POST['printview'] != '1') {
            $scripts->addFile('makegrid.js');
            $scripts->addFile('vendor/stickyfill.min.js');
            $scripts->addFile('sql.js');
            unset($GLOBALS['message']);
            //we don't need to buffer the output in getMessage here.
            //set a global variable and check against it in the function
            $GLOBALS['buffer_message'] = false;
        }

        $previousUpdateQueryHtml = $this->getHtmlForPreviousUpdateQuery(
            $disp_query ?? null,
            (bool) $GLOBALS['cfg']['ShowSQL'],
            $sql_data ?? null,
            $disp_message ?? null
        );

        $profilingChartHtml = '';
        if (! empty($profiling_results)) {
            $profiling = $this->getDetailedProfilingStats($profiling_results);
            $profilingChartHtml = $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
        }

        $missingUniqueColumnMessage = $this->getMessageIfMissingColumnIndex(
            $table,
            $db,
            $editable,
            $has_unique
        );

        $bookmarkCreatedMessage = $this->getBookmarkCreatedMessage();

        $tableHtml = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $themeImagePath,
            $displayParts,
            $editable,
            $unlim_num_rows,
            $num_rows,
            $showtable,
            $result,
            $analyzed_sql_results
        );

        $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
        $bookmarkSupportHtml = '';
        if (is_array($cfgBookmark)
            && $displayParts['bkm_form'] == '1'
            && (! empty($cfgBookmark) && empty($_GET['id_bookmark']))
            && ! empty($sql_query)
        ) {
            $bookmarkSupportHtml = $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => Url::getFromRoute('/sql', [
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sql_query,
                    'id_bookmark' => 1,
                ]),
                'user' => $cfgBookmark['user'],
                'sql_query' => $complete_query ?? $sql_query,
            ]);
        }

        return $this->template->render('sql/sql_query_results', [
            'previous_update_query' => $previousUpdateQueryHtml,
            'profiling_chart' => $profilingChartHtml,
            'missing_unique_column_message' => $missingUniqueColumnMessage,
            'bookmark_created_message' => $bookmarkCreatedMessage,
            'table' => $tableHtml,
            'bookmark_support' => $bookmarkSupportHtml,
        ]);
    }

    /**
     * Function to execute the query and send the response
     *
     * @param array|null          $analyzed_sql_results   analysed sql results
     * @param bool                $is_gotofile            whether goto file or not
     * @param string              $db                     current database
     * @param string|null         $table                  current table
     * @param bool|null           $find_real_end          whether to find real end or not
     * @param string|null         $sql_query_for_bookmark the sql query to be stored as bookmark
     * @param array|null          $extra_data             extra data
     * @param string|null         $message_to_show        message to show
     * @param array|null          $sql_data               sql data
     * @param string              $goto                   goto page url
     * @param string              $themeImagePath         uri of the PMA theme image
     * @param string|null         $disp_query             display query
     * @param Message|string|null $disp_message           display message
     * @param string              $sql_query              sql query
     * @param string|null         $complete_query         complete query
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
        $sql_data,
        $goto,
        $themeImagePath,
        $disp_query,
        $disp_message,
        $sql_query,
        $complete_query
    ): string {
        if ($analyzed_sql_results == null) {
            // Parse and analyze the query
            [
                $analyzed_sql_results,
                $db,
                $table_from_sql,
            ] = ParseAnalyze::sqlQuery($sql_query, $db);

            if ($table != $table_from_sql && ! empty($table_from_sql)) {
                $table = $table_from_sql;
            }
        }

        return $this->executeQueryAndGetQueryResponse(
            $analyzed_sql_results, // analyzed_sql_results
            $is_gotofile, // is_gotofile
            $db, // db
            $table, // table
            $find_real_end, // find_real_end
            $sql_query_for_bookmark, // sql_query_for_bookmark
            $extra_data, // extra_data
            $message_to_show, // message_to_show
            $sql_data, // sql_data
            $goto, // goto
            $themeImagePath,
            $disp_query, // disp_query
            $disp_message, // disp_message
            $sql_query, // sql_query
            $complete_query // complete_query
        );
    }

    /**
     * Function to execute the query and send the response
     *
     * @param array               $analyzed_sql_results   analysed sql results
     * @param bool                $is_gotofile            whether goto file or not
     * @param string|null         $db                     current database
     * @param string|null         $table                  current table
     * @param bool|null           $find_real_end          whether to find real end or not
     * @param string|null         $sql_query_for_bookmark the sql query to be stored as bookmark
     * @param array|null          $extra_data             extra data
     * @param string|null         $message_to_show        message to show
     * @param array|null          $sql_data               sql data
     * @param string              $goto                   goto page url
     * @param string              $themeImagePath         uri of the PMA theme image
     * @param string|null         $disp_query             display query
     * @param Message|string|null $disp_message           display message
     * @param string              $sql_query              sql query
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
        $sql_data,
        $goto,
        $themeImagePath,
        ?string $disp_query,
        $disp_message,
        $sql_query,
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
        $this->dbi->selectDb($db);

        [
            $result,
            $num_rows,
            $unlim_num_rows,
            $profiling_results,
            $extra_data,
        ] = $this->executeTheQuery(
            $analyzed_sql_results,
            $full_sql_query,
            $is_gotofile,
            $db,
            $table,
            $find_real_end ?? null,
            $sql_query_for_bookmark ?? null,
            $extra_data ?? null
        );

        if ($this->dbi->moreResults()) {
            $this->dbi->nextResult();
        }

        $warning_messages = $this->operations->getWarningMessagesArray();

        // No rows returned -> move back to the calling page
        if (($num_rows == 0 && $unlim_num_rows == 0)
            || $analyzed_sql_results['is_affected']
        ) {
            $html_output = $this->getQueryResponseForNoResultsReturned(
                $analyzed_sql_results,
                $db,
                $table,
                $message_to_show ?? null,
                $num_rows,
                $displayResultsObject,
                $extra_data,
                $themeImagePath,
                $profiling_results,
                $result ?? null,
                $sql_query,
                $complete_query ?? null
            );
        } else {
            // At least one row is returned -> displays a table with results
            $html_output = $this->getQueryResponseForResultsReturned(
                $result ?? null,
                $analyzed_sql_results,
                $db,
                $table,
                $sql_data ?? null,
                $displayResultsObject,
                $themeImagePath,
                $unlim_num_rows,
                $num_rows,
                $disp_query ?? null,
                $disp_message ?? null,
                $profiling_results,
                $sql_query,
                $complete_query ?? null
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
        if ($max_rows === null) {
            $max_rows = $_SESSION['tmpval']['max_rows'];
        }

        return @((int) ceil($number_of_line / $max_rows) - 1) * $max_rows;
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
        if ($pos === null) {
            $pos = $_SESSION['tmpval']['pos'];
        }

        $_table = new Table($table, $db);
        $unlim_num_rows = $_table->countRecords(true);
        //If position is higher than number of rows
        if ($unlim_num_rows <= $pos && $pos != 0) {
            $pos = $this->getStartPosToDisplayRow($unlim_num_rows);
        }

        return $pos;
    }
}
