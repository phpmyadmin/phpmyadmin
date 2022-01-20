<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Relation;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Represents a node that is a child of a database node
 * This may either be a concrete child such as table or a container
 * such as table container
 *
 * @package PhpMyAdmin-Navigation
 */
abstract class NodeDatabaseChild extends Node
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
        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['navwork']) {
            $db = $this->realParent()->real_name;
            $item = $this->real_name;

            $params = array(
                'hideNavItem' => true,
                'itemType' => $this->getItemType(),
                'itemName' => $item,
                'dbName' => $db
            );

            $ret = '<span class="navItemControls">'
                . '<a href="navigation.php" data-post="'
                . Url::getCommon($params, '', false) . '"'
                . ' class="hideNavItem ajax">'
                . Util::getImage('hide', __('Hide'))
                . '</a></span>';
        }

        return $ret;
    }
}
