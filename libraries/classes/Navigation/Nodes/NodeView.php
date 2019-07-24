<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Represents a view node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeView extends NodeDatabaseChild
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
        $this->icon = Util::getImage('b_props', __('View'));
        $iconLink = Url::getFromRoute('/table/structure');
        $this->links = [
            'text' => 'sql.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s&amp;pos=0',
            'icon' => $iconLink . (strpos($iconLink, '?') === false ? '?' : '&')
                . 'server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
        ];
        $this->classes = 'view';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'view';
    }
}
