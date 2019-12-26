<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Util;

/**
 * Represents a event node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeEvent extends NodeDatabaseChild
{
    /**
     * Initialises the class
     *
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $isGroup = false)
    {
        parent::__construct($name, $type, $isGroup);
        $this->icon = Util::getImage('b_events');
        $this->links = [
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;item_name=%1$s&amp;edit_item=1',
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;item_name=%1$s&amp;export_item=1',
        ];
        $this->classes = 'event';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'event';
    }
}
