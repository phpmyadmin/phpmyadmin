<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Superclass for the single Property Item classes.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the OptionsPropertyItem class */
require_once "OptionsPropertyItem.class.php";

/**
 * Parents only single property items (not groups).
 * Defines possible options and getters and setters for them.
 *
 * @todo modify descriptions if needed, when the options are integrated
 * @package PhpMyAdmin
 */
abstract class OptionsPropertyOneItem extends OptionsPropertyItem
{
    /**
     * Whether to force or not
     *
     * @var bool
     */
    private $_force;

    /**
     * Values
     *
     * @var array
     */
    private $_values;

    /**
     * Doc
     *
     * @var string
     */
    private $_doc;


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the force parameter
     *
     * @return string
     */
    public function getForce()
    {
        return $this->_force;
    }

    /**
     * Sets the force parameter
     *
     * @param bool $force force parameter
     *
     * @return void
     */
    public function setForce($force)
    {
        $this->_force = $force;
    }

    /**
     * Gets the values
     *
     * @return string
     */
    public function getValues()
    {
        return $this->_values;
    }

    /**
     * Sets the values
     *
     * @param array $values values
     *
     * @return void
     */
    public function setValues($values)
    {
        $this->_values = $values;
    }

    /**
     * Gets the type of the newline character
     *
     * @return string
     */
    public function getDoc()
    {
        return $this->_doc;
    }

    /**
     * Sets the doc
     *
     * @param string $doc doc
     *
     * @return void
     */
    public function setDoc($doc)
    {
        $this->_doc = $doc;
    }
}
?>