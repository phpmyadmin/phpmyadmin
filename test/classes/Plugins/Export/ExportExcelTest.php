<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportExcel;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportExcel
 * @group medium
 */
class ExportExcelTest extends AbstractTestCase
{
    /** @var ExportExcel */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $this->object = new ExportExcel();
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
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportExcel::class, 'properties');
        if (PHP_VERSION_ID < 80100) {
            $attrProperties->setAccessible(true);
        }

        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame('CSV for MS Excel', $properties->getText());

        self::assertSame('csv', $properties->getExtension());

        self::assertSame('text/comma-separated-values', $properties->getMimeType());

        self::assertSame('Options', $properties->getOptionsText());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('general_opts', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame('null', $property->getName());

        self::assertSame('Replace NULL with:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame('removeCRLF', $property->getName());

        self::assertSame('Remove carriage return/line feed characters within columns', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame('columns', $property->getName());

        self::assertSame('Put columns names in the first row', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(SelectPropertyItem::class, $property);

        self::assertSame('edition', $property->getName());

        self::assertSame([
            'win' => 'Windows',
            'mac_excel2003' => 'Excel 2003 / Macintosh',
            'mac_excel2008' => 'Excel 2008 / Macintosh',
        ], $property->getValues());

        self::assertSame('Excel edition:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame('structure_or_data', $property->getName());
    }
}
