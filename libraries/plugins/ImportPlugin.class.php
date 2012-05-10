<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the import plugins
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PluginObserver class */
require_once "PluginObserver.class.php";

/**
 * Provides a common interface that will have to implemented by all of the
 * import plugins.
 *
 * @todo descriptions
 * @package PhpMyAdmin
 */
abstract class ImportPlugin extends PluginObserver
{
    /**
     * Array containing the import plugin properties
     *
     * @var type array
     */
    protected $properties;

    /**
     *
     *
     * @var type
     */
    private $_error;

    /**
     *
     *
     * @var type
     */
    private $_timeout_passed;

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    abstract public function doImport();

    /**
     * Initializes the local variables with the global values.
     * These are variables that are used by all of the import plugins.
     *
     * @global type $error
     * @global type $timeout_passed
     * @global type $finished
     *
     * @return void
     */
    protected function initImportCommonVariables()
    {
        global $error;
        global $timeout_passed;
        $this->setError($error);
        $this->setTimeout_passed($timeout_passed);
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the import specific format plugin properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each import
     * plugin
     *
     * @return void
     */
    abstract protected function setProperties();

    public function getError()
    {
        return $this->_error;
    }

    public function setError($error)
    {
        $this->_error = $error;
    }

    public function getTimeout_passed()
    {
        return $this->_timeout_passed;
    }

    public function setTimeout_passed($timeout_passed)
    {
        $this->_timeout_passed = $timeout_passed;
    }
}
?>