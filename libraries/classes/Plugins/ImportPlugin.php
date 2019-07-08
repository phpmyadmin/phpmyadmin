<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the import plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Import;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;

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
     * @var Import
     */
    protected $import;

    /**
     * ImportPlugin constructor.
     */
    public function __construct()
    {
        $this->import = new Import();
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     *
     * @return void
     */
    abstract public function doImport(array &$sql_data = []);


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
        if (strlen((string) $currentDb) > 0) {
            $db_name = $currentDb;
            $options = ['create_db' => false];
        } else {
            $db_name = $defaultDb;
            $options = null;
        }

        return [
            $db_name,
            $options,
        ];
    }
}
