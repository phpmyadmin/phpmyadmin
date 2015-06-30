<?php
namespace PMA\DI;

interface Item
{

    /**
     * Get a value from the item
     *
     * @param array $params
     * @return mixed
     */
    public function get($params = array());
}