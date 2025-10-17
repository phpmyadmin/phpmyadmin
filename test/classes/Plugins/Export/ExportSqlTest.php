<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
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
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

use function array_shift;
use function ob_get_clean;
use function ob_start;

use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_UNIQUE_KEY_FLAG;
use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportSql
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
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
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
    public function testSetPropertiesWithHideSql(): void
    {
        // test with hide structure and hide sql as true
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;

        $method = new ReflectionMethod(ExportSql::class, 'setProperties');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $properties = $method->invoke($this->object, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);
        self::assertSame('SQL', $properties->getText());
        self::assertNull($properties->getOptions());
    }

    /**
     * @group medium
     */
    public function testSetProperties(): void
    {
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

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db',
            'relation' => 'relation',
            'column_info' => 'column_info',
            'relwork' => true,
            'mimework' => true,
        ]);
        $_SESSION = ['relation' => [$GLOBALS['server'] => $relationParameters->toArray()]];

        $method = new ReflectionMethod(ExportSql::class, 'setProperties');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $properties = $method->invoke($this->object, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);
        self::assertSame('SQL', $properties->getText());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $properties = $generalOptions->getProperties();

        $property = array_shift($properties);

        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertInstanceOf(BoolPropertyItem::class, $property->getSubgroupHeader());

        $leaves = $property->getProperties();

        $leaf = array_shift($leaves);
        self::assertInstanceOf(TextPropertyItem::class, $leaf);

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $property = array_shift($properties);
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($properties);
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($properties);
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($properties);
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($properties);
        self::assertInstanceOf(SelectPropertyItem::class, $property);

        self::assertSame([
            'v1' => 'v1',
            'v2' => 'v2',
        ], $property->getValues());

        $property = array_shift($properties);
        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertInstanceOf(RadioPropertyItem::class, $property->getSubgroupHeader());

        $structureOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $structureOptions);

        $properties = $structureOptions->getProperties();

        $property = array_shift($properties);

        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertInstanceOf(MessageOnlyPropertyItem::class, $property->getSubgroupHeader());

        $leaves = $property->getProperties();

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        self::assertSame(
            'Add <code>DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT</code><code> / TRIGGER</code> statement',
            $leaf->getText()
        );

        $leaf = array_shift($leaves);
        self::assertInstanceOf(OptionsPropertySubgroup::class, $leaf);

        self::assertCount(2, $leaf->getProperties());

        self::assertInstanceOf(BoolPropertyItem::class, $leaf->getSubgroupHeader());

        $leaf = array_shift($leaves);
        self::assertInstanceOf(OptionsPropertySubgroup::class, $leaf);

        self::assertCount(3, $leaf->getProperties());

        self::assertInstanceOf(BoolPropertyItem::class, $leaf->getSubgroupHeader());

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = array_shift($leaves);
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $property = array_shift($properties);
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $dataOptions = array_shift($generalOptionsArray);
        self::assertInstanceOf(OptionsPropertyMainGroup::class, $dataOptions);

        $properties = $dataOptions->getProperties();

        self::assertCount(7, $properties);

        self::assertCount(2, $properties[1]->getProperties());
    }

    public function testExportRoutines(): void
    {
        $GLOBALS['crlf'] = '##';
        $GLOBALS['sql_drop_table'] = true;

        $this->expectOutputString(
            '##DELIMITER $$##DROP PROCEDURE IF EXISTS `test_proc1`$$##CREATE PROCEDURE'
                . ' `test_proc1` (`p` INT)   BEGIN END$$####DROP PROCEDURE IF EXISTS'
                . ' `test_proc2`$$##CREATE PROCEDURE `test_proc2` (`p` INT)   BEGIN END$$####DROP'
                . ' FUNCTION IF EXISTS `test_func`$$##CREATE FUNCTION'
                . ' `test_func` (`p` INT) RETURNS INT(11)  BEGIN END$$####DELIMITER ;##'
        );

        $this->object->exportRoutines('test_db');
    }

    public function testExportComment(): void
    {
        $method = new ReflectionMethod(ExportSql::class, 'exportComment');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $GLOBALS['crlf'] = '##';
        $GLOBALS['sql_include_comments'] = true;

        self::assertSame('--##', $method->invoke($this->object, ''));

        self::assertSame('-- Comment##', $method->invoke($this->object, 'Comment'));

        $GLOBALS['sql_include_comments'] = false;

        self::assertSame('', $method->invoke($this->object, 'Comment'));

        unset($GLOBALS['sql_include_comments']);

        self::assertSame('', $method->invoke($this->object, 'Comment'));
    }

    public function testPossibleCRLF(): void
    {
        $method = new ReflectionMethod(ExportSql::class, 'possibleCRLF');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $GLOBALS['crlf'] = '##';
        $GLOBALS['sql_include_comments'] = true;

        self::assertSame('##', $method->invoke($this->object, ''));

        self::assertSame('##', $method->invoke($this->object, 'Comment'));

        $GLOBALS['sql_include_comments'] = false;

        self::assertSame('', $method->invoke($this->object, 'Comment'));

        unset($GLOBALS['sql_include_comments']);

        self::assertSame('', $method->invoke($this->object, 'Comment'));
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

        $this->expectOutputString('SET FOREIGN_KEY_CHECKS=1;COMMIT;');

        self::assertTrue($this->object->exportFooter());
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
        self::assertTrue($this->object->exportHeader());
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('h1C', $result);

        self::assertStringContainsString('h2C', $result);

        self::assertStringContainsString("SET FOREIGN_KEY_CHECKS=0;\n", $result);

        self::assertStringContainsString('40101 SET', $result);

        self::assertStringContainsString("SET FOREIGN_KEY_CHECKS=0;\n" .
        "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" .
        "START TRANSACTION;\n" .
        "SET time_zone = \"+00:00\";\n", $result);
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
        self::assertTrue($this->object->exportDBCreate('db', 'database'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("DROP DATABASE IF EXISTS `db`;\n", $result);

        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `db` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;',
            $result
        );

        self::assertStringContainsString('USE `db`;', $result);

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
        self::assertTrue($this->object->exportDBCreate('db', 'database'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("DROP DATABASE IF EXISTS db;\n", $result);

        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS db DEFAULT CHARACTER SET testcollation;',
            $result
        );

        self::assertStringContainsString('USE db;', $result);
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

        ob_start();
        self::assertTrue($this->object->exportDBHeader('testDB'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&quot;testDB&quot;', $result);

        // case 2
        unset($GLOBALS['sql_compatibility']);
        unset($GLOBALS['sql_backquotes']);

        ob_start();
        self::assertTrue($this->object->exportDBHeader('testDB'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('testDB', $result);
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
            ->with('SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \'db\';')
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
        self::assertTrue($this->object->exportEvents('db'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("DELIMITER $$\n", $result);

        self::assertStringContainsString("DELIMITER ;\n", $result);

        self::assertStringContainsString("f1event$$\n", $result);

        self::assertStringContainsString("f2event$$\n", $result);
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
        self::assertTrue($this->object->exportDBFooter('db'));
        $result = ob_get_clean();

        self::assertSame('SqlConstraints', $result);
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

        self::assertStringContainsString('DROP VIEW IF EXISTS `view`;', $result);

        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `view` (`cname` int);', $result);
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
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $result = $method->invoke($this->object, 'db', 'view', "\n");

        self::assertSame("CREATE TABLE `view`(\n" .
        "    `fname` char COLLATE utf-8 NOT NULL DEFAULT 'a' COMMENT 'cmt'\n" .
        ");\n", $result);

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

        $result = $method->invoke($this->object, 'db', 'view', "\n", false);

        self::assertSame("CREATE TABLE IF NOT EXISTS `view`(\n" .
        "    `fname` char COLLATE utf-8 DEFAULT NULL COMMENT 'cmt'\n" .
        ")\n", $result);
    }

    /**
     * @group medium
     * @requires PHPUnit < 10
     */
    public function testGetTableDef(): void
    {
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_auto_increment'] = true;
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_if_not_exists'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";
        if (isset($GLOBALS['sql_constraints'])) {
            unset($GLOBALS['sql_constraints']);
        }

        if (isset($GLOBALS['no_constraints_comments'])) {
            unset($GLOBALS['no_constraints_comments']);
        }

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->never())
            ->method('fetchSingleRow');

        $resultStub->expects($this->once())
            ->method('numRows')
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

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue($tmpres));

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->withConsecutive(
                ["SHOW TABLE STATUS FROM `db` WHERE Name = 'table'"],
                ['USE `db`'],
                ['SHOW CREATE TABLE `db`.`table`']
            )
            ->willReturnOnConsecutiveCalls($resultStub, $resultStub, $resultStub);

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

        $resultStub->expects($this->exactly(1))
            ->method('fetchRow')
            ->will($this->returnValue($row));

        $dbi->expects($this->exactly(2))
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'db', $dbi)));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $result = $this->object->getTableDef('db', 'table', "\n", 'example.com/err', true, true, false);

        self::assertStringContainsString('-- Creation: Jan 01, 2000 at 10:00 AM', $result);

        self::assertStringContainsString('-- Last update: Jan 02, 2000 at 12:00 PM', $result);

        self::assertStringContainsString('-- Last check: Jan 02, 2000 at 01:00 PM', $result);

        self::assertStringContainsString('DROP TABLE IF EXISTS `table`;', $result);

        self::assertStringContainsString('CREATE TABLE `table`', $result);

        self::assertStringContainsString('-- Constraints for dumped tables', $GLOBALS['sql_constraints']);

        self::assertStringContainsString('-- Constraints for table "table"', $GLOBALS['sql_constraints']);

        self::assertStringContainsString('ALTER TABLE "table"', $GLOBALS['sql_constraints']);

        self::assertStringContainsString('ADD CONSTRAINT', $GLOBALS['sql_constraints']);

        self::assertStringContainsString('ALTER TABLE "table"', $GLOBALS['sql_constraints_query']);

        self::assertStringContainsString('ADD CONSTRAINT', $GLOBALS['sql_constraints_query']);

        self::assertStringContainsString('ALTER TABLE "table"', $GLOBALS['sql_drop_foreign_keys']);

        self::assertStringContainsString('DROP FOREIGN KEY', $GLOBALS['sql_drop_foreign_keys']);
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testGetTableDefWithError(): void
    {
        $GLOBALS['sql_compatibility'] = '';
        $GLOBALS['sql_auto_increment'] = true;
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['sql_backquotes'] = false;
        $GLOBALS['sql_if_not_exists'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

        if (isset($GLOBALS['sql_constraints'])) {
            unset($GLOBALS['sql_constraints']);
        }

        if (isset($GLOBALS['no_constraints_comments'])) {
            unset($GLOBALS['no_constraints_comments']);
        }

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->never())
            ->method('fetchSingleRow');

        $resultStub->expects($this->once())
            ->method('numRows')
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

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue($tmpres));

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->withConsecutive(
                ["SHOW TABLE STATUS FROM `db` WHERE Name = 'table'"],
                ['USE `db`'],
                ['SHOW CREATE TABLE `db`.`table`']
            )
            ->willReturnOnConsecutiveCalls($resultStub, $resultStub, $resultStub);

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

        $result = $this->object->getTableDef('db', 'table', "\n", 'example.com/err', true, true, false);

        self::assertStringContainsString('-- Error reading structure for table db.table: error occurred', $result);
    }

    public function testGetTableComments(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ])->toArray();
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
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $result = $method->invoke($this->object, 'db', '', true, true);

        self::assertStringContainsString("-- MEDIA TYPES FOR TABLE :\n" .
        "--   fieldname\n" .
        '--       Test<', $result);

        self::assertStringContainsString("-- RELATIONSHIPS FOR TABLE :\n" .
        "--   foo\n" .
        '--       ftable -> ffield', $result);
    }

    /**
     * @group medium
     */
    public function testExportStructure(): void
    {
        $GLOBALS['sql_compatibility'] = 'MSSQL';
        $GLOBALS['sql_backquotes'] = true;
        $GLOBALS['sql_include_comments'] = true;
        $GLOBALS['crlf'] = "\n";

        // case 1
        ob_start();
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'create_table',
            'test'
        ));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Table structure for table &quot;test_table&quot;', $result);
        self::assertStringContainsString('CREATE TABLE `test_table`', $result);

        // case 2
        unset($GLOBALS['sql_compatibility']);
        unset($GLOBALS['sql_backquotes']);

        $GLOBALS['sql_create_trigger'] = true;
        $GLOBALS['sql_drop_table'] = true;

        ob_start();
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'triggers',
            'test'
        ));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Triggers test_table', $result);
        self::assertStringContainsString(
            "CREATE TRIGGER `test_trigger` AFTER INSERT ON `test_table` FOR EACH ROW BEGIN END\n$$",
            $result
        );

        unset($GLOBALS['sql_create_trigger']);
        unset($GLOBALS['sql_drop_table']);

        // case 3
        $GLOBALS['sql_views_as_tables'] = false;

        ob_start();
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'create_view',
            'test'
        ));
        $result = ob_get_clean();
        $sqlViewsProp = new ReflectionProperty(ExportSql::class, 'sqlViews');
        if (PHP_VERSION_ID < 80100) {
            $sqlViewsProp->setAccessible(true);
        }

        $sqlViews = $sqlViewsProp->getValue($this->object);

        self::assertSame('', $result);
        self::assertIsString($sqlViews);
        self::assertStringContainsString('-- Structure for view test_table', $sqlViews);
        self::assertStringContainsString('DROP TABLE IF EXISTS `test_table`;', $sqlViews);
        self::assertStringContainsString('CREATE TABLE `test_table`', $sqlViews);

        // case 4
        $GLOBALS['sql_views_as_tables'] = true;
        unset($GLOBALS['sql_if_not_exists']);

        ob_start();
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'create_view',
            'test'
        ));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Structure for view test_table exported as a table', $result);
        self::assertStringContainsString('DROP TABLE IF EXISTS `test_table`;', $result);
        self::assertStringContainsString('CREATE TABLE`test_table`', $result);

        // case 5
        ob_start();
        self::assertTrue($this->object->exportStructure(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'stand_in',
            'test'
        ));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Stand-in structure for view test_table', $result);
        self::assertStringContainsString('CREATE TABLE `test_table`', $result);
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
        $a->name = 'name';
        $a->length = 2;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_LONG, 0, $a);

        $a = new stdClass();
        $a->name = 'name';
        $a->length = 2;
        $flags[] = new FieldMetadata(-1, MYSQLI_NUM_FLAG, $a);

        $a = new stdClass();
        $a->name = 'name';
        $a->length = 2;
        $a->charsetnr = 63;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $a);

        $a = new stdClass();
        $a->name = 'name';
        $a->length = 2;
        $a->charsetnr = 63;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $a);

        $a = new stdClass();
        $a->name = 'name';
        $a->length = 2;
        $a->charsetnr = 63;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_BLOB, 0, $a);

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SELECT a FROM b WHERE 1', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numFields')
            ->will($this->returnValue(5));

        $resultStub->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    'test',
                    '10',
                    '6',
                    "\x00\x0a\x0d\x1a",
                ],
                []
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
        $this->object->exportData('db', 'table', "\n", 'example.com/err', 'SELECT a FROM b WHERE 1');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('TRUNCATE TABLE &quot;table&quot;;', $result);

        self::assertStringContainsString('SET IDENTITY_INSERT &quot;table&quot; ON ;', $result);

        self::assertStringContainsString('INSERT DELAYED IGNORE INTO &quot;table&quot; (&quot;name&quot;, ' .
        '&quot;name&quot;, &quot;name&quot;, &quot;name&quot;, ' .
        '&quot;name&quot;) VALUES', $result);

        self::assertStringContainsString('(NULL, \'test\', 0x3130, 0x36, 0x000a0d1a);', $result);

        self::assertStringContainsString('SET IDENTITY_INSERT &quot;table&quot; OFF;', $result);
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
        $a->name = 'name';
        $a->orgname = 'pma';
        $a->table = 'tbl';
        $a->orgtable = 'tbl';
        $a->length = 2;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_FLOAT, MYSQLI_PRI_KEY_FLAG, $a);

        $a = new stdClass();
        $a->name = 'name';
        $a->orgname = 'pma';
        $a->table = 'tbl';
        $a->orgtable = 'tbl';
        $a->length = 2;
        $flags[] = new FieldMetadata(MYSQLI_TYPE_FLOAT, MYSQLI_UNIQUE_KEY_FLAG, $a);

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SELECT a FROM b WHERE 1', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numFields')
            ->will($this->returnValue(2));

        $resultStub->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                [
                    null,
                    null,
                ],
                []
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
        $this->object->exportData('db', 'table', "\n", 'example.com/err', 'SELECT a FROM b WHERE 1');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('UPDATE IGNORE &quot;table&quot; SET &quot;name&quot; = NULL,' .
        '&quot;name&quot; = NULL WHERE CONCAT(`tbl`.`pma`) IS NULL;', $result);
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
        self::assertTrue($this->object->exportData('db', 'tbl', "\n", 'err.com', 'SELECT'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("-- VIEW `tbl`\n", $result);

        self::assertStringContainsString("-- Data: None\n", $result);

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
        self::assertTrue($this->object->exportData('db', 'table', "\n", 'err.com', 'SELECT'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('-- Error reading data for table db.table: err', $result);
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

        $method = new ReflectionMethod(ExportSql::class, 'makeCreateTableMSSQLCompatible');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $result = $method->invoke($this->object, $query);

        self::assertSame("CREATE TABLE (\" datetime DEFAULT NULL,\n" .
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
        " \" float NOT NULL DEFAULT '213'\n", $result);
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
        $table = '';

        $this->object->initAlias($aliases, $db, $table);
        self::assertSame('aliastest', $db);
        self::assertSame('', $table);

        $db = 'foo';
        $table = 'qwerty';

        $this->object->initAlias($aliases, $db, $table);
        self::assertSame('foo', $db);
        self::assertSame('qwerty', $table);

        $db = 'a';
        $table = 'foo';

        $this->object->initAlias($aliases, $db, $table);
        self::assertSame('aliastest', $db);
        self::assertSame('qwerty', $table);
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

        self::assertSame('f', $this->object->getAlias($aliases, 'bar'));

        self::assertSame('aliastest', $this->object->getAlias($aliases, 'a'));

        self::assertSame('pphymdain', $this->object->getAlias($aliases, 'pqr'));

        self::assertSame('', $this->object->getAlias($aliases, 'abc'));
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
        $result = $this->object->replaceWithAliases(null, $sql_query, $aliases, $db, $table);

        self::assertSame("CREATE TABLE IF NOT EXISTS `bartest` (\n" .
        "  `p` tinyint(3) UNSIGNED NOT NULL COMMENT 'Primary Key',\n" .
        "  `xyz` varchar(255) COLLATE latin1_general_ci NOT NULL COMMENT 'xyz',\n" .
        "  `pphymdain` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'pqr',\n" .
        "  CONSTRAINT `fk_om_dept` FOREIGN KEY (`p`) REFERENCES dept_master (`baz`)\n" .
        ") ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='List'", $result);

        $result = $this->object->replaceWithAliases(null, $sql_query, [], '', '');

        self::assertSame("CREATE TABLE IF NOT EXISTS foo (\n" .
        "  `baz` tinyint(3) UNSIGNED NOT NULL COMMENT 'Primary Key',\n" .
        "  `xyz` varchar(255) COLLATE latin1_general_ci NOT NULL COMMENT 'xyz',\n" .
        "  `pqr` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'pqr',\n" .
        "  CONSTRAINT `fk_om_dept` FOREIGN KEY (`baz`) REFERENCES dept_master (`baz`)\n" .
        ") ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='List'", $result);

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
        $result = $this->object->replaceWithAliases('$$', $sql_query, $aliases, $db, $table);

        self::assertSame('CREATE TRIGGER `BEFORE_bar_INSERT` BEFORE INSERT ON `f` FOR EACH ROW BEGIN ' .
        'SET @cnt=(SELECT count(*) FROM `f` WHERE `n`=NEW.`n` AND id=NEW.id AND abc=NEW.`n` LIMIT 1); ' .
        'IF @cnt<>0 THEN ' .
        'SET NEW.`n`=1; ' .
        'END IF; ' .
        'END', $result);

        $table = 'bar';
        $sql_query = <<<'SQL'
CREATE FUNCTION `HTML_UnEncode`(`x` TEXT CHARSET utf8) RETURNS text CHARSET utf8
BEGIN

DECLARE TextString TEXT ;
SET TextString = x ;

#quotation mark
IF INSTR( x , '&quot;' )
THEN SET TextString = REPLACE(TextString, '&quot;','"') ;
END IF ;

#apostrophe
IF INSTR( x , '&apos;' )
THEN SET TextString = REPLACE(TextString, '&apos;','"') ;
END IF ;

RETURN TextString ;

END
SQL;

        $result = $this->object->replaceWithAliases('$$', $sql_query, $aliases, $db, $table);

        $expectedQuery = <<<'SQL'
CREATE FUNCTION `HTML_UnEncode` (`x` TEXT CHARSET utf8) RETURNS TEXT CHARSET utf8  BEGIN

DECLARE TextString TEXT ;
SET TextString = x ;

#quotation mark
IF INSTR( x , '&quot;' )
THEN SET TextString = REPLACE(TextString, '&quot;','"') ;
END IF ;

#apostrophe
IF INSTR( x , '&apos;' )
THEN SET TextString = REPLACE(TextString, '&apos;','"') ;
END IF ;

RETURN TextString ;

END
SQL;

        self::assertSame($expectedQuery, $result);
    }
}
