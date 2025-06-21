<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Plugins\Export\ExportPdf;
use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function __;

#[CoversClass(ExportPdf::class)]
#[Medium]
class ExportPdfTest extends AbstractTestCase
{
    protected ExportPdf $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        Export::$outputKanjiConversion = false;
        Export::$outputCharsetConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = true;
        Export::$saveOnServer = false;
        $relation = new Relation($dbi);
        $this->object = new ExportPdf($relation, new Export($dbi), new Transformations($dbi, $relation));
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
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportPdf::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'PDF',
            $properties->getText(),
        );

        self::assertSame(
            'pdf',
            $properties->getExtension(),
        );

        self::assertSame(
            'application/pdf',
            $properties->getMimeType(),
        );

        self::assertSame(
            'Options',
            $properties->getOptionsText(),
        );

        self::assertTrue(
            $properties->getForceFile(),
        );

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'report_title',
            $property->getName(),
        );

        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'dump_what',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Dump table',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(RadioPropertyItem::class, $property);

        self::assertSame(
            'structure_or_data',
            $property->getName(),
        );

        self::assertSame(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
            $property->getValues(),
        );
    }

    public function testExportHeader(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects(self::once())
            ->method('Open');

        $pdf->expects(self::once())
            ->method('setTopMargin');

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue(
            $this->object->exportHeader(),
        );
    }

    public function testExportFooter(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects(self::once())
            ->method('getPDFData')
            ->willReturn('');

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue(
            $this->object->exportFooter(),
        );
    }

    public function testExportDBHeader(): void
    {
        self::assertTrue(
            $this->object->exportDBHeader('testDB'),
        );
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue(
            $this->object->exportDBFooter('testDB'),
        );
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue(
            $this->object->exportDBCreate('testDB'),
        );
    }

    public function testExportData(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects(self::once())
            ->method('mysqlReport')
            ->with('SELECT');

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue(
            $this->object->exportData(
                'db',
                'table',
                'SELECT',
            ),
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::setPdf
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::getPdf
     */
    public function testSetGetPdf(): void
    {
        $setter = new ReflectionMethod(ExportPdf::class, 'setPdf');
        $setter->invoke($this->object, new Pdf());

        $getter = new ReflectionMethod(ExportPdf::class, 'getPdf');
        self::assertInstanceOf(
            Pdf::class,
            $getter->invoke($this->object),
        );
    }
}
