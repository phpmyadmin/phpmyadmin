<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Plugins\Export\ExportOdt;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
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
use PhpMyAdmin\Triggers\Event;
use PhpMyAdmin\Triggers\Timing;
use PhpMyAdmin\Triggers\Trigger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function bin2hex;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;

#[CoversClass(ExportOdt::class)]
#[Medium]
#[RequiresPhpExtension('zip')]
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
        DatabaseInterface::$instance = $this->dbi;
        Export::$outputKanjiConversion = false;
        Export::$outputCharsetConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = true;
        Export::$saveOnServer = false;
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;
        Config::getInstance()->selectedServer['DisableIS'] = true;
        $relation = new Relation($this->dbi);
        $this->object = new ExportOdt($relation, new Export($this->dbi), new Transformations($this->dbi, $relation));
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
        ExportPlugin::$exportType = ExportType::Raw;
        ExportPlugin::$singleTable = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::RELATION => 'relation',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::REL_WORK => true,
            RelationParameters::MIME_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $method = new ReflectionMethod(ExportOdt::class, 'setProperties');
        $properties = $method->invoke($this->object, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'OpenDocument Text',
            $properties->getText(),
        );

        self::assertSame(
            'odt',
            $properties->getExtension(),
        );

        self::assertSame(
            'application/vnd.oasis.opendocument.text',
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

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'structure',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Object creation options',
            $generalOptions->getText(),
        );

        self::assertSame(
            'data',
            $generalOptions->getForce(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'relation',
            $property->getName(),
        );

        self::assertSame(
            'Display foreign key relationships',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'comments',
            $property->getName(),
        );

        self::assertSame(
            'Display comments',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'mime',
            $property->getName(),
        );

        self::assertSame(
            'Display media types',
            $property->getText(),
        );

        // hide structure
        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'data',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Data dump options',
            $generalOptions->getText(),
        );

        self::assertSame(
            'structure',
            $generalOptions->getForce(),
        );

        $generalProperties = $generalOptions->getProperties();

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

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'null',
            $property->getName(),
        );

        self::assertSame(
            'Replace NULL with:',
            $property->getText(),
        );

        // case 2
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;

        $method->invoke($this->object, null);

        $generalOptionsArray = $options->getProperties();

        self::assertCount(3, $generalOptionsArray);
    }

    public function testExportHeader(): void
    {
        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertStringContainsString('<office:document-content', $this->object->buffer);
        self::assertStringContainsString('office:version', $this->object->buffer);
    }

    public function testExportFooter(): void
    {
        $this->object->buffer = 'header';
        self::assertTrue($this->object->exportFooter());
        $output = $this->getActualOutputForAssertion();
        self::assertMatchesRegularExpression('/^504b.*636f6e74656e742e786d6c/', bin2hex($output));
        self::assertStringContainsString('header', $this->object->buffer);
        self::assertStringContainsString(
            '</office:text></office:body></office:document-content>',
            $this->object->buffer,
        );
    }

    public function testExportDBHeader(): void
    {
        $this->object->buffer = 'header';

        self::assertTrue(
            $this->object->exportDBHeader('d&b'),
        );

        self::assertStringContainsString('header', $this->object->buffer);

        self::assertStringContainsString('Database d&amp;b</text:h>', $this->object->buffer);
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
            ->willReturn(4);

        $resultStub->expects(self::exactly(2))
            ->method('fetchRow')
            ->willReturn([null, 'a<b', 'a>b', 'a&b'], []);

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['odt_null' => '&']);

        $this->object->setExportOptions($request, []);

        self::assertTrue(
            $this->object->exportData(
                'db',
                'ta<ble',
                'SELECT',
            ),
        );

        self::assertSame(
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
            ->withParsedBody(['odt_columns' => 'On']);

        $this->object->setExportOptions($request, []);

        self::assertTrue(
            $this->object->exportData(
                'db',
                'table',
                'SELECT',
            ),
        );

        self::assertSame(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:' .
            'is-list-header="true">Dumping data for table table</text:h><table:' .
            'table table:name="table_structure"><table:table-column table:number-' .
            'columns-repeated="2"/><table:table-row><table:table-cell office:' .
            'value-type="string"><text:p>fna\&quot;me</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>fnam/&lt;e2' .
            '</text:p></table:table-cell></table:table-row></table:table>',
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

        self::assertTrue(
            $this->object->exportData(
                'db',
                'table',
                'SELECT',
            ),
        );

        self::assertSame(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table table</text:h>' .
            '<table:table table:name="table_structure"><table:table-column ' .
            'table:number-columns-repeated="0"/><table:table-row>' .
            '</table:table-row></table:table>',
            $this->object->buffer,
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->dummyDbi->addSelectDb('test_db');
        self::assertSame(
            $this->object->getTableDefStandIn('test_db', 'test_table'),
            '',
        );
        $this->dummyDbi->assertAllSelectsConsumed();

        self::assertSame(
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
            $this->object->buffer,
        );
    }

    public function testGetTableDef(): void
    {
        $relation = new Relation($this->dbi);
        $this->object = $this->getMockBuilder(ExportOdt::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->setConstructorArgs([$relation, new Export($this->dbi), new Transformations($this->dbi, $relation)])
            ->getMock();

        // case 1

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('fetchResult')
            ->willReturn(
                [],
                ['fieldname' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $column = new Column('fieldname', '', null, false, '', null, '', '', '');
        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn([$column]);

        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numRows')
            ->willReturn(1);

        $resultStub->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(['comment' => 'testComment']);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->object->relation = $relation;
        $this->object->transformations = new Transformations($dbi, $relation);

        $this->object->expects(self::exactly(2))
            ->method('formatOneColumnDefinition')
            ->with($column)
            ->willReturn('1');

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['odt_relation' => 'On', 'odt_mime' => 'On', 'odt_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        self::assertTrue($this->object->getTableDef('database', ''));

        self::assertStringContainsString(
            '<table:table table:name="_structure"><table:table-column table:number-columns-repeated="6"/>',
            $this->object->buffer,
        );

        self::assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Comments</text:p></table:table-cell>',
            $this->object->buffer,
        );

        self::assertStringContainsString(
            '<table:table-cell office:value-type="string"><text:p>Media type</text:p></table:table-cell>',
            $this->object->buffer,
        );

        self::assertStringContainsString(
            '</table:table-row>1<table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell office:value-' .
            'type="string"><text:p>Test&lt;</text:p></table:table-cell>' .
            '</table:table-row></table:table>',
            $this->object->buffer,
        );

        // case 2

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('fetchResult')
            ->willReturn(
                ['fieldname' => ['foreign_table' => 'ftable', 'foreign_field' => 'ffield']],
                ['field' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $column = new Column('fieldname', '', null, false, '', null, '', '', '');
        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn([$column]);

        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numRows')
            ->willReturn(1);

        $resultStub->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(['comment' => 'testComment']);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->object->relation = $relation;
        $this->object->transformations = new Transformations($dbi, $relation);
        $this->object->buffer = '';
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        self::assertTrue($this->object->getTableDef('database', ''));

        self::assertStringContainsString('<text:p>ftable (ffield)</text:p>', $this->object->buffer);
    }

    public function testGetTriggers(): void
    {
        $triggers = [
            new Trigger(
                TriggerName::from('tna"me'),
                Timing::After,
                Event::Insert,
                TableName::from('ta<ble'),
                'def',
                'test_user@localhost',
            ),
        ];

        $method = new ReflectionMethod(ExportOdt::class, 'getTriggers');
        $result = $method->invoke($this->object, 'ta<ble', $triggers);

        self::assertSame($result, $this->object->buffer);

        self::assertStringContainsString('<table:table table:name="ta&lt;ble_triggers">', $result);

        self::assertStringContainsString('<text:p>tna&quot;me</text:p>', $result);

        self::assertStringContainsString('<text:p>AFTER</text:p>', $result);

        self::assertStringContainsString('<text:p>INSERT</text:p>', $result);

        self::assertStringContainsString('<text:p>def</text:p>', $result);
    }

    public function testExportStructure(): void
    {
        // case 1
        $this->dummyDbi->addSelectDb('test_db');
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_table'));
        $this->dummyDbi->assertAllSelectsConsumed();

        self::assertSame(
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
            $this->object->buffer,
        );

        // case 2
        $this->object->buffer = '';

        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'triggers'));

        self::assertSame(
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
            $this->object->buffer,
        );

        // case 3
        $this->object->buffer = '';

        $this->dummyDbi->addSelectDb('test_db');
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_view'));
        $this->dummyDbi->assertAllSelectsConsumed();

        self::assertSame(
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
            $this->object->buffer,
        );

        // case 4
        $this->dummyDbi->addSelectDb('test_db');
        $this->object->buffer = '';
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'stand_in'));
        $this->dummyDbi->assertAllSelectsConsumed();

        self::assertSame(
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
            $this->object->buffer,
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(ExportOdt::class, 'formatOneColumnDefinition');

        $column = new Column('field', 'set(abc)enum123', null, true, 'PRI', null, '', '', '');

        $colAlias = 'alias';

        self::assertSame(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>alias</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>set(abc)</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>Yes' .
            '</text:p></table:table-cell><table:table-cell office:value-typ' .
            'e="string"><text:p>NULL</text:p></table:table-cell>',
            $method->invoke($this->object, $column, $colAlias),
        );

        $column = new Column('fields', '', null, false, 'COMP', 'def', '', '', '');

        self::assertSame(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>fields</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>&amp;nbsp;</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>No' .
            '</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"string"><text:p>def</text:p></table:table-cell>',
            $method->invoke($this->object, $column, ''),
        );
    }
}
