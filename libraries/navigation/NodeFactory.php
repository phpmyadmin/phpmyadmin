<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class is responsible for creating Node objects
 *
 * @package PhpMyAdmin-navigation
 */
namespace PMA\libraries\navigation;

use PMA\libraries\navigation\nodes\Node;

/**
 * Node factory - instantiates Node objects or objects derived from the Node class
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeFactory
{
    /**
     * @var string $_path A template for generating paths to files
     *                    that contain various Node classes
     * @access private
     */
    private static $_path = 'libraries/navigation/nodes/%s.php';

    /**
     * Sanitizes the name of a Node class
     *
     * @param string $class The class name to be sanitized
     *
     * @return string
     */
    private static function _sanitizeClass($class)
    {
        if ($class !== 'PMA\libraries\navigation\nodes\Node' && !preg_match('@^Node\w+(_\w+)?$@', $class)) {
            $class = 'PMA\libraries\navigation\nodes\Node';
            trigger_error(
                sprintf(
                /* l10n: The word "Node" must not be translated here */
                    __('Invalid class name "%1$s", using default of "PMA\libraries\navigation\nodes\Node"'),
                    $class
                ),
                E_USER_ERROR
            );
        }

        return self::_checkFile($class);
    }

    /**
     * Checks if a file exists for a given class name
     * Will return the default class name back if the
     * file for some subclass is not available
     *
     * @param string $class The class name to check
     *
     * @return string
     */
    private static function _checkFile($class)
    {
        $path = sprintf(self::$_path, $class);
        if (!is_readable($path)) {
            $class = 'PMA\libraries\navigation\nodes\Node';
            trigger_error(
                sprintf(
                    __('Could not include class "%1$s", file "%2$s" not found'),
                    $class,
                    'Nodes/' . $class . '.php'
                ),
                E_USER_ERROR
            );
        }

        return $class;
    }

    /**
     * Instantiates a Node object
     *
     * @param string $class    The name of the class to instantiate
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return mixed
     */
    public static function getInstance(
        $class = 'PMA\libraries\navigation\nodes\Node',
        $name = 'default',
        $type = Node::OBJECT,
        $is_group = false
    ) {
        $class = self::_sanitizeClass($class);
        return new $class($name, $type, $is_group);
    }
}

