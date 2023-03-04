<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ArrayObject;

use function in_array;

/** @extends ArrayObject<int, string> */
abstract class ListAbstract extends ArrayObject
{
    /** @var mixed   empty item */
    protected mixed $itemEmpty = '';

    /**
     * defines what is an empty item (0, '', false or null)
     *
     * @return mixed   an empty item
     */
    public function getEmpty(): mixed
    {
        return $this->itemEmpty;
    }

    /**
     * checks if the given db names exists in the current list, if there is
     * missing at least one item it returns false otherwise true
     *
     * @param mixed[] ...$params params
     */
    public function exists(...$params): bool
    {
        $this_elements = $this->getArrayCopy();
        foreach ($params as $result) {
            if (! in_array($result, $this_elements)) {
                return false;
            }
        }

        return true;
    }

    /**
     * returns default item
     *
     * @return string  default item
     */
    public function getDefault(): string
    {
        return $this->getEmpty();
    }

    /**
     * builds up the list
     */
    abstract public function build(): void;
}
