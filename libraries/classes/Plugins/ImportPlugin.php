<?php
/**
 * Abstract class for the import plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\File;
use PhpMyAdmin\Import;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;

/**
 * Provides a common interface that will have to be implemented by all of the
 * import plugins.
 */
abstract class ImportPlugin implements Plugin
{
    /**
     * Object containing the import plugin properties.
     */
    protected ImportPluginProperties $properties;

    protected Import $import;

    final public function __construct()
    {
        $this->import = new Import();
        $this->init();
        $this->properties = $this->setProperties();
    }

    /**
     * Plugin specific initializations.
     */
    protected function init(): void
    {
    }

    /**
     * Handles the whole import logic
     *
     * @return string[]
     */
    abstract public function doImport(File|null $importHandle = null): array;

    /**
     * Gets the import specific format plugin properties
     *
     * @return ImportPluginProperties
     */
    public function getProperties(): PluginPropertyItem
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each import plugin.
     */
    abstract protected function setProperties(): ImportPluginProperties;

    public static function isAvailable(): bool
    {
        return true;
    }
}
