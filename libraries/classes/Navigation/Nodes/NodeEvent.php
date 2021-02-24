<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Url;

/**
 * Represents a event node in the navigation tree
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
        $this->icon = Generator::getImage('b_events');
        $this->links = [
            'text' => Url::getFromRoute('/database/events', [
                'server' => $GLOBALS['server'],
                'edit_item' => 1,
            ]) . '&amp;db=%2$s&amp;item_name=%1$s',
            'icon' => Url::getFromRoute('/database/events', [
                'server' => $GLOBALS['server'],
                'export_item' => 1,
            ]) . '&amp;db=%2$s&amp;item_name=%1$s',
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
