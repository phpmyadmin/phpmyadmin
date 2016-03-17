<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The top-level class of the object-oriented properties system.
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\properties;

/**
 * Provides an interface for Property classes
 *
 * @package PhpMyAdmin
 */
abstract class PropertyItem
{
    /**
     * Returns the property type ( either "Options", or "Plugin" ).
     *
     * @return string
     */
    public abstract function getPropertyType();

    /**
     * Returns the property item type of either an instance of
     *  - PMA\libraries\properties\options\OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - PMA\libraries\properties\options\OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PMA\libraries\properties\plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public abstract function getItemType();

    /**
     * Only overwritten in the PMA\libraries\properties\options\OptionsPropertyGroup class:
     * Used to tell whether we can use the current item as a group by calling
     * the addProperty() or removeProperty() methods, which are not available
     * for simple PMA\libraries\properties\options\OptionsPropertyOneItem subclasses.
     *
     * @return string
     */
    public function getGroup()
    {
        return null;
    }
}
