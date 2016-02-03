<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Super class of CSV import plugins for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
namespace PMA\libraries\plugins\import;

use PMA\libraries\properties\options\items\BoolPropertyItem;
use PMA\libraries\properties\plugins\ImportPluginProperties;
use PMA\libraries\properties\options\groups\OptionsPropertyMainGroup;
use PMA\libraries\properties\options\groups\OptionsPropertyRootGroup;
use PMA\libraries\plugins\ImportPlugin;
use PMA\libraries\properties\options\items\TextPropertyItem;

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
     * @return \PMA\libraries\properties\options\groups\OptionsPropertyMainGroup PMA\libraries\properties\options\groups\OptionsPropertyMainGroup object of the plugin
     */
    protected function setProperties()
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup(
            "Format Specific Options"
        );

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup("general_opts");

        // create common items and add them to the group
        $leaf = new BoolPropertyItem(
            "replace",
            __(
                'Update data when duplicate keys found on import (add ON DUPLICATE '
                . 'KEY UPDATE)'
            )
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "terminated",
            __('Columns separated with:')
        );
        $leaf->setSize(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "enclosed",
            __('Columns enclosed with:')
        );
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "escaped",
            __('Columns escaped with:')
        );
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "new_line",
            __('Lines terminated with:')
        );
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
