<?php
/**
 * Dia schema export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Plugins\Schema\Dia\DiaRelationSchema;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\SchemaPluginProperties;

use function __;

/**
 * Handles the schema export for the Dia format
 */
class SchemaDia extends SchemaPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'dia';
    }

    /**
     * Sets the schema export Dia properties
     */
    protected function setProperties(): SchemaPluginProperties
    {
        $schemaPluginProperties = new SchemaPluginProperties();
        $schemaPluginProperties->setText('Dia');
        $schemaPluginProperties->setExtension('dia');
        $schemaPluginProperties->setMimeType('application/dia');

        // create the root group that will be the options field for
        // $schemaPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // specific options main group
        $specificOptions = new OptionsPropertyMainGroup('general_opts');
        // add options common to all plugins
        $this->addCommonOptions($specificOptions);

        $leaf = new SelectPropertyItem(
            'orientation',
            __('Orientation'),
        );
        $leaf->setValues(
            ['L' => __('Landscape'), 'P' => __('Portrait')],
        );
        $specificOptions->addProperty($leaf);

        $leaf = new SelectPropertyItem(
            'paper',
            __('Paper size'),
        );
        $leaf->setValues($this->getPaperSizeArray());
        $specificOptions->addProperty($leaf);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($specificOptions);

        // set the options for the schema export plugin property item
        $schemaPluginProperties->setOptions($exportSpecificOptions);

        return $schemaPluginProperties;
    }

    /** @return array{fileName: non-empty-string, mediaType: non-empty-string, fileData: string} */
    public function getExportInfo(DatabaseName $db): array
    {
        $export = new DiaRelationSchema($db);
        $exportInfo = $export->getExportInfo();

        return [
            'fileName' => $exportInfo['fileName'],
            'mediaType' => 'application/x-dia-diagram',
            'fileData' => $exportInfo['fileData'],
        ];
    }
}
