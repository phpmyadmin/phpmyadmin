<?php
/**
 * PDF schema export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Pdf\PdfRelationSchema;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\SchemaPluginProperties;

/**
 * Handles the schema export for the PDF format
 */
class SchemaPdf extends SchemaPlugin
{
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
        $exportSpecificOptions = new OptionsPropertyRootGroup(
            'Format Specific Options'
        );

        // specific options main group
        $specificOptions = new OptionsPropertyMainGroup('general_opts');
        // add options common to all plugins
        $this->addCommonOptions($specificOptions);

        // create leaf items and add them to the group
        $leaf = new BoolPropertyItem(
            'all_tables_same_width',
            __('Same width for all tables')
        );
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem(
            'orientation',
            __('Orientation')
        );
        $leaf->setValues(
            [
                'L' => __('Landscape'),
                'P' => __('Portrait'),
            ]
        );
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem(
            'paper',
            __('Paper size')
        );
        $leaf->setValues($this->getPaperSizeArray());
        $specificOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'show_grid',
            __('Show grid')
        );
        $specificOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'with_doc',
            __('Data dictionary')
        );
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem(
            'table_order',
            __('Order of the tables')
        );
        $leaf->setValues(
            [
                ''          => __('None'),
                'name_asc'  => __('Name (Ascending)'),
                'name_desc' => __('Name (Descending)'),
            ]
        );
        $specificOptions->addProperty($leaf);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($specificOptions);

        // set the options for the schema export plugin property item
        $schemaPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $schemaPluginProperties;
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

        return true;
    }
}
