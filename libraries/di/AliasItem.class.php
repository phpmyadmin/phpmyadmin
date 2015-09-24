<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DI\AliasItem class
 *
 * @package PMA
 */

namespace PMA\DI;

require_once 'libraries/di/Item.int.php';

/**
 * Class AliasItem
 *
 * @package PMA\DI
 */
class AliasItem implements Item
{

    /** @var Container */
    protected $container;

    /** @var string */
    protected $target;

    /**
     * Constructor
     *
     * @param Container $container Container
     * @param string    $target    Target
     */
    public function __construct(Container $container, $target)
    {
        $this->container = $container;
        $this->target = $target;
    }

    /**
     * Get the target item
     *
     * @param array $params Parameters
     * @return mixed
     */
    public function get($params = array())
    {
        return $this->container->get($this->target, $params);
    }
}
