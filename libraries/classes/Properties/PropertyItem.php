<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The top-level class of the object-oriented properties system.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Properties;

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
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public abstract function getItemType();

    /**
     * Only overwritten in the PhpMyAdmin\Properties\Options\OptionsPropertyGroup class:
     * Used to tell whether we can use the current item as a group by calling
     * the addProperty() or removeProperty() methods, which are not available
     * for simple PhpMyAdmin\Properties\Options\OptionsPropertyOneItem subclasses.
     *
     * @return string
     */
    public function getGroup()
    {
        return null;
    }
}
