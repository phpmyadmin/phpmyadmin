<?php
/**
 * Functionality for the navigation tree in the left frame
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\NavigationItemsHidingFeature;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function array_reverse;
use function array_slice;
use function base64_encode;
use function count;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sort;
use function sprintf;
use function str_starts_with;
use function strstr;

/**
 * The Node is the building block for the collapsible navigation tree
 */
class Node
{
    /**
     * @var string A non-unique identifier for the node
     *             This will never change after being assigned
     */
    public string $realName = '';

    /**
     * @var bool Whether to add a "display: none;" CSS
     *           rule to the node when rendering it
     */
    public bool $visible = false;
    /**
     * @var Node|null A reference to the parent object of
     *           this node, NULL for the root node.
     */
    public Node|null $parent = null;
    /**
     * @var Node[] An array of Node objects that are
     *             direct children of this node
     */
    public array $children = [];
    /**
     * @var mixed A string used to group nodes, or an array of strings
     *            Only relevant if the node is of type CONTAINER
     */
    public mixed $separator = '';
    /**
     * @var int How many time to recursively apply the grouping function
     *          Only relevant if the node is of type CONTAINER
     */
    public int $separatorDepth = 1;

    /**
     * For the IMG tag, used when rendering the node.
     *
     * @var array<string, string>
     * @psalm-var array{image: string, title: string}
     */
    public array $icon = ['image' => '', 'title' => ''];

    /**
     * An array of A tags, used when rendering the node.
     *
     * @var array<string, mixed>
     * @psalm-var array{
     *   text: array{route: string, params: array<string, mixed>},
     *   icon: array{route: string, params: array<string, mixed>},
     *   second_icon?: array{route: string, params: array<string, mixed>},
     *   title?: string
     * }
     */
    public array $links = ['text' => ['route' => '', 'params' => []], 'icon' => ['route' => '', 'params' => []]];

    /** @var string HTML title */
    public string $title = '';
    /** @var string Extra CSS classes for the node */
    public string $classes = '';
    /** @var bool Whether this node is a link for creating new objects */
    public bool $isNew = false;
    /**
     * @var int The position for the pagination of
     *          the branch at the second level of the tree
     */
    public int $pos2 = 0;
    /**
     * @var int The position for the pagination of
     *          the branch at the third level of the tree
     */
    public int $pos3 = 0;

    /** @var string $displayName  display name for the navigation tree */
    public string|null $displayName = null;

    public string|null $urlParamName = null;

    /**
     * Initialises the class by setting the mandatory variables
     *
     * @param string   $name    A non-unique identifier for the node
     *                          This may be trimmed when grouping nodes
     * @param NodeType $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool     $isGroup Whether this object has been created while grouping nodes
     *                          Only relevant if the node is of type CONTAINER
     */
    public function __construct(
        protected readonly Config $config,
        public string $name = '',
        public readonly NodeType $type = NodeType::Object,
        public bool $isGroup = false,
    ) {
        $this->realName = $name;
    }

    /**
     * Instantiates a Node object that will be used only for "New db/table/etc.." objects
     *
     * @param string $name    An identifier for the new node
     * @param string $classes Extra CSS classes for the node
     */
    public function getInstanceForNewNode(
        string $name,
        string $classes,
    ): Node {
        $node = new Node($this->config, $name);
        $node->title = $name;
        $node->isNew = true;
        $node->classes = $classes;

        return $node;
    }

    /**
     * Adds a child node to this node
     *
     * @param Node $child A child node
     */
    public function addChild(Node $child): void
    {
        $this->children[] = $child;
        $child->parent = $this;
    }

    /**
     * Returns a child node given it's name
     *
     * @param string $name     The name of requested child
     * @param bool   $realName Whether to use the "realName"
     *                         instead of "name" in comparisons
     *
     * @return Node|null The requested child node or null,
     *                   if the requested node cannot be found
     */
    public function getChild(string $name, bool $realName = false): Node|null
    {
        if ($realName) {
            foreach ($this->children as $child) {
                if ($child->realName === $name) {
                    return $child;
                }
            }
        } else {
            foreach ($this->children as $child) {
                if ($child->name === $name && ! $child->isNew) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Removes a child node from this node
     *
     * @param string $name The name of child to be removed
     */
    public function removeChild(string $name): void
    {
        foreach ($this->children as $key => $child) {
            if ($child->name === $name) {
                unset($this->children[$key]);
                break;
            }
        }
    }

    /**
     * Retrieves the parents for a node
     *
     * @param bool $self       Whether to include the Node itself in the results
     * @param bool $containers Whether to include nodes of type CONTAINER
     * @param bool $groups     Whether to include nodes which have $group == true
     *
     * @return Node[] An array of parent Nodes
     */
    public function parents(bool $self = false, bool $containers = false, bool $groups = false): array
    {
        $parents = [];
        if ($self && ($this->type !== NodeType::Container || $containers) && (! $this->isGroup || $groups)) {
            $parents[] = $this;
        }

        $parent = $this->parent;
        if ($parent === null) {
            /** @infection-ignore-all */
            return $parents;
        }

        while ($parent !== null) {
            if (($parent->type !== NodeType::Container || $containers) && (! $parent->isGroup || $groups)) {
                $parents[] = $parent;
            }

            $parent = $parent->parent;
        }

        return $parents;
    }

    /**
     * Returns the actual parent of a node. If used twice on an index or columns
     * node, it will return the table and database nodes. The names of the returned
     * nodes can be used in SQL queries, etc...
     */
    public function realParent(): Node|false
    {
        $retval = $this->parents();
        if (count($retval) <= 0) {
            return false;
        }

        return $retval[0];
    }

    /**
     * This function checks if the node has children nodes associated with it
     *
     * @param bool $countEmptyContainers Whether to count empty child
     *                                   containers as valid children
     */
    public function hasChildren(bool $countEmptyContainers = true): bool
    {
        if ($countEmptyContainers) {
            return $this->children !== [];
        }

        foreach ($this->children as $child) {
            if ($child->type === NodeType::Object || $child->hasChildren(false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the node has some siblings (other nodes on the same tree
     * level, in the same branch), false otherwise.
     * The only exception is for nodes on
     * the third level of the tree (columns and indexes), for which the function
     * always returns true. This is because we want to render the containers
     * for these nodes
     */
    public function hasSiblings(): bool
    {
        if ($this->parent === null) {
            return false;
        }

        $paths = $this->getPaths();
        if (count($paths['aPath_clean']) > 3) {
            return true;
        }

        foreach ($this->parent->children as $child) {
            if ($child !== $this && ($child->type === NodeType::Object || $child->hasChildren(false))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the number of child nodes that a node has associated with it
     *
     * @return int The number of children nodes
     */
    public function numChildren(): int
    {
        $retval = 0;
        foreach ($this->children as $child) {
            if ($child->type === NodeType::Object) {
                $retval++;
            } else {
                $retval += $child->numChildren();
            }
        }

        return $retval;
    }

    /**
     * Returns the actual path and the virtual paths for a node
     * both as clean arrays and base64 encoded strings
     *
     * @return array{aPath: string, aPath_clean: string[], vPath: string, vPath_clean: string[]}
     */
    public function getPaths(): array
    {
        $aPath = [];
        $aPathClean = [];
        foreach ($this->parents(true, true) as $parent) {
            $aPath[] = base64_encode($parent->realName);
            $aPathClean[] = $parent->realName;
        }

        $aPath = implode('.', array_reverse($aPath));
        $aPathClean = array_reverse($aPathClean);

        $vPath = [];
        $vPathClean = [];
        foreach ($this->parents(true, true, true) as $parent) {
            $vPath[] = base64_encode($parent->name);
            $vPathClean[] = $parent->name;
        }

        $vPath = implode('.', array_reverse($vPath));
        $vPathClean = array_reverse($vPathClean);

        return ['aPath' => $aPath, 'aPath_clean' => $aPathClean, 'vPath' => $vPath, 'vPath_clean' => $vPathClean];
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
    public function getData(
        UserPrivileges $userPrivileges,
        RelationParameters $relationParameters,
        string $type,
        int $pos,
        string $searchClause = '',
    ): array {
        if (isset($this->config->selectedServer['DisableIS']) && ! $this->config->selectedServer['DisableIS']) {
            return $this->getDataFromInfoSchema($pos, $searchClause);
        }

        if ($userPrivileges->databasesToTest === false) {
            return $this->getDataFromShowDatabases($pos, $searchClause);
        }

        return $this->getDataFromShowDatabasesLike($userPrivileges, $pos, $searchClause);
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of the query
     */
    public function getPresence(UserPrivileges $userPrivileges, string $type = '', string $searchClause = ''): int
    {
        $dbi = DatabaseInterface::getInstance();
        if (
            ! $this->config->settings['NavigationTreeEnableGrouping']
            || ! $this->config->settings['ShowDatabasesNavigationAsTree']
        ) {
            if (isset($this->config->selectedServer['DisableIS']) && ! $this->config->selectedServer['DisableIS']) {
                $query = 'SELECT COUNT(*) ';
                $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
                $query .= $this->getWhereClause('SCHEMA_NAME', $searchClause);

                return (int) $dbi->fetchValue($query);
            }

            if ($userPrivileges->databasesToTest === false) {
                $query = 'SHOW DATABASES ';
                $query .= $this->getWhereClause('Database', $searchClause);

                return (int) $dbi->queryAndGetNumRows($query);
            }

            $retval = 0;
            foreach ($this->getDatabasesToSearch($userPrivileges, $searchClause) as $db) {
                $query = 'SHOW DATABASES LIKE ' . $dbi->quoteString($db);
                $retval += (int) $dbi->queryAndGetNumRows($query);
            }

            return $retval;
        }

        $dbSeparator = $this->config->settings['NavigationTreeDbSeparator'];
        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT COUNT(*) ';
            $query .= 'FROM ( ';
            $query .= 'SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, ';
            $query .= "'" . $dbSeparator . "', 1) ";
            $query .= 'DB_first_level ';
            $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
            $query .= $this->getWhereClause('SCHEMA_NAME', $searchClause);
            $query .= ') t ';

            return (int) $dbi->fetchValue($query);
        }

        if ($userPrivileges->databasesToTest !== false) {
            $prefixMap = [];
            foreach ($this->getDatabasesToSearch($userPrivileges, $searchClause) as $db) {
                $query = 'SHOW DATABASES LIKE ' . $dbi->quoteString($db);
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    continue;
                }

                while ($arr = $handle->fetchRow()) {
                    if ($this->isHideDb($arr[0])) {
                        continue;
                    }

                    $prefix = strstr($arr[0], $dbSeparator, true);
                    if ($prefix === false) {
                        $prefix = $arr[0];
                    }

                    $prefixMap[$prefix] = 1;
                }
            }

            return count($prefixMap);
        }

        $prefixMap = [];
        $query = 'SHOW DATABASES ';
        $query .= $this->getWhereClause('Database', $searchClause);
        $handle = $dbi->tryQuery($query);
        if ($handle !== false) {
            while ($arr = $handle->fetchRow()) {
                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }

                $prefixMap[$prefix] = 1;
            }
        }

        return count($prefixMap);
    }

    /**
     * Detemines whether a given database should be hidden according to 'hide_db'
     *
     * @param string $db database name
     */
    private function isHideDb(string $db): bool
    {
        return ! empty($this->config->selectedServer['hide_db'])
            && preg_match('/' . $this->config->selectedServer['hide_db'] . '/', $db);
    }

    /**
     * Get the list of databases for 'SHOW DATABASES LIKE' queries.
     * If a search clause is set it gets the highest priority while only_db gets
     * the next priority. In case both are empty list of databases determined by
     * GRANTs are used
     *
     * @param string $searchClause search clause
     *
     * @return mixed[] array of databases
     */
    private function getDatabasesToSearch(UserPrivileges $userPrivileges, string $searchClause): array
    {
        $databases = [];
        if ($searchClause !== '') {
            $databases = ['%' . DatabaseInterface::getInstance()->escapeMysqlWildcards($searchClause) . '%'];
        } elseif (! empty($this->config->selectedServer['only_db'])) {
            $databases = $this->config->selectedServer['only_db'];
        } elseif ($userPrivileges->databasesToTest !== false && $userPrivileges->databasesToTest !== []) {
            $databases = $userPrivileges->databasesToTest;
        }

        sort($databases);

        return $databases;
    }

    /**
     * Returns the WHERE clause depending on the $searchClause parameter
     * and the hide_db directive
     *
     * @param string $columnName   Column name of the column having database names
     * @param string $searchClause A string used to filter the results of the query
     */
    private function getWhereClause(string $columnName, string $searchClause = ''): string
    {
        $whereClause = 'WHERE TRUE ';
        $dbi = DatabaseInterface::getInstance();
        if ($searchClause !== '') {
            $whereClause .= 'AND ' . Util::backquote($columnName)
                . ' LIKE ' . $dbi->quoteString('%' . $dbi->escapeMysqlWildcards($searchClause) . '%') . ' ';
        }

        if (! empty($this->config->selectedServer['hide_db'])) {
            $whereClause .= 'AND ' . Util::backquote($columnName)
                . ' NOT REGEXP ' . $dbi->quoteString($this->config->selectedServer['hide_db']) . ' ';
        }

        if (! empty($this->config->selectedServer['only_db'])) {
            if (is_string($this->config->selectedServer['only_db'])) {
                $this->config->selectedServer['only_db'] = [$this->config->selectedServer['only_db']];
            }

            $whereClause .= 'AND (';
            $subClauses = [];
            foreach ($this->config->selectedServer['only_db'] as $eachOnlyDb) {
                $subClauses[] = ' ' . Util::backquote($columnName)
                    . ' LIKE ' . $dbi->quoteString($eachOnlyDb) . ' ';
            }

            $whereClause .= implode('OR', $subClauses) . ') ';
        }

        return $whereClause;
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return string HTML for control buttons
     */
    public function getHtmlForControlButtons(NavigationItemsHidingFeature|null $navigationItemsHidingFeature): string
    {
        return '';
    }

    /**
     * Returns CSS classes for a node
     *
     * @param bool $match Whether the node matched loaded tree
     *
     * @return string with html classes.
     */
    public function getCssClasses(bool $match): string
    {
        if (! $this->config->settings['NavigationTreeEnableExpansion']) {
            return '';
        }

        $result = ['expander'];

        if ($this->isGroup || $match) {
            $result[] = 'loaded';
        }

        if ($this->type === NodeType::Container) {
            $result[] = 'container';
        }

        return implode(' ', $result);
    }

    /**
     * Returns icon for the node
     *
     * @param bool $match Whether the node matched loaded tree
     *
     * @return string with image name
     */
    public function getIcon(bool $match): string
    {
        if (! $this->config->settings['NavigationTreeEnableExpansion']) {
            return '';
        }

        if ($match) {
            $this->visible = true;

            return Generator::getImage('b_minus');
        }

        return Generator::getImage('b_plus', __('Expand/Collapse'));
    }

    /**
     * Gets the count of hidden elements for each database
     *
     * @return mixed[]|null array containing the count of hidden elements for each database
     */
    public function getNavigationHidingData(NavigationItemsHidingFeature|null $navigationItemsHidingFeature): array|null
    {
        if ($navigationItemsHidingFeature !== null) {
            $navTable = Util::backquote($navigationItemsHidingFeature->database)
                . '.' . Util::backquote($navigationItemsHidingFeature->navigationHiding);
            $dbi = DatabaseInterface::getInstance();
            $sqlQuery = 'SELECT `db_name`, COUNT(*) AS `count` FROM ' . $navTable
                . ' WHERE `username`='
                . $dbi->quoteString($this->config->selectedServer['user'])
                . ' GROUP BY `db_name`';

            return $dbi->fetchResult($sqlQuery, 'db_name', 'count', ConnectionType::ControlUser);
        }

        return null;
    }

    /**
     * @param int    $pos          The offset of the list within the results.
     * @param string $searchClause A string used to filter the results of the query.
     *
     * @return mixed[]
     */
    private function getDataFromInfoSchema(int $pos, string $searchClause): array
    {
        $maxItems = $this->config->settings['FirstLevelNavigationItems'];
        $dbi = DatabaseInterface::getInstance();
        if (
            ! $this->config->settings['NavigationTreeEnableGrouping']
            || ! $this->config->settings['ShowDatabasesNavigationAsTree']
        ) {
            $query = sprintf(
                'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA` %sORDER BY `SCHEMA_NAME` LIMIT %d, %d',
                $this->getWhereClause('SCHEMA_NAME', $searchClause),
                $pos,
                $maxItems,
            );

            return $dbi->fetchResult($query);
        }

        $dbSeparator = $this->config->settings['NavigationTreeDbSeparator'];
        $query = sprintf(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, (SELECT DB_first_level'
                . ' FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, %1$s, 1) DB_first_level'
                . ' FROM INFORMATION_SCHEMA.SCHEMATA %2$s) t'
                . ' ORDER BY DB_first_level ASC LIMIT %3$d, %4$d) t2'
                . ' %2$sAND 1 = LOCATE(CONCAT(DB_first_level, %1$s),'
                . ' CONCAT(SCHEMA_NAME, %1$s)) ORDER BY SCHEMA_NAME ASC',
            $dbi->quoteString($dbSeparator),
            $this->getWhereClause('SCHEMA_NAME', $searchClause),
            $pos,
            $maxItems,
        );

        return $dbi->fetchResult($query);
    }

    /**
     * @param int    $pos          The offset of the list within the results.
     * @param string $searchClause A string used to filter the results of the query.
     *
     * @return mixed[]
     */
    private function getDataFromShowDatabases(int $pos, string $searchClause): array
    {
        $maxItems = $this->config->settings['FirstLevelNavigationItems'];
        $dbi = DatabaseInterface::getInstance();
        if (
            ! $this->config->settings['NavigationTreeEnableGrouping']
            || ! $this->config->settings['ShowDatabasesNavigationAsTree']
        ) {
            $handle = $dbi->tryQuery(sprintf(
                'SHOW DATABASES %s',
                $this->getWhereClause('Database', $searchClause),
            ));
            if ($handle === false) {
                return [];
            }

            $count = 0;
            if (! $handle->seek($pos)) {
                return [];
            }

            $retval = [];
            while ($arr = $handle->fetchRow()) {
                if ($count >= $maxItems) {
                    break;
                }

                $retval[] = $arr[0];
                $count++;
            }

            return $retval;
        }

        $dbSeparator = $this->config->settings['NavigationTreeDbSeparator'];
        $handle = $dbi->tryQuery(sprintf(
            'SHOW DATABASES %s',
            $this->getWhereClause('Database', $searchClause),
        ));
        $prefixes = [];
        if ($handle !== false) {
            $prefixMap = [];
            $total = $pos + $maxItems;
            while ($arr = $handle->fetchRow()) {
                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }

                $prefixMap[$prefix] = 1;
                if (count($prefixMap) == $total) {
                    break;
                }
            }

            $prefixes = array_slice(array_keys($prefixMap), $pos);
        }

        $subClauses = [];
        foreach ($prefixes as $prefix) {
            $subClauses[] = sprintf(
                ' LOCATE(%s, CONCAT(`Database`, %s)) = 1 ',
                $dbi->quoteString($prefix . $dbSeparator),
                $dbi->quoteString($dbSeparator),
            );
        }

        $query = sprintf(
            'SHOW DATABASES %sAND (%s)',
            $this->getWhereClause('Database', $searchClause),
            implode('OR', $subClauses),
        );

        return $dbi->fetchResult($query);
    }

    /**
     * @param int    $pos          The offset of the list within the results.
     * @param string $searchClause A string used to filter the results of the query.
     *
     * @return mixed[]
     */
    private function getDataFromShowDatabasesLike(UserPrivileges $userPrivileges, int $pos, string $searchClause): array
    {
        $maxItems = $this->config->settings['FirstLevelNavigationItems'];
        $dbi = DatabaseInterface::getInstance();
        if (
            ! $this->config->settings['NavigationTreeEnableGrouping']
            || ! $this->config->settings['ShowDatabasesNavigationAsTree']
        ) {
            $retval = [];
            $count = 0;
            foreach ($this->getDatabasesToSearch($userPrivileges, $searchClause) as $db) {
                $handle = $dbi->tryQuery(sprintf('SHOW DATABASES LIKE %s', $dbi->quoteString($db)));
                if ($handle === false) {
                    continue;
                }

                while ($arr = $handle->fetchRow()) {
                    if ($this->isHideDb($arr[0])) {
                        continue;
                    }

                    if (in_array($arr[0], $retval, true)) {
                        continue;
                    }

                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr[0];
                        $count++;
                    }

                    $pos--;
                }
            }

            sort($retval);

            return $retval;
        }

        $dbSeparator = $this->config->settings['NavigationTreeDbSeparator'];
        $retval = [];
        $prefixMap = [];
        $total = $pos + $maxItems;
        foreach ($this->getDatabasesToSearch($userPrivileges, $searchClause) as $db) {
            $handle = $dbi->tryQuery(sprintf('SHOW DATABASES LIKE %s', $dbi->quoteString($db)));
            if ($handle === false) {
                continue;
            }

            while ($arr = $handle->fetchRow()) {
                if ($this->isHideDb($arr[0])) {
                    continue;
                }

                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }

                $prefixMap[$prefix] = 1;
                if (count($prefixMap) == $total) {
                    break 2;
                }
            }
        }

        $prefixes = array_slice(array_keys($prefixMap), $pos);

        foreach ($this->getDatabasesToSearch($userPrivileges, $searchClause) as $db) {
            $handle = $dbi->tryQuery(sprintf('SHOW DATABASES LIKE %s', $dbi->quoteString($db)));
            if ($handle === false) {
                continue;
            }

            while ($arr = $handle->fetchRow()) {
                if ($this->isHideDb($arr[0])) {
                    continue;
                }

                if (in_array($arr[0], $retval, true)) {
                    continue;
                }

                foreach ($prefixes as $prefix) {
                    $startsWith = str_starts_with($arr[0] . $dbSeparator, $prefix . $dbSeparator);
                    if ($startsWith) {
                        $retval[] = $arr[0];
                        break;
                    }
                }
            }
        }

        sort($retval);

        return $retval;
    }
}
