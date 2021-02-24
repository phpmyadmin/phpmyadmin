<?php
/**
 * Superclass for the single Property Item classes.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options;

/**
 * Parents only single property items (not groups).
 * Defines possible options and getters and setters for them.
 */
abstract class OptionsPropertyOneItem extends OptionsPropertyItem
{
    /**
     * Whether to force or not
     *
     * @var bool|string
     */
    private $forceOne;
    /**
     * Values
     *
     * @var array
     */
    private $values;
    /**
     * Doc
     *
     * @var string
     */
    private $doc;
    /**
     * Length
     *
     * @var int
     */
    private $len;
    /**
     * Size
     *
     * @var int
     */
    private $size;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the force parameter
     *
     * @return bool|string
     */
    public function getForce()
    {
        return $this->forceOne;
    }

    /**
     * Sets the force parameter
     *
     * @param bool|string $force force parameter
     *
     * @return void
     */
    public function setForce($force)
    {
        $this->forceOne = $force;
    }

    /**
     * Gets the values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Sets the values
     *
     * @param array $values values
     *
     * @return void
     */
    public function setValues(array $values)
    {
        $this->values = $values;
    }

    /**
     * Gets MySQL documentation pointer
     *
     * @return string
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * Sets the doc
     *
     * @param string $doc MySQL documentation pointer
     *
     * @return void
     */
    public function setDoc($doc)
    {
        $this->doc = $doc;
    }

    /**
     * Gets the length
     *
     * @return int
     */
    public function getLen()
    {
        return $this->len;
    }

    /**
     * Sets the length
     *
     * @param int $len length
     *
     * @return void
     */
    public function setLen($len)
    {
        $this->len = $len;
    }

    /**
     * Gets the size
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the size
     *
     * @param int $size size
     *
     * @return void
     */
    public function setSize($size)
    {
        $this->size = $size;
    }
}
