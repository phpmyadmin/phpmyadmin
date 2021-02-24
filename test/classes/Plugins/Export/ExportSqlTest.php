<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * @group medium
 */
class ExportSqlTest extends AbstractTestCase
{
    /** @var ExportSql */
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
        $GLOBALS['db'] = 'db';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
        $this->object = new ExportSql();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * @group medium
     */
    public function testSetProperties(): void
    {
        // test with hide structure and hide sql as true
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['mimework'] = true;

        $method = new ReflectionMethod(ExportSql::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportSql::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertNull(
            $properties
        );

        // test with hide structure and hide sql as false
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCompatibilities')
            ->will($this->returnValue(['v1', 'v2']));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['plugin_param']['export_type'] = 'server';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['mimework'] = true;
        $GLOBALS['cfgRelation']['relation'] = true;

        $method->invoke($this->object, null);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
            $properties
        );

        $this->assertEquals(
            'SQL',
            $properties->getText()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            OptionsPropertyRootGroup::class,
            $options
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $properties = $generalOptions->getProperties();

        $property = array_shift($properties);

        $this->assertInstanceOf(
            OptionsPropertySubgroup::class,
            $property
        );

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property->getSubgroupHeader()
        );

        $leaves = $property->getProperties();

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            TextPropertyItem::class,
            $leaf
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            SelectPropertyItem::class,
            $property
        );

        $this->assertEquals(
            [
                'v1' => 'v1',
                'v2' => 'v2',
            ],
            $property->getValues()
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            OptionsPropertySubgroup::class,
            $property
        );

        $this->assertInstanceOf(
            RadioPropertyItem::class,
            $property->getSubgroupHeader()
        );

        $structureOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $structureOptions
        );

        $properties = $structureOptions->getProperties();

        $property = array_shift($properties);

        $this->assertInstanceOf(
            OptionsPropertySubgroup::class,
            $property
        );

        $this->assertInstanceOf(
            MessageOnlyPropertyItem::class,
            $property->getSubgroupHeader()
        );

        $leaves = $property->getProperties();

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $this->assertEquals(
            'Add <code>DROP TABLE / VIEW / PROCEDURE / FUNCTION' .
            ' / EVENT</code><code> / TRIGGER</code> statement',
            $leaf->getText()
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            OptionsPropertySubgroup::class,
            $leaf
        );

        $this->assertCount(
            2,
            $leaf->getProperties()
        );

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf->getSubgroupHeader()
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            OptionsPropertySubgroup::class,
            $leaf
        );

        $this->assertCount(
            3,
            $leaf->getProperties()
        );

        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf->getSubgroupHeader()
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $leaf = array_shift($leaves);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $leaf
        );

        $property = array_shift($properties);
        $this->assertInstanceOf(
            BoolPropertyItem::class,
            $property
        );

        $dataOptions = array_shift($generalOptionsArray);
        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $dataOptions
        );

        $properties = $dataOptions->getProperties();

        $this->assertCount(
            7,
            $properties
        );

        $this->assertCount(
            2,
            $properties[1]->getProperties()
        );
    }

    public function testExportRoutines(): void
    {
        $GLOBALS['crlf'] = '##';
        $GLOBALS['sql_drop_table'] = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getProceduresOrFunctions')
            ->with('db', 'PROCEDURE')
            ->will($this->returnValue(['p1', 'p2']));

        $dbi->expects($this->at(1))
            ->method('getProceduresOrFunctions')
            ->with('db', 'FUNCTION')
            ->will($this->returnValue(['f1']));

        $dbi->expects($this->at(2))
            ->method('getDefinition')
            ->with('db', 'PROCEDURE', 'p1')
            ->will($this->returnValue('testp1'));

        $dbi->expects($this->at(3))
            ->method('getDefinition')
            ->with('db', 'PROCEDURE', 'p2')
            ->will($this->returnValue('testp2'));

        $dbi->expects($this->at(4))
            ->method('getDefinition')
            ->with('db', 'FUNCTION', 'f1')
            ->will($this->returnValue('testf1'));

        $GLOBALS['dbi'] = $dbi;

        $this->expectOutputString(
            '##DELIMITER $$##DROP PROCEDURE IF EXISTS `p1`$$##testp1$$####' .
            'DROP PROCEDURE IF EXISTS `p2`$$##testp2$$####DROP FUNCTION IF' .
            ' EXISTS `f1`$$##testf1$$####DELIMITER ;##'
        );

        $this->object->exportRoutines('db');
    }

    public function testExportComment(): void
    {
        $method = new ReflectionMethod(ExportSql::class, 'exportComment');
        $method->setAccessible(true);

        $GLOBALS['crlf'] = '##';
        $GLOBALS['sql_include_comments'] = true;

        $this->assertEquals(
            '--##',
            $method->invoke($this->object, '')
        );

        $this->assertEquals(
            '-- Comment##',
            $method->invoke($this->object, 'Comment')
        );

        $GLOBALS['sql_include_comments'] = false;

        $this->assertEquals(
            '',
            $method->invoke($this->object, 'Comment')
        );

        unset($GLOBALS['sql_include_comments']);

        $this->assertEquals(
            '',
            $method->invoke($this->object, 'Comment')
        );
    }

    public function testPossibleCRLF(): void
    {
        $method = new ReflectionMethod(ExportSql::class, 'possibleCRLF');
        $method->setAccessible(true);

        $GLOBALS['crlf'] = '##';
        $GLOBALS['sql_include_comments'] = true;

        $this->assertEquals(
            '##',
            $method->invoke($this->object, '')
        );

        $this->assertEquals(
            '##',
            $method->invoke($this->object, 'Comment')
        );

        $GLOBALS['sql_include_comments'] = false;

        $this->assertEquals(
            '',
            $method->invoke($this->object, 'Comment')
        );

        unset($GLOBALS['sql_include_comments']);

        $this->assertEquals(
            '',
            $method->invoke($this->object, 'Comment')
        );
    }

    public function testExportFooter(): void
    {
        $GLOBALS['crlf'] = '';
        $GLOBALS['sql_disable_fk'] = true;
        $GLOBALS['sql_use_transaction'] = true;
        $GLOBALS['charset'] = 'utf-8';
        $GLOBALS['sql_utc_time'] = true;
        $GLOBALS['old_tz'] = 'GMT';
        $GLOBALS['asfile'] = 'yes';
        $GLOBALS['output_charset_conversion'] = 'utf-8';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SET time_zone = "GMT"');

        $GLOBALS['dbi'] = $dbi;

        $this->expectOutputString(
            'SET FOREIGN_KEY_CHECKS=1;COMMIT;'
        );

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportHeader(): void
    {
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['sql_compatibility'] = 'NONE';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['port'] = 80;
        $GLOBALS['sql_disable_fk'] = true;
        $GLOBALS['sql_use_transaction'] = true;
        $GLOBALS['sql_utc_time'] = true;
        $GLOBALS['old_tz'] = 'GMT';
        $GLOBALS['asfile'] = 'yes';
        $GLOBALS['output_charset_conversion'] = 'utf-8';
        $GLOBALS['sql_header_comment'] = "h1C\nh2C";
        $GLOBALS['sql_use_transaction'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['charset'] = 'utf-8';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SET SQL_MODE=""');

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with('SELECT @@session.time_zone')
            ->will($this->returnValue('old_tz'));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SET time_zone = "+00:00"');

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            'h1C',
            $result
        );

        $this->assertStringContainsString(
            'h2C',
            $result
        );

        $this->assertStringContainsString(
            "SET FOREIGN_KEY_CHECKS=0;\n",
            $result
        );

        $this->assertStringContainsString(
            '40101 SET',
            $result
        );

        $this->assertStringContainsString(
            "SET FOREIGN_KEY_CHECKS=0;\n" .
            "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" .
            "START TRANSACTION;\n" .
            "SET time_zone = \"+00:00\";\n",
            $result
        );
    }

    public function testExportDBCreate(): void
    {
        $GLOBALS['sql_compatibility'] = 'NONE';
        $GLOBALS['sql_drop_database'] = true;
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_create_database'] = true;
        $GLOBALS['sql_create_table'] = true;
        $GLOBALS['sql_create_view'] = true;
        $GLOBALS['crlf'] = "\n";

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('getDbCollation')
            ->with('db')
            ->will($this->returnValue('utf8_general_ci'));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportDBCreate('db', 'database')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "DROP DATABASE IF EXISTS `db`;\n",
            $result
        );

        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `db` DEFAULT CHARACTER ' .
            'SET utf8 COLLATE utf8_general_ci;',
            $result
        );

        $this->assertStringContainsString(
            'USE `db`;',
            $result
        );

        // case2: no backquotes
        unset($GLOBALS['sql_compatibility']);
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        unset($GLOBALS['sql_backquotes']);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('getDbCollation')
            ->with('db')
            ->will($this->returnValue('testcollation'));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportDBCreate('db', 'database')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "DROP DATABASE IF EXISTS db;\n",
            $result
        );

        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS db DEFAULT CHARACTER SET testcollation;',
            $result
        );

        $this->assertStringContainsString(
            'USE db;',
            $result
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            '&quot;testDB&quot;',
            $result
        );

        // case 2
        unset($GLOBALS['sql_compatibility']);
        unset($GLOBALS['sql_backquotes']);

        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            'testDB',
            $result
        );
    }

    public function testExportEvents(): void
    {
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['sql_structure_or_data'] = 'structure';
        $GLOBALS['sql_procedure_function'] = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE'
                . ' EVENT_SCHEMA= \'db\';'
            )
            ->will($this->returnValue(['f1', 'f2']));

        $dbi->expects($this->exactly(2))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'db',
                            'EVENT',
                            'f1',
                            DatabaseInterface::CONNECT_USER,
                            'f1event',
                        ],
                        [
                            'db',
                            'EVENT',
                            'f2',
                            DatabaseInterface::CONNECT_USER,
                            'f2event',
                        ],
                    ]
                )
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportEvents('db')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "DELIMITER $$\n",
            $result
        );

        $this->assertStringContainsString(
            "DELIMITER ;\n",
            $result
        );

        $this->assertStringContainsString(
            "f1event$$\n",
            $result
        );

        $this->assertStringContainsString(
            "f2event$$\n",
            $result
        );
    }

    public function testExportDBFooter(): void
    {
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['sql_constraints'] = 'SqlConstraints';
        $GLOBALS['sql_structure_or_data'] = 'structure';
        $GLOBALS['sql_procedure_function'] = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportDBFooter('db')
        );
        $result = ob_get_clean();

        $this->assertEquals(
            'SqlConstraints',
            $result
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['sql_if_not_exists'] = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('getColumnsFull')
            ->with('db', 'view')
            ->will(
                $this->returnValue(
                    ['cname' => ['Type' => 'int']]
                )
            );

        $GLOBALS['dbi'] = $dbi;

        $result = $this->object->getTableDefStandIn('db', 'view', '');

        $this->assertStringContainsString(
            'DROP VIEW IF EXISTS `view`;',
            $result
        );

        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `view` (`cname` int);',
            $result
        );
    }

    public function testGetTableDefForView(): void
    {
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['sql_if_not_exists'] = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->any())
            ->method('getColumns')
            ->with('db', 'view')
            ->will(
                $this->returnValue(
                    [
                        'cname' => [
                            'Type' => 'char',
                            'Collation' => 'utf-8',
                            'Null' => 'NO',
                            'Default' => 'a',
                            'Comment' => 'cmt',
                            'Field' => 'fname',
                        ],
                    ]
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['sql_compatibility'] = 'MSSQL';

        $method = new ReflectionMethod(ExportSql::class, 'getTableDefForView');
        $method->setAccessible(true);
        $result = $method->invoke(
            $this->object,
            'db',
            'view',
            "\n"
        );

        $this->assertEquals(
            "CREATE TABLE `view`(\n" .
            "    `fname` char COLLATE utf-8 NOT NULL DEFAULT 'a' COMMENT 'cmt'\n" .
            ");\n",
            $result
        );

        // case 2
        unset($GLOBALS['sql_compatibility']);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->any())
            ->method('getColumns')
            ->with('db', 'view')
            ->will(
                $this->returnValue(
                    [
                        'cname' => [
                            'Type' => 'char',
                            'Collation' => 'utf-8',
                            'Null' => 'YES',
                            'Comment' => 'cmt',
                            'Field' => 'fname',
                        ],
                    ]
                )
            );
        $GLOBALS['dbi'] = $dbi;

        $result = $method->invoke(
            $this->object,
            'db',
            'view',
            "\n",
            false
        );

        $this->assertEquals(
            "CREATE TABLE IF NOT EXISTS `view`(\n" .
            "    `fname` char COLLATE utf-8 DEFAULT NULL COMMENT 'cmt'\n" .
            ")\n",
            $result
        );
    }

    /**
     * @group medium
     */
    public function testGetTableDef(): void
    {
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_auto_increment'] = true;
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_if_not_exists']  = true;
        $GLOBALS['sql_include_comments']  = true;
        $GLOBALS['crlf'] = "\n";
        if (isset($GLOBALS['sql_constraints'])) {
            unset($GLOBALS['sql_constraints']);
        }

        if (isset($GLOBALS['no_constraints_comments'])) {
            unset($GLOBALS['no_constraints_comments']);
        }

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue('res'));

        $dbi->expects($this->never())
            ->method('fetchSingleRow');

        $dbi->expects($this->once())
            ->method('numRows')
            ->with('res')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchValue')
            ->will($this->returnValue(false));

        $tmpres = [
            'Auto_increment' => 1,
            'Create_time' => '2000-01-01 10:00:00',
            'Update_time' => '2000-01-02 12:00:00',
            'Check_time' => '2000-01-02 13:00:00',
        ];

        $dbi->expects($this->once())
            ->method('fetchAssoc')
            ->with('res')
            ->will($this->returnValue($tmpres));

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->withConsecutive(
                ["SHOW TABLE STATUS FROM `db` WHERE Name = 'table'"],
                ['USE `db`'],
                ['SHOW CREATE TABLE `db`.`table`']
            )
            ->willReturnOnConsecutiveCalls(
                'res',
                'res',
                'res'
            );

        $row = [
            '',
            "CREATE TABLE `table` (\n" .
            "`payment_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "`customer_id` smallint(5) unsigned NOT NULL,\n" .
            "`staff_id` tinyint(3) unsigned NOT NULL,\n" .
            "`rental_id` int(11) DEFAULT NULL,\n" .
            "`amount` decimal(5,2) NOT NULL,\n" .
            "`payment_date` datetime NOT NULL,\n" .
            "`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
            "PRIMARY KEY (`payment_id`),\n" .
            "KEY `idx_fk_staff_id` (`staff_id`),\n" .
            "KEY `idx_fk_customer_id` (`customer_id`),\n" .
            "KEY `fk_payment_rental` (`rental_id`),\n" .
            'CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES' .
            " `customer` (`customer_id`) ON UPDATE CASCADE,\n" .
            'CONSTRAINT `fk_payment_rental` FOREIGN KEY (`rental_id`) REFERENCES' .
            " `rental` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,\n" .
            'CONSTRAINT `fk_payment_staff` FOREIGN KEY (`staff_id`) REFERENCES' .
            " `staff` (`staff_id`) ON UPDATE CASCADE\n" .
            ") ENGINE=InnoDB AUTO_INCREMENT=16050 DEFAULT CHARSET=utf8\n",
        ];

        $dbi->expects($this->exactly(1))
            ->method('fetchRow')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'res',
                            $row,
                        ],
                    ]
                )
            );
        $dbi->expects($this->exactly(2))
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'db', $dbi)));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $result = $this->object->getTableDef(
            'db',
            'table',
            "\n",
            'example.com/err',
            true,
            true,
            false
        );

        $this->assertStringContainsString(
            '-- Creation: Jan 01, 2000 at 10:00 AM',
            $result
        );

        $this->assertStringContainsString(
            '-- Last update: Jan 02, 2000 at 12:00 PM',
            $result
        );

        $this->assertStringContainsString(
            '-- Last check: Jan 02, 2000 at 01:00 PM',
            $result
        );

        $this->assertStringContainsString(
            'DROP TABLE IF EXISTS `table`;',
            $result
        );

        $this->assertStringContainsString(
            'CREATE TABLE `table`',
            $result
        );

        $this->assertStringContainsString(
            '-- Constraints for dumped tables',
            $GLOBALS['sql_constraints']
        );

        $this->assertStringContainsString(
            '-- Constraints for table "table"',
            $GLOBALS['sql_constraints']
        );

        $this->assertStringContainsString(
            'ALTER TABLE "table"',
            $GLOBALS['sql_constraints']
        );

        $this->assertStringContainsString(
            'ADD CONSTRAINT',
            $GLOBALS['sql_constraints']
        );

        $this->assertStringContainsString(
            'ALTER TABLE "table"',
            $GLOBALS['sql_constraints_query']
        );

        $this->assertStringContainsString(
            'ADD CONSTRAINT',
            $GLOBALS['sql_constraints_query']
        );

        $this->assertStringContainsString(
            'ALTER TABLE "table"',
            $GLOBALS['sql_drop_foreign_keys']
        );

        $this->assertStringContainsString(
            'DROP FOREIGN KEY',
            $GLOBALS['sql_drop_foreign_keys']
        );
    }

    public function testGetTableDefWithError(): void
    {
        $GLOBALS['sql_compatibility'] = '';
        $GLOBALS['sql_auto_increment'] = true;
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['sql_backquotes'] = false;
        $GLOBALS['sql_if_not_exists']  = true;
        $GLOBALS['sql_include_comments']  = true;
        $GLOBALS['crlf'] = "\n";

        if (isset($GLOBALS['sql_constraints'])) {
            unset($GLOBALS['sql_constraints']);
        }

        if (isset($GLOBALS['no_constraints_comments'])) {
            unset($GLOBALS['no_constraints_comments']);
        }

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue('res'));

        $dbi->expects($this->never())
            ->method('fetchSingleRow');

        $dbi->expects($this->once())
            ->method('numRows')
            ->with('res')
            ->will($this->returnValue(2));

        $dbi->expects($this->any())
            ->method('fetchValue')
            ->will($this->returnValue(false));

        $tmpres = [
            'Auto_increment' => 1,
            'Create_time' => '2000-01-01 10:00:00',
            'Update_time' => '2000-01-02 12:00:00',
            'Check_time' => '2000-01-02 13:00:00',
        ];

        $dbi->expects($this->once())
            ->method('fetchAssoc')
            ->with('res')
            ->will($this->returnValue($tmpres));

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->withConsecutive(
                ["SHOW TABLE STATUS FROM `db` WHERE Name = 'table'"],
                ['USE `db`'],
                ['SHOW CREATE TABLE `db`.`table`']
            )
            ->willReturnOnConsecutiveCalls(
                'res',
                'res',
                'res'
            );

        $dbi->expects($this->once())
            ->method('getError')
            ->will($this->returnValue('error occurred'));

        $dbi->expects($this->exactly(2))
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'db', $dbi)));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $result = $this->object->getTableDef(
            'db',
            'table',
            "\n",
            'example.com/err',
            true,
            true,
            false
        );

        $this->assertStringContainsString(
            '-- Error reading structure for table db.table: error occurred',
            $result
        );
    }

    public function testGetTableComments(): void
    {
        $_SESSION['relation'][0] = [
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ];
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [
                    'foo' => [
                        'foreign_table' => 'ftable',
                        'foreign_field' => 'ffield',
                    ],
                ],
                [
                    'fieldname' => [
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<',
                    ],
                ]
            );

        $GLOBALS['dbi'] = $dbi;
        $this->object->relation = new Relation($dbi);

        $method = new ReflectionMethod(ExportSql::class, 'getTableComments');
        $method->setAccessible(true);
        $result = $method->invoke(
            $this->object,
            'db',
            '',
            "\n",
            true,
            true
        );

        $this->assertStringContainsString(
            "-- MEDIA TYPES FOR TABLE :\n" .
            "--   fieldname\n" .
            '--       Test<',
            $result
        );

        $this->assertStringContainsString(
            "-- RELATIONSHIPS FOR TABLE :\n" .
            "--   foo\n" .
            '--       ftable -> ffield',
            $result
        );
    }

    /**
     * @group medium
     */
    public function testExportStructure(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('db', 't&bl')
            ->will(
                $this->returnValue(
                    [
                        [
                            'create' => 'bar',
                            'drop' => 'foo',
                        ],
                    ]
                )
            );

        $this->object = $this->getMockBuilder(ExportSql::class)
            ->setMethods(['getTableDef', 'getTriggers', 'getTableDefStandIn'])
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('getTableDef')
            ->with('db', 't&bl', "\n", 'example.com', false)
            ->will($this->returnValue('dumpText1'));

        $this->object->expects($this->at(1))
            ->method('getTableDef')
            ->with(
                'db',
                't&bl',
                "\n",
                'example.com',
                false
            )
            ->will($this->returnValue('dumpText3'));

        $this->object->expects($this->once())
            ->method('getTableDefStandIn')
            ->with('db', 't&bl', "\n")
            ->will($this->returnValue('dumpText4'));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

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
            '-- Table structure for table &quot;t&amp;bl&quot;',
            $result
        );

        $this->assertStringContainsString(
            'dumpText1',
            $result
        );

        // case 2
        unset($GLOBALS['sql_compatibility']);
        unset($GLOBALS['sql_backquotes']);

        $GLOBALS['sql_create_trigger'] = true;
        $GLOBALS['sql_drop_table'] = true;

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

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "-- Triggers t&amp;bl\n",
            $result
        );

        $this->assertStringContainsString(
            "foo;\nDELIMITER $$\nbarDELIMITER ;\n",
            $result
        );

        unset($GLOBALS['sql_create_trigger']);
        unset($GLOBALS['sql_drop_table']);

        // case 3
        $GLOBALS['sql_views_as_tables'] = false;

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

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "-- Structure for view t&amp;bl\n",
            $result
        );

        $this->assertStringContainsString(
            "DROP TABLE IF EXISTS `t&amp;bl`;\n" .
            'dumpText3',
            $result
        );

        // case 4
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('getColumns')
            ->will(
                $this->returnValue(
                    []
                )
            );
        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['sql_views_as_tables'] = true;
        unset($GLOBALS['sql_if_not_exists']);

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

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "CREATE TABLE`t&amp;bl`(\n\n);",
            $result
        );

        $this->assertStringContainsString(
            "DROP TABLE IF EXISTS `t&amp;bl`;\n",
            $result
        );

        // case 5

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

        $this->assertIsString($result);

        $this->assertStringContainsString(
            'dumpText4',
            $result
        );
    }

    /**
     * @group medium
     */
    public function testExportData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->blob = false;
        $a->numeric = true;
        $a->type = 'ts';
        $a->name = 'name';
        $a->length = 2;
        $flags[] = $a;

        $a = new stdClass();
        $a->blob = false;
        $a->numeric = true;
        $a->type = 'ts';
        $a->name = 'name';
        $a->length = 2;
        $flags[] = $a;

        $a = new stdClass();
        $a->blob = true;
        $a->numeric = false;
        $a->type = 'ts';
        $a->name = 'name';
        $a->length = 2;
        $flags[] = $a;

        $a = new stdClass();
        $a->type = 'bit';
        $a->blob = false;
        $a->numeric = false;
        $a->name = 'name';
        $a->length = 2;
        $flags[] = $a;

        $a = new stdClass();
        $a->blob = false;
        $a->numeric = true;
        $a->type = 'timestamp';
        $a->name = 'name';
        $a->length = 2;
        $flags[] = $a;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with('res')
            ->will($this->returnValue($flags));

        $dbi->expects($this->any())
            ->method('fieldFlags')
            ->will($this->returnValue('biNAry'));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with(
                'SELECT a FROM b WHERE 1',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_UNBUFFERED
            )
            ->will($this->returnValue('res'));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with('res')
            ->will($this->returnValue(5));

        $dbi->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    'test',
                    '10',
                    '6',
                    "\x00\x0a\x0d\x1a",
                ],
                null
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $_table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $_table->expects($this->once())
            ->method('isMerge')
            ->will($this->returnValue(false));
        $_table->expects($this->once())
            ->method('isView')
            ->will($this->returnValue(false));

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue($_table));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_max_query_size'] = 50000;
        $GLOBALS['sql_views_as_tables'] = true;
        $GLOBALS['sql_type'] = 'INSERT';
        $GLOBALS['sql_delayed'] = ' DELAYED';
        $GLOBALS['sql_ignore'] = true;
        $GLOBALS['sql_truncate'] = true;
        $GLOBALS['sql_insert_syntax'] = 'both';
        $GLOBALS['sql_hex_for_binary'] = true;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        ob_start();
        $this->object->exportData(
            'db',
            'table',
            "\n",
            'example.com/err',
            'SELECT a FROM b WHERE 1'
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            'TRUNCATE TABLE &quot;table&quot;;',
            $result
        );

        $this->assertStringContainsString(
            'SET IDENTITY_INSERT &quot;table&quot; ON ;',
            $result
        );

        $this->assertStringContainsString(
            'INSERT DELAYED IGNORE INTO &quot;table&quot; (&quot;name&quot;, ' .
            '&quot;name&quot;, &quot;name&quot;, &quot;name&quot;, ' .
            '&quot;name&quot;) VALUES',
            $result
        );

        $this->assertStringContainsString(
            '(NULL, test, 0x3130, 0x36, 0x000a0d1a);',
            $result
        );

        $this->assertStringContainsString(
            'SET IDENTITY_INSERT &quot;table&quot; OFF;',
            $result
        );
    }

    /**
     * @group medium
     */
    public function testExportDataWithUpdate(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->blob = false;
        $a->numeric = true;
        $a->type = 'real';
        $a->name = 'name';
        $a->length = 2;
        $a->table = 'tbl';
        $a->orgname = 'pma';
        $a->primary_key = 1;
        $flags[] = $a;

        $a = new stdClass();
        $a->blob = false;
        $a->numeric = true;
        $a->type = '';
        $a->name = 'name';
        $a->table = 'tbl';
        $a->orgname = 'pma';
        $a->length = 2;
        $a->primary_key = 0;
        $a->unique_key = 1;
        $flags[] = $a;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with('res')
            ->will($this->returnValue($flags));

        $dbi->expects($this->any())
            ->method('fieldFlags')
            ->will($this->returnValue('biNAry'));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with(
                'SELECT a FROM b WHERE 1',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_UNBUFFERED
            )
            ->will($this->returnValue('res'));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with('res')
            ->will($this->returnValue(2));

        $dbi->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    null,
                ],
                null
            );

        $_table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $_table->expects($this->once())
            ->method('isMerge')
            ->will($this->returnValue(false));
        $_table->expects($this->once())
            ->method('isView')
            ->will($this->returnValue(false));

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue($_table));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_views_as_tables'] = true;
        $GLOBALS['sql_type'] = 'UPDATE';
        $GLOBALS['sql_delayed'] = ' DELAYED';
        $GLOBALS['sql_ignore'] = true;
        $GLOBALS['sql_truncate'] = true;
        $GLOBALS['sql_insert_syntax'] = 'both';
        $GLOBALS['sql_hex_for_binary'] = true;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        ob_start();
        $this->object->exportData(
            'db',
            'table',
            "\n",
            'example.com/err',
            'SELECT a FROM b WHERE 1'
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            'UPDATE IGNORE &quot;table&quot; SET &quot;name&quot; = NULL,' .
            '&quot;name&quot; = NULL WHERE CONCAT(`tbl`.`pma`) IS NULL;',
            $result
        );
    }

    public function testExportDataWithIsView(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $_table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $_table->expects($this->once())
            ->method('isMerge')
            ->will($this->returnValue(false));
        $_table->expects($this->once())
            ->method('isView')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue($_table));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['sql_views_as_tables'] = false;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";
        $oldVal = $GLOBALS['sql_compatibility'] ?? '';
        $GLOBALS['sql_compatibility'] = 'NONE';
        $GLOBALS['sql_backquotes'] = true;

        ob_start();
        $this->assertTrue(
            $this->object->exportData('db', 'tbl', "\n", 'err.com', 'SELECT')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "-- VIEW `tbl`\n",
            $result
        );

        $this->assertStringContainsString(
            "-- Data: None\n",
            $result
        );

        // reset
        $GLOBALS['sql_compatibility'] = $oldVal;
    }

    public function testExportDataWithError(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getError')
            ->will($this->returnValue('err'));

        $_table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $_table->expects($this->once())
            ->method('isMerge')
            ->will($this->returnValue(false));
        $_table->expects($this->once())
            ->method('isView')
            ->will($this->returnValue(false));

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue($_table));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['sql_views_as_tables'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

        ob_start();
        $this->assertTrue(
            $this->object->exportData('db', 'table', "\n", 'err.com', 'SELECT')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            '-- Error reading data for table db.table: err',
            $result
        );
    }

    public function testMakeCreateTableMSSQLCompatible(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS (\" date DEFAULT NULL,\n" .
            "\" date DEFAULT NULL\n\" date NOT NULL,\n\" date NOT NULL\n," .
            " \" date NOT NULL DEFAULT 'asd'," .
            " ) unsigned NOT NULL\n, ) unsigned NOT NULL,\n" .
            " ) unsigned DEFAULT NULL\n, ) unsigned DEFAULT NULL,\n" .
            " ) unsigned NOT NULL DEFAULT 'dsa',\n" .
            " \" int(10) DEFAULT NULL,\n" .
            " \" tinyint(0) DEFAULT NULL\n" .
            " \" smallint(10) NOT NULL,\n" .
            " \" bigint(0) NOT NULL\n" .
            " \" bigint(0) NOT NULL DEFAULT '12'\n" .
            " \" float(22,2,) DEFAULT NULL,\n" .
            " \" double DEFAULT NULL\n" .
            " \" float(22,2,) NOT NULL,\n" .
            " \" double NOT NULL\n" .
            " \" double NOT NULL DEFAULT '213'\n";

        $method = new ReflectionMethod(
            ExportSql::class,
            'makeCreateTableMSSQLCompatible'
        );
        $method->setAccessible(true);
        $result = $method->invoke(
            $this->object,
            $query
        );

        $this->assertEquals(
            "CREATE TABLE (\" datetime DEFAULT NULL,\n" .
            "\" datetime DEFAULT NULL\n" .
            "\" datetime NOT NULL,\n" .
            "\" datetime NOT NULL\n" .
            ", \" datetime NOT NULL DEFAULT 'asd', ) NOT NULL\n" .
            ", ) NOT NULL,\n" .
            " ) DEFAULT NULL\n" .
            ", ) DEFAULT NULL,\n" .
            " ) NOT NULL DEFAULT 'dsa',\n" .
            " \" int DEFAULT NULL,\n" .
            " \" tinyint DEFAULT NULL\n" .
            " \" smallint NOT NULL,\n" .
            " \" bigint NOT NULL\n" .
            " \" bigint NOT NULL DEFAULT '12'\n" .
            " \" float DEFAULT NULL,\n" .
            " \" float DEFAULT NULL\n" .
            " \" float NOT NULL,\n" .
            " \" float NOT NULL\n" .
            " \" float NOT NULL DEFAULT '213'\n",
            $result
        );
    }

    public function testInitAlias(): void
    {
        $aliases = [
            'a' => [
                'alias' => 'aliastest',
                'tables' => [
                    'foo' => ['alias' => 'qwerty'],
                    'bar' => ['alias' => 'f'],
                ],
            ],
        ];
        $db = 'a';
        $table = null;

        $this->object->initAlias($aliases, $db, $table);
        $this->assertEquals('aliastest', $db);
        $this->assertNull($table);

        $db = 'foo';
        $table = 'qwerty';

        $this->object->initAlias($aliases, $db, $table);
        $this->assertEquals('foo', $db);
        $this->assertEquals('qwerty', $table);

        $db = 'a';
        $table = 'foo';

        $this->object->initAlias($aliases, $db, $table);
        $this->assertEquals('aliastest', $db);
        $this->assertEquals('qwerty', $table);
    }

    public function testGetAlias(): void
    {
        $aliases = [
            'a' => [
                'alias' => 'aliastest',
                'tables' => [
                    'foo' => [
                        'alias' => 'qwerty',
                        'columns' => [
                            'baz' => 'p',
                            'pqr' => 'pphymdain',
                        ],
                    ],
                    'bar' => [
                        'alias' => 'f',
                        'columns' => ['xy' => 'n'],
                    ],
                ],
            ],
        ];

        $this->assertEquals(
            'f',
            $this->object->getAlias($aliases, 'bar')
        );

        $this->assertEquals(
            'aliastest',
            $this->object->getAlias($aliases, 'a')
        );

        $this->assertEquals(
            'pphymdain',
            $this->object->getAlias($aliases, 'pqr')
        );

        $this->assertEquals(
            '',
            $this->object->getAlias($aliases, 'abc')
        );
    }

    public function testReplaceWithAlias(): void
    {
        $aliases = [
            'a' => [
                'alias' => 'aliastest',
                'tables' => [
                    'foo' => [
                        'alias' => 'bartest',
                        'columns' => [
                            'baz' => 'p',
                            'pqr' => 'pphymdain',
                        ],
                    ],
                    'bar' => [
                        'alias' => 'f',
                        'columns' => ['xy' => 'n'],
                    ],
                ],
            ],
        ];

        $db = 'a';
        $table = 'foo';
        $sql_query = "CREATE TABLE IF NOT EXISTS foo (\n"
            . "baz tinyint(3) unsigned NOT NULL COMMENT 'Primary Key',\n"
            . 'xyz varchar(255) COLLATE latin1_general_ci NOT NULL '
            . "COMMENT 'xyz',\n"
            . 'pqr varchar(10) COLLATE latin1_general_ci NOT NULL '
            . "COMMENT 'pqr',\n"
            . 'CONSTRAINT fk_om_dept FOREIGN KEY (baz) '
            . "REFERENCES dept_master (baz)\n"
            . ') ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE='
            . "latin1_general_ci COMMENT='List' AUTO_INCREMENT=5";
        $result = $this->object->replaceWithAliases(
            $sql_query,
            $aliases,
            $db,
            $table
        );

        $this->assertEquals(
            "CREATE TABLE IF NOT EXISTS `bartest` (\n" .
            "  `p` tinyint(3) UNSIGNED NOT NULL COMMENT 'Primary Key',\n" .
            "  `xyz` varchar(255) COLLATE latin1_general_ci NOT NULL COMMENT 'xyz',\n" .
            "  `pphymdain` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'pqr',\n" .
            "  CONSTRAINT `fk_om_dept` FOREIGN KEY (`p`) REFERENCES dept_master (`baz`)\n" .
            ") ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='List'",
            $result
        );

        $result = $this->object->replaceWithAliases($sql_query, [], '', '');

        $this->assertEquals(
            "CREATE TABLE IF NOT EXISTS foo (\n" .
            "  `baz` tinyint(3) UNSIGNED NOT NULL COMMENT 'Primary Key',\n" .
            "  `xyz` varchar(255) COLLATE latin1_general_ci NOT NULL COMMENT 'xyz',\n" .
            "  `pqr` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'pqr',\n" .
            "  CONSTRAINT `fk_om_dept` FOREIGN KEY (`baz`) REFERENCES dept_master (`baz`)\n" .
            ") ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='List'",
            $result
        );

        $table = 'bar';
        $sql_query = 'CREATE TRIGGER `BEFORE_bar_INSERT` '
            . 'BEFORE INSERT ON `bar` '
            . 'FOR EACH ROW BEGIN '
            . 'SET @cnt=(SELECT count(*) FROM bar WHERE '
            . 'xy=NEW.xy AND id=NEW.id AND '
            . 'abc=NEW.xy LIMIT 1); '
            . 'IF @cnt<>0 THEN '
            . 'SET NEW.xy=1; '
            . 'END IF; END';
        $result = $this->object->replaceWithAliases(
            $sql_query,
            $aliases,
            $db,
            $table
        );

        $this->assertEquals(
            'CREATE TRIGGER `BEFORE_bar_INSERT` BEFORE INSERT ON `f` FOR EACH ROW BEGIN ' .
            'SET @cnt=(SELECT count(*) FROM `f` WHERE `n`=NEW.`n` AND id=NEW.id AND abc=NEW.`n` LIMIT 1); ' .
            'IF @cnt<>0 THEN ' .
            'SET NEW.`n`=1; ' .
            'END IF; ' .
            'END',
            $result
        );
    }
}
