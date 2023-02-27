<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportExcel;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportExcel
 * @group medium
 */
class ExportExcelTest extends AbstractTestCase
{
    protected ExportExcel $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
        $this->object = new ExportExcel(
            new Relation($GLOBALS['dbi']),
            new Export($GLOBALS['dbi']),
            new Transformations(),
        );
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportExcel::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportExcel::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

        $this->assertEquals(
            'CSV for MS Excel',
            $properties->getText(),
        );

        $this->assertEquals(
            'csv',
            $properties->getExtension(),
        );

        $this->assertEquals(
            'text/comma-separated-values',
            $properties->getMimeType(),
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText(),
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $this->assertEquals(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray->current();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'null',
            $property->getName(),
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'removeCRLF',
            $property->getName(),
        );

        $this->assertEquals(
            'Remove carriage return/line feed characters within columns',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'columns',
            $property->getName(),
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(SelectPropertyItem::class, $property);

        $this->assertEquals(
            'edition',
            $property->getName(),
        );

        $this->assertEquals(
            [
                'win' => 'Windows',
                'mac_excel2003' => 'Excel 2003 / Macintosh',
                'mac_excel2008' => 'Excel 2008 / Macintosh',
            ],
            $property->getValues(),
        );

        $this->assertEquals(
            'Excel edition:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        $this->assertInstanceOf(HiddenPropertyItem::class, $property);

        $this->assertEquals(
            'structure_or_data',
            $property->getName(),
        );
    }
}
