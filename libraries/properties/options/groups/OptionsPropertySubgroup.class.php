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
require_once "../OptionsPropertyGroup.class.php";

/**
 * Group property item class of type subgroup
 *
 * @package PhpMyAdmin
 */
class OptionsPropertySubgroup extends OptionsPropertyGroup
{
    /**
     * Returns the property item type of either an instance of
     *  - OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PluginPropertyItem     ( "export", "import", "transformations" ) 
     *
     * @return string
     */
    public abstract function getItemType()
    {
        return "subgroup";
    }
}
?>