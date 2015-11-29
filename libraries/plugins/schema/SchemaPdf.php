<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PDF schema export code
 *
 * @package    PhpMyAdmin-Schema
 * @subpackage PDF
 */
namespace PMA\libraries\plugins\schema;

use PMA\libraries\properties\options\items\BoolPropertyItem;
use PMA\libraries\properties\options\groups\OptionsPropertyMainGroup;
use PMA\libraries\properties\options\groups\OptionsPropertyRootGroup;
use PMA\libraries\plugins\schema\pdf\PdfRelationSchema;
use PMA\libraries\plugins\SchemaPlugin;
use PMA\libraries\properties\plugins\SchemaPluginProperties;
use PMA\libraries\properties\options\items\SelectPropertyItem;

/**
 * Handles the schema export for the PDF format
 *
 * @package    PhpMyAdmin-Schema
 * @subpackage PDF
 */
class SchemaPdf extends SchemaPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the schema export PDF properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $schemaPluginProperties = new SchemaPluginProperties();
        $schemaPluginProperties->setText('PDF');
        $schemaPluginProperties->setExtension('pdf');
        $schemaPluginProperties->setMimeType('application/pdf');

        // create the root group that will be the options field for
        // $schemaPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // specific options main group
        $specificOptions = new OptionsPropertyMainGroup();
        $specificOptions->setName("general_opts");
        // add options common to all plugins
        $this->addCommonOptions($specificOptions);

        // create leaf items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName('all_tables_same_width');
        $leaf->setText(__('Same width for all tables'));
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem();
        $leaf->setName("orientation");
        $leaf->setText(__('Orientation'));
        $leaf->setValues(
            array(
                'L' => __('Landscape'),
                'P' => __('Portrait'),
            )
        );
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem();
        $leaf->setName("paper");
        $leaf->setText(__('Paper size'));
        $leaf->setValues($this->_getPaperSizeArray());
        $specificOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem();
        $leaf->setName('show_grid');
        $leaf->setText(__('Show grid'));
        $specificOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem();
        $leaf->setName('with_doc');
        $leaf->setText(__('Data dictionary'));
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem();
        $leaf->setName("table_order");
        $leaf->setText(__('Order of the tables'));
        $leaf->setValues(
            array(
                ''          => __('None'),
                'name_asc'  => __('Name (Ascending)'),
                'name_desc' => __('Name (Descending)'),
            )
        );
        $specificOptions->addProperty($leaf);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($specificOptions);

        // set the options for the schema export plugin property item
        $schemaPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $schemaPluginProperties;
    }

    /**
     * Returns the array of paper sizes
     *
     * @return array array of paper sizes
     */
    private function _getPaperSizeArray()
    {
        $ret = array();
        foreach ($GLOBALS['cfg']['PDFPageSizes'] as $val) {
            $ret[$val] = $val;
        }

        return $ret;
    }

    /**
     * Exports the schema into PDF format.
     *
     * @param string $db database name
     *
     * @return bool Whether it succeeded
     */
    public function exportSchema($db)
    {
        $export = new PdfRelationSchema($db);
        $export->showOutput();
    }
}
