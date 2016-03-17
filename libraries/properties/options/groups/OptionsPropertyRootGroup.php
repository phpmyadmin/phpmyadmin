<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\properties\options\groups\OptionsPropertyRootGroup class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\properties\options\groups;

use PMA\libraries\properties\options\OptionsPropertyGroup;

/**
 * Group property item class of type root
 *
 * @package PhpMyAdmin
 */
class OptionsPropertyRootGroup extends OptionsPropertyGroup
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
        return "root";
    }
}
