<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ArrayObject;

use function in_array;

/** @extends ArrayObject<int, string> */
abstract class ListAbstract extends ArrayObject
{
    /**
     * Checks if the given strings exists in the current list, if there is
     * missing at least one item it returns false otherwise true
     */
    public function exists(string ...$params): bool
    {
        $elements = $this->getArrayCopy();
        foreach ($params as $param) {
            if (! in_array($param, $elements, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns default item
     */
    public function getDefault(): string
    {
        return '';
    }

    /**
     * builds up the list
     */
    abstract public function build(): void;
}
