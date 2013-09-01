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
require_once 'PluginObserver.class.php';

/**
 * Provides a common interface that will have to be implemented by all of the
 * import plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ImportPlugin extends PluginObserver
{
    /**
     * ImportPluginProperties object containing the import plugin properties
     *
     * @var ImportPluginProperties
     */
    protected $properties;

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    abstract public function doImport();


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
}
?>