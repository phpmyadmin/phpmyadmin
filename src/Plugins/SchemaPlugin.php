<?php
/**
 * Abstract class for the schema export plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Config;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;
use PhpMyAdmin\Properties\Plugins\SchemaPluginProperties;

use function __;

/**
 * Provides a common interface that will have to be implemented by all of the
 * schema export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 */
abstract class SchemaPlugin implements Plugin
{
    /**
     * Object containing the specific schema export plugin type properties.
     */
    protected SchemaPluginProperties $properties;

    final public function __construct()
    {
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
     * Gets the export specific format plugin properties
     *
     * @return SchemaPluginProperties
     */
    public function getProperties(): PluginPropertyItem
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each schema export plugin.
     */
    abstract protected function setProperties(): SchemaPluginProperties;

    /** @return array{fileName: non-empty-string, mediaType: non-empty-string, fileData: string} */
    abstract public function getExportInfo(DatabaseName $db): array;

    /**
     * Adds export options common to all plugins.
     *
     * @param OptionsPropertyMainGroup $propertyGroup property group
     */
    protected function addCommonOptions(OptionsPropertyMainGroup $propertyGroup): void
    {
        $leaf = new BoolPropertyItem('show_color', __('Show color'));
        $propertyGroup->addProperty($leaf);
        $leaf = new BoolPropertyItem('show_keys', __('Only show keys'));
        $propertyGroup->addProperty($leaf);
    }

    /**
     * Returns the array of paper sizes
     *
     * @return mixed[] array of paper sizes
     */
    protected function getPaperSizeArray(): array
    {
        $ret = [];
        foreach (Config::getInstance()->settings['PDFPageSizes'] as $val) {
            $ret[$val] = $val;
        }

        return $ret;
    }

    public static function isAvailable(): bool
    {
        return true;
    }

    public function getTranslatedText(string $text): string
    {
        return $text;
    }
}
