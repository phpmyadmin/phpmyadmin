<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\Export\ExportOds;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

use function array_shift;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TIME;
use const MYSQLI_TYPE_TINY_BLOB;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportOds
 * @requires extension zip
 * @group medium
 */
class ExportOdsTest extends AbstractTestCase
{
    /** @var ExportOds */
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
        $this->object = new ExportOds();
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
        $method = new ReflectionMethod(ExportOds::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportOds::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

        $this->assertEquals(
            'OpenDocument Spreadsheet',
            $properties->getText()
        );

        $this->assertEquals(
            'ods',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/vnd.oasis.opendocument.spreadsheet',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $this->assertTrue(
            $properties->getForceFile()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'null',
            $property->getName()
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(HiddenPropertyItem::class, $property);

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );
    }

    public function testExportHeader(): void
    {
        $this->assertArrayHasKey('ods_buffer', $GLOBALS);

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testExportFooter(): void
    {
        $GLOBALS['ods_buffer'] = 'header';

        $this->expectOutputRegex('/^504b.*636f6e74656e742e786d6c/');
        $this->setOutputCallback('bin2hex');

        $this->assertTrue(
            $this->object->exportFooter()
        );

        $this->assertStringContainsString('header', $GLOBALS['ods_buffer']);

        $this->assertStringContainsString('</office:spreadsheet>', $GLOBALS['ods_buffer']);

        $this->assertStringContainsString('</office:body>', $GLOBALS['ods_buffer']);

        $this->assertStringContainsString('</office:document-content>', $GLOBALS['ods_buffer']);
    }

    public function testExportDBHeader(): void
    {
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    public function testExportData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $flags[] = new FieldMetadata(-1, 0, (object) []);

        $a = new stdClass();
        $a->charsetnr = 63;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_TINY_BLOB, MYSQLI_BLOB_FLAG, $a);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_DATE, 0, (object) []);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_TIME, 0, (object) []);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_DATETIME, 0, (object) []);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_DECIMAL, 0, (object) []);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_DECIMAL, 0, (object) []);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) []);

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numFields')
            ->will($this->returnValue(8));

        $resultStub->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    '01-01-2000',
                    '01-01-2000',
                    '01-01-2000 10:00:00',
                    '01-01-2014 10:02:00',
                    't>s',
                    'a&b',
                    '<',
                ],
                []
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                "\n",
                'example.com',
                'SELECT'
            )
        );

        $this->assertEquals(
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
            $GLOBALS['ods_buffer']
        );
    }

    public function testExportDataWithFieldNames(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->name = 'fna\"me';
        $a->length = 20;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $a);
        $b = new stdClass();
        $b->name = 'fnam/<e2';
        $b->length = 20;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $b);

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numFields')
            ->will($this->returnValue(2));

        $resultStub->expects($this->exactly(1))
            ->method('fetchRow')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        $GLOBALS['foo_columns'] = true;

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                "\n",
                'example.com',
                'SELECT'
            )
        );

        $this->assertEquals(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>fna&quot;me</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>' .
            'fnam/&lt;e2</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $GLOBALS['ods_buffer']
        );

        // with no row count
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numFields')
            ->will($this->returnValue(0));

        $resultStub->expects($this->once())
            ->method('fetchRow')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        $GLOBALS['ods_buffer'] = '';

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                "\n",
                'example.com',
                'SELECT'
            )
        );

        $this->assertEquals(
            '<table:table table:name="table"><table:table-row></table:table-row></table:table>',
            $GLOBALS['ods_buffer']
        );
    }
}
