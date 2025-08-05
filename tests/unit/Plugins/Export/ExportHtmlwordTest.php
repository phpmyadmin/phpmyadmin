<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Plugins\Export\ExportHtmlword;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Triggers\Event;
use PhpMyAdmin\Triggers\Timing;
use PhpMyAdmin\Triggers\Trigger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportHtmlword::class)]
#[Medium]
class ExportHtmlwordTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected ExportHtmlword $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $relation = new Relation($this->dbi);
        $this->object = new ExportHtmlword(
            $relation,
            new Export($this->dbi),
            new Transformations($this->dbi, $relation),
        );
        Export::$outputKanjiConversion = false;
        Export::$outputCharsetConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = true;
        Export::$saveOnServer = false;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = '';
        Config::getInstance()->selectedServer['DisableIS'] = true;
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
        $method = new ReflectionMethod(ExportHtmlword::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportHtmlword::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'Microsoft Word 2000',
            $properties->getText(),
        );

        self::assertSame(
            'doc',
            $properties->getExtension(),
        );

        self::assertSame(
            'application/vnd.ms-word',
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

        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'dump_what',
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

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'columns',
            $property->getName(),
        );

        self::assertSame(
            'Put columns names in the first row',
            $property->getText(),
        );
    }

    public function testExportHeader(): void
    {
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . 'utf-8" />
            </head>
            <body>';

        self::assertSame($expected, $result);

        // case 2

        Current::$charset = 'ISO-8859-1';
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . 'ISO-8859-1" />
            </head>
            <body>';

        self::assertSame($expected, $result);
    }

    public function testExportFooter(): void
    {
        ob_start();
        self::assertTrue(
            $this->object->exportFooter(),
        );
        $result = ob_get_clean();

        self::assertSame('</body></html>', $result);
    }

    public function testExportDBHeader(): void
    {
        ob_start();
        self::assertTrue(
            $this->object->exportDBHeader('d"b'),
        );
        $result = ob_get_clean();

        self::assertSame('<h1>Database d&quot;b</h1>', $result);
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
        // case 1
        Export::$outputKanjiConversion = false;
        Export::$outputCharsetConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = true;
        Export::$saveOnServer = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['htmlword_columns' => 'On']);

        $this->object->setExportOptions($request, []);

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            '<h2>Dumping data for table test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<td class="print"><strong>id</strong></td>'
            . '<td class="print"><strong>name</strong></td>'
            . '<td class="print"><strong>datetimefield</strong></td>'
            . '</tr><tr class="print-category">'
            . '<td class="print">1</td><td class="print">abcd</td><td class="print">2011-01-20 02:00:02</td>'
            . '</tr><tr class="print-category">'
            . '<td class="print">2</td><td class="print">foo</td><td class="print">2010-01-20 02:00:02</td>'
            . '</tr><tr class="print-category">'
            . '<td class="print">3</td><td class="print">Abcd</td><td class="print">2012-01-20 02:00:02</td>'
            . '</tr></table>',
            $result,
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->object = $this->getMockBuilder(ExportHtmlword::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->disableOriginalConstructor()
            ->getMock();

        // case 1

        $keys = [['Non_unique' => 0, 'Column_name' => 'name1'], ['Non_unique' => 1, 'Column_name' => 'name2']];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', 'view')
            ->willReturn($keys);

        $column = new Column('column', '', null, false, '', null, '', '', '');

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', 'view')
            ->willReturn([$column]);

        DatabaseInterface::$instance = $dbi;

        $this->object->expects(self::once())
            ->method('formatOneColumnDefinition')
            ->with($column, ['name1'], 'column')
            ->willReturn('1');

        self::assertSame(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>' .
            '1</tr></table>',
            $this->object->getTableDefStandIn('database', 'view'),
        );
    }

    public function testGetTableDef(): void
    {
        $relation = new Relation($this->dbi);
        $this->object = $this->getMockBuilder(ExportHtmlword::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->setConstructorArgs([$relation, new Export($this->dbi), new Transformations($this->dbi, $relation)])
            ->getMock();

        $keys = [['Non_unique' => 0, 'Column_name' => 'name1'], ['Non_unique' => 1, 'Column_name' => 'name2']];

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

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

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

        $this->object->expects(self::exactly(3))
            ->method('formatOneColumnDefinition')
            ->with($column, ['name1'])
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
            ->withParsedBody(['htmlword_relation' => 'On', 'htmlword_mime' => 'On', 'htmlword_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        $result = $this->object->getTableDef('database', '');

        self::assertSame(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td>' .
            '<td class="print"><strong>Comments</strong></td>' .
            '<td class="print"><strong>Media type</strong></td></tr>' .
            '1<td class="print"></td><td class="print">Test&lt;</td></tr></table>',
            $result,
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

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

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

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $result = $this->object->getTableDef('database', '');

        self::assertStringContainsString('<td class="print">ftable (ffield)</td>', $result);

        self::assertStringContainsString('<td class="print"></td><td class="print"></td>', $result);

        // case 3

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $column = new Column('fieldname', '', null, false, '', null, '', '', '');

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn([$column]);

        $dbi->expects(self::never())
            ->method('tryQuery');

        DatabaseInterface::$instance = $dbi;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['htmlword_relation' => 'On', 'htmlword_mime' => 'On']);

        $this->object->setExportOptions($request, []);

        $result = $this->object->getTableDef('database', '');

        self::assertSame(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>1</tr></table>',
            $result,
        );
    }

    public function testGetTriggers(): void
    {
        $triggers = [
            new Trigger(
                TriggerName::from('tna"me'),
                Timing::Before,
                Event::Update,
                TableName::from('table'),
                'def',
                'test_user@localhost',
            ),
        ];

        $method = new ReflectionMethod(ExportHtmlword::class, 'getTriggers');
        $result = $method->invoke($this->object, $triggers);

        self::assertStringContainsString(
            '<td class="print">tna&quot;me</td>' .
            '<td class="print">BEFORE</td>' .
            '<td class="print">UPDATE</td>' .
            '<td class="print">def</td>',
            $result,
        );
    }

    public function testExportStructure(): void
    {
        ob_start();
        $this->dummyDbi->addSelectDb('test_db');
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_table'));
        $this->dummyDbi->assertAllSelectsConsumed();
        $result = ob_get_clean();

        self::assertSame(
            '<h2>Table structure for table test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong></td></tr>'
            . '<tr class="print-category"><td class="print"><em><strong>id</strong></em></td>'
            . '<td class="print">int(11)</td><td class="print">No</td><td class="print">NULL</td></tr>'
            . '<tr class="print-category"><td class="print">name</td><td class="print">varchar(20)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result,
        );

        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'triggers'));
        $result = ob_get_clean();

        self::assertSame(
            '<h2>Triggers test_table</h2><table width="100%" cellspacing="1">'
            . '<tr class="print-category"><th class="print">Name</th>'
            . '<td class="print"><strong>Time</strong></td><td class="print"><strong>Event</strong></td>'
            . '<td class="print"><strong>Definition</strong></td></tr><tr class="print-category">'
            . '<td class="print">test_trigger</td><td class="print">AFTER</td>'
            . '<td class="print">INSERT</td><td class="print">BEGIN END</td></tr></table>',
            $result,
        );

        ob_start();
        $this->dummyDbi->addSelectDb('test_db');
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_view'));
        $this->dummyDbi->assertAllSelectsConsumed();
        $result = ob_get_clean();

        self::assertSame(
            '<h2>Structure for view test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong>'
            . '</td></tr><tr class="print-category"><td class="print"><em><strong>id</strong></em></td>'
            . '<td class="print">int(11)</td><td class="print">No</td><td class="print">NULL</td></tr>'
            . '<tr class="print-category"><td class="print">name</td><td class="print">varchar(20)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result,
        );

        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'stand_in'));
        $result = ob_get_clean();

        self::assertSame(
            '<h2>Stand-in structure for view test_table</h2>'
            . '<table width="100%" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong></td>'
            . '</tr><tr class="print-category">'
            . '<td class="print"><em><strong>id</strong></em></td><td class="print">int(11)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">name</td><td class="print">varchar(20)</td><td class="print">No</td>'
            . '<td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result,
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(ExportHtmlword::class, 'formatOneColumnDefinition');

        $column = new Column('field', 'set(abc)enum123', null, true, 'PRI', null, '', '', '');

        $uniqueKeys = ['field'];

        self::assertSame(
            '<tr class="print-category"><td class="print"><em>' .
            '<strong>field</strong></em></td><td class="print">set(abc)</td>' .
            '<td class="print">Yes</td><td class="print">NULL</td>',
            $method->invoke($this->object, $column, $uniqueKeys),
        );

        $column = new Column('fields', '', null, false, 'COMP', 'def', '', '', '');

        $uniqueKeys = ['field'];

        self::assertSame(
            '<tr class="print-category"><td class="print">fields</td>' .
            '<td class="print">&amp;nbsp;</td><td class="print">No</td>' .
            '<td class="print">def</td>',
            $method->invoke($this->object, $column, $uniqueKeys),
        );
    }
}
