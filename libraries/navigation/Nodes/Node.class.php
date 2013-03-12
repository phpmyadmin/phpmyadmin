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
     * @var array An array of Node objects that are
     *            direct children of this node
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
     * @var string Extra CSS classes for the node
     */
    public $classes = '';

    /**
     * @var string Whether this node is a link for creating new objects
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
     *
     * @return Node
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
     * @return nothing
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
     * @return nothing
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
     * Retreives the parents for a node
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
            && ($this->is_group != true || $groups)
        ) {
            $parents[] = $this;
            $self      = false;
        }
        $parent = $this->parent;
        while (isset($parent)) {
            if (   ($parent->type != Node::CONTAINER || $containers)
                && ($parent->is_group != true || $groups)
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
     * @return Node
     */
    public function realParent()
    {
        $retval = $this->parents();
        if (count($retval) > 0) {
            return $retval[0];
        } else {
            return false;
        }
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
     * Returns true the node has some siblings (other nodes on the same tree level,
     * in the same branch), false otherwise. The only exception is for nodes on
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
        } else {
            foreach ($this->parent->children as $child) {
                if ($child != $this
                    && ($child->type == Node::OBJECT || $child->hasChildren(false))
                ) {
                    $retval = true;
                    break;
                }
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
        // @todo obey the DisableIS directive
        $query  = "SELECT `SCHEMA_NAME` ";
        $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA` ";
        $query .= $this->_getWhereClause($searchClause); 
        $query .= "ORDER BY `SCHEMA_NAME` ASC ";
        $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxNavigationItems']}";
        return PMA_DBI_fetch_result($query);
    }

    /**
     * Returns the comment associated with node
     * This method should be overridden by specific type of nodes
     *
     * @return string
     */
    public function getComment()
    {
        return '';
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
        if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA` ";
            $query .= $this->_getWhereClause($searchClause); 
            $retval = (int)PMA_DBI_fetch_value($query);
        } else {
            $query = "SHOW DATABASES ";
            if (! empty($searchClause)) {
                $query .= "LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%' ";
            }
            $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
        }
        return $retval;
    }

    /**
     * Returns the WHERE clause depending on the $searchClause parameter
     * and the hide_db directive  
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string 
     */
    private function _getWhereClause($searchClause = '')
    {
        $whereClause = "WHERE TRUE ";
        if (! empty($searchClause)) {
            $whereClause .= "AND `SCHEMA_NAME` LIKE '%";
            $whereClause .= PMA_Util::sqlAddSlashes(
                $searchClause, true
            );
            $whereClause .= "%' ";
        }

        if (! empty($GLOBALS['cfg']['Server']['hide_db'])) {
            $whereClause .= "AND `SCHEMA_NAME` NOT REGEXP '"
                . $GLOBALS['cfg']['Server']['hide_db'] . "' ";
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
                $subClauses[] = " `SCHEMA_NAME` LIKE '"
                    . $each_only_db . "' ";
            }
            $whereClause .= implode("OR", $subClauses) . ")";
        }
        return $whereClause;
    }

}
?>
