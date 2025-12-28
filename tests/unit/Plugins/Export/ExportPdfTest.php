<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportPdf;
use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Table\Table;
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
        OutputHandler::$asFile = true;
        $relation = new Relation($dbi);
        $this->object = new ExportPdf($relation, new OutputHandler(), new Transformations($dbi, $relation));
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

    public function testSetExportOptions(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        $this->object->setExportOptions($request, []);

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $pdf = $attrPdf->getValue($this->object);
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

    
    public function testExportStructure(): void
    {
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Verify all setup methods are called
        $pdf->expects(self::once())
            ->method('setCurrentDb')
            ->with('db');

        $pdf->expects(self::once())
            ->method('setCurrentTable')
            ->with('table');

        $pdf->expects(self::once())
            ->method('setDbAlias')
            ->with('db'); // getDbAlias returns 'db' when no alias is set

        $pdf->expects(self::once())
            ->method('setTableAlias')
            ->with('table'); // getTableAlias returns 'table' when no alias is set

        $pdf->expects(self::once())
            ->method('setAliases')
            ->with([]);

        $pdf->expects(self::once())
            ->method('setPurpose')
            ->with('Table structure');

        $pdf->expects(self::once())
            ->method('getTableDef')
            ->with('db', 'table', false, true, false);

        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($this->object, $pdf);

        self::assertTrue(
            $this->object->exportStructure(
                'db',
                'table',
                'create_table',
            ),
        );
    }


    /**
     * Integration test: Export table structure through Export::exportTable()
     * their exportStructure() method called when exporting through Export::exportTable()
     */
    public function testExportTableStructureThroughExportCore(): void
    {
        // Mock the database interface
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock Table class to return isView = false
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $table->method('isView')->willReturn(false);

        $dbi->method('getTable')->willReturn($table);
        DatabaseInterface::$instance = $dbi;

        // Create a mock PDF that we can verify methods are called on
        $pdf = $this->getMockBuilder(Pdf::class)
            ->disableOriginalConstructor()
            ->getMock();

        // getTableDef should be called
        $pdf->expects(self::once())
            ->method('getTableDef')
            ->with('testdb', 'testtable', false, true, false);

        // Set up the PDF in our export plugin
        $attrPdf = new ReflectionProperty(ExportPdf::class, 'pdf');
        $attrPdf->setValue($this->object, $pdf);

        // Force structureOrData to be StructureAndData so structure export is attempted
        $attrStructureOrData = new ReflectionProperty(ExportPdf::class, 'structureOrData');
        $attrStructureOrData->setValue($this->object, StructureOrData::StructureAndData);

        // Now call exportTable through the Export class
        $exportcore = new Export($dbi, new OutputHandler());
        $exportcore->exportTable(
            'testdb',
            'testtable',
            $this->object,
            null,
            '0',
            '0',
            '',
            []
        );
    }


}
