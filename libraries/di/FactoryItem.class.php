<?php
namespace PMA\DI;

require_once 'libraries/di/ReflectorItem.class.php';

class FactoryItem extends ReflectorItem
{

    /**
     * Constructor
     *
     * @param Container $container
     * @param mixed $definition
     */
    function __construct(Container $container, $definition)
    {
        parent::__construct($container, $definition);
    }

    /**
     * Construct an instance
     *
     * @param array $params
     * @return mixed
     */
    public function get($params = array())
    {
        return $this->invoke($params);
    }
}