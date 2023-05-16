<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Display\DeleteLinkEnum;
use PhpMyAdmin\Display\DisplayParts;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function array_keys;
use function bin2hex;
use function ceil;
use function count;
use function defined;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_bool;
use function is_object;
use function session_start;
use function session_write_close;
use function sprintf;
use function str_contains;
use function str_replace;
use function ucwords;

/**
 * Set of functions for the SQL executor
 */
class Sql
{
    public function __construct(
        private DatabaseInterface $dbi,
        private Relation $relation,
        private RelationCleanup $relationCleanup,
        private Operations $operations,
        private Transformations $transformations,
        private Template $template,
    ) {
    }

    /**
     * Handle remembered sorting order, only for single table query
     *
     * @param string $db           database name
     * @param string $table        table name
     * @param string $fullSqlQuery SQL query
     */
    private function handleSortOrder(
        string $db,
        string $table,
        StatementInfo $statementInfo,
        string &$fullSqlQuery,
    ): StatementInfo {
        if ($statementInfo->statement === null || $statementInfo->parser === null) {
            return $statementInfo;
        }

        $tableObject = new Table($table, $db, $this->dbi);

        if (! $statementInfo->order) {
            // Retrieving the name of the column we should sort after.
            $sortCol = $tableObject->getUiProp(Table::PROP_SORTED_COLUMN);
            if (empty($sortCol)) {
                return $statementInfo;
            }

            // Remove the name of the table from the retrieved field name.
            $sortCol = str_replace(
                Util::backquote($table) . '.',
                '',
                $sortCol,
            );

            // Create the new query.
            $fullSqlQuery = Query::replaceClause(
                $statementInfo->statement,
                $statementInfo->parser->list,
                'ORDER BY ' . $sortCol,
            );

            // TODO: Avoid reparsing the query.
            $statementInfo = StatementInfo::fromArray(Query::getAll($fullSqlQuery));
        } else {
            // Store the remembered table into session.
            $tableObject->setUiProp(
                Table::PROP_SORTED_COLUMN,
                Query::getClause(
                    $statementInfo->statement,
                    $statementInfo->parser->list,
                    'ORDER BY',
                ),
            );
        }

        return $statementInfo;
    }

    /**
     * Append limit clause to SQL query
     *
     * @return string limit clause appended SQL query
     */
    private function getSqlWithLimitClause(StatementInfo $statementInfo): string
    {
        if ($statementInfo->statement === null || $statementInfo->parser === null) {
            return '';
        }

        return Query::replaceClause(
            $statementInfo->statement,
            $statementInfo->parser->list,
            'LIMIT ' . $_SESSION['tmpval']['pos'] . ', '
            . $_SESSION['tmpval']['max_rows'],
        );
    }

    /**
     * Verify whether the result set has columns from just one table
     *
     * @param mixed[] $fieldsMeta meta fields
     */
    private function resultSetHasJustOneTable(array $fieldsMeta): bool
    {
        $justOneTable = true;
        $prevTable = '';
        foreach ($fieldsMeta as $oneFieldMeta) {
            if ($oneFieldMeta->table != '' && $prevTable != '' && $oneFieldMeta->table != $prevTable) {
                $justOneTable = false;
            }

            if ($oneFieldMeta->table == '') {
                continue;
            }

            $prevTable = $oneFieldMeta->table;
        }

        return $justOneTable && $prevTable != '';
    }

    /**
     * Verify whether the result set contains all the columns
     * of at least one unique key
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param mixed[] $fieldsMeta meta fields
     */
    private function resultSetContainsUniqueKey(string $db, string $table, array $fieldsMeta): bool
    {
        $columns = $this->dbi->getColumns($db, $table);
        $resultSetColumnNames = [];
        foreach ($fieldsMeta as $oneMeta) {
            $resultSetColumnNames[] = $oneMeta->name;
        }

        foreach (Index::getFromTable($this->dbi, $table, $db) as $index) {
            if (! $index->isUnique()) {
                continue;
            }

            $indexColumns = $index->getColumns();
            $numberFound = 0;
            foreach (array_keys($indexColumns) as $indexColumnName) {
                if (
                    ! in_array($indexColumnName, $resultSetColumnNames)
                    && in_array($indexColumnName, $columns)
                    && ! str_contains($columns[$indexColumnName]['Extra'], 'INVISIBLE')
                ) {
                    continue;
                }

                $numberFound++;
            }

            if ($numberFound === count($indexColumns)) {
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
     * @param string $db           current database
     * @param string $table        current table
     * @param string $column       current column
     * @param string $currentValue current selected value
     *
     * @return string html for the dropdown
     */
    public function getHtmlForRelationalColumnDropdown(
        string $db,
        string $table,
        string $column,
        string $currentValue,
    ): string {
        $foreigners = $this->relation->getForeigners($db, $table, $column);

        $foreignData = $this->relation->getForeignData($foreigners, $column, false, '', '');

        if ($foreignData['disp_row'] == null) {
            //Handle the case when number of values
            //is more than $cfg['ForeignKeyMaxLimit']
            $urlParams = ['db' => $db, 'table' => $table, 'field' => $column];

            return $this->template->render('sql/relational_column_dropdown', [
                'current_value' => $_POST['curr_value'],
                'params' => $urlParams,
            ]);
        }

        $dropdown = $this->relation->foreignDropdown(
            $foreignData['disp_row'],
            $foreignData['foreign_field'],
            $foreignData['foreign_display'],
            $currentValue,
            $GLOBALS['cfg']['ForeignKeyMaxLimit'],
        );

        return '<select>' . $dropdown . '</select>';
    }

    /**
     * @param mixed[] $profilingResults
     *
     * @return array<string, int|mixed[]>
     */
    private function getDetailedProfilingStats(array $profilingResults): array
    {
        $profiling = ['total_time' => 0, 'states' => [], 'chart' => [], 'profile' => []];

        foreach ($profilingResults as $oneResult) {
            $status = ucwords($oneResult['Status']);
            $profiling['total_time'] += $oneResult['Duration'];
            $profiling['profile'][] = [
                'status' => $status,
                'duration' => Util::formatNumber($oneResult['Duration'], 3, 1),
                'duration_raw' => $oneResult['Duration'],
            ];

            if (! isset($profiling['states'][$status])) {
                $profiling['states'][$status] = ['total_time' => $oneResult['Duration'], 'calls' => 1];
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
        string $whereClause,
    ): string {
        $row = $this->dbi->fetchSingleRow(sprintf(
            'SELECT `%s` FROM `%s`.`%s` WHERE %s',
            $column,
            $db,
            $table,
            $whereClause,
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
     * @return mixed[]|null array containing the value list for the column, null on failure
     */
    public function getValuesForColumn(string $db, string $table, string $column): array|null
    {
        $fieldInfoQuery = QueryGenerator::getColumnsSql($db, $table, $this->dbi->escapeString($column));

        $fieldInfoResult = $this->dbi->fetchResult($fieldInfoQuery);

        if (! isset($fieldInfoResult[0])) {
            return null;
        }

        return Util::parseEnumSetValues($fieldInfoResult[0]['Type']);
    }

    /**
     * Function to check whether to remember the sorting order or not.
     */
    private function isRememberSortingOrder(StatementInfo $statementInfo): bool
    {
        return $GLOBALS['cfg']['RememberSorting']
            && ! ($statementInfo->isCount
                || $statementInfo->isExport
                || $statementInfo->isFunction
                || $statementInfo->isAnalyse)
            && $statementInfo->selectFrom
            && (empty($statementInfo->selectExpression)
                || ((count($statementInfo->selectExpression) === 1)
                    && ($statementInfo->selectExpression[0] === '*')))
            && count($statementInfo->selectTables) === 1;
    }

    /**
     * Function to check whether the LIMIT clause should be appended or not.
     */
    private function isAppendLimitClause(StatementInfo $statementInfo): bool
    {
        // Assigning LIMIT clause to an syntactically-wrong query
        // is not needed. Also we would want to show the true query
        // and the true error message to the query executor

        return (isset($statementInfo->parser)
            && $statementInfo->parser->errors === [])
            && ($_SESSION['tmpval']['max_rows'] !== 'all')
            && ! ($statementInfo->isExport
            || $statementInfo->isAnalyse)
            && ($statementInfo->selectFrom
                || $statementInfo->isSubquery)
            && ! $statementInfo->limit;
    }

    /**
     * Function to check whether this query is for just browsing
     *
     * @param bool|null $findRealEnd whether the real end should be found
     */
    public static function isJustBrowsing(StatementInfo $statementInfo, bool|null $findRealEnd): bool
    {
        return ! $statementInfo->isGroup
            && ! $statementInfo->isFunction
            && ! $statementInfo->union
            && ! $statementInfo->distinct
            && $statementInfo->selectFrom
            && (count($statementInfo->selectTables) === 1)
            && (empty($statementInfo->statement->where)
                || (count($statementInfo->statement->where) === 1
                    && $statementInfo->statement->where[0]->expr === '1'))
            && ! $statementInfo->group
            && ! isset($findRealEnd)
            && ! $statementInfo->isSubquery
            && ! $statementInfo->join
            && ! $statementInfo->having;
    }

    /**
     * Function to check whether the related transformation information should be deleted.
     */
    private function isDeleteTransformationInfo(StatementInfo $statementInfo): bool
    {
        return ! empty($statementInfo->queryType)
            && (($statementInfo->queryType === 'ALTER')
                || ($statementInfo->queryType === 'DROP'));
    }

    /**
     * Function to check whether the user has rights to drop the database
     *
     * @param bool $allowUserDropDatabase whether the user is allowed to drop db
     * @param bool $isSuperUser           whether this user is a superuser
     */
    public function hasNoRightsToDropDatabase(
        StatementInfo $statementInfo,
        bool $allowUserDropDatabase,
        bool $isSuperUser,
    ): bool {
        return ! $allowUserDropDatabase && $statementInfo->dropDatabase && ! $isSuperUser;
    }

    /**
     * Function to find the real end of rows
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return int the number of rows
     */
    public function findRealEndOfRows(string $db, string $table): int
    {
        $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords(true);
        $_SESSION['tmpval']['pos'] = $this->getStartPosToDisplayRow($unlimNumRows);

        return $unlimNumRows;
    }

    /**
     * Function to get the default sql query for browsing page
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return string the default $sql_query for browse page
     */
    public function getDefaultSqlQueryForBrowse(string $db, string $table): string
    {
        $bookmark = Bookmark::get(
            $this->dbi,
            $GLOBALS['cfg']['Server']['user'],
            DatabaseName::fromValue($db),
            $table,
            'label',
            false,
            true,
        );

        if ($bookmark !== null && $bookmark->getQuery() !== '') {
            $GLOBALS['using_bookmark_message'] = Message::notice(
                __('Using bookmark "%s" as default browse query.'),
            );
            $GLOBALS['using_bookmark_message']->addParam($table);
            $GLOBALS['using_bookmark_message']->addHtml(
                MySQLDocumentation::showDocumentation('faq', 'faq6-22'),
            );

            return $bookmark->getQuery();
        }

        $defaultOrderByClause = '';

        if (
            isset($GLOBALS['cfg']['TablePrimaryKeyOrder'])
            && ($GLOBALS['cfg']['TablePrimaryKeyOrder'] !== 'NONE')
        ) {
            $primaryKey = null;
            $primary = Index::getPrimary($this->dbi, $table, $db);

            if ($primary !== null) {
                $primarycols = $primary->getColumns();

                foreach ($primarycols as $col) {
                    $primaryKey = $col->getName();
                    break;
                }

                if ($primaryKey !== null) {
                    $defaultOrderByClause = ' ORDER BY '
                        . Util::backquote($table) . '.'
                        . Util::backquote($primaryKey) . ' '
                        . $GLOBALS['cfg']['TablePrimaryKeyOrder'];
                }
            }
        }

        return 'SELECT * FROM ' . Util::backquote($table) . $defaultOrderByClause;
    }

    /**
     * Responds an error when an error happens when executing the query
     *
     * @param bool   $isGotoFile   whether goto file or not
     * @param string $error        error after executing the query
     * @param string $fullSqlQuery full sql query
     */
    private function handleQueryExecuteError(bool $isGotoFile, string $error, string $fullSqlQuery): never
    {
        if ($isGotoFile) {
            $message = Message::rawError($error);
            $response = ResponseRenderer::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
        } else {
            Generator::mysqlDie($error, $fullSqlQuery, false);
        }

        exit;
    }

    /**
     * Function to store the query as a bookmark
     *
     * @param string $db                  the current database
     * @param string $bookmarkUser        the bookmarking user
     * @param string $sqlQueryForBookmark the query to be stored in bookmark
     * @param string $bookmarkLabel       bookmark label
     * @param bool   $bookmarkReplace     whether to replace existing bookmarks
     */
    public function storeTheQueryAsBookmark(
        BookmarkFeature|null $bookmarkFeature,
        string $db,
        string $bookmarkUser,
        string $sqlQueryForBookmark,
        string $bookmarkLabel,
        bool $bookmarkReplace,
    ): void {
        $bfields = [
            'bkm_database' => $db,
            'bkm_user' => $bookmarkUser,
            'bkm_sql_query' => $sqlQueryForBookmark,
            'bkm_label' => $bookmarkLabel,
        ];

        // Should we replace bookmark?
        if ($bookmarkReplace && $bookmarkFeature !== null) {
            $bookmarks = Bookmark::getList($bookmarkFeature, $this->dbi, $GLOBALS['cfg']['Server']['user'], $db);
            foreach ($bookmarks as $bookmark) {
                if ($bookmark->getLabel() !== $bookmarkLabel) {
                    continue;
                }

                $bookmark->delete();
            }
        }

        $bookmark = Bookmark::createBookmark($this->dbi, $bfields, isset($_POST['bkm_all_users']));

        if ($bookmark === false) {
            return;
        }

        $bookmark->save();
    }

    /**
     * Function to get the affected or changed number of rows after executing a query
     *
     * @param bool                  $isAffected whether the query affected a table
     * @param ResultInterface|false $result     results of executing the query
     *
     * @return int|string number of rows affected or changed
     * @psalm-return int|numeric-string
     */
    private function getNumberOfRowsAffectedOrChanged(bool $isAffected, ResultInterface|false $result): int|string
    {
        if ($isAffected) {
            return $this->dbi->affectedRows();
        }

        if ($result) {
            return $result->numRows();
        }

        return 0;
    }

    /**
     * Checks if the current database has changed
     * This could happen if the user sends a query like "USE `database`;"
     *
     * @param string $db the database in the query
     *
     * @return bool whether to reload the navigation(1) or not(0)
     */
    private function hasCurrentDbChanged(string $db): bool
    {
        if ($db === '') {
            return false;
        }

        $currentDb = $this->dbi->fetchValue('SELECT DATABASE()');

        // $current_db is false, except when a USE statement was sent
        return ($currentDb != false) && ($db !== $currentDb);
    }

    /**
     * If a table, database or column gets dropped, clean comments.
     *
     * @param string      $db     current database
     * @param string      $table  current table
     * @param string|null $column current column
     * @param bool        $purge  whether purge set or not
     */
    private function cleanupRelations(string $db, string $table, string|null $column, bool $purge): void
    {
        if (! $purge || $db === '') {
            return;
        }

        if ($table !== '') {
            if ($column !== null && $column !== '') {
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
     * the 'LIMIT' clause that may have been programmatically added
     *
     * @param int|string $numRows      number of rows affected/changed by the query
     * @param bool       $justBrowsing whether just browsing or not
     * @param string     $db           the current database
     * @param string     $table        the current table
     * @psalm-param int|numeric-string $numRows
     *
     * @return int|string unlimited number of rows
     * @psalm-return int|numeric-string
     */
    private function countQueryResults(
        int|string $numRows,
        bool $justBrowsing,
        string $db,
        string $table,
        StatementInfo $statementInfo,
    ): int|string {
        /* Shortcut for not analyzed/empty query */
        if ($statementInfo->statement === null || $statementInfo->parser === null) {
            return 0;
        }

        if (! $this->isAppendLimitClause($statementInfo)) {
            // if we did not append a limit, set this to get a correct
            // "Showing rows..." message
            // $_SESSION['tmpval']['max_rows'] = 'all';
            $unlimNumRows = $numRows;
        } elseif ($_SESSION['tmpval']['max_rows'] > $numRows) {
            // When user has not defined a limit in query and total rows in
            // result are less than max_rows to display, there is no need
            // to count total rows for that query again
            $unlimNumRows = $_SESSION['tmpval']['pos'] + $numRows;
        } elseif ($statementInfo->queryType === 'SELECT' || $statementInfo->isSubquery) {
            //    c o u n t    q u e r y

            // If we are "just browsing", there is only one table (and no join),
            // and no WHERE clause (or just 'WHERE 1 '),
            // we do a quick count (which uses MaxExactCount) because
            // SQL_CALC_FOUND_ROWS is not quick on large InnoDB tables

            // However, do not count again if we did it previously
            // due to $find_real_end == true
            if ($justBrowsing) {
                // Get row count (is approximate for InnoDB)
                $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords();
                /**
                 * @todo Can we know at this point that this is InnoDB,
                 *       (in this case there would be no need for getting
                 *       an exact count)?
                 */
                if ($unlimNumRows < $GLOBALS['cfg']['MaxExactCount']) {
                    // Get the exact count if approximate count
                    // is less than MaxExactCount
                    /**
                     * @todo In countRecords(), MaxExactCount is also verified,
                     *       so can we avoid checking it twice?
                     */
                    $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords(true);
                }
            } else {
                $statement = $statementInfo->statement;

                // Remove ORDER BY to decrease unnecessary sorting time
                if ($statementInfo->order) {
                    $statement->order = null;
                }

                // Removes LIMIT clause that might have been added
                if ($statementInfo->limit) {
                    $statement->limit = false;
                }

                if (! $statementInfo->isGroup && count($statement->expr) === 1) {
                    $statement->expr[0] = new Expression();
                    $statement->expr[0]->expr = '1';
                }

                $countQuery = 'SELECT COUNT(*) FROM (' . $statement->build() . ' ) as cnt';

                $unlimNumRows = (int) $this->dbi->fetchValue($countQuery);
            }
        } else {// not $is_select
            $unlimNumRows = 0;
        }

        return $unlimNumRows;
    }

    /**
     * Function to handle all aspects relating to executing the query
     *
     * @param string       $fullSqlQuery        full sql query
     * @param bool         $isGotoFile          whether to go to a file
     * @param string       $db                  current database
     * @param string|null  $table               current table
     * @param bool|null    $findRealEnd         whether to find the real end
     * @param string|null  $sqlQueryForBookmark sql query to be stored as bookmark
     * @param mixed[]|null $extraData           extra data
     *
     * @psalm-return array{
     *  ResultInterface|false|null,
     *  int|numeric-string,
     *  int|numeric-string,
     *  array<string, string>|null,
     *  array|null
     * }
     */
    private function executeTheQuery(
        StatementInfo $statementInfo,
        string $fullSqlQuery,
        bool $isGotoFile,
        string $db,
        string|null $table,
        bool|null $findRealEnd,
        string|null $sqlQueryForBookmark,
        array|null $extraData,
    ): array {
        $response = ResponseRenderer::getInstance();
        $response->getHeader()->getMenu()->setTable($table ?? '');

        // Only if we ask to see the php code
        if (isset($GLOBALS['show_as_php'])) {
            $result = null;
            $numRows = 0;
            $unlimNumRows = 0;
            $profilingResults = null;
        } else { // If we don't ask to see the php code
            Profiling::enable($this->dbi);

            if (! defined('TESTSUITE')) {
                // close session in case the query takes too long
                session_write_close();
            }

            $result = $this->dbi->tryQuery($fullSqlQuery);
            $GLOBALS['querytime'] = $this->dbi->lastQueryExecutionTime;

            if (! defined('TESTSUITE')) {
                // reopen session
                session_start();
            }

            // Displays an error message if required and stop parsing the script
            $error = $this->dbi->getError();
            if ($error && $GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
                $extraData['error'] = $error;
            } elseif ($error) {
                $this->handleQueryExecuteError($isGotoFile, $error, $fullSqlQuery);
            }

            // If there are no errors and bookmarklabel was given,
            // store the query as a bookmark
            if (! empty($_POST['bkm_label']) && $sqlQueryForBookmark) {
                $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
                $this->storeTheQueryAsBookmark(
                    $bookmarkFeature,
                    $db,
                    $bookmarkFeature !== null ? $GLOBALS['cfg']['Server']['user'] : '',
                    $sqlQueryForBookmark,
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace']),
                );
            }

            // Gets the number of rows affected/returned
            // (This must be done immediately after the query because
            // mysql_affected_rows() reports about the last query done)
            $numRows = $this->getNumberOfRowsAffectedOrChanged($statementInfo->isAffected, $result);

            $profilingResults = Profiling::getInformation($this->dbi);

            $justBrowsing = self::isJustBrowsing($statementInfo, $findRealEnd ?? null);

            $unlimNumRows = $this->countQueryResults($numRows, $justBrowsing, $db, $table ?? '', $statementInfo);

            $this->cleanupRelations($db, $table ?? '', $_POST['dropped_column'] ?? null, ! empty($_POST['purge']));

            if (
                isset($_POST['dropped_column'])
                && $db !== '' && $table !== null && $table !== ''
            ) {
                // to refresh the list of indexes (Ajax mode)

                $indexes = Index::getFromTable($this->dbi, $table, $db);
                $indexesDuplicates = Index::findDuplicates($table, $db);
                $template = new Template();

                $extraData['indexes_list'] = $template->render('indexes', [
                    'url_params' => $GLOBALS['urlParams'],
                    'indexes' => $indexes,
                    'indexes_duplicates' => $indexesDuplicates,
                ]);
            }
        }

        return [$result, $numRows, $unlimNumRows, $profilingResults, $extraData];
    }

    /**
     * Delete related transformation information
     *
     * @param string $db    current database
     * @param string $table current table
     */
    private function deleteTransformationInfo(string $db, string $table, StatementInfo $statementInfo): void
    {
        if (! isset($statementInfo->statement)) {
            return;
        }

        $statement = $statementInfo->statement;
        if ($statement instanceof AlterStatement) {
            if (
                ! empty($statement->altered[0])
                && $statement->altered[0]->options->has('DROP')
                && ! empty($statement->altered[0]->field->column)
            ) {
                $this->transformations->clear($db, $table, $statement->altered[0]->field->column);
            }
        } elseif ($statement instanceof DropStatement) {
            $this->transformations->clear($db, $table);
        }
    }

    /**
     * Function to get the message for the no rows returned case
     *
     * @param string|null $messageToShow message to show
     * @param int|string  $numRows       number of rows
     */
    private function getMessageForNoRowsReturned(
        string|null $messageToShow,
        StatementInfo $statementInfo,
        int|string $numRows,
    ): Message {
        if ($statementInfo->queryType === 'DELETE') {
            $message = Message::getMessageForDeletedRows($numRows);
        } elseif ($statementInfo->isInsert) {
            if ($statementInfo->queryType === 'REPLACE') {
                // For REPLACE we get DELETED + INSERTED row count,
                // so we have to call it affected
                $message = Message::getMessageForAffectedRows($numRows);
            } else {
                $message = Message::getMessageForInsertedRows($numRows);
            }

            $insertId = $this->dbi->insertId();
            if ($insertId !== 0) {
                // insert_id is id of FIRST record inserted in one insert,
                // so if we inserted multiple rows, we had to increment this
                $message->addText('[br]');
                // need to use a temporary because the Message class
                // currently supports adding parameters only to the first
                // message
                $inserted = Message::notice(__('Inserted row id: %1$d'));
                $inserted->addParam($insertId + $numRows - 1);
                $message->addMessage($inserted);
            }
        } elseif ($statementInfo->isAffected) {
            $message = Message::getMessageForAffectedRows($numRows);

            // Ok, here is an explanation for the !$is_select.
            // The form generated by PhpMyAdmin\SqlQueryForm
            // and /database/sql has many submit buttons
            // on the same form, and some confusion arises from the
            // fact that $message_to_show is sent for every case.
            // The $message_to_show containing a success message and sent with
            // the form should not have priority over errors
        } elseif ($messageToShow && $statementInfo->queryType !== 'SELECT') {
            $message = Message::rawSuccess(htmlspecialchars($messageToShow));
        } elseif (! empty($GLOBALS['show_as_php'])) {
            $message = Message::success(__('Showing as PHP code'));
        } elseif (isset($GLOBALS['show_as_php'])) {
            /* User disable showing as PHP, query is only displayed */
            $message = Message::notice(__('Showing SQL query'));
        } else {
            $message = Message::success(
                __('MySQL returned an empty result set (i.e. zero rows).'),
            );
        }

        if (isset($GLOBALS['querytime'])) {
            $queryTime = Message::notice(
                '(' . __('Query took %01.4f seconds.') . ')',
            );
            $queryTime->addParam($GLOBALS['querytime']);
            $message->addMessage($queryTime);
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
     * @param string                     $db                   current database
     * @param string|null                $table                current table
     * @param string|null                $messageToShow        message to show
     * @param int|string                 $numRows              number of rows
     * @param DisplayResults             $displayResultsObject DisplayResult instance
     * @param mixed[]|null               $extraData            extra data
     * @param mixed[]|null               $profilingResults     profiling results
     * @param ResultInterface|false|null $result               executed query results
     * @param string                     $sqlQuery             sql query
     * @param string|null                $completeQuery        complete sql query
     * @psalm-param int|numeric-string $numRows
     *
     * @return string html
     */
    private function getQueryResponseForNoResultsReturned(
        StatementInfo $statementInfo,
        string $db,
        string|null $table,
        string|null $messageToShow,
        int|string $numRows,
        DisplayResults $displayResultsObject,
        array|null $extraData,
        array|null $profilingResults,
        ResultInterface|false|null $result,
        string $sqlQuery,
        string|null $completeQuery,
    ): string {
        if ($this->isDeleteTransformationInfo($statementInfo)) {
            $this->deleteTransformationInfo($db, $table ?? '', $statementInfo);
        }

        if (isset($extraData['error'])) {
            $message = Message::rawError($extraData['error']);
        } else {
            $message = $this->getMessageForNoRowsReturned($messageToShow, $statementInfo, $numRows);
        }

        $queryMessage = Generator::getMessage($message, $GLOBALS['sql_query'], 'success');

        if (isset($GLOBALS['show_as_php'])) {
            return $queryMessage;
        }

        if (! empty($GLOBALS['reload'])) {
            $extraData['reload'] = 1;
            $extraData['db'] = $GLOBALS['db'];
        }

        // For ajax requests add message and sql_query as JSON
        if (empty($_REQUEST['ajax_page_request'])) {
            $extraData['message'] = $message;
            if ($GLOBALS['cfg']['ShowSQL']) {
                $extraData['sql_query'] = $queryMessage;
            }
        }

        $response = ResponseRenderer::getInstance();
        $response->addJSON($extraData ?? []);

        if (! $statementInfo->isSelect || isset($extraData['error'])) {
            return $queryMessage;
        }

        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => false,
            'deleteLink' => DeleteLinkEnum::NO_DELETE,
            'hasSortLink' => true,
            'hasNavigationBar' => false,
            'hasBookmarkForm' => true,
            'hasTextButton' => true,
            'hasPrintLink' => true,
        ]);

        $sqlQueryResultsTable = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $displayParts,
            false,
            0,
            $numRows,
            null,
            $result,
            $statementInfo,
            true,
        );

        $profilingChart = '';
        if ($profilingResults !== null) {
            $header = $response->getHeader();
            $scripts = $header->getScripts();
            $scripts->addFile('sql.js');

            $profiling = $this->getDetailedProfilingStats($profilingResults);
            $profilingChart = $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
        }

        $bookmark = '';
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if (
            $bookmarkFeature !== null
            && empty($_GET['id_bookmark'])
            && $sqlQuery
        ) {
            $bookmark = $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => Url::getFromRoute('/sql', [
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sqlQuery,
                    'id_bookmark' => 1,
                ]),
                'user' => $GLOBALS['cfg']['Server']['user'],
                'sql_query' => $completeQuery ?? $sqlQuery,
                'allow_shared_bookmarks' => $GLOBALS['cfg']['AllowSharedBookmarks'],
            ]);
        }

        return $this->template->render('sql/no_results_returned', [
            'message' => $queryMessage,
            'sql_query_results_table' => $sqlQueryResultsTable,
            'profiling_chart' => $profilingChart,
            'bookmark' => $bookmark,
            'db' => $db,
            'table' => $table,
            'sql_query' => $sqlQuery,
            'is_procedure' => $statementInfo->isProcedure,
        ]);
    }

    /**
     * Function to send response for ajax grid edit
     *
     * @param ResultInterface $result result of the executed query
     */
    private function getResponseForGridEdit(ResultInterface $result): void
    {
        $row = $result->fetchRow();
        $fieldsMeta = $this->dbi->getFieldsMeta($result);

        if (isset($fieldsMeta[0]) && $fieldsMeta[0]->isBinary()) {
            $row[0] = bin2hex($row[0]);
        }

        $response = ResponseRenderer::getInstance();
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
                __('Bookmark %s has been created.'),
            );
            $message->addParam($_GET['label']);
            $output = $message->getDisplay();
        }

        return $output;
    }

    /**
     * Function to get html for the sql query results table
     *
     * @param DisplayResults             $displayResultsObject instance of DisplayResult
     * @param bool                       $editable             whether the result table is
     *                                                         editable or not
     * @param int|string                 $unlimNumRows         unlimited number of rows
     * @param int|string                 $numRows              number of rows
     * @param mixed[]|null               $showTable            table definitions
     * @param ResultInterface|false|null $result               result of the executed query
     * @param bool                       $isLimitedDisplay     Show only limited operations or not
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     */
    private function getHtmlForSqlQueryResultsTable(
        DisplayResults $displayResultsObject,
        DisplayParts $displayParts,
        bool $editable,
        int|string $unlimNumRows,
        int|string $numRows,
        array|null $showTable,
        ResultInterface|false|null $result,
        StatementInfo $statementInfo,
        bool $isLimitedDisplay = false,
    ): string {
        $printView = isset($_POST['printview']) && $_POST['printview'] == '1' ? '1' : null;
        $tableHtml = '';
        $isBrowseDistinct = ! empty($_POST['is_browse_distinct']);

        if ($statementInfo->isProcedure) {
            do {
                if ($result === null) {
                    $result = $this->dbi->storeResult();
                }

                if ($result === false) {
                    $result = null;
                    continue;
                }

                $numRows = $result->numRows();

                if ($numRows > 0) {
                    $fieldsMeta = $this->dbi->getFieldsMeta($result);
                    $fieldsCount = count($fieldsMeta);

                    $displayResultsObject->setProperties(
                        $numRows,
                        $fieldsMeta,
                        $statementInfo->isCount,
                        $statementInfo->isExport,
                        $statementInfo->isFunction,
                        $statementInfo->isAnalyse,
                        $numRows,
                        $fieldsCount,
                        $GLOBALS['querytime'],
                        $GLOBALS['text_dir'],
                        $statementInfo->isMaint,
                        $statementInfo->isExplain,
                        $statementInfo->isShow,
                        $showTable,
                        $printView,
                        $editable,
                        $isBrowseDistinct,
                    );

                    $displayParts = DisplayParts::fromArray([
                        'hasEditLink' => false,
                        'deleteLink' => DeleteLinkEnum::NO_DELETE,
                        'hasSortLink' => true,
                        'hasNavigationBar' => true,
                        'hasBookmarkForm' => true,
                        'hasTextButton' => true,
                        'hasPrintLink' => true,
                    ]);

                    $tableHtml .= $displayResultsObject->getTable(
                        $result,
                        $displayParts,
                        $statementInfo,
                        $isLimitedDisplay,
                    );
                }

                $result = null;
            } while ($this->dbi->moreResults() && $this->dbi->nextResult());
        } else {
            $fieldsMeta = [];
            if (isset($result) && ! is_bool($result)) {
                $fieldsMeta = $this->dbi->getFieldsMeta($result);
            }

            $fieldsCount = count($fieldsMeta);
            $_SESSION['is_multi_query'] = false;
            $displayResultsObject->setProperties(
                $unlimNumRows,
                $fieldsMeta,
                $statementInfo->isCount,
                $statementInfo->isExport,
                $statementInfo->isFunction,
                $statementInfo->isAnalyse,
                $numRows,
                $fieldsCount,
                $GLOBALS['querytime'],
                $GLOBALS['text_dir'],
                $statementInfo->isMaint,
                $statementInfo->isExplain,
                $statementInfo->isShow,
                $showTable,
                $printView,
                $editable,
                $isBrowseDistinct,
            );

            if (! is_bool($result)) {
                $tableHtml .= $displayResultsObject->getTable(
                    $result,
                    $displayParts,
                    $statementInfo,
                    $isLimitedDisplay,
                );
            }
        }

        return $tableHtml;
    }

    /**
     * Function to get html for the previous query if there is such.
     *
     * @param string|null    $displayQuery   display query
     * @param bool           $showSql        whether to show sql
     * @param mixed[]        $sqlData        sql data
     * @param Message|string $displayMessage display message
     */
    private function getHtmlForPreviousUpdateQuery(
        string|null $displayQuery,
        bool $showSql,
        array $sqlData,
        Message|string $displayMessage,
    ): string {
        if ($displayQuery !== null && $showSql && $sqlData === []) {
            return Generator::getMessage($displayMessage, $displayQuery, 'success');
        }

        return '';
    }

    /**
     * To get the message if a column index is missing. If not will return null
     *
     * @param string|null $table        current table
     * @param string      $database     current database
     * @param bool        $editable     whether the results table can be editable or not
     * @param bool        $hasUniqueKey whether there is a unique key
     */
    private function getMessageIfMissingColumnIndex(
        string|null $table,
        string $database,
        bool $editable,
        bool $hasUniqueKey,
    ): string {
        if ($table === null) {
            return '';
        }

        $output = '';
        if (Utilities::isSystemSchema($database) || ! $editable) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, checkbox, Edit, Copy and Delete features'
                        . ' are not available. %s',
                    ),
                    MySQLDocumentation::showDocumentation(
                        'config',
                        'cfg_RowActionLinksWithoutUnique',
                    ),
                ),
            )->getDisplay();
        } elseif (! $hasUniqueKey) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, Edit, Copy and Delete features may result in'
                        . ' undesired behavior. %s',
                    ),
                    MySQLDocumentation::showDocumentation(
                        'config',
                        'cfg_RowActionLinksWithoutUnique',
                    ),
                ),
            )->getDisplay();
        }

        return $output;
    }

    /**
     * Function to display results when the executed query returns non empty results
     *
     * @param ResultInterface|false|null $result               executed query results
     * @param string                     $db                   current database
     * @param string|null                $table                current table
     * @param mixed[]|null               $sqlData              sql data
     * @param DisplayResults             $displayResultsObject Instance of DisplayResults
     * @param int|string                 $unlimNumRows         unlimited number of rows
     * @param int|string                 $numRows              number of rows
     * @param string|null                $dispQuery            display query
     * @param Message|string|null        $dispMessage          display message
     * @param mixed[]|null               $profilingResults     profiling results
     * @param string                     $sqlQuery             sql query
     * @param string|null                $completeQuery        complete sql query
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     *
     * @return string html
     */
    private function getQueryResponseForResultsReturned(
        ResultInterface|false|null $result,
        StatementInfo $statementInfo,
        string $db,
        string|null $table,
        array|null $sqlData,
        DisplayResults $displayResultsObject,
        int|string $unlimNumRows,
        int|string $numRows,
        string|null $dispQuery,
        Message|string|null $dispMessage,
        array|null $profilingResults,
        string $sqlQuery,
        string|null $completeQuery,
    ): string {
        $GLOBALS['showtable'] ??= null;

        // If we are retrieving the full value of a truncated field or the original
        // value of a transformed field, show it here
        if (isset($_POST['grid_edit']) && $_POST['grid_edit'] == true && is_object($result)) {
            $this->getResponseForGridEdit($result);
            exit;
        }

        // Gets the list of fields properties
        $fieldsMeta = [];
        if ($result !== null && ! is_bool($result)) {
            $fieldsMeta = $this->dbi->getFieldsMeta($result);
        }

        // Should be initialized these parameters before parsing
        if (! is_array($GLOBALS['showtable'])) {
            $GLOBALS['showtable'] = null;
        }

        $response = ResponseRenderer::getInstance();
        $header = $response->getHeader();
        $scripts = $header->getScripts();

        $justOneTable = $this->resultSetHasJustOneTable($fieldsMeta);

        // hide edit and delete links:
        // - for information_schema
        // - if the result set does not contain all the columns of a unique key
        //   (unless this is an updatable view)
        // - if the SELECT query contains a join or a subquery

        $updatableView = false;

        $statement = $statementInfo->statement;
        if ($statement instanceof SelectStatement) {
            if ($statement->expr && $statement->expr[0]->expr === '*' && $table) {
                $tableObj = new Table($table, $db, $this->dbi);
                $updatableView = $tableObj->isUpdatableView();
            }

            if (
                $statementInfo->join
                || $statementInfo->isSubquery
                || count($statementInfo->selectTables) !== 1
            ) {
                $justOneTable = false;
            }
        }

        $hasUnique = $table !== null && $this->resultSetContainsUniqueKey($db, $table, $fieldsMeta);

        $editable = ($hasUnique
            || $GLOBALS['cfg']['RowActionLinksWithoutUnique']
            || $updatableView)
            && $justOneTable
            && ! Utilities::isSystemSchema($db);

        $_SESSION['tmpval']['possible_as_geometry'] = $editable;

        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => true,
            'deleteLink' => DeleteLinkEnum::DELETE_ROW,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => false,
            'hasPrintLink' => true,
        ]);

        if (! $editable) {
            $displayParts = DisplayParts::fromArray([
                'hasEditLink' => false,
                'deleteLink' => DeleteLinkEnum::NO_DELETE,
                'hasSortLink' => true,
                'hasNavigationBar' => true,
                'hasBookmarkForm' => true,
                'hasTextButton' => true,
                'hasPrintLink' => true,
            ]);
        }

        if (isset($_POST['printview']) && $_POST['printview'] == '1') {
            $displayParts = DisplayParts::fromArray([
                'hasEditLink' => false,
                'deleteLink' => DeleteLinkEnum::NO_DELETE,
                'hasSortLink' => false,
                'hasNavigationBar' => false,
                'hasBookmarkForm' => false,
                'hasTextButton' => false,
                'hasPrintLink' => false,
            ]);
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
            $dispQuery,
            (bool) $GLOBALS['cfg']['ShowSQL'],
            $sqlData ?? [],
            $dispMessage ?? '',
        );

        $profilingChartHtml = '';
        if ($profilingResults) {
            $profiling = $this->getDetailedProfilingStats($profilingResults);
            $profilingChartHtml = $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
        }

        $missingUniqueColumnMessage = $this->getMessageIfMissingColumnIndex($table, $db, $editable, $hasUnique);

        $bookmarkCreatedMessage = $this->getBookmarkCreatedMessage();

        $tableHtml = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $displayParts,
            $editable,
            $unlimNumRows,
            $numRows,
            $GLOBALS['showtable'],
            $result,
            $statementInfo,
        );

        $bookmarkSupportHtml = '';
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if (
            $bookmarkFeature !== null
            && $displayParts->hasBookmarkForm
            && empty($_GET['id_bookmark'])
            && $sqlQuery
        ) {
            $bookmarkSupportHtml = $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => Url::getFromRoute('/sql', [
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sqlQuery,
                    'id_bookmark' => 1,
                ]),
                'user' => $GLOBALS['cfg']['Server']['user'],
                'sql_query' => $completeQuery ?? $sqlQuery,
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
     * @param bool                $isGotoFile          whether goto file or not
     * @param string              $db                  current database
     * @param string|null         $table               current table
     * @param bool|null           $findRealEnd         whether to find real end or not
     * @param string|null         $sqlQueryForBookmark the sql query to be stored as bookmark
     * @param mixed[]|null        $extraData           extra data
     * @param string|null         $messageToShow       message to show
     * @param mixed[]|null        $sqlData             sql data
     * @param string              $goto                goto page url
     * @param string|null         $dispQuery           display query
     * @param Message|string|null $dispMessage         display message
     * @param string              $sqlQuery            sql query
     * @param string|null         $completeQuery       complete query
     */
    public function executeQueryAndSendQueryResponse(
        StatementInfo|null $statementInfo,
        bool $isGotoFile,
        string $db,
        string|null $table,
        bool|null $findRealEnd,
        string|null $sqlQueryForBookmark,
        array|null $extraData,
        string|null $messageToShow,
        array|null $sqlData,
        string $goto,
        string|null $dispQuery,
        Message|string|null $dispMessage,
        string $sqlQuery,
        string|null $completeQuery,
    ): string {
        if ($statementInfo === null) {
            // Parse and analyze the query
            [$statementInfo, $db, $tableFromSql] = ParseAnalyze::sqlQuery($sqlQuery, $db);

            $table = $tableFromSql !== '' ? $tableFromSql : $table;
        }

        return $this->executeQueryAndGetQueryResponse(
            $statementInfo,
            $isGotoFile, // is_gotofile
            $db, // db
            $table, // table
            $findRealEnd, // find_real_end
            $sqlQueryForBookmark, // sql_query_for_bookmark
            $extraData, // extra_data
            $messageToShow, // message_to_show
            $sqlData, // sql_data
            $goto, // goto
            $dispQuery, // disp_query
            $dispMessage, // disp_message
            $sqlQuery, // sql_query
            $completeQuery, // complete_query
        );
    }

    /**
     * Function to execute the query and send the response
     *
     * @param bool                $isGotoFile          whether goto file or not
     * @param string              $db                  current database
     * @param string|null         $table               current table
     * @param bool|null           $findRealEnd         whether to find real end or not
     * @param string|null         $sqlQueryForBookmark the sql query to be stored as bookmark
     * @param mixed[]|null        $extraData           extra data
     * @param string|null         $messageToShow       message to show
     * @param mixed[]|null        $sqlData             sql data
     * @param string              $goto                goto page url
     * @param string|null         $dispQuery           display query
     * @param Message|string|null $dispMessage         display message
     * @param string              $sqlQuery            sql query
     * @param string|null         $completeQuery       complete query
     *
     * @return string html
     */
    public function executeQueryAndGetQueryResponse(
        StatementInfo $statementInfo,
        bool $isGotoFile,
        string $db,
        string|null $table,
        bool|null $findRealEnd,
        string|null $sqlQueryForBookmark,
        array|null $extraData,
        string|null $messageToShow,
        array|null $sqlData,
        string $goto,
        string|null $dispQuery,
        Message|string|null $dispMessage,
        string $sqlQuery,
        string|null $completeQuery,
    ): string {
        // Handle disable/enable foreign key checks
        $defaultFkCheck = ForeignKey::handleDisableCheckInit();

        // Handle remembered sorting order, only for single table query.
        // Handling is not required when it's a union query
        // (the parser never sets the 'union' key to 0).
        // Handling is also not required if we came from the "Sort by key"
        // drop-down.
        if (
            $this->isRememberSortingOrder($statementInfo)
            && ! $statementInfo->union
            && ! isset($_POST['sort_by_key'])
        ) {
            if (! isset($_SESSION['sql_from_query_box'])) {
                $statementInfo = $this->handleSortOrder($db, $table, $statementInfo, $sqlQuery);
            } else {
                unset($_SESSION['sql_from_query_box']);
            }
        }

        $displayResultsObject = new DisplayResults(
            $this->dbi,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['server'],
            $goto,
            $sqlQuery,
        );
        $displayResultsObject->setConfigParamsForDisplayTable($statementInfo);

        // assign default full_sql_query
        $fullSqlQuery = $sqlQuery;

        // Do append a "LIMIT" clause?
        if ($this->isAppendLimitClause($statementInfo)) {
            $fullSqlQuery = $this->getSqlWithLimitClause($statementInfo);
        }

        $GLOBALS['reload'] = $this->hasCurrentDbChanged($db);
        $this->dbi->selectDb($db);

        [$result, $numRows, $unlimNumRows, $profilingResults, $extraData] = $this->executeTheQuery(
            $statementInfo,
            $fullSqlQuery,
            $isGotoFile,
            $db,
            $table,
            $findRealEnd,
            $sqlQueryForBookmark,
            $extraData,
        );

        if ($this->dbi->moreResults()) {
            $this->dbi->nextResult();
        }

        $warningMessages = $this->operations->getWarningMessagesArray();

        // No rows returned -> move back to the calling page
        if (($numRows == 0 && $unlimNumRows == 0) || $statementInfo->isAffected) {
            $htmlOutput = $this->getQueryResponseForNoResultsReturned(
                $statementInfo,
                $db,
                $table,
                $messageToShow,
                $numRows,
                $displayResultsObject,
                $extraData,
                $profilingResults,
                $result,
                $sqlQuery,
                $completeQuery,
            );
        } else {
            // At least one row is returned -> displays a table with results
            $htmlOutput = $this->getQueryResponseForResultsReturned(
                $result,
                $statementInfo,
                $db,
                $table,
                $sqlData,
                $displayResultsObject,
                $unlimNumRows,
                $numRows,
                $dispQuery,
                $dispMessage,
                $profilingResults,
                $sqlQuery,
                $completeQuery,
            );
        }

        // Handle disable/enable foreign key checks
        ForeignKey::handleDisableCheckCleanup($defaultFkCheck);

        foreach ($warningMessages as $warning) {
            $message = Message::notice(Message::sanitize($warning));
            $htmlOutput .= $message->getDisplay();
        }

        return $htmlOutput;
    }

    /**
     * Function to define pos to display a row
     *
     * @param int $numberOfLine Number of the line to display
     *
     * @return int Start position to display the line
     */
    private function getStartPosToDisplayRow(int $numberOfLine): int
    {
        $maxRows = $_SESSION['tmpval']['max_rows'];

        return @((int) ceil($numberOfLine / $maxRows) - 1) * $maxRows;
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
    public function calculatePosForLastPage(string $db, string $table, int|null $pos): int
    {
        if ($pos === null) {
            $pos = $_SESSION['tmpval']['pos'];
        }

        $tableObject = new Table($table, $db, $this->dbi);
        $unlimNumRows = $tableObject->countRecords(true);
        //If position is higher than number of rows
        if ($unlimNumRows <= $pos && $pos != 0) {
            return $this->getStartPosToDisplayRow($unlimNumRows);
        }

        return $pos;
    }
}
