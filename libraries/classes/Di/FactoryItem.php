<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Di\FactoryItem class
 *
 * @package PhpMyAdmin\Di
 */
namespace PhpMyAdmin\Di;

/**
 * Factory manager
 *
 * @package PhpMyAdmin\Di
 */
class FactoryItem extends ReflectorItem
{

    /**
     * Construct an instance
     *
     * @param array $params Parameters
     *
     * @return mixed
     */
    public function get(array $params = array())
    {
        return $this->invoke($params);
    }
}
