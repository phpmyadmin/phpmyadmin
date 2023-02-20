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
     * Values
     *
     * @var array
     */
    private $values;
    /**
     * Doc
     *
     * @var string|array
     */
    private $doc;
    /**
     * Length
     *
     * @var int
     */
    private $len;

    private int $size = 0;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

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
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * Gets MySQL documentation pointer
     *
     * @return string|array
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * Sets the doc
     *
     * @param string|array $doc MySQL documentation pointer
     */
    public function setDoc($doc): void
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
     */
    public function setLen($len): void
    {
        $this->len = $len;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }
}
