<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\di\FactoryItem class
 *
 * @package PMA
 */
namespace PMA\libraries\di;

/**
 * Factory manager
 *
 * @package PMA\libraries\di
 */
class FactoryItem extends ReflectorItem
{

    /**
     * Constructor
     *
     * @param Container $container  Container
     * @param mixed     $definition Definition
     */
    public function __construct(Container $container, $definition)
    {
        parent::__construct($container, $definition);
    }

    /**
     * Construct an instance
     *
     * @param array $params Parameters
     *
     * @return mixed
     */
    public function get($params = array())
    {
        return $this->invoke($params);
    }
}
