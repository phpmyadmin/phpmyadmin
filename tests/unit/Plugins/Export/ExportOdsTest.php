<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

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
class ExportOdsTest extends AbstractTestCase
{
    protected ExportOds $object;

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
        $this->object = new ExportOds($relation, new OutputHandler(), new Transformations($dbi, $relation));
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        DatabaseInterface::$instance = null;
        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportOds::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportOds::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

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
            'general_opts',
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
        $this->object->buffer = '';
        $this->object->exportHeader();
        self::assertStringStartsWith(
            '<?xml version="1.0" encoding="utf-8"?><office:document-content',
            $this->object->buffer,
        );
    }

    public function testExportFooter(): void
    {
        $this->object->buffer = 'header';
        $this->object->exportFooter();
        $output = $this->getActualOutputForAssertion();
        self::assertMatchesRegularExpression('/^504b.*636f6e74656e742e786d6c/', bin2hex($output));
        self::assertStringContainsString('header', $this->object->buffer);
        self::assertStringContainsString('</office:spreadsheet>', $this->object->buffer);
        self::assertStringContainsString('</office:body>', $this->object->buffer);
        self::assertStringContainsString('</office:document-content>', $this->object->buffer);
    }

    public function testExportDBHeader(): void
    {
        $this->expectNotToPerformAssertions();
        $this->object->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $this->expectNotToPerformAssertions();
        $this->object->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->object->exportDBCreate('testDB');
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

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['ods_null' => '&']);

        $this->object->setExportOptions($request, []);

        $this->object->exportData('db', 'table', 'SELECT');

        self::assertSame(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>c</text:p></table:table-cell><table:' .
            'table-cell office:value-type="string"><text:p>c</text:p></table:' .
            'table-cell><table:table-cell office:value-type="string"><text:p>c</text:' .
            'p></table:table-cell><table:table-cell office:value-type="string"><text:' .
            'p>c</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"string"><text:p>c</text:p></table:table-cell><table:table-cell office:' .
            'value-type="string"><text:p>c</text:p></table:table-cell><table:table-' .
            'cell office:value-type="string"><text:p>c</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>c</text:p></table:' .
            'table-cell></table:table-row><table:table-row><table:table-cell ' .
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
            $this->object->buffer,
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

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['ods_columns' => 'On']);

        $this->object->setExportOptions($request, []);

        $this->object->exportData('db', 'table', 'SELECT');

        self::assertSame(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>fna\&quot;me</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>' .
            'fnam/&lt;e2</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $this->object->buffer,
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

        DatabaseInterface::$instance = $dbi;
        $this->object->buffer = '';

        $this->object->exportData('db', 'table', 'SELECT');

        self::assertSame(
            '<table:table table:name="table"><table:table-row></table:table-row></table:table>',
            $this->object->buffer,
        );
    }
}
