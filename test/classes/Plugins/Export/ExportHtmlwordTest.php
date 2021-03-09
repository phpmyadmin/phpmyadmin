<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportHtmlword;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Version;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * @group medium
 */
class ExportHtmlwordTest extends AbstractTestCase
{
    /** @var ExportHtmlword */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
        $this->object = new ExportHtmlword();
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = '';
        $GLOBALS['text_dir'] = '';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
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
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportHtmlword::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
            $properties
        );

        $this->assertEquals(
            'Microsoft Word 2000',
            $properties->getText()
        );

        $this->assertEquals(
            'doc',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/vnd.ms-word',
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

        $this->assertInstanceOf(
            OptionsPropertyRootGroup::class,
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'dump_what',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Dump table',
            $generalOptions->getText()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            RadioPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

        $this->assertEquals(
            [
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data'),
            ],
            $property->getValues()
        );

        $generalOptions = $generalOptionsArray[1];

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'dump_what',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Data dump options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            TextPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText()
        );
    }

    public function testExportHeader(): void
    {
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected
            = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
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

        $this->assertEquals(
            $expected,
            $result
        );

        // case 2

        $GLOBALS['charset'] = 'ISO-8859-1';
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected
            = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
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

        $this->assertEquals(
            $expected,
            $result
        );
    }

    public function testExportFooter(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportFooter()
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '</body></html>',
            $result
        );
    }

    public function testExportDBHeader(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('d"b')
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h1>Database d&quot;b</h1>',
            $result
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
        // case 1
        $GLOBALS['htmlword_columns'] = true;
        $GLOBALS['what'] = 'UT';
        $GLOBALS['UT_null'] = 'customNull';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Dumping data for table test_table</h2>'
            . '<table class="pma-table w-100" cellspacing="1"><tr class="print-category">'
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
            $result
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->object = $this->getMockBuilder(ExportHtmlword::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->getMock();

        // case 1

        $keys = [
            [
                'Non_unique' => 0,
                'Column_name' => 'name1',
            ],
            [
                'Non_unique' => 1,
                'Column_name' => 'name2',
            ],
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', 'view')
            ->will($this->returnValue($keys));

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', 'view')
            ->will($this->returnValue([['Field' => 'column']]));

        $GLOBALS['dbi'] = $dbi;

        $this->object->expects($this->once())
            ->method('formatOneColumnDefinition')
            ->with(['Field' => 'column'], ['name1'], 'column')
            ->will($this->returnValue(1));

        $this->assertEquals(
            '<table class="pma-table w-100" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>' .
            '1</tr></table>',
            $this->object->getTableDefStandIn('database', 'view', "\n")
        );
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportHtmlword::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->getMock();

        $keys = [
            [
                'Non_unique' => 0,
                'Column_name' => 'name1',
            ],
            [
                'Non_unique' => 1,
                'Column_name' => 'name2',
            ],
        ];

        // case 1

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

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

        $columns = ['Field' => 'fieldname'];
        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    [
                        'comment' => ['fieldname' => 'testComment'],
                    ]
                )
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $this->object->expects($this->exactly(3))
            ->method('formatOneColumnDefinition')
            ->with($columns, ['name1'])
            ->will($this->returnValue(1));

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = [
            'version' => Version::VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ];

        $result = $this->object->getTableDef(
            'database',
            '',
            true,
            true,
            true
        );

        $this->assertEquals(
            '<table class="pma-table w-100" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td>' .
            '<td class="print"><strong>Comments</strong></td>' .
            '<td class="print"><strong>Media type</strong></td></tr>' .
            '1<td class="print"></td><td class="print">Test&lt;</td></tr></table>',
            $result
        );

        // case 2

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

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

        $columns = ['Field' => 'fieldname'];

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    [
                        'comment' => ['field' => 'testComment'],
                    ]
                )
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = [
            'version' => Version::VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ];

        $result = $this->object->getTableDef(
            'database',
            '',
            true,
            true,
            true
        );

        $this->assertStringContainsString(
            '<td class="print">ftable (ffield)</td>',
            $result
        );

        $this->assertStringContainsString(
            '<td class="print"></td><td class="print"></td>',
            $result
        );

         // case 3

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

        $columns = ['Field' => 'fieldname'];

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue([$columns]));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    [
                        'comment' => ['field' => 'testComment'],
                    ]
                )
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = [
            'version' => Version::VERSION,
            'relwork' => false,
            'commwork' => false,
            'mimework' => false,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ];

        $result = $this->object->getTableDef(
            'database',
            '',
            false,
            false,
            false
        );

        $this->assertEquals(
            '<table class="pma-table w-100" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>1</tr></table>',
            $result
        );
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
            ->with('database', 'table')
            ->will($this->returnValue($triggers));

        $GLOBALS['dbi'] = $dbi;

        $method = new ReflectionMethod(ExportHtmlword::class, 'getTriggers');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'database', 'table');

        $this->assertStringContainsString(
            '<td class="print">tna&quot;me</td>' .
            '<td class="print">ac&gt;t</td>' .
            '<td class="print">manip&amp;</td>' .
            '<td class="print">def</td>',
            $result
        );
    }

    public function testExportStructure(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'create_table',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Table structure for table test_table</h2>'
            . '<table class="pma-table w-100" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong></td></tr>'
            . '<tr class="print-category"><td class="print"><em><strong>id</strong></em></td>'
            . '<td class="print">int(11)</td><td class="print">No</td><td class="print">NULL</td></tr>'
            . '<tr class="print-category"><td class="print">name</td><td class="print">varchar(20)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'triggers',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Triggers test_table</h2><table class="pma-table w-100" cellspacing="1">'
            . '<tr class="print-category"><th class="print">Name</th>'
            . '<td class="print"><strong>Time</strong></td><td class="print"><strong>Event</strong></td>'
            . '<td class="print"><strong>Definition</strong></td></tr><tr class="print-category">'
            . '<td class="print">test_trigger</td><td class="print">AFTER</td>'
            . '<td class="print">INSERT</td><td class="print">BEGIN END</td></tr></table>',
            $result
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'create_view',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Structure for view test_table</h2>'
            . '<table class="pma-table w-100" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong>'
            . '</td></tr><tr class="print-category"><td class="print"><em><strong>id</strong></em></td>'
            . '<td class="print">int(11)</td><td class="print">No</td><td class="print">NULL</td></tr>'
            . '<tr class="print-category"><td class="print">name</td><td class="print">varchar(20)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'stand_in',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Stand-in structure for view test_table</h2>'
            . '<table class="pma-table w-100" cellspacing="1"><tr class="print-category">'
            . '<th class="print">Column</th><td class="print"><strong>Type</strong></td>'
            . '<td class="print"><strong>Null</strong></td><td class="print"><strong>Default</strong></td>'
            . '</tr><tr class="print-category">'
            . '<td class="print"><em><strong>id</strong></em></td><td class="print">int(11)</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">name</td><td class="print">varchar(20)</td><td class="print">No</td>'
            . '<td class="print">NULL</td></tr><tr class="print-category">'
            . '<td class="print">datetimefield</td><td class="print">datetime</td>'
            . '<td class="print">No</td><td class="print">NULL</td></tr></table>',
            $result
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $method = new ReflectionMethod(
            ExportHtmlword::class,
            'formatOneColumnDefinition'
        );
        $method->setAccessible(true);

        $cols = [
            'Null' => 'Yes',
            'Field' => 'field',
            'Key' => 'PRI',
            'Type' => 'set(abc)enum123',
        ];

        $unique_keys = ['field'];

        $this->assertEquals(
            '<tr class="print-category"><td class="print"><em>' .
            '<strong>field</strong></em></td><td class="print">set(abc)</td>' .
            '<td class="print">Yes</td><td class="print">NULL</td>',
            $method->invoke($this->object, $cols, $unique_keys)
        );

        $cols = [
            'Null' => 'NO',
            'Field' => 'fields',
            'Key' => 'COMP',
            'Type' => '',
            'Default' => 'def',
        ];

        $unique_keys = ['field'];

        $this->assertEquals(
            '<tr class="print-category"><td class="print">fields</td>' .
            '<td class="print">&amp;nbsp;</td><td class="print">No</td>' .
            '<td class="print">def</td>',
            $method->invoke($this->object, $cols, $unique_keys)
        );
    }
}
