<?php
/**
 * Superclass for the Property Group classes.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options;

use Countable;
use SplObjectStorage;

/**
 * Parents group property items and provides methods to manage groups of
 * properties.
 *
 * @todo    modify descriptions if needed, when the options are integrated
 */
abstract class OptionsPropertyGroup extends OptionsPropertyItem implements Countable
{
    /**
     * Holds a group of properties (PhpMyAdmin\Properties\Options\OptionsPropertyItem instances)
     *
     * @var SplObjectStorage<OptionsPropertyItem, null>
     */
    private SplObjectStorage $properties;

    public function __construct(string|null $name = null, string|null $text = null)
    {
        parent::__construct($name, $text);

        $this->properties = new SplObjectStorage();
    }

    /**
     * Adds a property to the group of properties
     *
     * @param OptionsPropertyItem $property the property instance to be added
     *                                      to the group
     */
    public function addProperty(OptionsPropertyItem $property): void
    {
        $this->properties->attach($property);
    }

    /**
     * Removes a property from the group of properties
     *
     * @param OptionsPropertyItem $property the property instance to be removed
     *                                      from the group
     */
    public function removeProperty(OptionsPropertyItem $property): void
    {
        $this->properties->detach($property);
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the instance of the class
     */
    public function getGroup(): static
    {
        return $this;
    }

    /**
     * Gets the group of properties
     *
     * @return SplObjectStorage<OptionsPropertyItem, null>
     */
    public function getProperties(): SplObjectStorage
    {
        return $this->properties;
    }

    /**
     * Gets the number of properties
     */
    public function getNrOfProperties(): int
    {
        return $this->properties->count();
    }

    /**
     * Countable interface implementation.
     */
    public function count(): int
    {
        return $this->getNrOfProperties();
    }
}
