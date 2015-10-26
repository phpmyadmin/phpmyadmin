<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree in the left frame
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The Node is the building block for the collapsible navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node
{
    /**
     * @var int Defines a possible node type
     */
    const CONTAINER = 0;

    /**
     * @var int Defines a possible node type
     */
    const OBJECT = 1;

    /**
     * @var string A non-unique identifier for the node
     *             This may be trimmed when grouping nodes
     */
    public $name = "";

    /**
     * @var string A non-unique identifier for the node
     *             This will never change after being assigned
     */
    public $real_name = "";

    /**
     * @var int May be one of CONTAINER or OBJECT
     */
    public $type = Node::OBJECT;

    /**
     * @var bool Whether this object has been created while grouping nodes
     *           Only relevant if the node is of type CONTAINER
     */
    public $is_group;

    /**
     * @var bool Whether to add a "display: none;" CSS
     *           rule to the node when rendering it
     */
    public $visible = false;

    /**
     * @var Node A reference to the parent object of
     *           this node, NULL for the root node.
     */
    public $parent;

    /**
     * @var Node[] An array of Node objects that are
     *             direct children of this node
     */
    public $children = array();

    /**
     * @var Mixed A string used to group nodes, or an array of strings
     *            Only relevant if the node is of type CONTAINER
     */
    public $separator = '';

    /**
     * @var int How many time to recursively apply the grouping function
     *          Only relevant if the node is of type CONTAINER
     */
    public $separator_depth = 1;

    /**
     * @var string An IMG tag, used when rendering the node
     */
    public $icon;

    /**
     * @var Array An array of A tags, used when rendering the node
     *            The indexes in the array may be 'icon' and 'text'
     */
    public $links;

    /**
     * @var string HTML title
     */
    public $title;

    /**
     * @var string Extra CSS classes for the node
     */
    public $classes = '';

    /**
     * @var bool Whether this node is a link for creating new objects
     */
    public $isNew = false;

    /**
     * @var int The position for the pagination of
     *          the branch at the second level of the tree
     */
    public $pos2 = 0;

    /**
     * @var int The position for the pagination of
     *          the branch at the third level of the tree
     */
    public $pos3 = 0;

    /**
     * Initialises the class by setting the mandatory variables
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        if (! empty($name)) {
            $this->name      = $name;
            $this->real_name = $name;
        }
        if ($type === Node::CONTAINER) {
            $this->type = Node::CONTAINER;
        }
        $this->is_group = (bool)$is_group;
    }

    /**
     * Adds a child node to this node
     *
     * @param Node $child A child node
     *
     * @return void
     */
    public function addChild($child)
    {
        $this->children[] = $child;
        $child->parent    = $this;
    }

    /**
     * Returns a child node given it's name
     *
     * @param string $name      The name of requested child
     * @param bool   $real_name Whether to use the "real_name"
     *                          instead of "name" in comparisons
     *
     * @return false|Node The requested child node or false,
     *                    if the requested node cannot be found
     */
    public function getChild($name, $real_name = false)
    {
        if ($real_name) {
            foreach ($this->children as $child) {
                if ($child->real_name == $name) {
                    return $child;
                }
            }
        } else {
            foreach ($this->children as $child) {
                if ($child->name == $name) {
                    return $child;
                }
            }
        }
        return false;
    }

    /**
     * Removes a child node from this node
     *
     * @param string $name The name of child to be removed
     *
     * @return void
     */
    public function removeChild($name)
    {
        foreach ($this->children as $key => $child) {
            if ($child->name == $name) {
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
     * @return array An array of parent Nodes
     */
    public function parents($self = false, $containers = false, $groups = false)
    {
        $parents = array();
        if ($self
            && ($this->type != Node::CONTAINER || $containers)
            && (!$this->is_group || $groups)
        ) {
            $parents[] = $this;
        }
        $parent = $this->parent;
        while (isset($parent)) {
            if (($parent->type != Node::CONTAINER || $containers)
                && (!$parent->is_group || $groups)
            ) {
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
     *
     * @return Node|false
     */
    public function realParent()
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
     * @param bool $count_empty_containers Whether to count empty child
     *                                     containers as valid children
     *
     * @return bool Whether the node has child nodes
     */
    public function hasChildren($count_empty_containers = true)
    {
        $retval = false;
        if ($count_empty_containers) {
            if (count($this->children)) {
                $retval = true;
            }
        } else {
            foreach ($this->children as $child) {
                if ($child->type == Node::OBJECT || $child->hasChildren(false)) {
                    $retval = true;
                    break;
                }
            }
        }
        return $retval;
    }

    /**
     * Returns true if the node has some siblings (other nodes on the same tree
     * level, in the same branch), false otherwise.
     * The only exception is for nodes on
     * the third level of the tree (columns and indexes), for which the function
     * always returns true. This is because we want to render the containers
     * for these nodes
     *
     * @return bool
     */
    public function hasSiblings()
    {
        $retval = false;
        $paths  = $this->getPaths();
        if (count($paths['aPath_clean']) > 3) {
            $retval = true;
            return $retval;
        }

        foreach ($this->parent->children as $child) {
            if ($child !== $this
                && ($child->type == Node::OBJECT || $child->hasChildren(false))
            ) {
                $retval = true;
                break;
            }
        }
        return $retval;
    }

    /**
     * Returns the number of child nodes that a node has associated with it
     *
     * @return int The number of children nodes
     */
    public function numChildren()
    {
        $retval = 0;
        foreach ($this->children as $child) {
            if ($child->type == Node::OBJECT) {
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
     * @return array
     */
    public function getPaths()
    {
        $aPath       = array();
        $aPath_clean = array();
        foreach ($this->parents(true, true, false) as $parent) {
            $aPath[]       = base64_encode($parent->real_name);
            $aPath_clean[] = $parent->real_name;
        }
        $aPath       = implode('.', array_reverse($aPath));
        $aPath_clean = array_reverse($aPath_clean);

        $vPath       = array();
        $vPath_clean = array();
        foreach ($this->parents(true, true, true) as $parent) {
            $vPath[]       = base64_encode($parent->name);
            $vPath_clean[] = $parent->name;
        }
        $vPath       = implode('.', array_reverse($vPath));
        $vPath_clean = array_reverse($vPath_clean);

        return array(
            'aPath' => $aPath,
            'aPath_clean' => $aPath_clean,
            'vPath' => $vPath,
            'vPath_clean' => $vPath_clean
        );
    }

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the Node_Database and Node_Table classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    public function getData($type, $pos, $searchClause = '')
    {
        $maxItems = $GLOBALS['cfg']['FirstLevelNavigationItems'];
        if (!$GLOBALS['cfg']['NavigationTreeEnableGrouping']
            || !$GLOBALS['cfg']['ShowDatabasesNavigationAsTree']
        ) {
            if (isset($GLOBALS['cfg']['Server']['DisableIS'])
                && ! $GLOBALS['cfg']['Server']['DisableIS']
            ) {
                $query  = "SELECT `SCHEMA_NAME` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA` ";
                $query .= $this->_getWhereClause('SCHEMA_NAME', $searchClause);
                $query .= "ORDER BY `SCHEMA_NAME` ";
                $query .= "LIMIT $pos, $maxItems";
                $retval = $GLOBALS['dbi']->fetchResult($query);
                return $retval;
            }

            if ($GLOBALS['dbs_to_test'] === false) {
                $retval = array();
                $query = "SHOW DATABASES ";
                $query .= $this->_getWhereClause('Database', $searchClause);
                $handle = $GLOBALS['dbi']->tryQuery($query);
                if ($handle === false) {
                    return $retval;
                }

                $count = 0;
                if (!$GLOBALS['dbi']->dataSeek($handle, $pos)) {
                    return $retval;
                }

                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($count < $maxItems) {
                        $retval[] = $arr[0];
                        $count++;
                    } else {
                        break;
                    }
                }

                return $retval;
            }

            $retval = array();
            $count = 0;
            foreach ($this->_getDatabasesToSearch($searchClause) as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $handle = $GLOBALS['dbi']->tryQuery($query);
                if ($handle === false) {
                    continue;
                }

                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($this->_isHideDb($arr[0])) {
                        continue;
                    }
                    if (in_array($arr[0], $retval)) {
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

        $dbSeparator = $GLOBALS['cfg']['NavigationTreeDbSeparator'];
        if (isset($GLOBALS['cfg']['Server']['DisableIS'])
            && ! $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $query  = "SELECT `SCHEMA_NAME` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA`, ";
            $query .= "(";
            $query .= "SELECT DB_first_level ";
            $query .= "FROM ( ";
            $query .= "SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, ";
            $query .= "'$dbSeparator', 1) ";
            $query .= "DB_first_level ";
            $query .= "FROM INFORMATION_SCHEMA.SCHEMATA ";
            $query .= $this->_getWhereClause('SCHEMA_NAME', $searchClause);
            $query .= ") t ";
            $query .= "ORDER BY DB_first_level ASC ";
            $query .= "LIMIT $pos, $maxItems";
            $query .= ") t2 ";
            $query .= $this->_getWhereClause('SCHEMA_NAME', $searchClause);
            $query .= "AND 1 = LOCATE(CONCAT(DB_first_level, ";
            $query .= "'$dbSeparator'), ";
            $query .= "CONCAT(SCHEMA_NAME, ";
            $query .= "'$dbSeparator')) ";
            $query .= "ORDER BY SCHEMA_NAME ASC";
            $retval = $GLOBALS['dbi']->fetchResult($query);
            return $retval;
        }

        if ($GLOBALS['dbs_to_test'] === false) {
            $query = "SHOW DATABASES ";
            $query .= $this->_getWhereClause('Database', $searchClause);
            $handle = $GLOBALS['dbi']->tryQuery($query);
            $prefixes = array();
            if ($handle !== false) {
                $prefixMap = array();
                $total = $pos + $maxItems;
                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    $prefix = strstr($arr[0], $dbSeparator, true);
                    if ($prefix === false) {
                        $prefix = $arr[0];
                    }
                    $prefixMap[$prefix] = 1;
                    if (sizeof($prefixMap) == $total) {
                        break;
                    }
                }
                $prefixes = array_slice(array_keys($prefixMap), $pos);
            }

            $query = "SHOW DATABASES ";
            $query .= $this->_getWhereClause('Database', $searchClause);
            $query .= " AND (";
            $subClauses = array();
            foreach ($prefixes as $prefix) {
                $subClauses[] = " LOCATE('"
                    . PMA_Util::sqlAddSlashes($prefix) . $dbSeparator . "', "
                    . "CONCAT(`Database`, '" . $dbSeparator . "')) = 1 ";
            }
            $query .= implode("OR", $subClauses) . ")";
            $retval = $GLOBALS['dbi']->fetchResult($query);
            return $retval;
        }

        $retval = array();
        $prefixMap = array();
        $total = $pos + $maxItems;
        foreach ($this->_getDatabasesToSearch($searchClause) as $db) {
            $query = "SHOW DATABASES LIKE '" . $db . "'";
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle === false) {
                continue;
            }

            while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                if ($this->_isHideDb($arr[0])) {
                    continue;
                }
                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }
                $prefixMap[$prefix] = 1;
                if (sizeof($prefixMap) == $total) {
                    break 2;
                }
            }
        }
        $prefixes = array_slice(array_keys($prefixMap), $pos);

        foreach ($this->_getDatabasesToSearch($searchClause) as $db) {
            $query = "SHOW DATABASES LIKE '" . $db . "'";
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle === false) {
                continue;
            }

            while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                if ($this->_isHideDb($arr[0])) {
                    continue;
                }
                if (in_array($arr[0], $retval)) {
                    continue;
                }

                foreach ($prefixes as $prefix) {
                    $starts_with = strpos(
                        $arr[0] . $dbSeparator,
                        $prefix . $dbSeparator
                    ) === 0;
                    if ($starts_with) {
                        $retval[] = $arr[0];
                        break;
                    }
                }
            }
        }
        sort($retval);

        return $retval;
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the Node_Database and Node_Table classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '')
    {
        if (!$GLOBALS['cfg']['NavigationTreeEnableGrouping']
            || !$GLOBALS['cfg']['ShowDatabasesNavigationAsTree']
        ) {
            if (isset($GLOBALS['cfg']['Server']['DisableIS'])
                && ! $GLOBALS['cfg']['Server']['DisableIS']
            ) {
                $query = "SELECT COUNT(*) ";
                $query .= "FROM INFORMATION_SCHEMA.SCHEMATA ";
                $query .= $this->_getWhereClause('SCHEMA_NAME', $searchClause);
                $retval = (int)$GLOBALS['dbi']->fetchValue($query);
                return $retval;
            }

            if ($GLOBALS['dbs_to_test'] === false) {
                $query = "SHOW DATABASES ";
                $query .= $this->_getWhereClause('Database', $searchClause);
                $retval = $GLOBALS['dbi']->numRows(
                    $GLOBALS['dbi']->tryQuery($query)
                );
                return $retval;
            }

            $retval = 0;
            foreach ($this->_getDatabasesToSearch($searchClause) as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $retval += $GLOBALS['dbi']->numRows(
                    $GLOBALS['dbi']->tryQuery($query)
                );
            }
            return $retval;
        }

        $dbSeparator = $GLOBALS['cfg']['NavigationTreeDbSeparator'];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = "SELECT COUNT(*) ";
            $query .= "FROM ( ";
            $query .= "SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, ";
            $query .= "'$dbSeparator', 1) ";
            $query .= "DB_first_level ";
            $query .= "FROM INFORMATION_SCHEMA.SCHEMATA ";
            $query .= $this->_getWhereClause('SCHEMA_NAME', $searchClause);
            $query .= ") t ";
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            return $retval;
        }

        if ($GLOBALS['dbs_to_test'] !== false) {
            $prefixMap = array();
            foreach ($this->_getDatabasesToSearch($searchClause) as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $handle = $GLOBALS['dbi']->tryQuery($query);
                if ($handle === false) {
                    continue;
                }

                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($this->_isHideDb($arr[0])) {
                        continue;
                    }
                    $prefix = strstr($arr[0], $dbSeparator, true);
                    if ($prefix === false) {
                        $prefix = $arr[0];
                    }
                    $prefixMap[$prefix] = 1;
                }
            }
            $retval = count($prefixMap);
            return $retval;
        }

        $prefixMap = array();
        $query = "SHOW DATABASES ";
        $query .= $this->_getWhereClause('Database', $searchClause);
        $handle = $GLOBALS['dbi']->tryQuery($query);
        if ($handle !== false) {
            while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                $prefix = strstr($arr[0], $dbSeparator, true);
                if ($prefix === false) {
                    $prefix = $arr[0];
                }
                $prefixMap[$prefix] = 1;
            }
        }
        $retval = count($prefixMap);

        return $retval;
    }

    /**
     * Detemines whether a given database should be hidden according to 'hide_db'
     *
     * @param string $db database name
     *
     * @return boolean whether to hide
     */
    private function _isHideDb($db)
    {
        if (! empty($GLOBALS['cfg']['Server']['hide_db'])
            && preg_match('/' . $GLOBALS['cfg']['Server']['hide_db'] . '/', $db)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the list of databases for 'SHOW DATABASES LIKE' queries.
     * If a search clause is set it gets the highest priority while only_db gets
     * the next priority. In case both are empty list of databases determined by
     * GRANTs are used
     *
     * @param string $searchClause search clause
     *
     * @return array array of databases
     */
    private function _getDatabasesToSearch($searchClause)
    {
        if (! empty($searchClause)) {
            $databases = array(
                "%" . PMA_Util::sqlAddSlashes($searchClause, true) . "%"
            );
        } elseif (! empty($GLOBALS['cfg']['Server']['only_db'])) {
            $databases = $GLOBALS['cfg']['Server']['only_db'];
        } elseif (! empty($GLOBALS['dbs_to_test'])) {
            $databases = $GLOBALS['dbs_to_test'];
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
     *
     * @return string
     */
    private function _getWhereClause($columnName, $searchClause = '')
    {
        $whereClause = "WHERE TRUE ";
        if (! empty($searchClause)) {
            $whereClause .= "AND " . PMA_Util::backquote($columnName) . " LIKE '%";
            $whereClause .= PMA_Util::sqlAddSlashes(
                $searchClause, true
            );
            $whereClause .= "%' ";
        }

        if (! empty($GLOBALS['cfg']['Server']['hide_db'])) {
            $whereClause .= "AND " . PMA_Util::backquote($columnName)
                . " NOT REGEXP '" . $GLOBALS['cfg']['Server']['hide_db'] . "' ";
        }

        if (! empty($GLOBALS['cfg']['Server']['only_db'])) {
            if (is_string($GLOBALS['cfg']['Server']['only_db'])) {
                $GLOBALS['cfg']['Server']['only_db'] = array(
                    $GLOBALS['cfg']['Server']['only_db']
                );
            }
            $whereClause .= "AND (";
            $subClauses = array();
            foreach ($GLOBALS['cfg']['Server']['only_db'] as $each_only_db) {
                $subClauses[] = " " . PMA_Util::backquote($columnName) . " LIKE '"
                    . $each_only_db . "' ";
            }
            $whereClause .= implode("OR", $subClauses) . ")";
        }
        return $whereClause;
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return String HTML for control buttons
     */
    public function getHtmlForControlButtons()
    {
        return '';
    }

    /**
     * Returns CSS classes for a node
     *
     * @param boolean $match Whether the node matched loaded tree
     *
     * @return String with html classes.
     */
    public function getCssClasses($match)
    {
        if (! $GLOBALS['cfg']['NavigationTreeEnableExpansion']
        ) {
            return '';
        }

        $result = array('expander');

        if ($this->is_group || $match) {
            $result[] = 'loaded';
        }
        if ($this->type == Node::CONTAINER) {
            $result[] = 'container';
        }

        return implode(' ', $result);
    }

    /**
     * Returns icon for the node
     *
     * @param boolean $match Whether the node matched loaded tree
     *
     * @return String with image name
     */
    public function getIcon($match)
    {
        if (! $GLOBALS['cfg']['NavigationTreeEnableExpansion']
        ) {
            return '';
        } elseif ($match && ! $this->is_group) {
            $this->visible = true;
            return PMA_Util::getImage('b_minus.png');
        } else {
            return PMA_Util::getImage('b_plus.png', __('Expand/Collapse'));
        }
    }

    /**
     * Gets the count of hidden elements for each database
     *
     * @return array array containing the count of hidden elements for each database
     */
    public function getNavigationHidingData()
    {
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            $navTable = PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['navigationhiding']);
            $sqlQuery = "SELECT `db_name`, COUNT(*) AS `count` FROM " . $navTable
            . " WHERE `username`='"
                . PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "'"
                    . " GROUP BY `db_name`";
            $counts = $GLOBALS['dbi']->fetchResult(
                $sqlQuery, 'db_name', 'count', $GLOBALS['controllink']
            );
            return $counts;
        }
        return null;
    }
}
