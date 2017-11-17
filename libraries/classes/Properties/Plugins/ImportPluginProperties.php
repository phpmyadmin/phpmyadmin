<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the import plug-in
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Properties\Plugins;

/**
 * Defines possible options and getters and setters for them.
 *
 * @package PhpMyAdmin
 */
class ImportPluginProperties extends PluginPropertyItem
{
    /**
     * Returns the property item type of either an instance of
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool",
     *  "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main"
     *  or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return "import";
    }
}