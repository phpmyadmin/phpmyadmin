<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The top-level class of the object-oriented properties system.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
     *  - OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PluginPropertyItem     ( "export", "import", "transformations" ) 
     *
     * @return string
     */
    public abstract function getItemType();

    /**
     * Only overwritten in the OptionsPropertyGroup class:
     * Used to tell whether we can use the current item as a group by calling
     * the addProperty() or removeProperty() methods, which are not available
     * for simple OptionsPropertyOneItem subclasses.
     *
     * @return string
     */
    public function getGroup()
    {
        return null;
    }
}
?>