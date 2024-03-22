<?php
/**
 * The top-level class of the "Options" subtree of the object-oriented
 * properties system (the other subtree is "Plugin").
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options;

use PhpMyAdmin\Properties\PropertyItem;

/**
 * Superclass for
 *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem and
 *  - OptionsProperty Group
 */
abstract class OptionsPropertyItem extends PropertyItem
{
    /**
     * What to force
     */
    private string|null $force = null;

    public function __construct(private string|null $name = null, private string|null $text = null)
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

    public function getText(): string|null
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getForce(): string|null
    {
        return $this->force;
    }

    public function setForce(string $force): void
    {
        $this->force = $force;
    }

    /**
     * Returns the property type ( either "options", or "plugin" ).
     */
    public function getPropertyType(): string
    {
        return 'options';
    }
}
