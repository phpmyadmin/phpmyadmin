<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\di\AliasItem class
 *
 * @package PMA
 */
namespace PMA\libraries\di;

/**
 * Class AliasItem
 *
 * @package PMA\libraries\di
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
