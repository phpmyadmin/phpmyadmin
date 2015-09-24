<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Class for exporting CSV dumps of tables for excel
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CSV-Excel
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Extend the export CSV class */
require_once 'libraries/plugins/export/ExportCsv.class.php';

/**
 * Handles the export for the CSV-Excel format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CSV-Excel
 */
class ExportExcel extends ExportCsv
{
    /**
     * Sets the export CSV for Excel properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/TextPropertyItem.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";
        include_once "$props/options/items/SelectPropertyItem.class.php";
        include_once "$props/options/items/HiddenPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CSV for MS Excel');
        $exportPluginProperties->setExtension('csv');
        $exportPluginProperties->setMimeType('text/comma-separated-values');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");
        // create primary items and add them to the group
        $leaf = new TextPropertyItem();
        $leaf->setName('null');
        $leaf->setText(__('Replace NULL with:'));
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem();
        $leaf->setName('removeCRLF');
        $leaf->setText(
            __('Remove carriage return/line feed characters within columns')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem();
        $leaf->setName('columns');
        $leaf->setText(__('Put columns names in the first row'));
        $generalOptions->addProperty($leaf);
        $leaf = new SelectPropertyItem();
        $leaf->setName('edition');
        $leaf->setValues(
            array(
                'win' => 'Windows',
                'mac_excel2003' => 'Excel 2003 / Macintosh',
                'mac_excel2008' => 'Excel 2008 / Macintosh'
            )
        );
        $leaf->setText(__('Excel edition:'));
        $generalOptions->addProperty($leaf);
        $leaf = new HiddenPropertyItem();
        $leaf->setName('structure_or_data');
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }
}
