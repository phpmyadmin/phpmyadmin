<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the OptionsPropertySubgroup class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the OptionsPropertyGroup class */
require_once 'libraries/properties/options/OptionsPropertyGroup.class.php';

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
     *  - OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PluginPropertyItem     ( "export", "import", "transformations" )
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
     * @return string
     */
    public function getSubgroupHeader()
    {
        return $this->_subgroupHeader;
    }

    /**
     * Sets the subgroup header
     *
     * @param string $subgroupHeader subgroup header
     *
     * @return void
     */
    public function setSubgroupHeader($subgroupHeader)
    {
        $this->_subgroupHeader = $subgroupHeader;
    }
}
?>