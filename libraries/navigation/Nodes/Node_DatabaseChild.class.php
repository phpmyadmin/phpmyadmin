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
 * Represents a node that is a child of a database node
 * This may either be a concrete child such as table or a container
 * such as table container
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

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return String HTML for control buttons
     */
    public function getHtmlForControlButtons()
    {
        $ret = '';
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            $db   = $this->realParent()->real_name;
            $item = $this->real_name;
            $ret  = '<span class="navItemControls">'
                . '<a href="navigation.php'
                . PMA_URL_getCommon()
                . '&hideNavItem=true'
                . '&itemType=' . urlencode($this->getItemType())
                . '&itemName=' . urlencode($item)
                . '&dbName=' . urlencode($db) . '"'
                . ' class="hideNavItem ajax">'
                . PMA_Util::getImage('lightbulb_off.png', __('Hide'))
                . '</a></span>';
        }
        return $ret;
    }
}
