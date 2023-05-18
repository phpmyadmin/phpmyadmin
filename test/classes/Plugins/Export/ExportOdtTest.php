<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportOdt;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use ReflectionClass;
use ReflectionMethod;

use function __;
use function bin2hex;

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
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected ExportOdt $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
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
        $this->object = new ExportOdt(
            new Relation($GLOBALS['dbi']),
            new Export($GLOBALS['dbi']),
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
        $properties = $method->invoke($this->object, null);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

        $this->assertEquals(
            'OpenDocument Text',
            $properties->getText(),
        );

        $this->assertEquals(
            'odt',
            $properties->getExtension(),
        );

        $this->assertEquals(
            'application/vnd.oasis.opendocument.text',
            $properties->getMimeType(),
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText(),
        );

        $this->assertTrue(
            $properties->getForceFile(),
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $this->assertEquals(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName(),
        );

        $this->assertEquals(
            'Dump table',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        $this->assertInstanceOf(RadioPropertyItem::class, $property);

        $this->assertEquals(
            'structure_or_data',
            $property->getName(),
        );

        $this->assertEquals(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
            $property->getValues(),
        );

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'structure',
            $generalOptions->getName(),
        );

        $this->assertEquals(
            'Object creation options',
            $generalOptions->getText(),
        );

        $this->assertEquals(
            'data',
            $generalOptions->getForce(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'relation',
            $property->getName(),
        );

        $this->assertEquals(
            'Display foreign key relationships',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'comments',
            $property->getName(),
        );

        $this->assertEquals(
            'Display comments',
            $property->getText(),
        );

        $property = $generalProperties->current();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'mime',
            $property->getName(),
        );

        $this->assertEquals(
            'Display media types',
            $property->getText(),
        );

        // hide structure
        $generalOptions = $generalOptionsArray->current();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'data',
            $generalOptions->getName(),
        );

        $this->assertEquals(
            'Data dump options',
            $generalOptions->getText(),
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getForce(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'columns',
            $property->getName(),
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText(),
        );

        $property = $generalProperties->current();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'null',
            $property->getName(),
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText(),
        );

        // case 2
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;

        $method->invoke($this->object, null);

        $generalOptionsArray = $options->getProperties();

        $this->assertCount(3, $generalOptionsArray);
    }

    public function testExportHeader(): void
    {
        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertStringContainsString('<office:document-content', $GLOBALS['odt_buffer']);
        $this->assertStringContainsString('office:version', $GLOBALS['odt_buffer']);
    }

    public function testExportFooter(): void
    {
        $GLOBALS['odt_buffer'] = 'header';
        $this->assertTrue($this->object->exportFooter());
        $output = $this->getActualOutputForAssertion();
        $this->assertMatchesRegularExpression('/^504b.*636f6e74656e742e786d6c/', bin2hex($output));
        $this->assertStringContainsString('header', $GLOBALS['odt_buffer']);
        $this->assertStringContainsString(
            '</office:text></office:body></office:document-content>',
            $GLOBALS['odt_buffer'],
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['odt_buffer'] = 'header';

        $this->assertTrue(
            $this->object->exportDBHeader('d&b'),
        );

        $this->assertStringContainsString('header', $GLOBALS['odt_buffer']);

        $this->assertStringContainsString('Database d&amp;b</text:h>', $GLOBALS['odt_buffer']);
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB'),
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database'),
        );
    }

    public function testExportData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fields = [
            FieldHelper::fromArray(['type' => -1]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_BLOB,
                'flags' => MYSQLI_BLOB_FLAG,
                'charsetnr' => 63,
            ]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_DECIMAL, 'flags' => MYSQLI_NUM_FLAG]),
            FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING]),
        ];
        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($fields));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numFields')
            ->will($this->returnValue(4));

        $resultStub->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [null, 'a<b', 'a>b', 'a&b'],
                [],
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = '&';
        unset($GLOBALS['foo_columns']);

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'ta<ble',
                'example.com',
                'SELECT',
            ),
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table ta&lt;ble</text:h>' .
            '<table:table table:name="ta&lt;ble_structure"><table:table-column ' .
            'table:number-columns-repeated="4"/><table:table-row>' .
            '<table:table-cell office:value-type="string"><text:p>&amp;</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell ' .
            'office:value-type="float" office:value="a>b" ><text:p>a&gt;b</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="string">' .
            '<text:p>a&amp;b</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $GLOBALS['odt_buffer'],
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

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($fields));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED)
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

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                'example.com',
                'SELECT',
            ),
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:' .
            'is-list-header="true">Dumping data for table table</text:h><table:' .
            'table table:name="table_structure"><table:table-column table:number-' .
            'columns-repeated="2"/><table:table-row><table:table-cell office:' .
            'value-type="string"><text:p>fna\&quot;me</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>fnam/&lt;e2' .
            '</text:p></table:table-cell></table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
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
            ->with('SELECT', Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED)
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

        $this->assertTrue(
            $this->object->exportData(
                'db',
                'table',
                'example.com',
                'SELECT',
            ),
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table table</text:h>' .
            '<table:table table:name="table_structure"><table:table-column ' .
            'table:number-columns-repeated="0"/><table:table-row>' .
            '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->dummyDbi->addSelectDb('test_db');
        $this->assertSame(
            $this->object->getTableDefStandIn('test_db', 'test_table'),
            '',
        );
        $this->dummyDbi->assertAllSelectsConsumed();

        $this->assertEquals(
            '<table:table table:name="test_table_data">'
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
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportOdt::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->setConstructorArgs([new Relation($GLOBALS['dbi']), new Export($GLOBALS['dbi']), new Transformations()])
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
                ['fieldname' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
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
            ->will($this->returnValue('1'));

        $relationParameters = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $this->assertTrue(
            $this->object->getTableDef(
                'database',
                '',
                true,
                true,
                true,
            ),
        );

        $this->assertStringContainsString(
            '<table:table table:name="_structure"><table:table-column table:number-columns-repeated="6"/>',
            $GLOBALS['odt_buffer'],
        );

        $this->assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Comments</text:p></table:table-cell>',
            $GLOBALS['odt_buffer'],
        );

        $this->assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Media type</text:p></table:table-cell>',
            $GLOBALS['odt_buffer'],
        );

        $this->assertStringContainsString(
            '</table:table-row>1<table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell office:value-' .
            'type="string"><text:p>Test&lt;</text:p></table:table-cell>' .
            '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );

        // case 2

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                ['fieldname' => ['foreign_table' => 'ftable', 'foreign_field' => 'ffield']],
                ['field' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
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
        $relationParameters = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $this->assertTrue(
            $this->object->getTableDef(
                'database',
                '',
                true,
                true,
                true,
            ),
        );

        $this->assertStringContainsString('<text:p>ftable (ffield)</text:p>', $GLOBALS['odt_buffer']);
    }

    public function testGetTriggers(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $triggers = [
            [
                'TRIGGER_SCHEMA' => 'database',
                'TRIGGER_NAME' => 'tna"me',
                'EVENT_MANIPULATION' => 'manip&',
                'EVENT_OBJECT_TABLE' => 'ta<ble',
                'ACTION_TIMING' => 'ac>t',
                'ACTION_STATEMENT' => 'def',
                'EVENT_OBJECT_SCHEMA' => 'database',
                'DEFINER' => 'test_user@localhost',
            ],
        ];

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls($triggers);

        $GLOBALS['dbi'] = $dbi;

        $method = new ReflectionMethod(ExportOdt::class, 'getTriggers');
        $result = $method->invoke($this->object, 'database', 'ta<ble');

        $this->assertSame($result, $GLOBALS['odt_buffer']);

        $this->assertStringContainsString('<table:table table:name="ta&lt;ble_triggers">', $result);

        $this->assertStringContainsString('<text:p>tna&quot;me</text:p>', $result);

        $this->assertStringContainsString('<text:p>ac&gt;t</text:p>', $result);

        $this->assertStringContainsString('<text:p>manip&amp;</text:p>', $result);

        $this->assertStringContainsString('<text:p>def</text:p>', $result);
    }

    public function testExportStructure(): void
    {
        // case 1
        $this->dummyDbi->addSelectDb('test_db');
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'create_table',
                'test',
            ),
        );
        $this->dummyDbi->assertAllSelectsConsumed();

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
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
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );

        // case 2
        $GLOBALS['odt_buffer'] = '';

        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'triggers',
                'test',
            ),
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
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
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );

        // case 3
        $GLOBALS['odt_buffer'] = '';

        $this->dummyDbi->addSelectDb('test_db');
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'create_view',
                'test',
            ),
        );
        $this->dummyDbi->assertAllSelectsConsumed();

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
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
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );

        // case 4
        $this->dummyDbi->addSelectDb('test_db');
        $GLOBALS['odt_buffer'] = '';
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'stand_in',
                'test',
            ),
        );
        $this->dummyDbi->assertAllSelectsConsumed();

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is-list-header="true">'
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
            . '</table:table-row></table:table>',
            $GLOBALS['odt_buffer'],
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(ExportOdt::class, 'formatOneColumnDefinition');

        $cols = ['Null' => 'Yes', 'Field' => 'field', 'Key' => 'PRI', 'Type' => 'set(abc)enum123'];

        $colAlias = 'alias';

        $this->assertEquals(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>alias</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>set(abc)</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>Yes' .
            '</text:p></table:table-cell><table:table-cell office:value-typ' .
            'e="string"><text:p>NULL</text:p></table:table-cell>',
            $method->invoke($this->object, $cols, $colAlias),
        );

        $cols = ['Null' => 'NO', 'Field' => 'fields', 'Key' => 'COMP', 'Type' => '', 'Default' => 'def'];

        $this->assertEquals(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>fields</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>&amp;nbsp;</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>No' .
            '</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"string"><text:p>def</text:p></table:table-cell>',
            $method->invoke($this->object, $cols, ''),
        );
    }
}
