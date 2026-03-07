<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export as SettingsExport;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\OutputHandler;
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
final class ExportHtmlwordTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = '';
    }

    public function testSetProperties(): void
    {
        $exportHtmlword = $this->getExportHtmlword();

        $method = new ReflectionMethod(ExportHtmlword::class, 'setProperties');
        $method->invoke($exportHtmlword, null);

        $attrProperties = new ReflectionProperty(ExportHtmlword::class, 'properties');
        $properties = $attrProperties->getValue($exportHtmlword);

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
            'htmlword_dump_what',
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
            'htmlword_structure_or_data',
            $property->getName(),
        );

        self::assertSame(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
            $property->getValues(),
        );

        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'htmlword_dump_data_options',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Data dump options',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'htmlword_null',
            $property->getName(),
        );

        self::assertSame(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'htmlword_columns',
            $property->getName(),
        );

        self::assertSame(
            'Put columns names in the first row',
            $property->getText(),
        );
    }

    public function testExportHeader(): void
    {
        $exportHtmlword = $this->getExportHtmlword();

        ob_start();
        $exportHtmlword->exportHeader();
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
        $exportHtmlword->exportHeader();
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
        $exportHtmlword = $this->getExportHtmlword();

        ob_start();
        $exportHtmlword->exportFooter();
        $result = ob_get_clean();

        self::assertSame('</body></html>', $result);
    }

    public function testExportDBHeader(): void
    {
        $exportHtmlword = $this->getExportHtmlword();

        ob_start();
        $exportHtmlword->exportDBHeader('d"b');
        $result = ob_get_clean();

        self::assertSame('<h1>Database d&quot;b</h1>', $result);
    }

    public function testExportDBFooter(): void
    {
        $exportHtmlword = $this->getExportHtmlword();
        $this->expectNotToPerformAssertions();
        $exportHtmlword->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportHtmlword = $this->getExportHtmlword();
        $this->expectNotToPerformAssertions();
        $exportHtmlword->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $exportHtmlword = $this->getExportHtmlword();

        // case 1
        OutputHandler::$asFile = true;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['htmlword_columns' => 'On']);

        $exportHtmlword->setExportOptions($request, new SettingsExport());

        ob_start();
        $exportHtmlword->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
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

        $exportHtmlword = $this->getExportHtmlword($dbi);

        self::assertSame(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>' .
            '<tr class="print-category"><td class="print">column</td>' .
            '<td class="print">&amp;nbsp;</td><td class="print">No</td><td class="print"></td>' .
            '</tr></table>',
            $exportHtmlword->getTableDefStandIn('database', 'view'),
        );
    }

    public function testGetTableDef(): void
    {
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

        $exportHtmlword = $this->getExportHtmlword($dbi);

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

        $exportHtmlword->setExportOptions($request, new SettingsExport());

        $result = $exportHtmlword->getTableDef('database', '');

        self::assertSame(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td>' .
            '<td class="print"><strong>Comments</strong></td>' .
            '<td class="print"><strong>Media type</strong></td></tr>' .
            '<tr class="print-category"><td class="print">fieldname</td><td class="print">&amp;nbsp;</td>' .
            '<td class="print">No</td><td class="print"></td>' .
            '<td class="print"></td><td class="print">Test&lt;</td></tr></table>',
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

        $exportHtmlword = $this->getExportHtmlword($dbi);
        $exportHtmlword->setExportOptions($request, new SettingsExport());

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $result = $exportHtmlword->getTableDef('database', '');

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

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['htmlword_relation' => 'On', 'htmlword_mime' => 'On']);

        $exportHtmlword = $this->getExportHtmlword($dbi);
        $exportHtmlword->setExportOptions($request, new SettingsExport());

        $result = $exportHtmlword->getTableDef('database', '');

        self::assertSame(
            '<table width="100%" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>' .
            '<tr class="print-category"><td class="print">fieldname</td><td class="print">&amp;nbsp;</td>' .
            '<td class="print">No</td><td class="print"></td>' .
            '</tr></table>',
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

        $exportHtmlword = $this->getExportHtmlword();

        $method = new ReflectionMethod(ExportHtmlword::class, 'getTriggers');
        $result = $method->invoke($exportHtmlword, $triggers);

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
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dbiDummy = $this->createDbiDummy();
        $exportHtmlword = $this->getExportHtmlword($this->createDatabaseInterface($dbiDummy));

        ob_start();
        $dbiDummy->addSelectDb('test_db');
        $exportHtmlword->exportStructure('test_db', 'test_table', 'create_table');
        $dbiDummy->assertAllSelectsConsumed();
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
        $exportHtmlword->exportStructure('test_db', 'test_table', 'triggers');
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
        $dbiDummy->addSelectDb('test_db');
        $exportHtmlword->exportStructure('test_db', 'test_table', 'create_view');
        $dbiDummy->assertAllSelectsConsumed();
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
        $exportHtmlword->exportStructure('test_db', 'test_table', 'stand_in');
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

        $exportHtmlword = $this->getExportHtmlword();

        self::assertSame(
            '<tr class="print-category"><td class="print"><em>' .
            '<strong>field</strong></em></td><td class="print">set(abc)</td>' .
            '<td class="print">Yes</td><td class="print">NULL</td>',
            $method->invoke($exportHtmlword, $column, $uniqueKeys),
        );

        $column = new Column('fields', '', null, false, 'COMP', 'def', '', '', '');

        $uniqueKeys = ['field'];

        self::assertSame(
            '<tr class="print-category"><td class="print">fields</td>' .
            '<td class="print">&amp;nbsp;</td><td class="print">No</td>' .
            '<td class="print">def</td>',
            $method->invoke($exportHtmlword, $column, $uniqueKeys),
        );
    }

    public function testExportTableCallsExportStructureMethod(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exportHtmlword = $this->getExportHtmlword($dbi);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['htmlword_structure_or_data' => 'structure']);
        $exportHtmlword->setExportOptions($request, new SettingsExport());
        $export = new Export($dbi, new OutputHandler());
        ob_start();
        $export->exportTable(
            'test_db',
            'test_table',
            $exportHtmlword,
            null,
            '',
            '',
            '',
            [],
        );
        $output = ob_get_clean();
        self::assertStringContainsString('<h2>Table structure for table test_table</h2>', $output);
    }

    private function getExportHtmlword(DatabaseInterface|null $dbi = null): ExportHtmlword
    {
        $dbi ??= $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportHtmlword($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
