<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the import plug-in
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PluginPropertyItem class */
require_once 'PluginPropertyItem.class.php';

/**
 * Defines possible options and getters and setters for them.
 *
 * @package PhpMyAdmin
 */
class ImportPluginProperties extends PluginPropertyItem
{
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
        return "import";
    }
}