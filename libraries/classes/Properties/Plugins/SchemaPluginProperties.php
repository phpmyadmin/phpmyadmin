<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the schema export plug-in
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Properties\Plugins;

/**
 * Defines possible options and getters and setters for them.
 *
 * @package PhpMyAdmin
 */
class SchemaPluginProperties extends PluginPropertyItem
{
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns the property item type of either an instance of
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool",
     *  "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main"
     *  or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export",
     *  "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return "schema";
    }

    /**
     * Gets the force file parameter
     *
     * @return bool
     */
    public function getForceFile()
    {
        return true;
    }
}
