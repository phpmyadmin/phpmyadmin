<?php
namespace PMA\DI;

require_once 'libraries/di/Item.int.php';

class AliasItem implements Item
{

    /** @var Container */
    protected $container;

    /** @var string */
    protected $target;

    /**
     * Constructor
     *
     * @param Container $container
     * @param string $target
     */
    function __construct(Container $container, $target)
    {
        $this->container = $container;
        $this->target = $target;
    }

    /**
     * Get the target item
     *
     * @param array $params
     * @return mixed
     */
    public function get($params = array())
    {
        return $this->container->get($this->target, $params);
    }
}