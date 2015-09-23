<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DI\ServiceItem class
 *
 * @package PMA
 */

namespace PMA\DI;

require_once 'libraries/di/ReflectorItem.class.php';

/**
 * Service manager
 *
 * @package PMA\DI
 */
class ServiceItem extends ReflectorItem
{

    /** @var mixed */
    protected $instance;

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
     * Get the instance of the service
     *
     * @param array $params Parameters
     * @return mixed
     */
    public function get($params = array())
    {
        if (!isset($this->instance)) {
            $this->instance = $this->invoke();
        }
        return $this->instance;
    }
}
