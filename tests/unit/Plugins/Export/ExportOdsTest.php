<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportOds;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use ReflectionMethod;
use ReflectionProperty;

use function bin2hex;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TIME;
use const MYSQLI_TYPE_TINY_BLOB;

#[CoversClass(ExportOds::class)]
#[Medium]
#[RequiresPhpExtension('zip')]
final class ExportOdsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
    }

    public function testSetProperties(): void
    {
        $exportOds = $this->getExportOds();

        $method = new ReflectionMethod(ExportOds::class, 'setProperties');
        $method->invoke($exportOds, null);

        $attrProperties = new ReflectionProperty(ExportOds::class, 'properties');
        $properties = $attrProperties->getValue($exportOds);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'OpenDocument Spreadsheet',
            $properties->getText(),
        );

        self::assertSame(
            'ods',
            $properties->getExtension(),
        );

        self::assertSame(
            'application/vnd.oasis.opendocument.spreadsheet',
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

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'ods_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'null',
            $property->getName(),
        );

        self::assertSame(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'columns',
            $property->getName(),
        );

        self::assertSame(
            'Put columns names in the first row',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame(
            'structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        $exportOds = $this->getExportOds();
        $exportOds->buffer = '';
        $exportOds->exportHeader();
        self::assertStringStartsWith(
            '<?xml version="1.0" encoding="utf-8"?><office:document-content',
            $exportOds->buffer,
        );
    }

    public function testExportFooter(): void
    {
        $exportOds = $this->getExportOds();
        $exportOds->buffer = 'header';
        $exportOds->exportFooter();
        $output = $this->getActualOutputForAssertion();
        self::assertMatchesRegularExpression('/^504b.*636f6e74656e742e786d6c/', bin2hex($output));
        self::assertStringContainsString('header', $exportOds->buffer);
        self::assertStringContainsString('</office:spreadsheet>', $exportOds->buffer);
        self::assertStringContainsString('</office:body>', $exportOds->buffer);
        self::assertStringContainsString('</office:document-content>', $exportOds->buffer);
    }

    public function testExportDBHeader(): void
    {
        $exportOds = $this->getExportOds();
        $this->expectNotToPerformAssertions();
        $exportOds->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $exportOds = $this->getExportOds();
        $this->expectNotToPerformAssertions();
        $exportOds->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportOds = $this->getExportOds();
        $this->expectNotToPerformAssertions();
        $exportOds->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fields = [
            FieldHelper::fromArray(['type' => -1]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_TINY_BLOB,
                'flags' => MYSQLI_BLOB_FLAG,
                'charsetnr' => 63,
            ]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_DATE]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_TIME]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_DATETIME]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_DECIMAL]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_DECIMAL]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING]),
        ];
        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn($fields);

        $dbi->expects(self::once())
            ->method('query')
            ->with('SELECT', ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED)
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numFields')
            ->willReturn(8);

        $resultStub->expects(self::exactly(2))
            ->method('fetchRow')
            ->willReturn(
                [null, '01-01-2000', '01-01-2000', '01-01-2000 10:00:00', '01-01-2014 10:02:00', 't>s', 'a&b', '<'],
                [],
            );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['ods_null' => '&']);

        $exportOds = $this->getExportOds($dbi);
        $exportOds->setExportOptions($request, new Export());

        $exportOds->exportData('db', 'table', 'SELECT');

        self::assertSame(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>&amp;</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p></text:p>' .
            '</table:table-cell><table:table-cell office:value-type="date" office:' .
            'date-value="2000-01-01" table:style-name="DateCell"><text:p>01-01' .
            '-2000</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"time" office:time-value="PT10H00M00S" table:style-name="TimeCell">' .
            '<text:p>01-01-2000 10:00:00</text:p></table:table-cell><table:table-' .
            'cell office:value-type="date" office:date-value="2014-01-01T10:02:00"' .
            ' table:style-name="DateTimeCell"><text:p>01-01-2014 10:02:00' .
            '</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"float" office:value="t>s" ><text:p>t&gt;s</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="float" ' .
            'office:value="a&b" ><text:p>a&amp;b</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>&lt;</text:p>' .
            '</table:table-cell></table:table-row></table:table>',
            $exportOds->buffer,
        );
    }

    public function testExportDataWithFieldNames(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fields = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'fna\"me',
                'length' => 20,
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'fnam/<e2',
                'length' => 20,
            ]),
        ];

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn($fields);

        $dbi->expects(self::once())
            ->method('query')
            ->with('SELECT', ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED)
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numFields')
            ->willReturn(2);

        $resultStub->expects(self::exactly(1))
            ->method('fetchRow')
            ->willReturn([]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['ods_columns' => 'On']);

        $exportOds = $this->getExportOds($dbi);
        $exportOds->setExportOptions($request, new Export());

        $exportOds->exportData('db', 'table', 'SELECT');

        self::assertSame(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>fna\&quot;me</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>' .
            'fnam/&lt;e2</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $exportOds->buffer,
        );

        // with no row count
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn($flags);

        $dbi->expects(self::once())
            ->method('query')
            ->with('SELECT', ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED)
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numFields')
            ->willReturn(0);

        $resultStub->expects(self::once())
            ->method('fetchRow')
            ->willReturn([]);

        $exportOds = $this->getExportOds($dbi);
        $exportOds->setExportOptions($request, new Export());
        $exportOds->buffer = '';

        $exportOds->exportData('db', 'table', 'SELECT');

        self::assertSame(
            '<table:table table:name="table"><table:table-row></table:table-row></table:table>',
            $exportOds->buffer,
        );
    }

    private function getExportOds(DatabaseInterface|null $dbi = null): ExportOds
    {
        $dbi ??= $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportOds($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
