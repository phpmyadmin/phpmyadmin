<?php
/**
 * Superclass for the Property Group classes.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options;

use Countable;
use function array_diff;
use function count;
use function in_array;

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
     * @var array
     */
    private $properties;

    /**
     * Adds a property to the group of properties
     *
     * @param OptionsPropertyItem $property the property instance to be added
     *                                      to the group
     *
     * @return void
     */
    public function addProperty($property)
    {
        if (! $this->getProperties() == null
            && in_array($property, $this->getProperties(), true)
        ) {
            return;
        }
        $this->properties[] = $property;
    }

    /**
     * Removes a property from the group of properties
     *
     * @param OptionsPropertyItem $property the property instance to be removed
     *                                      from the group
     *
     * @return void
     */
    public function removeProperty($property)
    {
        $this->properties = array_diff(
            $this->getProperties(),
            [$property]
        );
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the instance of the class
     *
     * @return OptionsPropertyGroup
     */
    public function getGroup()
    {
        return $this;
    }

    /**
     * Gets the group of properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Gets the number of properties
     *
     * @return int
     */
    public function getNrOfProperties()
    {
        if ($this->properties === null) {
            return 0;
        }

        return count($this->properties);
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly, Squiz.WhiteSpace.FunctionSpacing
    /**
     * Countable interface implementation.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->getNrOfProperties();
    }
}
