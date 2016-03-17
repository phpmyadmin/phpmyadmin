<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the import plug-in
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\properties\plugins;

/**
 * Defines possible options and getters and setters for them.
 *
 * @package PhpMyAdmin
 */
class ImportPluginProperties extends PluginPropertyItem
{
    /**
     * Returns the property item type of either an instance of
     *  - PMA\libraries\properties\options\OptionsPropertyOneItem ( f.e. "bool",
     *  "text", "radio", etc ) or
     *  - PMA\libraries\properties\options\OptionsPropertyGroup   ( "root", "main"
     *  or "subgroup" )
     *  - PMA\libraries\properties\plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return "import";
    }
}