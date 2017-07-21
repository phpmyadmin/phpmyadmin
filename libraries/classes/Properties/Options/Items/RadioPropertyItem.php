<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Properties\Options\Items\RadioPropertyItem class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

/**
 * Single property item class of type radio
 *
 * @package PhpMyAdmin
 */
class RadioPropertyItem extends OptionsPropertyOneItem
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
        return "radio";
    }
}
