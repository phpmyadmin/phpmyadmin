<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class is responsible for creating Node objects
 *
 * @package PhpMyAdmin-navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Navigation\Nodes\Node;

/**
 * Node factory - instantiates Node objects or objects derived from the Node class
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeFactory
{
    protected static $namespace = 'PhpMyAdmin\\Navigation\\Nodes\\%s';
    /**
     * Sanitizes the name of a Node class
     *
     * @param string $class The class name to be sanitized
     *
     * @return string
     */
    private static function sanitizeClass($class)
    {
        if (! preg_match('@^Node\w*$@', $class)) {
            $class = 'Node';
            trigger_error(
                sprintf(
                    /* l10n: The word "Node" must not be translated here */
                    __('Invalid class name "%1$s", using default of "Node"'),
                    $class
                ),
                E_USER_ERROR
            );
        }

        return self::checkClass($class);
    }

    /**
     * Checks if a class exists and try to load it.
     * Will return the default class name back if the
     * file for some subclass is not available
     *
     * @param string $class The class name to check
     *
     * @return string
     */
    private static function checkClass($class)
    {
        $class = sprintf(self::$namespace, $class);

        if (! class_exists($class)) {
            $class = sprintf(self::$namespace, 'Node');
            trigger_error(
                sprintf(
                    __('Could not load class "%1$s"'),
                    $class
                ),
                E_USER_ERROR
            );
        }

        return $class;
    }

    /**
     * Instantiates a Node object
     *
     * @param string $class   The name of the class to instantiate
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     *
     * @return mixed
     */
    public static function getInstance(
        $class = 'Node',
        $name = 'default',
        $type = Node::OBJECT,
        $isGroup = false
    ) {
        $class = self::sanitizeClass($class);
        return new $class($name, $type, $isGroup);
    }
}
