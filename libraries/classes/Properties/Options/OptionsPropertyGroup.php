<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Superclass for the Property Group classes.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Properties\Options;

/**
 * Parents group property items and provides methods to manage groups of
 * properties.
 *
 * @todo    modify descriptions if needed, when the options are integrated
 * @package PhpMyAdmin
 */
abstract class OptionsPropertyGroup extends OptionsPropertyItem implements \Countable
{
    /**
     * Holds a group of properties (PhpMyAdmin\Properties\Options\OptionsPropertyItem instances)
     *
     * @var array
     */
    private $_properties;

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
        if (!$this->getProperties() == null
            && in_array($property, $this->getProperties(), true)
        ) {
            return;
        }
        $this->_properties [] = $property;
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
        $this->_properties = array_diff(
            $this->getProperties(),
            array($property)
        );
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the instance of the class
     *
     * @return array
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
        return $this->_properties;
    }

    /**
     * Gets the number of properties
     *
     * @return int
     */
    public function getNrOfProperties()
    {
        if (is_null($this->_properties)) {
            return 0;
        }
        return count($this->_properties);
    }

    /**
     * Countable interface implementation.
     *
     * @return int
     */
    public function count() {
        return $this->getNrOfProperties();
    }
}
