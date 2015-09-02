<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\di\Item class
 *
 * @package PMA
 */
namespace PMA\libraries\di;

/**
 * Interface Item
 *
 * @package PMA\libraries\di
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
