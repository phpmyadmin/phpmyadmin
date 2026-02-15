<?php
/**
 * The top-level class of the "Options" subtree of the object-oriented
 * properties system (the other subtree is "Plugin").
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options;

/**
 * Superclass for
 *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem and
 *  - OptionsProperty Group
 */
abstract class OptionsPropertyItem
{
    public function __construct(private string|null $name = null, private readonly string $text = '')
    {
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
