<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Dia schema export code
 *
 * @package    PhpMyAdmin-Schema
 * @subpackage Dia
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the schema export interface */
require_once 'libraries/plugins/SchemaPlugin.class.php';
require_once 'libraries/plugins/schema/dia/Dia_Relation_Schema.class.php';

/**
 * Handles the schema export for the Dia format
 *
 * @package    PhpMyAdmin-Schema
 * @subpackage Dia
 */
class SchemaDia extends SchemaPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the schema export Dia properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/SchemaPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";
        include_once "$props/options/items/SelectPropertyItem.class.php";

        $schemaPluginProperties = new SchemaPluginProperties();
        $schemaPluginProperties->setText('Dia');
        $schemaPluginProperties->setExtension('dia');
        $schemaPluginProperties->setMimeType('application/dia');

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
     * Exports the schema into DIA format.
     *
     * @param string $db database name
     *
     * @return bool Whether it succeeded
     */
    public function exportSchema($db)
    {
        $export = new PMA_Dia_Relation_Schema();
        $export->showOutput();
    }
}
?>