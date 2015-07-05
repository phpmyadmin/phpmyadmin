<?php
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