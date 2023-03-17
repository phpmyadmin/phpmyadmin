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
     * Instantiates a Node object
     *
     * @param class-string<T> $class   The name of the class to instantiate
     * @param string|mixed[]  $name    An identifier for the new node
     * @param int             $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool            $isGroup Whether this object has been created while grouping nodes
     *
     * @return T
     *
     * @template T of Node
     */
    public static function getInstance(
        string $class,
        string|array $name = 'default',
        int $type = Node::OBJECT,
        bool $isGroup = false,
    ): Node {
        return new $class($name, $type, $isGroup);
    }

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
        $node = new Node($name, Node::OBJECT, false);
        $node->title = $name;
        $node->isNew = true;
        $node->classes = $classes;

        return $node;
    }
}
