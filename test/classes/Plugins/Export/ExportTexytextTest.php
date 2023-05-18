<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportTexytext;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Transformations;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportTexytext
 * @group medium
 */
class ExportTexytextTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected ExportTexytext $object;

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
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $this->object = new ExportTexytext(
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
        $method = new ReflectionMethod(ExportTexytext::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportTexytext::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

        $this->assertEquals(
            'Texy! text',
            $properties->getText(),
        );

        $this->assertEquals(
            'txt',
            $properties->getExtension(),
        );

        $this->assertEquals(
            'text/plain',
            $properties->getMimeType(),
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

        $generalOptions = $generalOptionsArray->current();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'data',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'columns',
            $property->getName(),
        );

        $property = $generalProperties->current();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'null',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        $this->assertTrue(
            $this->object->exportHeader(),
        );
    }

    public function testExportFooter(): void
    {
        $this->assertTrue(
            $this->object->exportFooter(),
        );
    }

    public function testExportDBHeader(): void
    {
        $this->expectOutputString("===Database testDb\n\n");
        $this->assertTrue(
            $this->object->exportDBHeader('testDb'),
        );
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
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_columns'] = '&';
        $GLOBALS['foo_null'] = '>';

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'test_db',
                'test_table',
                'localhost',
                'SELECT * FROM `test_db`.`test_table`;',
            ),
        );
        $result = ob_get_clean();

        $this->assertIsString($result);
        $this->assertEquals(
            '== Dumping data for table test_table' . "\n\n"
                . '|------' . "\n"
                . '|id|name|datetimefield' . "\n"
                . '|------' . "\n"
                . '|1|abcd|2011-01-20 02:00:02' . "\n"
                . '|2|foo|2010-01-20 02:00:02' . "\n"
                . '|3|Abcd|2012-01-20 02:00:02' . "\n",
            $result,
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $this->dummyDbi->addSelectDb('test_db');
        $result = $this->object->getTableDefStandIn('test_db', 'test_table');
        $this->dummyDbi->assertAllSelectsConsumed();

        $this->assertEquals(
            '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );
    }

    public function testGetTableDef(): void
    {
        $this->object = $this->getMockBuilder(ExportTexytext::class)
            ->onlyMethods(['formatOneColumnDefinition'])
            ->setConstructorArgs([new Relation($GLOBALS['dbi']), new Export($GLOBALS['dbi']), new Transformations()])
            ->getMock();

        // case 1

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $keys = [['Non_unique' => 0, 'Column_name' => 'cname'], ['Non_unique' => 1, 'Column_name' => 'cname2']];

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('db', 'table')
            ->will($this->returnValue($keys));

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                ['fname' => ['foreign_table' => '<ftable', 'foreign_field' => 'ffield>']],
                ['fname' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->will(
                $this->returnValue(
                    'SELECT a FROM b',
                ),
            );

        $columns = ['Field' => 'fname', 'Comment' => 'comm'];

        $dbi->expects($this->exactly(2))
            ->method('getColumns')
            ->with('db', 'table')
            ->will($this->returnValue([$columns]));

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $this->object->expects($this->exactly(1))
            ->method('formatOneColumnDefinition')
            ->with(['Field' => 'fname', 'Comment' => 'comm'], ['cname'])
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

        $result = $this->object->getTableDef('db', 'table', true, true, true);

        $this->assertStringContainsString('1|&lt;ftable (ffield&gt;)|comm|Test&lt;', $result);
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

        $result = $this->object->getTriggers('database', 'ta<ble');

        $this->assertStringContainsString('|tna"me|ac>t|manip&|def', $result);

        $this->assertStringContainsString('|Name|Time|Event|Definition', $result);
    }

    public function testExportStructure(): void
    {
        // case 1
        ob_start();
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
        $result = ob_get_clean();

        $this->assertIsString($result);
        $this->assertEquals(
            '== Table structure for table test_table' . "\n\n"
            . '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );

        // case 2
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'test_db',
                'test_table',
                'localhost',
                'triggers',
                'test',
            ),
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Triggers test_table' . "\n\n"
            . '|------' . "\n"
            . '|Name|Time|Event|Definition' . "\n"
            . '|------' . "\n"
            . '|test_trigger|AFTER|INSERT|BEGIN END' . "\n",
            $result,
        );

        // case 3
        ob_start();
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
        $result = ob_get_clean();

        $this->assertEquals(
            '== Structure for view test_table' . "\n\n"
            . '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );

        // case 4
        ob_start();
        $this->dummyDbi->addSelectDb('test_db');
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
        $result = ob_get_clean();

        $this->assertEquals(
            '== Stand-in structure for view test_table' . "\n\n"
            . '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $cols = ['Null' => 'Yes', 'Field' => 'field', 'Key' => 'PRI', 'Type' => 'set(abc)enum123'];

        $uniqueKeys = ['field'];

        $this->assertEquals(
            '|//**field**//|set(abc)|Yes|NULL',
            $this->object->formatOneColumnDefinition($cols, $uniqueKeys),
        );

        $cols = ['Null' => 'NO', 'Field' => 'fields', 'Key' => 'COMP', 'Type' => '', 'Default' => 'def'];

        $uniqueKeys = ['field'];

        $this->assertEquals(
            '|fields|&amp;nbsp;|No|def',
            $this->object->formatOneColumnDefinition($cols, $uniqueKeys),
        );
    }
}
