<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DI\FactoryItem class
 *
 * @package PMA
 */

namespace PMA\DI;

require_once 'libraries/di/ReflectorItem.class.php';

/**
 * Factory manager
 *
 * @package PMA\DI
 */
class FactoryItem extends ReflectorItem
{

    /**
     * Constructor
     *
     * @param Container $container
     * @param mixed     $definition
     */
    function __construct(Container $container, $definition)
    {
        parent::__construct($container, $definition);
    }

    /**
     * Construct an instance
     *
     * @param array $params Paramters
     * @return mixed
     */
    public function get($params = array())
    {
        return $this->invoke($params);
    }
}
