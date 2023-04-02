<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function in_array;
use function substr;

/**
 * Represents a database node in the navigation tree
 */
class NodeDatabase extends Node
{
    /**
     * The number of hidden items in this database
     *
     * @var int<0, max>
     */
    protected int $hiddenCount = 0;

    /** @var int[][] $presenceCounts */
    private array $presenceCounts = [];

    /**
     * Initialises the class
     *
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct(string $name, int $type = Node::OBJECT, bool $isGroup = false)
    {
        parent::__construct($name, $type, $isGroup);

        $this->icon = ['image' => 's_db', 'title' => __('Database operations')];

        $this->links = [
            'text' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database'),
                'params' => ['db' => null],
            ],
            'icon' => ['route' => '/database/operations', 'params' => ['db' => null]],
            'title' => __('Structure'),
        ];

        $this->classes = 'database';
        $this->urlParamName = 'db';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    public function getPresence(string $type = '', string $searchClause = ''): int
    {
        if (isset($this->presenceCounts[$type][$searchClause])) {
            return $this->presenceCounts[$type][$searchClause];
        }

        return $this->presenceCounts[$type][$searchClause] = match ($type) {
            'tables' => $this->getTableCount($searchClause),
            'views' => $this->getViewCount($searchClause),
            'procedures' => $this->getProcedureCount($searchClause),
            'functions' => $this->getFunctionCount($searchClause),
            'events' => $this->getEventCount($searchClause),
            default => 0,
        };
    }

    /**
     * Returns the number of tables or views present inside this database
     *
     * @param string $which        tables|views
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    private function getTableOrViewCount(string $which, string $searchClause): int
    {
        if ($which === 'tables') {
            $condition = 'IN';
        } else {
            $condition = 'NOT IN';
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`TABLES` ';
            $query .= 'WHERE `TABLE_SCHEMA`=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
            $query .= 'AND `TABLE_TYPE` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if ($searchClause !== '') {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'TABLE_NAME');
            }

            return (int) $GLOBALS['dbi']->fetchValue($query);
        }

        $query = 'SHOW FULL TABLES FROM ';
        $query .= Util::backquote($this->realName);
        $query .= ' WHERE `Table_type` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
        if ($searchClause !== '') {
            $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'Tables_in_' . $this->realName);
        }

        return $GLOBALS['dbi']->queryAndGetNumRows($query);
    }

    /**
     * Returns the number of tables present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    private function getTableCount(string $searchClause): int
    {
        return $this->getTableOrViewCount('tables', $searchClause);
    }

    /**
     * Returns the number of views present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    private function getViewCount(string $searchClause): int
    {
        return $this->getTableOrViewCount('views', $searchClause);
    }

    /**
     * Returns the number of procedures present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    private function getProcedureCount(string $searchClause): int
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` '
                . Util::getCollateForIS() . '=' . $GLOBALS['dbi']->quoteString($this->realName);
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
            if ($searchClause !== '') {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'ROUTINE_NAME');
            }

            return (int) $GLOBALS['dbi']->fetchValue($query);
        }

        $query = 'SHOW PROCEDURE STATUS WHERE `Db`=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
        if ($searchClause !== '') {
            $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'Name');
        }

        return $GLOBALS['dbi']->queryAndGetNumRows($query);
    }

    /**
     * Returns the number of functions present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    private function getFunctionCount(string $searchClause): int
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` '
                . Util::getCollateForIS() . '=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
            if ($searchClause !== '') {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'ROUTINE_NAME');
            }

            return (int) $GLOBALS['dbi']->fetchValue($query);
        }

        $query = 'SHOW FUNCTION STATUS WHERE `Db`=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
        if ($searchClause !== '') {
            $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'Name');
        }

        return $GLOBALS['dbi']->queryAndGetNumRows($query);
    }

    /**
     * Returns the number of events present inside this database
     *
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    private function getEventCount(string $searchClause): int
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`EVENTS` ';
            $query .= 'WHERE `EVENT_SCHEMA` '
                . Util::getCollateForIS() . '=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
            if ($searchClause !== '') {
                $query .= 'AND ' . $this->getWhereClauseForSearch($searchClause, 'EVENT_NAME');
            }

            return (int) $GLOBALS['dbi']->fetchValue($query);
        }

        $query = 'SHOW EVENTS FROM ' . Util::backquote($this->realName) . ' ';
        if ($searchClause !== '') {
            $query .= 'WHERE ' . $this->getWhereClauseForSearch($searchClause, 'Name');
        }

        return $GLOBALS['dbi']->queryAndGetNumRows($query);
    }

    /**
     * Returns the WHERE clause for searching inside a database
     *
     * @param string $searchClause A string used to filter the results of the query
     * @param string $columnName   Name of the column in the result set to match
     *
     * @return string WHERE clause for searching
     */
    private function getWhereClauseForSearch(
        string $searchClause,
        string $columnName,
    ): string {
        return Util::backquote($columnName) . ' LIKE '
            . $GLOBALS['dbi']->quoteString('%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%');
    }

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    public function getData(string $type, int $pos, string $searchClause = ''): array
    {
        $retval = [];
        switch ($type) {
            case 'tables':
                $retval = $this->getTables($pos, $searchClause);
                break;
            case 'views':
                $retval = $this->getViews($pos, $searchClause);
                break;
            case 'procedures':
                $retval = $this->getProcedures($pos, $searchClause);
                break;
            case 'functions':
                $retval = $this->getFunctions($pos, $searchClause);
                break;
            case 'events':
                $retval = $this->getEvents($pos, $searchClause);
                break;
            default:
                break;
        }

        // Remove hidden items so that they are not displayed in navigation tree
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            $hiddenItems = $this->getHiddenItems(substr($type, 0, -1));
            foreach ($retval as $key => $item) {
                if (! in_array($item, $hiddenItems)) {
                    continue;
                }

                unset($retval[$key]);
            }
        }

        return $retval;
    }

    /**
     * Return list of hidden items of given type
     *
     * @param string $type The type of items we are looking for
     *                     ('table', 'function', 'group', etc.)
     *
     * @return mixed[] Array containing hidden items of given type
     */
    public function getHiddenItems(string $type): array
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature === null || $relationParameters->user === null) {
            return [];
        }

        $navTable = Util::backquote($relationParameters->navigationItemsHidingFeature->database)
            . '.' . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding);
        $sqlQuery = 'SELECT `item_name` FROM ' . $navTable
            . ' WHERE `username`='
            . $GLOBALS['dbi']->quoteString($relationParameters->user, Connection::TYPE_CONTROL)
            . ' AND `item_type`='
            . $GLOBALS['dbi']->quoteString($type, Connection::TYPE_CONTROL)
            . ' AND `db_name`='
            . $GLOBALS['dbi']->quoteString($this->realName, Connection::TYPE_CONTROL);
        $result = $GLOBALS['dbi']->tryQueryAsControlUser($sqlQuery);
        if ($result) {
            return $result->fetchAllColumn();
        }

        return [];
    }

    /**
     * Returns the list of tables or views inside this database
     *
     * @param string $which        tables|views
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getTablesOrViews(string $which, int $pos, string $searchClause): array
    {
        if ($which === 'tables') {
            $condition = 'IN';
        } else {
            $condition = 'NOT IN';
        }

        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT `TABLE_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`TABLES` ';
            $query .= 'WHERE `TABLE_SCHEMA`=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
            $query .= 'AND `TABLE_TYPE` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if ($searchClause !== '') {
                $query .= 'AND `TABLE_NAME` LIKE ';
                $query .= $GLOBALS['dbi']->quoteString(
                    '%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%',
                );
            }

            $query .= 'ORDER BY `TABLE_NAME` ASC ';
            $query .= 'LIMIT ' . $pos . ', ' . $maxItems;

            return $GLOBALS['dbi']->fetchResult($query);
        }

        $query = ' SHOW FULL TABLES FROM ';
        $query .= Util::backquote($this->realName);
        $query .= ' WHERE `Table_type` ' . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
        if ($searchClause !== '') {
            $query .= 'AND ' . Util::backquote('Tables_in_' . $this->realName);
            $query .= ' LIKE ' . $GLOBALS['dbi']->quoteString(
                '%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%',
            );
        }

        $retval = [];
        $handle = $GLOBALS['dbi']->tryQuery($query);
        if ($handle !== false) {
            $count = 0;
            if ($handle->seek($pos)) {
                while ($arr = $handle->fetchRow()) {
                    if ($count >= $maxItems) {
                        break;
                    }

                    $retval[] = $arr[0];
                    $count++;
                }
            }
        }

        return $retval;
    }

    /**
     * Returns the list of tables inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getTables(int $pos, string $searchClause): array
    {
        return $this->getTablesOrViews('tables', $pos, $searchClause);
    }

    /**
     * Returns the list of views inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getViews(int $pos, string $searchClause): array
    {
        return $this->getTablesOrViews('views', $pos, $searchClause);
    }

    /**
     * Returns the list of procedures or functions inside this database
     *
     * @param string $routineType  PROCEDURE|FUNCTION
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getRoutines(string $routineType, int $pos, string $searchClause): array
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT `ROUTINE_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` '
                . Util::getCollateForIS() . '=' . $GLOBALS['dbi']->quoteString($this->realName);
            $query .= "AND `ROUTINE_TYPE`='" . $routineType . "' ";
            if ($searchClause !== '') {
                $query .= 'AND `ROUTINE_NAME` LIKE ';
                $query .= $GLOBALS['dbi']->quoteString(
                    '%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%',
                );
            }

            $query .= 'ORDER BY `ROUTINE_NAME` ASC ';
            $query .= 'LIMIT ' . $pos . ', ' . $maxItems;

            return $GLOBALS['dbi']->fetchResult($query);
        }

        $query = 'SHOW ' . $routineType . ' STATUS WHERE `Db`=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
        if ($searchClause !== '') {
            $query .= 'AND `Name` LIKE ';
            $query .= $GLOBALS['dbi']->quoteString(
                '%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%',
            );
        }

        $retval = [];
        $handle = $GLOBALS['dbi']->tryQuery($query);
        if ($handle !== false) {
            $count = 0;
            if ($handle->seek($pos)) {
                while ($arr = $handle->fetchAssoc()) {
                    if ($count >= $maxItems) {
                        break;
                    }

                    $retval[] = $arr['Name'];
                    $count++;
                }
            }
        }

        return $retval;
    }

    /**
     * Returns the list of procedures inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getProcedures(int $pos, string $searchClause): array
    {
        return $this->getRoutines('PROCEDURE', $pos, $searchClause);
    }

    /**
     * Returns the list of functions inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getFunctions(int $pos, string $searchClause): array
    {
        return $this->getRoutines('FUNCTION', $pos, $searchClause);
    }

    /**
     * Returns the list of events inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    private function getEvents(int $pos, string $searchClause): array
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT `EVENT_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`EVENTS` ';
            $query .= 'WHERE `EVENT_SCHEMA` '
                . Util::getCollateForIS() . '=' . $GLOBALS['dbi']->quoteString($this->realName) . ' ';
            if ($searchClause !== '') {
                $query .= 'AND `EVENT_NAME` LIKE ';
                $query .= $GLOBALS['dbi']->quoteString(
                    '%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%',
                );
            }

            $query .= 'ORDER BY `EVENT_NAME` ASC ';
            $query .= 'LIMIT ' . $pos . ', ' . $maxItems;

            return $GLOBALS['dbi']->fetchResult($query);
        }

        $query = 'SHOW EVENTS FROM ' . Util::backquote($this->realName) . ' ';
        if ($searchClause !== '') {
            $query .= 'WHERE `Name` LIKE ';
            $query .= $GLOBALS['dbi']->quoteString(
                '%' . $GLOBALS['dbi']->escapeMysqlWildcards($searchClause) . '%',
            );
        }

        $retval = [];
        $handle = $GLOBALS['dbi']->tryQuery($query);
        if ($handle !== false) {
            $count = 0;
            if ($handle->seek($pos)) {
                while ($arr = $handle->fetchAssoc()) {
                    if ($count >= $maxItems) {
                        break;
                    }

                    $retval[] = $arr['Name'];
                    $count++;
                }
            }
        }

        return $retval;
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return string HTML for control buttons
     */
    public function getHtmlForControlButtons(): string
    {
        $ret = '';
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            if ($this->hiddenCount > 0) {
                $params = ['showUnhideDialog' => true, 'dbName' => $this->realName];
                $ret = '<span class="dbItemControls">'
                    . '<a href="' . Url::getFromRoute('/navigation') . '" data-post="'
                    . Url::getCommon($params, '', false) . '"'
                    . ' class="showUnhide ajax">'
                    . Generator::getImage(
                        'show',
                        __('Show hidden items'),
                    )
                    . '</a></span>';
            }
        }

        return $ret;
    }

    /**
     * Sets the number of hidden items in this database
     */
    public function setHiddenCount(int $count): void
    {
        $this->hiddenCount = $count >= 1 ? $count : 0;
    }

    /**
     * Returns the number of hidden items in this database
     *
     * @return int<0, max>
     */
    public function getHiddenCount(): int
    {
        return $this->hiddenCount;
    }
}
