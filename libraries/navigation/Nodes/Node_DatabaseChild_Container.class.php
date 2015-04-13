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

/**
 * Represents container node that carries children of a database
 *
 * @package PhpMyAdmin-Navigation
 */
abstract class Node_DatabaseChild_Container extends Node
{
    /**
     * Initialises the class by setting the common variables
     *
     * @param string $name An identifier for the new node
     * @param int    $type Type of node, may be one of CONTAINER or OBJECT
     *
     * @return Node
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
}
?>