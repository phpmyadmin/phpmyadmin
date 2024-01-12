<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Export;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(ExportExcel::class)]
#[Group('medium')]
class ExportExcelTest extends AbstractTestCase
{
    protected ExportExcel $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $this->object = new ExportExcel(
            new Relation($dbi),
            new Export($dbi),
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

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertEquals(
            'CSV for MS Excel',
            $properties->getText(),
        );

        self::assertEquals(
            'csv',
            $properties->getExtension(),
        );

        self::assertEquals(
            'text/comma-separated-values',
            $properties->getMimeType(),
        );

        self::assertEquals(
            'Options',
            $properties->getOptionsText(),
        );

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertEquals(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertEquals(
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertEquals(
            'null',
            $property->getName(),
        );

        self::assertEquals(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertEquals(
            'removeCRLF',
            $property->getName(),
        );

        self::assertEquals(
            'Remove carriage return/line feed characters within columns',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertEquals(
            'columns',
            $property->getName(),
        );

        self::assertEquals(
            'Put columns names in the first row',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(SelectPropertyItem::class, $property);

        self::assertEquals(
            'edition',
            $property->getName(),
        );

        self::assertEquals(
            [
                'win' => 'Windows',
                'mac_excel2003' => 'Excel 2003 / Macintosh',
                'mac_excel2008' => 'Excel 2008 / Macintosh',
            ],
            $property->getValues(),
        );

        self::assertEquals(
            'Excel edition:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertEquals(
            'structure_or_data',
            $property->getName(),
        );
    }
}
