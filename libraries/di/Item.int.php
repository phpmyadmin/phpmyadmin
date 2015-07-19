<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DI\Item class
 *
 * @package PMA
 */

namespace PMA\DI;

/**
 * Interface Item
 *
 * @package PMA\DI
 */
interface Item
{

    /**
     * Get a value from the item
     *
     * @param array $params Parameters
     * @return mixed
     */
    public function get($params = array());
}
