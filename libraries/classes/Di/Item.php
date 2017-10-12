<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Di\Item class
 *
 * @package PhpMyAdmin\Di
 */
namespace PhpMyAdmin\Di;

/**
 * Interface Item
 *
 * @package PhpMyAdmin\Di
 */
interface Item
{

    /**
     * Get a value from the item
     *
     * @param array $params Parameters
     * @return mixed
     */
    public function get(array $params = array());
}
