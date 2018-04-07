<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Di\ValueItem class
 *
 * @package PhpMyAdmin\Di
 */
namespace PhpMyAdmin\Di;

/**
 * Value manager
 *
 * @package PhpMyAdmin\Di
 */
class ValueItem implements Item
{

    /** @var mixed */
    protected $value;

    /**
     * Constructor
     *
     * @param mixed $value Value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value
     *
     * @param array $params Parameters
     * @return mixed
     */
    public function get(array $params = array())
    {
        return $this->value;
    }
}
