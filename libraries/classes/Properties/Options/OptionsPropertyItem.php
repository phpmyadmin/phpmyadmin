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
     * Name
     *
     * @var string
     */
    private $name;
    /**
     * Text
     *
     * @var string
     */
    private $text;
    /**
     * What to force
     *
     * @var string
     */
    private $force;

    /**
     * @param string $name Item name
     * @param string $text Item text
     */
    public function __construct($name = null, $text = null)
    {
        if ($name) {
            $this->name = $name;
        }
        if (! $text) {
            return;
        }

        $this->text = $text;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text
     *
     * @param string $text text
     *
     * @return void
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Gets the force parameter
     *
     * @return string
     */
    public function getForce()
    {
        return $this->force;
    }

    /**
     * Sets the force parameter
     *
     * @param string $force force parameter
     *
     * @return void
     */
    public function setForce($force)
    {
        $this->force = $force;
    }

    /**
     * Returns the property type ( either "options", or "plugin" ).
     *
     * @return string
     */
    public function getPropertyType()
    {
        return 'options';
    }
}
