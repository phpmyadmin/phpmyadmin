<?php
/**
 * The top-level class of the object-oriented properties system.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties;

/**
 * Provides an interface for Property classes
 */
abstract class PropertyItem
{
    /**
     * Returns the property type ( either "Options", or "Plugin" ).
     */
    abstract public function getPropertyType(): string;

    /**
     * Returns the property item type of either an instance of
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     */
    abstract public function getItemType(): string;

    /**
     * Only overwritten in the PhpMyAdmin\Properties\Options\OptionsPropertyGroup class:
     * Used to tell whether we can use the current item as a group by calling
     * the addProperty() or removeProperty() methods, which are not available
     * for simple PhpMyAdmin\Properties\Options\OptionsPropertyOneItem subclasses.
     */
    public function getGroup(): static|null
    {
        return null;
    }
}
