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
    /** @var mixed[] */
    private array $values = [];

    /** @var string|string[] */
    private string|array $doc = '';

    private int $len = 0;

    private int $size = 0;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the values
     *
     * @return mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Sets the values
     *
     * @param mixed[] $values values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * Gets MySQL documentation pointer
     *
     * @return string|string[]
     */
    public function getDoc(): array|string
    {
        return $this->doc;
    }

    /**
     * Sets the doc
     *
     * @param string|string[] $doc MySQL documentation pointer
     */
    public function setDoc(string|array $doc): void
    {
        $this->doc = $doc;
    }

    public function getLen(): int
    {
        return $this->len;
    }

    public function setLen(int $len): void
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
