<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
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
final class ExportPdfTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
    }

    public function testSetProperties(): void
    {
        $exportPdf = $this->getExportPdf();

        $method = new ReflectionMethod(ExportPdf::class, 'setProperties');
        $method->invoke($exportPdf, null);

        $attrProperties = new ReflectionProperty(ExportPdf::class, 'properties');
        $properties = $attrProperties->getValue($exportPdf);

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
            'pdf_general_opts',
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
            'pdf_dump_what',
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

    public function testSetExportOptions(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        $exportPdf = $this->getExportPdf();
        $exportPdf->setExportOptions($request, new Export());

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $pdf = $attrPdf->getValue($exportPdf);
        self::assertInstanceOf(Pdf::class, $pdf);
    }

    public function testExportFooter(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects(self::once())
            ->method('getPDFData')
            ->willReturn('');

        $exportPdf = $this->getExportPdf();

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($exportPdf, $pdf);

        $exportPdf->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportPdf = $this->getExportPdf();
        $this->expectNotToPerformAssertions();
        $exportPdf->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $exportPdf = $this->getExportPdf();
        $this->expectNotToPerformAssertions();
        $exportPdf->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportPdf = $this->getExportPdf();
        $this->expectNotToPerformAssertions();
        $exportPdf->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $dbi = $this->createDatabaseInterface();

        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects(self::once())
            ->method('mysqlReport')
            ->with($dbi, 'SELECT');

        $exportPdf = $this->getExportPdf($dbi);

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($exportPdf, $pdf);

        $exportPdf->exportData('db', 'table', 'SELECT');
    }

    private function getExportPdf(DatabaseInterface|null $dbi = null): ExportPdf
    {
        $dbi ??= $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportPdf($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
