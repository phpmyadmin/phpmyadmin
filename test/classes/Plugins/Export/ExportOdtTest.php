<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\Export\ExportOdt;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use ReflectionMethod;
use stdClass;

use function __;
use function array_shift;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportOdt
 * @requires extension zip
 * @group medium
 */
class ExportOdtTest extends AbstractTestCase
{
    /** @var ExportOdt */
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
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $this->object = new ExportOdt();
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
        $GLOBALS['plugin_param']['export_type'] = '';
        $GLOBALS['plugin_param']['single_table'] = false;

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db',
            'relation' => 'relation',
            'column_info' => 'column_info',
            'relwork' => true,
            'mimework' => true,
        ]);
        $_SESSION = ['relation' => [$GLOBALS['server'] => $relationParameters->toArray()]];

        $method = new ReflectionMethod(ExportOdt::class, 'setProperties');
        $method->setAccessible(true);
        $properties = $method->invoke($this->object, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame('OpenDocument Text', $properties->getText());

        self::assertSame('odt', $properties->getExtension());

        self::assertSame('application/vnd.oasis.opendocument.text', $properties->getMimeType());

        self::assertSame('Options', $properties->getOptionsText());

        self::assertTrue($properties->getForceFile());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('general_opts', $generalOptions->getName());

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

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('structure', $generalOptions->getName());

        self::assertSame('Object creation options', $generalOptions->getText());

        self::assertSame('data', $generalOptions->getForce());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame('relation', $property->getName());

        self::assertSame('Display foreign key relationships', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame('comments', $property->getName());

        self::assertSame('Display comments', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame('mime', $property->getName());

        self::assertSame('Display media types', $property->getText());

        // hide structure
        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('data', $generalOptions->getName());

        self::assertSame('Data dump options', $generalOptions->getText());

        self::assertSame('structure', $generalOptions->getForce());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame('columns', $property->getName());

        self::assertSame('Put columns names in the first row', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame('null', $property->getName());

        self::assertSame('Replace NULL with:', $property->getText());

        // case 2
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;

        $method->invoke($this->object, null);

        $generalOptionsArray = $options->getProperties();

        self::assertCount(3, $generalOptionsArray);
    }

    public function testExportHeader(): void
    {
        self::assertTrue($this->object->exportHeader());

        self::assertStringContainsString('<office:document-content', $GLOBALS['odt_buffer']);
        self::assertStringContainsString('office:version', $GLOBALS['odt_buffer']);
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testExportFooter(): void
    {
        $GLOBALS['odt_buffer'] = 'header';

        $this->expectOutputRegex('/^504b.*636f6e74656e742e786d6c/');
        $this->setOutputCallback('bin2hex');

        self::assertTrue($this->object->exportFooter());

        self::assertStringContainsString('header', $GLOBALS['odt_buffer']);

        self::assertStringContainsString(
            '</office:text></office:body></office:document-content>',
            $GLOBALS['odt_buffer']
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['odt_buffer'] = 'header';

        self::assertTrue($this->object->exportDBHeader('d&b'));

        self::assertStringContainsString('header', $GLOBALS['odt_buffer']);

        self::assertStringContainsString('Database d&amp;b</text:h>', $GLOBALS['odt_buffer']);
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
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $flags[] = new FieldMetadata(-1, 0, $a);

        $a = new stdClass();
        $a->charsetnr = 63;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_BLOB, MYSQLI_BLOB_FLAG, $a);

        $flags[] = new FieldMetadata(MYSQLI_TYPE_DECIMAL, MYSQLI_NUM_FLAG, (object) []);

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
            ->will($this->returnValue(4));

        $resultStub->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    'a<b',
                    'a>b',
                    'a&b',
                ],
                []
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        unset($GLOBALS['foo_columns']);

        self::assertTrue($this->object->exportData(
            'db',
            'ta<ble',
            "\n",
            'example.com',
            'SELECT'
        ));

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" ' .
        'text:is-list-header="true">Dumping data for table ta&lt;ble</text:h>' .
        '<table:table table:name="ta&lt;ble_structure"><table:table-column ' .
        'table:number-columns-repeated="4"/><table:table-row>' .
        '<table:table-cell office:value-type="string"><text:p>&amp;</text:p>' .
        '</table:table-cell><table:table-cell office:value-type="string">' .
        '<text:p></text:p></table:table-cell><table:table-cell ' .
        'office:value-type="float" office:value="a>b" ><text:p>a&gt;b</text:p>' .
        '</table:table-cell><table:table-cell office:value-type="string">' .
        '<text:p>a&amp;b</text:p></table:table-cell></table:table-row>' .
        '</table:table>', $GLOBALS['odt_buffer']);
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
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        $GLOBALS['foo_columns'] = true;

        self::assertTrue($this->object->exportData(
            'db',
            'table',
            "\n",
            'example.com',
            'SELECT'
        ));

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" text:' .
        'is-list-header="true">Dumping data for table table</text:h><table:' .
        'table table:name="table_structure"><table:table-column table:number-' .
        'columns-repeated="2"/><table:table-row><table:table-cell office:' .
        'value-type="string"><text:p>fna&quot;me</text:p></table:table-cell>' .
        '<table:table-cell office:value-type="string"><text:p>fnam/&lt;e2' .
        '</text:p></table:table-cell></table:table-row></table:table>', $GLOBALS['odt_buffer']);

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
        $GLOBALS['odt_buffer'] = '';

        self::assertTrue($this->object->exportData(
            'db',
            'table',
            "\n",
            'example.com',
            'SELECT'
        ));

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" ' .
        'text:is-list-header="true">Dumping data for table table</text:h>' .
        '<table:table table:name="table_structure"><table:table-column ' .
        'table:number-columns-repeated="0"/><table:table-row>' .
        '</table:table-row></table:table>', $GLOBALS['odt_buffer']);
    }

    public function testGetTableDefStandIn(): void
    {
        $this->dummyDbi->addSelectDb('test_db');
        self::assertSame($this->object->getTableDefStandIn('test_db', 'test_table', "\n"), '');
        $this->assertAllSelectsConsumed();

        self::assertSame('<table:table table:name="test_table_data">'
        . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>Column</text:p>'
        . '</table:table-cell><table:table-cell office:value-type="string"><text:p>Type</text:p>'
        . '</table:table-cell><table:table-cell office:value-type="string"><text:p>Null</text:p>'
        . '</table:table-cell><table:table-cell office:value-type="string"><text:p>Default</text:p>'
        . '</table:table-cell></table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row></table:table>', $GLOBALS['odt_buffer']);
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportOdt::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->getMock();

        // case 1

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [],
                [
                    'fieldname' => [
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<',
                    ],
                ]
            );

        $columns = ['Field' => 'fieldname'];
        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numRows')
            ->will($this->returnValue(1));

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue(['comment' => 'testComment']));

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $this->object->expects($this->exactly(2))
            ->method('formatOneColumnDefinition')
            ->with(['Field' => 'fieldname'])
            ->will($this->returnValue(1));

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ])->toArray();

        self::assertTrue($this->object->getTableDef(
            'database',
            '',
            "\n",
            'example.com',
            true,
            true,
            true
        ));

        self::assertStringContainsString(
            '<table:table table:name="_structure"><table:table-column table:number-columns-repeated="6"/>',
            $GLOBALS['odt_buffer']
        );

        self::assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Comments</text:p></table:table-cell>',
            $GLOBALS['odt_buffer']
        );

        self::assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Media type</text:p></table:table-cell>',
            $GLOBALS['odt_buffer']
        );

        self::assertStringContainsString('</table:table-row>1<table:table-cell office:value-type="string">' .
        '<text:p></text:p></table:table-cell><table:table-cell office:value-' .
        'type="string"><text:p>Test&lt;</text:p></table:table-cell>' .
        '</table:table-row></table:table>', $GLOBALS['odt_buffer']);

        // case 2

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [
                    'fieldname' => [
                        'foreign_table' => 'ftable',
                        'foreign_field' => 'ffield',
                    ],
                ],
                [
                    'field' => [
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<',
                    ],
                ]
            );

        $columns = ['Field' => 'fieldname'];

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numRows')
            ->will($this->returnValue(1));

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue(['comment' => 'testComment']));

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);
        $GLOBALS['odt_buffer'] = '';
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ])->toArray();

        self::assertTrue($this->object->getTableDef(
            'database',
            '',
            "\n",
            'example.com',
            true,
            true,
            true
        ));

        self::assertStringContainsString('<text:p>ftable (ffield)</text:p>', $GLOBALS['odt_buffer']);
    }

    public function testGetTriggers(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $triggers = [
            [
                'name' => 'tna"me',
                'action_timing' => 'ac>t',
                'event_manipulation' => 'manip&',
                'definition' => 'def',
            ],
        ];

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('database', 'ta<ble')
            ->will($this->returnValue($triggers));

        $GLOBALS['dbi'] = $dbi;

        $method = new ReflectionMethod(ExportOdt::class, 'getTriggers');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'database', 'ta<ble');

        self::assertSame($result, $GLOBALS['odt_buffer']);

        self::assertStringContainsString('<table:table table:name="ta&lt;ble_triggers">', $result);

        self::assertStringContainsString('<text:p>tna&quot;me</text:p>', $result);

        self::assertStringContainsString('<text:p>ac&gt;t</text:p>', $result);

        self::assertStringContainsString('<text:p>manip&amp;</text:p>', $result);

        self::assertStringContainsString('<text:p>def</text:p>', $result);
    }

    public function testExportStructure(): void
    {
        // case 1
        $this->dummyDbi->addSelectDb('test_db');
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'create_table',
            'test'
        ));
        $this->assertAllSelectsConsumed();

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
        . 'Table structure for table test_table</text:h><table:table table:name="test_table_structure">'
        . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>Column</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Type</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Null</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Default</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row></table:table>', $GLOBALS['odt_buffer']);

        // case 2
        $GLOBALS['odt_buffer'] = '';

        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'triggers',
            'test'
        ));

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
        . 'Triggers test_table</text:h><table:table table:name="test_table_triggers">'
        . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>Name</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Time</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Event</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Definition</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>test_trigger</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>AFTER</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>INSERT</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>BEGIN END</text:p></table:table-cell>'
        . '</table:table-row></table:table>', $GLOBALS['odt_buffer']);

        // case 3
        $GLOBALS['odt_buffer'] = '';

        $this->dummyDbi->addSelectDb('test_db');
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'create_view',
            'test'
        ));
        $this->assertAllSelectsConsumed();

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
        . 'Structure for view test_table</text:h><table:table table:name="test_table_structure">'
        . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>Column</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Type</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Null</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Default</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row></table:table>', $GLOBALS['odt_buffer']);

        // case 4
        $this->dummyDbi->addSelectDb('test_db');
        $GLOBALS['odt_buffer'] = '';
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'stand_in',
            'test'
        ));
        $this->assertAllSelectsConsumed();

        self::assertSame('<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
        . 'Stand-in structure for view test_table</text:h><table:table table:name="test_table_data">'
        . '<table:table-column table:number-columns-repeated="4"/><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>Column</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Type</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Null</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>Default</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>id</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>int(11)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>name</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>varchar(20)</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row><table:table-row>'
        . '<table:table-cell office:value-type="string"><text:p>datetimefield</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>datetime</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>No</text:p></table:table-cell>'
        . '<table:table-cell office:value-type="string"><text:p>NULL</text:p></table:table-cell>'
        . '</table:table-row></table:table>', $GLOBALS['odt_buffer']);
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(ExportOdt::class, 'formatOneColumnDefinition');
        $method->setAccessible(true);

        $cols = [
            'Null' => 'Yes',
            'Field' => 'field',
            'Key' => 'PRI',
            'Type' => 'set(abc)enum123',
        ];

        $col_alias = 'alias';

        self::assertSame('<table:table-row><table:table-cell office:value-type="string">' .
        '<text:p>alias</text:p></table:table-cell><table:table-cell off' .
        'ice:value-type="string"><text:p>set(abc)</text:p></table:table' .
        '-cell><table:table-cell office:value-type="string"><text:p>Yes' .
        '</text:p></table:table-cell><table:table-cell office:value-typ' .
        'e="string"><text:p>NULL</text:p></table:table-cell>', $method->invoke($this->object, $cols, $col_alias));

        $cols = [
            'Null' => 'NO',
            'Field' => 'fields',
            'Key' => 'COMP',
            'Type' => '',
            'Default' => 'def',
        ];

        self::assertSame('<table:table-row><table:table-cell office:value-type="string">' .
        '<text:p>fields</text:p></table:table-cell><table:table-cell off' .
        'ice:value-type="string"><text:p>&amp;nbsp;</text:p></table:table' .
        '-cell><table:table-cell office:value-type="string"><text:p>No' .
        '</text:p></table:table-cell><table:table-cell office:value-type=' .
        '"string"><text:p>def</text:p></table:table-cell>', $method->invoke($this->object, $cols, ''));
    }
}
