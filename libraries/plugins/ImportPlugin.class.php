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

/**
 * Provides a common interface that will have to be implemented by all of the
 * import plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ImportPlugin
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
     * @return ImportPluginProperties
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
     * Define DB name and options
     *
     * @param string $currentDb DB
     * @param string $defaultDb Default DB name
     *
     * @return array DB name and options (an associative array of options)
     */
    protected function getDbnameAndOptions($currentDb, $defaultDb)
    {
        if (/*overload*/mb_strlen($currentDb)) {
            $db_name = $currentDb;
            $options = array('create_db' => false);
        } else {
            $db_name = $defaultDb;
            $options = null;
        }

        return array($db_name, $options);
    }
}
