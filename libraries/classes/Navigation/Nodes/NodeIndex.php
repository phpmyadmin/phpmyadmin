<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Url;

/**
 * Represents a index node in the navigation tree
 */
class NodeIndex extends Node
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
        $this->icon = Generator::getImage('b_index', __('Index'));
        $this->links = [
            'text' => Url::getFromRoute('/table/indexes', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%3$s&amp;table=%2$s&amp;index=%1$s',
            'icon' => Url::getFromRoute('/table/indexes', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%3$s&amp;table=%2$s&amp;index=%1$s',
        ];
        $this->classes = 'index';
    }
}
