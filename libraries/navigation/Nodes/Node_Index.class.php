<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Represents a index node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Index extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return Node_Index
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon  = PMA_Util::getImage('b_index.png');
        $this->links = array(
            'text' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s&amp;index=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s&amp;index=%1$s'
                    . '&amp;token=' . $GLOBALS['token']
        );
        $this->classes = 'index';
    }
}

?>
