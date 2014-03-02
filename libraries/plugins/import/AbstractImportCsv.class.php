<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Super class of CSV import plugins for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the import interface */
require_once 'libraries/plugins/ImportPlugin.class.php';

/**
 * Super class of the import plugins for the CSV format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
abstract class AbstractImportCsv extends ImportPlugin
{
    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return OptionsPropertyMainGroup OptionsPropertyMainGroup object of the plugin
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ImportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";
        include_once "$props/options/items/TextPropertyItem.class.php";

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup();
        $importSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");

        // create common items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName("replace");
        $leaf->setText(__('Replace table data with file'));
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("terminated");
        $leaf->setText(__('Columns separated with:'));
        $leaf->setSize(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("enclosed");
        $leaf->setText(__('Columns enclosed with:'));
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("escaped");
        $leaf->setText(__('Columns escaped with:'));
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("new_line");
        $leaf->setText(__('Lines terminated with:'));
        $leaf->setSize(2);
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);
        $this->properties = $importPluginProperties;

        return $generalOptions;
    }
}
?>