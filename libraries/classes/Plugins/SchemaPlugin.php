<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the schema export plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Plugins\SchemaPluginProperties;

/**
 * Provides a common interface that will have to be implemented by all of the
 * schema export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 *
 * @package PhpMyAdmin
 */
abstract class SchemaPlugin
{
    /**
     * PhpMyAdmin\Properties\Plugins\SchemaPluginProperties object containing
     * the specific schema export plugin type properties
     *
     * @var SchemaPluginProperties
     */
    protected $properties;

    /**
     * Gets the export specific format plugin properties
     *
     * @return SchemaPluginProperties
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by
     * each schema export plugin
     *
     * @return void
     */
    abstract protected function setProperties();

    /**
     * Exports the schema into the specified format.
     *
     * @param string $db database name
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportSchema($db);

    /**
     * Adds export options common to all plugins.
     *
     * @param OptionsPropertyMainGroup $propertyGroup property group
     *
     * @return void
     */
    protected function addCommonOptions(OptionsPropertyMainGroup $propertyGroup)
    {
        $leaf = new BoolPropertyItem('show_color', __('Show color'));
        $propertyGroup->addProperty($leaf);
        $leaf = new BoolPropertyItem('show_keys', __('Only show keys'));
        $propertyGroup->addProperty($leaf);
    }

    /**
     * Returns the array of paper sizes
     *
     * @return array array of paper sizes
     */
    protected function getPaperSizeArray()
    {
        $ret = [];
        foreach ($GLOBALS['cfg']['PDFPageSizes'] as $val) {
            $ret[$val] = $val;
        }

        return $ret;
    }
}
