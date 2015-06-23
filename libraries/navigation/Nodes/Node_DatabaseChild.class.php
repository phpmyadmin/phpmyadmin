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
 * Represents a node that is a concrete child of a database node
 *
 * @package PhpMyAdmin-Navigation
 */
abstract class Node_DatabaseChild extends Node
{
    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected abstract function getItemType();
}
?>
