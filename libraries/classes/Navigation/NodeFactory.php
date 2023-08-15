<?php
/**
 * This class is responsible for creating Node objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Navigation\Nodes\Node;

/**
 * Node factory - instantiates Node objects or objects derived from the Node class
 */
class NodeFactory
{
    /**
     * Instantiates a Node object that will be used only for "New db/table/etc.." objects
     *
     * @param string $name    An identifier for the new node
     * @param string $classes Extra CSS classes for the node
     */
    public static function getInstanceForNewNode(
        string $name,
        string $classes,
    ): Node {
        $node = new Node($name);
        $node->title = $name;
        $node->isNew = true;
        $node->classes = $classes;

        return $node;
    }
}
