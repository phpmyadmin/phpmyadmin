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
     * Tells whether there was an error during the import
     *
     * @var bool
     */
    private $_error;

    /**
     * Tells whether the timeout passed before the import finished
     *
     * @var bool
     */
    private $_timeoutPassed;

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
     *
     * @return void
     */
    protected function initImportCommonVariables()
    {
        global $error;
        global $timeout_passed;
        $this->setError($error);
        $this->setTimeoutPassed($timeout_passed);
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

    /**
     * Finds out whether there was an error during the import
     *
     * @return string
     */
    protected function getError()
    {
        return $this->_error;
    }

    /**
     * Sets to true if there was an error during the import, false otherwise
     *
     * @param bool $error whether there was an error during the import
     *
     * @return void
     */
    protected function setError($error)
    {
        $this->_error = $error;
    }

    /**
     * Finds out whether the timeout passed before the import finished
     *
     * @return string
     */
    protected function getTimeoutPassed()
    {
        return $this->_timeoutPassed;
    }

    /**
     * Sets to true if the timeout passed
     *
     * @param bool $timeoutPassed whether the timeout passed before the import
     *                            finished
     *
     * @return void
     */
    protected function setTimeoutPassed($timeoutPassed)
    {
        $this->_timeoutPassed = $timeoutPassed;
    }
}
?>