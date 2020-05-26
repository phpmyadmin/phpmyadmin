<?php
/**
 * Dia schema export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Dia\DiaRelationSchema;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\SchemaPluginProperties;

/**
 * Handles the schema export for the Dia format
 */
class SchemaDia extends SchemaPlugin
{
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
        $schemaPluginProperties = new SchemaPluginProperties();
        $schemaPluginProperties->setText('Dia');
        $schemaPluginProperties->setExtension('dia');
        $schemaPluginProperties->setMimeType('application/dia');

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

        // add the main group to the root group
        $exportSpecificOptions->addProperty($specificOptions);

        // set the options for the schema export plugin property item
        $schemaPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $schemaPluginProperties;
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
        $export = new DiaRelationSchema($db);
        $export->showOutput();

        return true;
    }
}
