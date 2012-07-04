<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree in the left frame
 *
 * @package PhpMyAdmin-Navigation
 */
/**
 * The Node is the building block for the collapsible navigation tree
 */
class Node {
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
    private $name;

    /**
     * @var string A non-unique identifier for the node
     *             This will never change after being assigned
     */
    private $real_name;

    /**
     * @var int May be one of CONTAINER or OBJECT
     */
    private $type;

    /**
     * @var bool Whether this object has been created while grouping nodes
     *           Only relevant if the node is of type CONTAINER
     */
    private $is_group;

    /**
     * @var bool Whether to add a "display: none;" CSS
     *           rule to the node when rendering it
     */
    private $visible = false;

    /**
     * @var Node A reference to the parent object of
     *           this node, NULL for the root node.
     */
    private $parent;

    /**
     * @var array An array of Node objects that are
     *            direct children of this node
     */
    private $children = array();

    /**
     * @var string This string is used to group nodes
     *             Only relevant if the node is of type CONTAINER
     */
    private $separator = '';

    /**
     * @var string How many time to recursively apply the grouping function
     *             Only relevant if the node is of type CONTAINER
     */
    private $separator_depth = 1;

    /**
     * @var string An IMG tag, used when rendering the node
     */
    protected $icon;

    /**
     * @var Array An array of A tags, used when rendering the node
     *            The indexes in the array may be 'icon' and 'text'
     */
    protected $links;

    /**
     * @var string classes
     */

    public $classes = '';

    /**
     * @var object A reference to the common functions object
     */
    private $_commonFunctions;

    /**
     * Initialises the class by setting the mandatory variables
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return bool Whether the initialisation was successful
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        $this->_commonFunctions = PMA_commonFunctions::getInstance();
        if (! empty($name)) {
            $this->name      = $name;
            $this->real_name = $name;
            if ($type === 0 || $type === 1) {
                $this->type = $type;
                if (is_bool($is_group)) {
                    $this->is_group = $is_group;
                    return true;
                }
            }
        }
        return false;

    }

    /**
     * Getter. Returns values of private variables
     *
     * @param string $a Variable name
     *
     * @return mixed The value of the requested variable
     */
    public function __get($a)
    {
        return $this->$a;
    }

    /**
     * Setter. Allows to change some of the private variables
     *
     * @param string $a Variable name
     * @param string $b Variable value
     *
     * @return bool Whether the operation was successful
     */
    public function __set($a, $b)
    {
        switch ($a) {
        case 'icon':
        case 'links':
        case 'parent':
        case 'real_name':
        case 'separator':
        case 'separator_depth':
        case 'visible':
            $this->$a = $b;
            return true;
        default:
            return false;
        }
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
        $child->parent = $this;
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
     * @param bool $self      Whether to include the Node itself in the results
     * @param bool $container Whether to include nodes of type CONTAINER
     * @param bool $groups    Whether to include nodes which have $group == true
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
            $self = false;
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
     * TODO: comment
     */
    function realParent()
    {
        $retval = $this->parents();
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

    public function hasSiblings()
    {
        $retval = false;
        foreach ($this->parent->children as $child) {
            if ($child != $this
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
     * TODO: comment
     */
    public function getData($pos)
    {
        $query  = "SELECT `SCHEMA_NAME` ";
        $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA` ";
        $query .= "ORDER BY `SCHEMA_NAME` ASC ";
        $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxDbList']}";
        return PMA_DBI_fetch_result($query);
    }

    /**
     * TODO: comment
     */
    public function getPresence($type)
    {
        return false;
    }
}
?>
