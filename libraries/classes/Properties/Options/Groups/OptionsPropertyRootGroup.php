<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;

/**
 * Group property item class of type root
 *
 * @package PhpMyAdmin
 */
class OptionsPropertyRootGroup extends OptionsPropertyGroup
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
        return "root";
    }
}
