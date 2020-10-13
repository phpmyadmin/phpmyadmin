<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportTexytext;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;
use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * @group medium
 */
class ExportTexytextTest extends AbstractTestCase
{
    /** @var ExportTexytext */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
        $this->object = new ExportTexytext();
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
        $method = new ReflectionMethod(ExportTexytext::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportTexytext::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
            $properties
        );

        $this->assertEquals(
            'Texy! text',
            $properties->getText()
        );

        $this->assertEquals(
            'txt',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/plain',
            $properties->getMimeType()
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

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
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

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'data',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            TextPropertyItem::class,
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );
    }

    public function testExportHeader(): void
    {
        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    public function testExportFooter(): void
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportDBHeader(): void
    {
        $this->expectOutputString(
            "===Database testDb\n\n"
        );
        $this->assertTrue(
            $this->object->exportDBHeader('testDb')
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

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(3));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->will($this->returnValue('fName1'));

        $dbi->expects($this->at(3))
            ->method('fieldName')
            ->will($this->returnValue('fNa"me2'));

        $dbi->expects($this->at(4))
            ->method('fieldName')
            ->will($this->returnValue('fName3'));

        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue([null, '0', 'test']));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_columns'] = '&';
        $GLOBALS['foo_null'] = '>';

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db',
                'ta<ble',
                "\n",
                'example.com',
                'SELECT'
            )
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            '|fName1|fNa&amp;quot;me2|fName3',
            $result
        );

        $this->assertStringContainsString(
            '|&amp;gt;|0|test',
            $result
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('db', 'view')
            ->will($this->returnValue([1, 2]));

        $keys = [
            [
                'Non_unique' => 0,
                'Column_name' => 'cname',
            ],
            [
                'Non_unique' => 1,
                'Column_name' => 'cname2',
            ],
        ];

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('db', 'view')
            ->will($this->returnValue($keys));

        $dbi->expects($this->once())
            ->method('selectDb')
            ->with('db');

        $GLOBALS['dbi'] = $dbi;

        $this->object = $this->getMockBuilder(ExportTexytext::class)
            ->disableOriginalConstructor()
            ->setMethods(['formatOneColumnDefinition'])
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('formatOneColumnDefinition')
            ->with(1, ['cname'])
            ->will($this->returnValue('c1'));

        $this->object->expects($this->at(1))
            ->method('formatOneColumnDefinition')
            ->with(2, ['cname'])
            ->will($this->returnValue('c2'));

        $result = $this->object->getTableDefStandIn('db', 'view', '#');

        $this->assertStringContainsString(
            "c1\nc2",
            $result
        );
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportTexytext::class)
            ->setMethods(['formatOneColumnDefinition'])
            ->getMock();

        // case 1

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $keys = [
            [
                'Non_unique' => 0,
                'Column_name' => 'cname',
            ],
            [
                'Non_unique' => 1,
                'Column_name' => 'cname2',
            ],
        ];

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('db', 'table')
            ->will($this->returnValue($keys));

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [
                    'fname' => [
                        'foreign_table' => '<ftable',
                        'foreign_field' => 'ffield>',
                    ],
                ],
                [
                    'fname' => [
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<',
                    ],
                ]
            );

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->will(
                $this->returnValue(
                    'SELECT a FROM b'
                )
            );

        $columns = [
            'Field' => 'fname',
            'Comment' => 'comm',
        ];

        $dbi->expects($this->exactly(2))
            ->method('getColumns')
            ->with('db', 'table')
            ->will($this->returnValue([$columns]));

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $this->object->expects($this->exactly(1))
            ->method('formatOneColumnDefinition')
            ->with(['Field' => 'fname', 'Comment' => 'comm'], ['cname'])
            ->will($this->returnValue(1));

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = [
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'db',
            'relation' => 'rel',
            'column_info' => 'col',
        ];

        $result = $this->object->getTableDef(
            'db',
            'table',
            "\n",
            'example.com',
            true,
            true,
            true
        );

        $this->assertStringContainsString(
            '1|&lt;ftable (ffield&gt;)|comm|Test&lt;',
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
            ->with('database', 'ta<ble')
            ->will($this->returnValue($triggers));

        $GLOBALS['dbi'] = $dbi;

        $result = $this->object->getTriggers('database', 'ta<ble');

        $this->assertStringContainsString(
            '|tna"me|ac>t|manip&|def',
            $result
        );

        $this->assertStringContainsString(
            '|Name|Time|Event|Definition',
            $result
        );
    }

    public function testExportStructure(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('db', 't&bl')
            ->will($this->returnValue(1));

        $this->object = $this->getMockBuilder(ExportTexytext::class)
            ->setMethods(['getTableDef', 'getTriggers', 'getTableDefStandIn'])
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('getTableDef')
            ->with('db', 't&bl', "\n", 'example.com', false, false, false, false)
            ->will($this->returnValue('dumpText1'));

        $this->object->expects($this->once())
            ->method('getTriggers')
            ->with('db', 't&bl')
            ->will($this->returnValue('dumpText2'));

        $this->object->expects($this->at(2))
            ->method('getTableDef')
            ->with(
                'db',
                't&bl',
                "\n",
                'example.com',
                false,
                false,
                false,
                false,
                true,
                true
            )
            ->will($this->returnValue('dumpText3'));

        $this->object->expects($this->once())
            ->method('getTableDefStandIn')
            ->with('db', 't&bl', "\n")
            ->will($this->returnValue('dumpText4'));

        $GLOBALS['dbi'] = $dbi;

        // case 1
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db',
                't&bl',
                "\n",
                'example.com',
                'create_table',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            '== Table structure for table t&amp;bl' . "\n\ndumpText1",
            $result
        );

        // case 2
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db',
                't&bl',
                "\n",
                'example.com',
                'triggers',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Triggers t&amp;bl' . "\n\ndumpText2",
            $result
        );

        // case 3
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db',
                't&bl',
                "\n",
                'example.com',
                'create_view',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Structure for view t&amp;bl' . "\n\ndumpText3",
            $result
        );

        // case 4
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db',
                't&bl',
                "\n",
                'example.com',
                'stand_in',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Stand-in structure for view t&amp;bl' . "\n\ndumpText4",
            $result
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $cols = [
            'Null' => 'Yes',
            'Field' => 'field',
            'Key' => 'PRI',
            'Type' => 'set(abc)enum123',
        ];

        $unique_keys = ['field'];

        $this->assertEquals(
            '|//**field**//|set(abc)|Yes|NULL',
            $this->object->formatOneColumnDefinition($cols, $unique_keys)
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
            '|fields|&amp;nbsp;|No|def',
            $this->object->formatOneColumnDefinition($cols, $unique_keys)
        );
    }
}
