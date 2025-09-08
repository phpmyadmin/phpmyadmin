<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Display\DeleteLinkEnum;
use PhpMyAdmin\Display\DisplayParts;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\SqlParser\Utils\StatementInfo;
use PhpMyAdmin\SqlParser\Utils\StatementType;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Table\UiProperty;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function array_column;
use function array_find_key;
use function array_key_exists;
use function array_keys;
use function array_sum;
use function arsort;
use function assert;
use function bin2hex;
use function ceil;
use function count;
use function defined;
use function htmlspecialchars;
use function in_array;
use function is_string;
use function session_start;
use function session_write_close;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtoupper;
use function ucwords;

/**
 * Set of functions for the SQL executor
 */
class Sql
{
    private float $queryTime = 0;
    public static bool|null $showAsPhp = null;
    public static Message|null $usingBookmarkMessage = null;

    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly Relation $relation,
        private readonly RelationCleanup $relationCleanup,
        private readonly Transformations $transformations,
        private readonly Template $template,
        private readonly BookmarkRepository $bookmarkRepository,
        private readonly Config $config,
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
        if ($statementInfo->statement === null) {
            return $statementInfo;
        }

        $tableObject = new Table($table, $db, $this->dbi);

        if (! $statementInfo->flags->order) {
            // Retrieving the name of the column we should sort after.
            $sortCol = $tableObject->getUiProp(UiProperty::SortedColumn);
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
            $statementInfo = Query::getAll($fullSqlQuery);
        } else {
            // Store the remembered table into session.
            $tableObject->setUiProp(
                UiProperty::SortedColumn,
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
        if ($statementInfo->statement === null) {
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
     * @param FieldMetadata[] $fieldsMeta meta fields
     */
    private function resultSetHasJustOneTable(array $fieldsMeta): bool
    {
        $justOneTable = true;
        $prevTable = '';
        foreach ($fieldsMeta as $oneFieldMeta) {
            if ($oneFieldMeta->table !== '' && $prevTable !== '' && $oneFieldMeta->table !== $prevTable) {
                $justOneTable = false;
            }

            if ($oneFieldMeta->table === '') {
                continue;
            }

            $prevTable = $oneFieldMeta->table;
        }

        return $justOneTable && $prevTable !== '';
    }

    /**
     * Verify whether the result set contains all the columns
     * of at least one unique key
     *
     * @param string          $db         database name
     * @param string          $table      table name
     * @param FieldMetadata[] $fieldsMeta meta fields
     */
    private function resultSetContainsUniqueKey(string $db, string $table, array $fieldsMeta): bool
    {
        if ($table === '') {
            return false;
        }

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
                    && array_key_exists($indexColumnName, $columns)
                    && ! str_contains($columns[$indexColumnName]->extra, 'INVISIBLE')
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
        $foreignData = $this->relation->getForeignData(
            $this->relation->getForeigners($db, $table, $column),
            $column,
            false,
            '',
            '',
        );

        if ($foreignData->dispRow === null) {
            //Handle the case when number of values
            //is more than $cfg['ForeignKeyMaxLimit']
            $urlParams = ['db' => $db, 'table' => $table, 'field' => $column];

            return $this->template->render('sql/relational_column_dropdown', [
                'current_value' => $_POST['curr_value'],
                'params' => $urlParams,
            ]);
        }

        $dropdown = $this->relation->foreignDropdown(
            $foreignData->dispRow,
            $foreignData->foreignField,
            $foreignData->foreignDisplay,
            $currentValue,
            $this->config->settings['ForeignKeyMaxLimit'],
        );

        return '<select>' . $dropdown . '</select>';
    }

    /**
     * @psalm-param non-empty-list<array{Status: non-empty-string, Duration: numeric-string}> $profilingResults
     *
     * @psalm-return array{
     *     total_time: float,
     *     states: array<string, array{total_time: float, calls: int<1, max>}>,
     *     chart: array{labels: list<string>, data: list<float>},
     *     profile: list<array{status: string, duration: string, duration_raw: numeric-string}>
     * }|array{}
     */
    private function getDetailedProfilingStats(array $profilingResults): array
    {
        $totalTime = (float) array_sum(array_column($profilingResults, 'Duration'));
        if ($totalTime === 0.0) {
            return [];
        }

        $states = [];
        $profile = [];
        foreach ($profilingResults as $result) {
            $status = ucwords($result['Status']);
            $profile[] = [
                'status' => $status,
                'duration' => Util::formatNumber($result['Duration'], 3, 1),
                'duration_raw' => $result['Duration'],
            ];

            if (! isset($states[$status])) {
                $states[$status] = ['total_time' => (float) $result['Duration'], 'calls' => 1];
            } else {
                $states[$status]['calls']++;
                $states[$status]['total_time'] += $result['Duration'];
            }
        }

        arsort($states);
        $chart = ['labels' => array_keys($states), 'data' => array_column($states, 'total_time')];

        return ['total_time' => $totalTime, 'states' => $states, 'chart' => $chart, 'profile' => $profile];
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

        if ($row === []) {
            return '';
        }

        return $row[$column];
    }

    /**
     * Get all the values for a enum column or set column in a table
     *
     * @param string $db         current database
     * @param string $table      current table
     * @param string $columnName current column
     *
     * @return string[]|null array containing the value list for the column, null on failure
     */
    public function getValuesForColumn(string $db, string $table, string $columnName): array|null
    {
        $column = $this->dbi->getColumn($db, $table, $columnName);

        if ($column === null) {
            return null;
        }

        return Util::parseEnumSetValues($column->type, false);
    }

    /**
     * Function to check whether to remember the sorting order or not.
     */
    private function isRememberSortingOrder(StatementInfo $statementInfo): bool
    {
        return $this->config->settings['RememberSorting']
            && ! ($statementInfo->flags->isCount
                || $statementInfo->flags->isExport
                || $statementInfo->flags->isFunc
                || $statementInfo->flags->isAnalyse)
            && $statementInfo->flags->selectFrom
            && ($statementInfo->selectExpressions === []
                || (count($statementInfo->selectExpressions) === 1
                    && $statementInfo->selectExpressions[0] === '*'))
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

        return $statementInfo->parser->errors === []
            && $_SESSION['tmpval']['max_rows'] !== 'all'
            && ! $statementInfo->flags->isExport && ! $statementInfo->flags->isAnalyse
            && ($statementInfo->flags->selectFrom || $statementInfo->flags->isSubQuery)
            && ! $statementInfo->flags->limit;
    }

    /**
     * Function to check whether this query is for just browsing
     */
    public static function isJustBrowsing(StatementInfo $statementInfo, bool $findRealEnd = false): bool
    {
        return ! $statementInfo->flags->isGroup
            && ! $statementInfo->flags->isFunc
            && ! $statementInfo->flags->union
            && ! $statementInfo->flags->distinct
            && $statementInfo->flags->selectFrom
            && (count($statementInfo->selectTables) === 1)
            && (empty($statementInfo->statement->where)
                || (count($statementInfo->statement->where) === 1
                    && $statementInfo->statement->where[0]->expr === '1'))
            && ! $statementInfo->flags->group
            && ! $findRealEnd
            && ! $statementInfo->flags->isSubQuery
            && ! $statementInfo->flags->join
            && ! $statementInfo->flags->having;
    }

    /**
     * Function to check whether the related transformation information should be deleted.
     */
    private function isDeleteTransformationInfo(StatementInfo $statementInfo): bool
    {
        return $statementInfo->flags->queryType === StatementType::Alter
            || $statementInfo->flags->queryType === StatementType::Drop;
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
        return ! $allowUserDropDatabase && $statementInfo->flags->dropDatabase && ! $isSuperUser;
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
        $bookmark = $this->bookmarkRepository->getByLabel(
            $this->config->selectedServer['user'],
            DatabaseName::from($db),
            $table,
        );

        if ($bookmark !== null && $bookmark->getQuery() !== '') {
            self::$usingBookmarkMessage = Message::notice(__('Using bookmark "%s" as default browse query.'));
            self::$usingBookmarkMessage->addParam($table);
            self::$usingBookmarkMessage->addHtml(MySQLDocumentation::showDocumentation('faq', 'faq6-22'));

            return $bookmark->getQuery();
        }

        $defaultOrderByClause = '';

        if (
            isset($this->config->settings['TablePrimaryKeyOrder'])
            && ($this->config->settings['TablePrimaryKeyOrder'] !== 'NONE')
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
                        . $this->config->settings['TablePrimaryKeyOrder'];
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
        $response = ResponseRenderer::getInstance();
        if ($isGotoFile) {
            $message = Message::rawError($error);
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
        } else {
            Generator::mysqlDie($error, $fullSqlQuery, false);
        }

        $response->callExit();
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
        string $db,
        string $bookmarkUser,
        string $sqlQueryForBookmark,
        string $bookmarkLabel,
        bool $bookmarkReplace,
    ): void {
        if ($bookmarkReplace) {
            $bookmarks = $this->bookmarkRepository->getList($this->config->selectedServer['user'], $db);
            foreach ($bookmarks as $bookmark) {
                if ($bookmark->getLabel() !== $bookmarkLabel) {
                    continue;
                }

                $bookmark->delete();
            }
        }

        $bookmark = $this->bookmarkRepository->createBookmark(
            $sqlQueryForBookmark,
            $bookmarkLabel,
            $bookmarkUser,
            $db,
            isset($_POST['bkm_all_users']),
        );

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

        /** Is false, except when a USE statement was sent. */
        $currentDb = $this->dbi->fetchValue('SELECT DATABASE()');

        return is_string($currentDb) && $db !== $currentDb;
    }

    /**
     * If a table, database or column gets dropped, clean comments.
     *
     * @param string $db     current database
     * @param string $table  current table
     * @param string $column current column
     * @param bool   $purge  whether purge set or not
     */
    private function cleanupRelations(string $db, string $table, string $column, bool $purge): void
    {
        if (! $purge || $db === '') {
            return;
        }

        if ($table !== '') {
            if ($column !== '') {
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
        if ($statementInfo->statement === null) {
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
        } elseif ($statementInfo->flags->queryType === StatementType::Select || $statementInfo->flags->isSubQuery) {
            //    c o u n t    q u e r y

            // If we are "just browsing", there is only one table (and no join),
            // and no WHERE clause (or just 'WHERE 1 '),
            // we do a quick count (which uses MaxExactCount) because
            // SQL_CALC_FOUND_ROWS is not quick on large InnoDB tables
            if ($justBrowsing) {
                // Get row count (is approximate for InnoDB)
                $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords();
                /**
                 * @todo Can we know at this point that this is InnoDB,
                 *       (in this case there would be no need for getting
                 *       an exact count)?
                 */
                if ($unlimNumRows < $this->config->settings['MaxExactCount']) {
                    // Get the exact count if approximate count
                    // is less than MaxExactCount
                    /**
                     * @todo In countRecords(), MaxExactCount is also verified,
                     *       so can we avoid checking it twice?
                     */
                    $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords(true);
                }
            } else {
                /** @var SelectStatement $statement */
                $statement = $statementInfo->statement;

                assert($statement->options !== null);
                /** @var int|null $noCacheIndex */
                $noCacheIndex = array_find_key(
                    $statement->options->options,
                    static function (mixed $value): bool {
                        return is_string($value) && strtoupper($value) === 'SQL_NO_CACHE';
                    },
                );

                $changeExpression = ! $statementInfo->flags->isGroup
                    && ! $statementInfo->flags->distinct
                    && ! $statementInfo->flags->union
                    && count($statement->expr) === 1;

                if (
                    $statementInfo->flags->order
                    || $statementInfo->flags->limit
                    || $changeExpression
                    || $noCacheIndex !== null
                ) {
                    $statement = clone $statement;
                    // Remove SQL_NO_CACHE from subquery because it is not valid sql
                    if ($noCacheIndex !== null) {
                        assert($statement->options !== null);
                        $statement->options = clone $statement->options;
                        unset($statement->options->options[$noCacheIndex]);
                    }
                }

                // Remove ORDER BY to decrease unnecessary sorting time
                $statement->order = null;

                // Removes LIMIT clause that might have been added
                $statement->limit = null;

                if ($changeExpression) {
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
     * @param string $fullSqlQuery        full sql query
     * @param bool   $isGotoFile          whether to go to a file
     * @param string $db                  current database
     * @param string $table               current table
     * @param string $sqlQueryForBookmark sql query to be stored as bookmark
     *
     * @psalm-return array{
     *  ResultInterface|false,
     *  int|numeric-string,
     *  int|numeric-string,
     *  list<array{Status: non-empty-string, Duration: numeric-string}>,
     *  string
     * }
     */
    private function executeTheQuery(
        StatementInfo $statementInfo,
        string $fullSqlQuery,
        bool $isGotoFile,
        string $db,
        string $table,
        string $sqlQueryForBookmark,
    ): array {
        $response = ResponseRenderer::getInstance();
        $response->getHeader()->getMenu()->setTable($table);

        Profiling::enable($this->dbi);

        if (! defined('TESTSUITE')) {
            // close session in case the query takes too long
            session_write_close();
        }

        $result = $this->dbi->tryQuery($fullSqlQuery);
        $this->queryTime = $this->dbi->lastQueryExecutionTime;

        if (! defined('TESTSUITE')) {
            // reopen session but prevent PHP from sending the session cookie again
            session_start(['use_cookies' => false]);
        }

        $errorMessage = '';

        // Displays an error message if required and stop parsing the script
        $error = $this->dbi->getError();
        if ($error && $this->config->settings['IgnoreMultiSubmitErrors']) {
            $errorMessage = $error;
        } elseif ($error !== '') {
            $this->handleQueryExecuteError($isGotoFile, $error, $fullSqlQuery);
        }

        // If there are no errors and bookmarklabel was given,
        // store the query as a bookmark
        if (! empty($_POST['bkm_label']) && $sqlQueryForBookmark) {
            $this->storeTheQueryAsBookmark(
                $db,
                $this->config->selectedServer['user'],
                $sqlQueryForBookmark,
                $_POST['bkm_label'],
                isset($_POST['bkm_replace']),
            );
        }

        // Gets the number of rows affected/returned
        // (This must be done immediately after the query because
        // mysql_affected_rows() reports about the last query done)
        $numRows = $this->getNumberOfRowsAffectedOrChanged($statementInfo->flags->isAffected, $result);

        $profilingResults = Profiling::getInformation($this->dbi);

        $justBrowsing = self::isJustBrowsing($statementInfo);

        $unlimNumRows = $this->countQueryResults($numRows, $justBrowsing, $db, $table, $statementInfo);

        $this->cleanupRelations($db, $table, $_POST['dropped_column'] ?? '', ! empty($_POST['purge']));

        return [$result, $numRows, $unlimNumRows, $profilingResults, $errorMessage];
    }

    /**
     * Delete related transformation information
     *
     * @param string $db    current database
     * @param string $table current table
     */
    private function deleteTransformationInfo(string $db, string $table, StatementInfo $statementInfo): void
    {
        if ($statementInfo->statement === null) {
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
     * @param string     $messageToShow message to show
     * @param int|string $numRows       number of rows
     */
    private function getMessageForNoRowsReturned(
        string $messageToShow,
        StatementInfo $statementInfo,
        int|string $numRows,
    ): Message {
        if ($statementInfo->flags->queryType === StatementType::Delete) {
            $message = Message::getMessageForDeletedRows($numRows);
        } elseif ($statementInfo->flags->isInsert) {
            if ($statementInfo->flags->queryType === StatementType::Replace) {
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
        } elseif ($statementInfo->flags->isAffected) {
            $message = Message::getMessageForAffectedRows($numRows);

            // Ok, here is an explanation for the !$is_select.
            // The form generated by PhpMyAdmin\SqlQueryForm
            // and /database/sql has many submit buttons
            // on the same form, and some confusion arises from the
            // fact that $message_to_show is sent for every case.
            // The $message_to_show containing a success message and sent with
            // the form should not have priority over errors
        } elseif ($messageToShow !== '' && $statementInfo->flags->queryType !== StatementType::Select) {
            $message = Message::rawSuccess(htmlspecialchars($messageToShow));
        } elseif (self::$showAsPhp === true) {
            $message = Message::success(__('Showing as PHP code'));
        } elseif (self::$showAsPhp === false) {
            /* User disable showing as PHP, query is only displayed */
            $message = Message::notice(__('Showing SQL query'));
        } else {
            $message = Message::success(
                __('MySQL returned an empty result set (i.e. zero rows).'),
            );
        }

        if ($this->queryTime > 0) {
            $queryTime = Message::notice(
                '(' . __('Query took %01.4f seconds.') . ')',
            );
            $queryTime->addParam($this->queryTime);
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
     * @param string                $db                   current database
     * @param string|null           $table                current table
     * @param string                $messageToShow        message to show
     * @param int|string            $numRows              number of rows
     * @param DisplayResults        $displayResultsObject DisplayResult instance
     * @param string                $errorMessage         error message from tryQuery
     * @param ResultInterface|false $result               executed query results
     * @param string                $sqlQuery             sql query
     * @param string                $completeQuery        complete sql query
     * @psalm-param int|numeric-string $numRows
     * @psalm-param list<array{Status: non-empty-string, Duration: numeric-string}> $profilingResults
     *
     * @return string html
     */
    private function getQueryResponseForNoResultsReturned(
        StatementInfo $statementInfo,
        string $db,
        string|null $table,
        string $messageToShow,
        int|string $numRows,
        DisplayResults $displayResultsObject,
        string $errorMessage,
        array $profilingResults,
        ResultInterface|false $result,
        string $sqlQuery,
        string $completeQuery,
    ): string {
        if ($this->isDeleteTransformationInfo($statementInfo)) {
            $this->deleteTransformationInfo($db, $table ?? '', $statementInfo);
        }

        if ($errorMessage !== '') {
            $message = Message::rawError($errorMessage);
        } else {
            $message = $this->getMessageForNoRowsReturned($messageToShow, $statementInfo, $numRows);
        }

        $queryMessage = Generator::getMessage($message, $sqlQuery, MessageType::Success);

        if (self::$showAsPhp !== null) {
            return $queryMessage;
        }

        $extraData = [];
        if (ResponseRenderer::$reload) {
            $extraData['reload'] = 1;
            $extraData['db'] = Current::$database;
        }

        // For ajax requests add message and sql_query as JSON
        if (empty($_REQUEST['ajax_page_request'])) {
            $extraData['message'] = $message;
            if ($this->config->settings['ShowSQL']) {
                $extraData['sql_query'] = $queryMessage;
            }
        }

        if (
            isset($_POST['dropped_column'])
            && $db !== '' && $table !== null && $table !== ''
        ) {
            // to refresh the list of indexes (Ajax mode)
            $extraData['indexes_list'] = $this->getIndexList($table, $db);
        }

        $response = ResponseRenderer::getInstance();
        $response->addJSON($extraData);
        $header = $response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('sql.js');

        // We can only skip result fetching if the result contains no columns.
        if (($result instanceof ResultInterface && $result->numFields() === 0) || $result === false) {
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
            $result,
            $statementInfo,
            true,
        );

        $profilingChart = $this->getProfilingChart($profilingResults);

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
                'user' => $this->config->selectedServer['user'],
                'sql_query' => $completeQuery,
                'allow_shared_bookmarks' => $this->config->config->AllowSharedBookmarks,
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
            'is_procedure' => $statementInfo->flags->isProcedure,
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
     * @param DisplayResults  $displayResultsObject instance of DisplayResult
     * @param bool            $editable             whether the result table is editable or not
     * @param int|string      $unlimNumRows         unlimited number of rows
     * @param int|string      $numRows              number of rows
     * @param ResultInterface $result               result of the executed query
     * @param bool            $isLimitedDisplay     Show only limited operations or not
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     */
    private function getHtmlForSqlQueryResultsTable(
        DisplayResults $displayResultsObject,
        DisplayParts $displayParts,
        bool $editable,
        int|string $unlimNumRows,
        int|string $numRows,
        ResultInterface $result,
        StatementInfo $statementInfo,
        bool $isLimitedDisplay = false,
    ): string {
        $printView = isset($_POST['printview']) && $_POST['printview'];
        $isBrowseDistinct = ! empty($_POST['is_browse_distinct']);

        if ($statementInfo->flags->isProcedure) {
            return $this->getHtmlForStoredProcedureResults(
                $result,
                $displayResultsObject,
                $statementInfo,
                $printView,
                $editable,
                $isBrowseDistinct,
                $isLimitedDisplay,
            );
        }

        $displayResultsObject->setProperties(
            $unlimNumRows,
            $this->dbi->getFieldsMeta($result),
            $statementInfo->flags->isCount,
            $statementInfo->flags->isExport,
            $statementInfo->flags->isFunc,
            $statementInfo->flags->isAnalyse,
            $numRows,
            $this->queryTime,
            $statementInfo->flags->isMaint,
            $statementInfo->flags->queryType === StatementType::Explain,
            $statementInfo->flags->queryType === StatementType::Show,
            $printView,
            $editable,
            $isBrowseDistinct,
        );

        return $displayResultsObject->getTable($result, $displayParts, $statementInfo, $isLimitedDisplay);
    }

    private function getHtmlForStoredProcedureResults(
        ResultInterface $result,
        DisplayResults $displayResultsObject,
        StatementInfo $statementInfo,
        bool $printView,
        bool $editable,
        bool $isBrowseDistinct,
        bool $isLimitedDisplay,
    ): string {
        $tableHtml = '';

        while ($result !== false) {
            $numRows = $result->numRows();

            if ($numRows > 0) {
                $displayResultsObject->setProperties(
                    $numRows,
                    $this->dbi->getFieldsMeta($result),
                    $statementInfo->flags->isCount,
                    $statementInfo->flags->isExport,
                    $statementInfo->flags->isFunc,
                    $statementInfo->flags->isAnalyse,
                    $numRows,
                    $this->queryTime,
                    $statementInfo->flags->isMaint,
                    $statementInfo->flags->queryType === StatementType::Explain,
                    $statementInfo->flags->queryType === StatementType::Show,
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

            $result = $this->dbi->nextResult();
        }

        return $tableHtml;
    }

    /**
     * Function to get html for the previous query if there is such.
     *
     * @param string|null    $displayQuery   display query
     * @param bool           $showSql        whether to show sql
     * @param Message|string $displayMessage display message
     */
    private function getHtmlForPreviousUpdateQuery(
        string|null $displayQuery,
        bool $showSql,
        Message|string $displayMessage,
    ): string {
        if ($displayQuery !== null && $showSql) {
            return Generator::getMessage($displayMessage, $displayQuery, MessageType::Success);
        }

        return '';
    }

    /**
     * To get the message if a column index is missing. If not will return null
     *
     * @param string $database     current database
     * @param bool   $editable     whether the results table can be editable or not
     * @param bool   $hasUniqueKey whether there is a unique key
     */
    private function getMessageIfMissingColumnIndex(
        string $database,
        bool $editable,
        bool $hasUniqueKey,
    ): string {
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
     * @param ResultInterface $result               executed query results
     * @param string          $db                   current database
     * @param string|null     $table                current table
     * @param DisplayResults  $displayResultsObject Instance of DisplayResults
     * @param int|string      $unlimNumRows         unlimited number of rows
     * @param int|string      $numRows              number of rows
     * @param string|null     $dispQuery            display query
     * @param Message|string  $dispMessage          display message
     * @param string          $sqlQuery             sql query
     * @param string          $completeQuery        complete sql query
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     * @psalm-param list<array{Status: non-empty-string, Duration: numeric-string}> $profilingResults
     *
     * @return string html
     */
    private function getQueryResponseForResultsReturned(
        ResultInterface $result,
        StatementInfo $statementInfo,
        string $db,
        string|null $table,
        DisplayResults $displayResultsObject,
        int|string $unlimNumRows,
        int|string $numRows,
        string|null $dispQuery,
        Message|string $dispMessage,
        array $profilingResults,
        string $sqlQuery,
        string $completeQuery,
    ): string {
        // If we are retrieving the full value of a truncated field or the original
        // value of a transformed field, show it here
        if (isset($_POST['grid_edit']) && $_POST['grid_edit']) {
            $this->getResponseForGridEdit($result);
            ResponseRenderer::getInstance()->callExit();
        }

        // Gets the list of fields properties
        $fieldsMeta = $this->dbi->getFieldsMeta($result);

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
                $statementInfo->flags->join
                || $statementInfo->flags->isSubQuery
                || count($statementInfo->selectTables) !== 1
            ) {
                $justOneTable = false;
            }
        }

        $hasUnique = $table !== null && $this->resultSetContainsUniqueKey($db, $table, $fieldsMeta);

        $editable = ($hasUnique
            || $this->config->settings['RowActionLinksWithoutUnique']
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

        if (isset($_POST['printview']) && $_POST['printview']) {
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

        if (! isset($_POST['printview']) || ! $_POST['printview']) {
            $scripts->addFile('makegrid.js');
            $scripts->addFile('sql.js');
            Current::$message = null;
        }

        $previousUpdateQueryHtml = $this->getHtmlForPreviousUpdateQuery(
            $dispQuery,
            $this->config->settings['ShowSQL'],
            $dispMessage,
        );

        $profilingChartHtml = $this->getProfilingChart($profilingResults);

        $missingUniqueColumnMessage = $table !== null
            ? $this->getMessageIfMissingColumnIndex($db, $editable, $hasUnique)
            : '';

        $bookmarkCreatedMessage = $this->getBookmarkCreatedMessage();

        $tableHtml = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $displayParts,
            $editable,
            $unlimNumRows,
            $numRows,
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
                'user' => $this->config->selectedServer['user'],
                'sql_query' => $completeQuery,
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
     * @param bool           $isGotoFile          whether goto file or not
     * @param string         $db                  current database
     * @param string|null    $table               current table
     * @param string         $sqlQueryForBookmark the sql query to be stored as bookmark
     * @param string         $messageToShow       message to show
     * @param string         $goto                goto page url
     * @param string|null    $dispQuery           display query
     * @param Message|string $dispMessage         display message
     * @param string         $sqlQuery            sql query
     * @param string         $completeQuery       complete query
     */
    public function executeQueryAndSendQueryResponse(
        StatementInfo|null $statementInfo,
        bool $isGotoFile,
        string $db,
        string|null $table,
        string $sqlQueryForBookmark,
        string $messageToShow,
        string $goto,
        string|null $dispQuery,
        Message|string $dispMessage,
        string $sqlQuery,
        string $completeQuery,
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
            $sqlQueryForBookmark, // sql_query_for_bookmark
            $messageToShow, // message_to_show
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
     * @param bool           $isGotoFile          whether goto file or not
     * @param string         $db                  current database
     * @param string|null    $table               current table
     * @param string         $sqlQueryForBookmark the sql query to be stored as bookmark
     * @param string         $messageToShow       message to show
     * @param string         $goto                goto page url
     * @param string|null    $dispQuery           display query
     * @param Message|string $dispMessage         display message
     * @param string         $sqlQuery            sql query
     * @param string         $completeQuery       complete query
     *
     * @return string html
     */
    public function executeQueryAndGetQueryResponse(
        StatementInfo $statementInfo,
        bool $isGotoFile,
        string $db,
        string|null $table,
        string $sqlQueryForBookmark,
        string $messageToShow,
        string $goto,
        string|null $dispQuery,
        Message|string $dispMessage,
        string $sqlQuery,
        string $completeQuery,
    ): string {
        // Handle remembered sorting order, only for single table query.
        // Handling is not required when it's a union query
        // (the parser never sets the 'union' key to 0).
        // Handling is also not required if we came from the "Sort by key"
        // drop-down.
        if (
            $this->isRememberSortingOrder($statementInfo)
            && ! $statementInfo->flags->union
            && ! isset($_POST['sort_by_key'])
        ) {
            if (! isset($_SESSION['sql_from_query_box'])) {
                $statementInfo = $this->handleSortOrder($db, $table ?? '', $statementInfo, $sqlQuery);
            } else {
                unset($_SESSION['sql_from_query_box']);
            }
        }

        $displayResultsObject = new DisplayResults(
            $this->dbi,
            $this->config,
            Current::$database,
            Current::$table,
            Current::$server,
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

        ResponseRenderer::$reload = $this->hasCurrentDbChanged($db);
        $this->dbi->selectDb($db);

        if (self::$showAsPhp !== null) {
            // Only if we ask to see the php code
            // The following was copied from getQueryResponseForNoResultsReturned()
            // Delete if it's not needed in this context
            if ($this->isDeleteTransformationInfo($statementInfo)) {
                $this->deleteTransformationInfo($db, $table ?? '', $statementInfo);
            }

            $message = $this->getMessageForNoRowsReturned($messageToShow, $statementInfo, 0);

            return Generator::getMessage($message, Current::$sqlQuery, MessageType::Success);
        }

        // Handle disable/enable foreign key checks
        $defaultFkCheck = ForeignKey::handleDisableCheckInit();

        [$result, $numRows, $unlimNumRows, $profilingResults, $errorMessage] = $this->executeTheQuery(
            $statementInfo,
            $fullSqlQuery,
            $isGotoFile,
            $db,
            $table ?? '',
            $sqlQueryForBookmark,
        );

        $warningMessages = $this->dbi->getWarnings();

        // No rows returned -> move back to the calling page
        if (($numRows === 0 && $unlimNumRows === 0) || $statementInfo->flags->isAffected || $result === false) {
            $htmlOutput = $this->getQueryResponseForNoResultsReturned(
                $statementInfo,
                $db,
                $table,
                $messageToShow,
                $numRows,
                $displayResultsObject,
                $errorMessage,
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
            $message = Message::notice(htmlspecialchars((string) $warning));
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
            $pos = (int) $_SESSION['tmpval']['pos'];
        }

        $tableObject = new Table($table, $db, $this->dbi);
        $unlimNumRows = $tableObject->countRecords(true);
        //If position is higher than number of rows
        if ($unlimNumRows <= $pos && $pos !== 0) {
            return $this->getStartPosToDisplayRow($unlimNumRows);
        }

        return $pos;
    }

    private function getIndexList(string $table, string $db): string
    {
        $indexes = Index::getFromTable($this->dbi, $table, $db);
        $indexesDuplicates = Index::findDuplicates($table, $db);
        $template = new Template();

        return $template->render('indexes', [
            'url_params' => UrlParams::$params,
            'indexes' => $indexes,
            'indexes_duplicates' => $indexesDuplicates,
        ]);
    }

    /** @psalm-param list<array{Status: non-empty-string, Duration: numeric-string}> $profilingResults */
    private function getProfilingChart(array $profilingResults): string
    {
        if ($profilingResults === []) {
            return '';
        }

        $profiling = $this->getDetailedProfilingStats($profilingResults);
        if ($profiling === []) {
            return '';
        }

        return $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
    }
}
