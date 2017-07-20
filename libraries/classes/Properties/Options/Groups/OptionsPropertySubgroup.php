<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;

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
        return "subgroup";
    }

    /**
     * Gets the subgroup header
     *
     * @return \PhpMyAdmin\Properties\PropertyItem
     */
    public function getSubgroupHeader()
    {
        return $this->_subgroupHeader;
    }

    /**
     * Sets the subgroup header
     *
     * @param \PhpMyAdmin\Properties\PropertyItem $subgroupHeader subgroup header
     *
     * @return void
     */
    public function setSubgroupHeader($subgroupHeader)
    {
        $this->_subgroupHeader = $subgroupHeader;
    }
}
