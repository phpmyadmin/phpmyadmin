<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportPdf;
use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function array_shift;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportPdf
 * @group medium
 */
class ExportPdfTest extends AbstractTestCase
{
    /** @var ExportPdf */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $this->object = new ExportPdf();
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
        $method = new ReflectionMethod(ExportPdf::class, 'setProperties');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportPdf::class, 'properties');
        if (PHP_VERSION_ID < 80100) {
            $attrProperties->setAccessible(true);
        }

        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame('PDF', $properties->getText());

        self::assertSame('pdf', $properties->getExtension());

        self::assertSame('application/pdf', $properties->getMimeType());

        self::assertSame('Options', $properties->getOptionsText());

        self::assertTrue($properties->getForceFile());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('general_opts', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame('report_title', $property->getName());

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('dump_what', $generalOptions->getName());

        self::assertSame('Dump table', $generalOptions->getText());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(RadioPropertyItem::class, $property);

        self::assertSame('structure_or_data', $property->getName());

        self::assertSame([
            'structure' => __('structure'),
            'data' => __('data'),
            'structure_and_data' => __('structure and data'),
        ], $property->getValues());
    }

    public function testExportHeader(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('Open');

        $pdf->expects($this->once())
            ->method('setTopMargin');

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        if (PHP_VERSION_ID < 80100) {
            $attrPdf->setAccessible(true);
        }

        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue($this->object->exportHeader());
    }

    public function testExportFooter(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('getPDFData');

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        if (PHP_VERSION_ID < 80100) {
            $attrPdf->setAccessible(true);
        }

        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue($this->object->exportFooter());
    }

    public function testExportDBHeader(): void
    {
        self::assertTrue($this->object->exportDBHeader('testDB'));
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue($this->object->exportDBFooter('testDB'));
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue($this->object->exportDBCreate('testDB', 'database'));
    }

    public function testExportData(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('mysqlReport')
            ->with('SELECT');

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        if (PHP_VERSION_ID < 80100) {
            $attrPdf->setAccessible(true);
        }

        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue($this->object->exportData(
            'db',
            'table',
            "\n",
            'phpmyadmin.net/err',
            'SELECT'
        ));
    }

    /**
     * Test for
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::setPdf
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::getPdf
     */
    public function testSetGetPdf(): void
    {
        $setter = new ReflectionMethod(ExportPdf::class, 'setPdf');
        if (PHP_VERSION_ID < 80100) {
            $setter->setAccessible(true);
        }

        $setter->invoke($this->object, new Pdf());

        $getter = new ReflectionMethod(ExportPdf::class, 'getPdf');
        if (PHP_VERSION_ID < 80100) {
            $getter->setAccessible(true);
        }

        self::assertInstanceOf(Pdf::class, $getter->invoke($this->object));
    }
}
