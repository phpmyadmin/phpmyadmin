<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\properties\options\groups\OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\properties\options\groups;

use PMA\libraries\properties\options\OptionsPropertyGroup;

/**
 * Group property item class of type subgroup
 *
 * @package PhpMyAdmin
 */
class OptionsPropertySubgroup extends OptionsPropertyGroup
{
    /**
     * Subgroup Header
     *
     * @var string
     */
    private $_subgroupHeader;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

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
        return "subgroup";
    }

    /**
     * Gets the subgroup header
     *
     * @return \PMA\libraries\properties\PropertyItem
     */
    public function getSubgroupHeader()
    {
        return $this->_subgroupHeader;
    }

    /**
     * Sets the subgroup header
     *
     * @param \PMA\libraries\properties\PropertyItem $subgroupHeader subgroup header
     *
     * @return void
     */
    public function setSubgroupHeader($subgroupHeader)
    {
        $this->_subgroupHeader = $subgroupHeader;
    }
}
