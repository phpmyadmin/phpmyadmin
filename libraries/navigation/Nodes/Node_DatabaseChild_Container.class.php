<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Represents container node that carries children of a database
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/navigation/Nodes/Node_DatabaseChild.class.php';

/**
 * Represents container node that carries children of a database
 *
 * @package PhpMyAdmin-Navigation
 */
abstract class Node_DatabaseChild_Container extends Node_DatabaseChild
{
    /**
     * Initialises the class by setting the common variables
     *
     * @param string $name An identifier for the new node
     * @param int    $type Type of node, may be one of CONTAINER or OBJECT
     */
    public function __construct($name, $type = Node::OBJECT)
    {
        parent::__construct($name, $type);
        if ($GLOBALS['cfg']['NavigationTreeEnableGrouping']) {
            $this->separator = $GLOBALS['cfg']['NavigationTreeTableSeparator'];
            $this->separator_depth = (int)(
                $GLOBALS['cfg']['NavigationTreeTableLevel']
            );
        }
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'group';
    }
}
