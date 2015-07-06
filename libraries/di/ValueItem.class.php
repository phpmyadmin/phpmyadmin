<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DI\ValueItem class
 *
 * @package PMA
 */

namespace PMA\DI;

require_once 'libraries/di/Item.int.php';

class ValueItem implements Item
{

    /** @var mixed */
    protected $value;

    /**
     * Constructor
     *
     * @param $value
     */
    function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value
     *
     * @param array $params
     * @return mixed
     */
    public function get($params = array())
    {
        return $this->value;
    }
}
