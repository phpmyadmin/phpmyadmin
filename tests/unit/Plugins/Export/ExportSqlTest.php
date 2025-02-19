<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_UNIQUE_KEY_FLAG;

#[CoversClass(ExportSql::class)]
#[Medium]
class ExportSqlTest extends AbstractTestCase
{
    protected ExportSql $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
        Config::getInstance()->selectedServer['DisableIS'] = true;
        Export::$outputKanjiConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = false;
        Export::$saveOnServer = false;
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;

        $this->object = new ExportSql(
            new Relation($dbi),
            new Export($dbi),
            new Transformations(),
        );
        $this->object->useSqlBackquotes(false);
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

    public function testSetPropertiesWithHideSql(): void
    {
        // test with hide structure and hide sql as true
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;

        $method = new ReflectionMethod(ExportSql::class, 'setProperties');
        $properties = $method->invoke($this->object, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);
        self::assertSame('SQL', $properties->getText());
        self::assertNull($properties->getOptions());
    }

    public function testSetProperties(): void
    {
        // test with hide structure and hide sql as false
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getCompatibilities')
            ->willReturn(['v1', 'v2']);

        DatabaseInterface::$instance = $dbi;
        ExportPlugin::$exportType = ExportType::Server;
        ExportPlugin::$singleTable = false;

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db',
            'relation' => 'relation',
            'column_info' => 'column_info',
            'relwork' => true,
            'mimework' => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $method = new ReflectionMethod(ExportSql::class, 'setProperties');
        $properties = $method->invoke($this->object, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);
        self::assertSame('SQL', $properties->getText());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $generalOptionsArray = $options->getProperties();

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $properties = $generalOptions->getProperties();

        $property = $properties->current();
        $properties->next();

        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertInstanceOf(
            BoolPropertyItem::class,
            $property->getSubgroupHeader(),
        );

        $leaves = $property->getProperties();

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(TextPropertyItem::class, $leaf);

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $property = $properties->current();
        $properties->next();
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $properties->current();
        $properties->next();
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $properties->current();
        $properties->next();
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $properties->current();
        $properties->next();
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $properties->current();
        $properties->next();
        self::assertInstanceOf(SelectPropertyItem::class, $property);

        self::assertSame(
            ['v1' => 'v1', 'v2' => 'v2'],
            $property->getValues(),
        );

        $property = $properties->current();
        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertInstanceOf(
            RadioPropertyItem::class,
            $property->getSubgroupHeader(),
        );

        $structureOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $structureOptions);

        $properties = $structureOptions->getProperties();

        $property = $properties->current();
        $properties->next();

        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertInstanceOf(
            MessageOnlyPropertyItem::class,
            $property->getSubgroupHeader(),
        );

        $leaves = $property->getProperties();

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        self::assertSame(
            'Add <code>DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT</code><code> / TRIGGER</code> statement',
            $leaf->getText(),
        );

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(OptionsPropertySubgroup::class, $leaf);

        self::assertCount(
            2,
            $leaf->getProperties(),
        );

        self::assertInstanceOf(
            BoolPropertyItem::class,
            $leaf->getSubgroupHeader(),
        );

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(OptionsPropertySubgroup::class, $leaf);

        self::assertCount(
            3,
            $leaf->getProperties(),
        );

        self::assertInstanceOf(
            BoolPropertyItem::class,
            $leaf->getSubgroupHeader(),
        );

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $leaf = $leaves->current();
        $leaves->next();
        self::assertInstanceOf(BoolPropertyItem::class, $leaf);

        $property = $properties->current();
        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $dataOptions = $generalOptionsArray->current();
        self::assertInstanceOf(OptionsPropertyMainGroup::class, $dataOptions);

        $properties = $dataOptions->getProperties();

        self::assertCount(7, $properties);

        $properties->next();

        $property = $properties->current();
        self::assertInstanceOf(OptionsPropertyGroup::class, $property);

        self::assertCount(
            2,
            $property->getProperties(),
        );
    }

    public function testExportRoutines(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_drop_table' => 'On']);

        $this->object->setExportOptions($request, []);

        $this->expectOutputString(
            "\n" . 'DELIMITER $$' . "\n" . 'DROP PROCEDURE IF EXISTS `test_proc1`$$' . "\n" . 'CREATE PROCEDURE'
                . ' `test_proc1` (`p` INT)   BEGIN END$$' . "\n\n" . 'DROP PROCEDURE IF EXISTS'
                . ' `test_proc2`$$' . "\n" . 'CREATE PROCEDURE `test_proc2` (`p` INT)   BEGIN END$$' . "\n\n" . 'DROP'
                . ' FUNCTION IF EXISTS `test_func`$$' . "\n" . 'CREATE FUNCTION'
                . ' `test_func` (`p` INT) RETURNS INT(11)  BEGIN END$$' . "\n\n" . 'DELIMITER ;' . "\n",
        );

        $this->object->exportRoutines('test_db');
    }

    public function testExportComment(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_include_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        $method = new ReflectionMethod(ExportSql::class, 'exportComment');

        self::assertSame(
            '--' . "\n",
            $method->invoke($this->object, ''),
        );

        self::assertSame(
            '-- Comment' . "\n",
            $method->invoke($this->object, 'Comment'),
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');
        $this->object->setExportOptions($request, []);

        self::assertSame(
            '',
            $method->invoke($this->object, 'Comment'),
        );

        self::assertSame(
            '',
            $method->invoke($this->object, 'Comment'),
        );
    }

    public function testPossibleCRLF(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_include_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        $method = new ReflectionMethod(ExportSql::class, 'possibleCRLF');

        self::assertSame(
            "\n",
            $method->invoke($this->object, ''),
        );

        self::assertSame(
            "\n",
            $method->invoke($this->object, 'Comment'),
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');
        $this->object->setExportOptions($request, []);

        self::assertSame(
            '',
            $method->invoke($this->object, 'Comment'),
        );

        self::assertSame(
            '',
            $method->invoke($this->object, 'Comment'),
        );
    }

    public function testExportFooter(): void
    {
        Current::$charset = 'utf-8';
        ExportSql::$oldTimezone = 'GMT';
        Export::$asFile = true;
        Export::$outputCharsetConversion = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('query')
            ->with('SET time_zone = "GMT"');

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_use_transaction' => 'On', 'sql_disable_fk' => 'On', 'sql_utc_time' => 'On']);

        $this->object->setExportOptions($request, []);

        $this->expectOutputString('SET FOREIGN_KEY_CHECKS=1;' . "\n" . 'COMMIT;' . "\n");

        self::assertTrue(
            $this->object->exportFooter(),
        );
    }

    public function testExportHeader(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['port'] = 80;
        ExportSql::$oldTimezone = 'GMT';
        Export::$asFile = true;
        Export::$outputCharsetConversion = true;
        Current::$charset = 'utf-8';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SET SQL_MODE=""');

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with('SELECT @@session.time_zone')
            ->willReturn('old_tz');

        $dbi->expects(self::once())
            ->method('query')
            ->with('SET time_zone = "+00:00"');

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_include_comments' => 'On',
                'sql_header_comment' => "h1C\nh2C",
                'sql_use_transaction' => 'On',
                'sql_disable_fk' => 'On',
                'sql_utc_time' => 'On',
            ]);

        $this->object->setExportOptions($request, []);

        ob_start();
        self::assertTrue(
            $this->object->exportHeader(),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('h1C', $result);

        self::assertStringContainsString('h2C', $result);

        self::assertStringContainsString("SET FOREIGN_KEY_CHECKS=0;\n", $result);

        self::assertStringContainsString('40101 SET', $result);

        self::assertStringContainsString(
            "SET FOREIGN_KEY_CHECKS=0;\n" .
            "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" .
            "START TRANSACTION;\n" .
            "SET time_zone = \"+00:00\";\n",
            $result,
        );
    }

    public function testExportDBCreate(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getDbCollation')
            ->with('db')
            ->willReturn('utf8_general_ci');

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_structure_or_data' => 'structure_and_data',
                'sql_backquotes' => 'true',
                'sql_drop_database' => 'On',
            ]);

        $this->object->setExportOptions($request, []);

        ob_start();
        self::assertTrue(
            $this->object->exportDBCreate('db'),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("DROP DATABASE IF EXISTS `db`;\n", $result);

        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `db` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;',
            $result,
        );

        self::assertStringContainsString('USE `db`;', $result);

        // case2: no backquotes
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getDbCollation')
            ->with('db')
            ->willReturn('testcollation');

        DatabaseInterface::$instance = $dbi;

        $this->object->useSqlBackquotes(false);

        ob_start();
        self::assertTrue(
            $this->object->exportDBCreate('db'),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("DROP DATABASE IF EXISTS db;\n", $result);

        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS db DEFAULT CHARACTER SET testcollation;',
            $result,
        );

        self::assertStringContainsString('USE db;', $result);
    }

    public function testExportDBHeader(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_backquotes' => 'true',
                'sql_include_comments' => 'On',
                'sql_compatibility' => 'MSSQL',
            ]);

        $this->object->setExportOptions($request, []);

        ob_start();
        self::assertTrue(
            $this->object->exportDBHeader('testDB'),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&quot;testDB&quot;', $result);

        // case 2
        $this->object->useSqlBackquotes(false);

        ob_start();
        self::assertTrue(
            $this->object->exportDBHeader('testDB'),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('testDB', $result);
    }

    public function testExportEvents(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchSingleColumn')
            ->with('SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \'db\'')
            ->willReturn(['f1', 'f2']);

        $dbi->expects(self::exactly(2))
            ->method('fetchValue')
            ->willReturnMap([
                ['SHOW CREATE EVENT `db`.`f1`', 'Create Event', ConnectionType::User, 'f1event'],
                ['SHOW CREATE EVENT `db`.`f2`', 'Create Event', ConnectionType::User, 'f2event'],
            ]);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;

        ob_start();
        self::assertTrue(
            $this->object->exportEvents('db'),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("DELIMITER $$\n", $result);

        self::assertStringContainsString("DELIMITER ;\n", $result);

        self::assertStringContainsString("f1event$$\n", $result);

        self::assertStringContainsString("f2event$$\n", $result);
    }

    public function testExportDBFooter(): void
    {
        $this->object->sqlConstraints = 'SqlConstraints';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        DatabaseInterface::$instance = $dbi;

        ob_start();
        self::assertTrue(
            $this->object->exportDBFooter('db'),
        );
        $result = ob_get_clean();

        self::assertSame('SqlConstraints', $result);
    }

    public function testGetTableDefStandIn(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('db', 'view')
            ->willReturn([new Column('cname', 'int', null, false, '', null, '', '', '')]);

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_drop_table' => 'On', 'sql_if_not_exists' => 'On']);

        $this->object->setExportOptions($request, []);

        $result = $this->object->getTableDefStandIn('db', 'view');

        self::assertStringContainsString('DROP VIEW IF EXISTS `view`;', $result);

        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `view` (' . "\n" . '`cname` int' . "\n" . ');' . "\n",
            $result,
        );
    }

    public function testGetTableDefForView(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $dbi->expects(self::any())
            ->method('getColumns')
            ->with('db', 'view')
            ->willReturn([
                new Column(
                    'fname',
                    'char',
                    'utf-8',
                    false,
                    '',
                    'a',
                    '',
                    '',
                    'cmt',
                ),
            ]);

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_compatibility' => 'MSSQL', 'sql_if_not_exists' => 'On']);

        $this->object->setExportOptions($request, []);

        $method = new ReflectionMethod(ExportSql::class, 'getTableDefForView');
        $result = $method->invoke($this->object, 'db', 'view');

        self::assertSame(
            "CREATE TABLE `view`(\n" .
            "    `fname` char COLLATE utf-8 NOT NULL DEFAULT 'a' COMMENT 'cmt'\n" .
            ");\n",
            $result,
        );

        // case 2
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $dbi->expects(self::any())
            ->method('getColumns')
            ->with('db', 'view')
            ->willReturn([
                new Column(
                    'fname',
                    'char',
                    'utf-8',
                    true,
                    '',
                    null,
                    '',
                    '',
                    'cmt',
                ),
            ]);
        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_if_not_exists' => 'On']);

        $this->object->setExportOptions($request, []);

        $result = $method->invoke($this->object, 'db', 'view');

        self::assertSame(
            "CREATE TABLE IF NOT EXISTS `view`(\n" .
            "    `fname` char COLLATE utf-8 DEFAULT NULL COMMENT 'cmt'\n" .
            ");\n",
            $result,
        );
    }

    public function testGetTableDef(): void
    {
        $this->object->sqlConstraints = null;

        ExportSql::$noConstraintsComments = false;

        $createTableStatement = <<<'SQL'
CREATE TABLE `table` (
    `payment_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` smallint(5) unsigned NOT NULL,
    `staff_id` tinyint(3) unsigned NOT NULL,
    `rental_id` int(11) DEFAULT NULL,
    `amount` decimal(5,2) NOT NULL,
    `payment_date` datetime NOT NULL,
    `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`payment_id`),
    KEY `idx_fk_staff_id` (`staff_id`),
    KEY `idx_fk_customer_id` (`customer_id`),
    KEY `fk_payment_rental` (`rental_id`),
    CONSTRAINT `fk_payment_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_rental`
        FOREIGN KEY (`rental_id`) REFERENCES `rental` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_staff`
        FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16050 DEFAULT CHARSET=utf8
SQL;
        $isViewQuery = 'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'db\' AND TABLE_NAME = \'table\'';

        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            'SHOW TABLE STATUS FROM `db` WHERE Name = \'table\'',
            [['table', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '1', '2000-01-01 10:00:00', '2000-01-02 12:00:00', '2000-01-02 13:00:00', 'utf8mb4_general_ci', null, '', '', '0', 'N']],
            ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary'],
        );
        // phpcs:enable
        $dbiDummy->addResult($isViewQuery, []);
        $dbiDummy->addResult($isViewQuery, []);
        $dbiDummy->addResult('USE `db`', true);
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `db`.`table`',
            [['table', $createTableStatement]],
            ['Table', 'Create Table'],
        );

        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_relation' => 'On',
                'sql_backquotes' => 'true',
                'sql_dates' => 'On',
                'sql_include_comments' => 'On',
                'sql_compatibility' => 'MSSQL',
                'sql_drop_table' => 'On',
            ]);

        $this->object->setExportOptions($request, []);

        $result = $this->object->getTableDef('db', 'table', true, false);

        $dbiDummy->assertAllQueriesConsumed();
        self::assertStringContainsString('-- Creation: Jan 01, 2000 at 10:00 AM', $result);
        self::assertStringContainsString('-- Last update: Jan 02, 2000 at 12:00 PM', $result);
        self::assertStringContainsString('-- Last check: Jan 02, 2000 at 01:00 PM', $result);
        self::assertStringContainsString('DROP TABLE IF EXISTS `table`;', $result);
        self::assertStringContainsString('CREATE TABLE `table`', $result);
        self::assertIsString($this->object->sqlConstraints);
        self::assertStringContainsString('-- Constraints for dumped tables', $this->object->sqlConstraints);
        self::assertStringContainsString('-- Constraints for table "table"', $this->object->sqlConstraints);
        self::assertStringContainsString('ALTER TABLE "table"', $this->object->sqlConstraints);
        self::assertStringContainsString('ADD CONSTRAINT', $this->object->sqlConstraints);
        self::assertStringContainsString('ALTER TABLE "table"', $this->object->sqlConstraintsQuery);
        self::assertStringContainsString('ADD CONSTRAINT', $this->object->sqlConstraintsQuery);
    }

    public function testGetTableDefWithError(): void
    {
        $this->object->sqlConstraints = null;

        ExportSql::$noConstraintsComments = false;

        $isViewQuery = 'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'db\' AND TABLE_NAME = \'table\'';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW TABLE STATUS FROM `db` WHERE Name = \'table\'', []);
        $dbiDummy->addResult($isViewQuery, []);
        $dbiDummy->addResult($isViewQuery, []);
        $dbiDummy->addResult('USE `db`', true);
        $dbiDummy->addResult('SHOW CREATE TABLE `db`.`table`', []);
        $dbiDummy->addErrorCode('error occurred');

        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_include_comments' => 'On', 'sql_drop_table' => 'On']);

        $this->object->setExportOptions($request, []);

        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('Error reading structure for table db.table: error occurred');

        $this->object->getTableDef('db', 'table', true, false);

        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllErrorCodesConsumed();
    }

    public function testGetTableComments(): void
    {
        $relationParameters = RelationParameters::fromArray([
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('fetchResult')
            ->willReturn(
                ['fieldname' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
                ['foo' => ['foreign_table' => 'ftable', 'foreign_field' => 'ffield']],
            );

        DatabaseInterface::$instance = $dbi;
        $this->object->relation = new Relation($dbi);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_relation' => 'On', 'sql_mime' => 'On', 'sql_include_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        $method = new ReflectionMethod(ExportSql::class, 'getTableComments');
        $result = $method->invoke($this->object, 'db', '');

        self::assertStringContainsString(
            '-- MEDIA TYPES FOR TABLE :' . "\n"
            . '--   fieldname' . "\n"
            . '--       Test<',
            $result,
        );

        self::assertStringContainsString(
            '-- RELATIONSHIPS FOR TABLE :' . "\n"
            . '--   foo' . "\n"
            . '--       ftable -> ffield',
            $result,
        );
    }

    public function testExportStructure(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_backquotes' => 'true',
                'sql_include_comments' => 'On',
                'sql_compatibility' => 'MSSQL',
            ]);

        $this->object->setExportOptions($request, []);

        // case 1
        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_table'));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Table structure for table &quot;test_table&quot;', $result);
        self::assertStringContainsString('CREATE TABLE `test_table`', $result);

        // case 2
        $this->object->useSqlBackquotes(false);

        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'triggers'));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Triggers test_table', $result);
        self::assertStringContainsString(
            "CREATE TRIGGER `test_trigger` AFTER INSERT ON `test_table` FOR EACH ROW BEGIN END\n$$",
            $result,
        );

        // case 3
        $this->object->useSqlBackquotes(false);
        ExportPlugin::$exportType = ExportType::Raw;

        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_view'));
        $result = ob_get_clean();

        $sqlViews = (new ReflectionProperty(ExportSql::class, 'sqlViews'))->getValue($this->object);

        self::assertSame('', $result);
        self::assertIsString($sqlViews);
        self::assertStringContainsString('-- Structure for view test_table', $sqlViews);
        self::assertStringContainsString('DROP TABLE IF EXISTS `test_table`;', $sqlViews);
        self::assertStringContainsString('CREATE TABLE `test_table`', $sqlViews);

        // case 4
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_include_comments' => 'On', 'sql_views_as_tables' => 'On']);

        $this->object->setExportOptions($request, []);

        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'create_view'));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Structure for view test_table exported as a table', $result);
        self::assertStringContainsString('DROP TABLE IF EXISTS `test_table`;', $result);
        self::assertStringContainsString('CREATE TABLE`test_table`', $result);

        // case 5
        ob_start();
        self::assertTrue($this->object->exportStructure('test_db', 'test_table', 'stand_in'));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertStringContainsString('-- Stand-in structure for view test_table', $result);
        self::assertStringContainsString('CREATE TABLE `test_table`', $result);
    }

    public function testExportData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fields = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'name' => 'name',
                'length' => 2,
            ]),
            FieldHelper::fromArray([
                'type' => -1,
                'flags' => MYSQLI_NUM_FLAG,
                'name' => 'name',
                'length' => 2,
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'name',
                'length' => 2,
                'charsetnr' => 63,
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'name' => 'name',
                'length' => 2,
                'charsetnr' => 63,
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_BLOB,
                'name' => 'name',
                'length' => 2,
                'charsetnr' => 63,
            ]),
        ];

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn($fields);

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SELECT a FROM b WHERE 1', ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED)
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numFields')
            ->willReturn(5);

        $resultStub->expects(self::exactly(2))
            ->method('fetchRow')
            ->willReturn([null, 'test', '10', '6', "\x00\x0a\x0d\x1a"], []);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $tableObj = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tableObj->expects(self::once())
            ->method('isMerge')
            ->willReturn(false);
        $tableObj->expects(self::once())
            ->method('isView')
            ->willReturn(false);

        $dbi->expects(self::any())
            ->method('getTable')
            ->willReturn($tableObj);

        DatabaseInterface::$instance = $dbi;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_backquotes' => 'true',
                'sql_compatibility' => 'MSSQL',
                'sql_truncate' => 'On',
                'sql_delayed' => 'On',
                'sql_ignore' => 'On',
                'sql_hex_for_binary' => 'On',
            ]);

        $this->object->setExportOptions($request, []);

        ob_start();
        $this->object->exportData('db', 'table', 'SELECT a FROM b WHERE 1');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('TRUNCATE TABLE &quot;table&quot;;', $result);

        self::assertStringContainsString('SET IDENTITY_INSERT &quot;table&quot; ON ;', $result);

        self::assertStringContainsString(
            'INSERT DELAYED IGNORE INTO &quot;table&quot; (&quot;name&quot;, ' .
            '&quot;name&quot;, &quot;name&quot;, &quot;name&quot;, ' .
            '&quot;name&quot;) VALUES',
            $result,
        );

        self::assertStringContainsString('(NULL, \'test\', 0x3130, 0x36, 0x000a0d1a);', $result);

        self::assertStringContainsString('SET IDENTITY_INSERT &quot;table&quot; OFF;', $result);
    }

    public function testExportDataWithUpdate(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fields = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_FLOAT,
                'flags' => MYSQLI_PRI_KEY_FLAG,
                'name' => 'name',
                'orgname' => 'pma',
                'table' => 'tbl',
                'orgtable' => 'tbl',
                'length' => 2,
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_FLOAT,
                'flags' => MYSQLI_UNIQUE_KEY_FLAG,
                'name' => 'name',
                'orgname' => 'pma',
                'table' => 'tbl',
                'orgtable' => 'tbl',
                'length' => 2,
            ]),
        ];

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn($fields);

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SELECT a FROM b WHERE 1', ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED)
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numFields')
            ->willReturn(2);

        $resultStub->expects(self::exactly(2))
            ->method('fetchRow')
            ->willReturn([null, null], []);

        $tableObj = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tableObj->expects(self::once())
            ->method('isMerge')
            ->willReturn(false);
        $tableObj->expects(self::once())
            ->method('isView')
            ->willReturn(false);

        $dbi->expects(self::any())
            ->method('getTable')
            ->willReturn($tableObj);

        DatabaseInterface::$instance = $dbi;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'sql_backquotes' => 'true',
                'sql_compatibility' => 'MSSQL',
                'sql_type' => 'UPDATE',
                'sql_ignore' => 'On',
            ]);

        $this->object->setExportOptions($request, []);

        ob_start();
        $this->object->exportData('db', 'table', 'SELECT a FROM b WHERE 1');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString(
            'UPDATE IGNORE &quot;table&quot; SET &quot;name&quot; = NULL,' .
            '&quot;name&quot; = NULL WHERE CONCAT(`tbl`.`pma`) IS NULL;',
            $result,
        );
    }

    public function testExportDataWithIsView(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tableObj = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tableObj->expects(self::once())
            ->method('isMerge')
            ->willReturn(false);
        $tableObj->expects(self::once())
            ->method('isView')
            ->willReturn(true);

        $dbi->expects(self::any())
            ->method('getTable')
            ->willReturn($tableObj);

        DatabaseInterface::$instance = $dbi;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_backquotes' => 'true', 'sql_include_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        ob_start();
        self::assertTrue(
            $this->object->exportData('db', 'tbl', 'SELECT'),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("-- VIEW `tbl`\n", $result);

        self::assertStringContainsString("-- Data: None\n", $result);
    }

    public function testExportDataWithError(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getError')
            ->willReturn('err');

        $tableObj = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tableObj->expects(self::once())
            ->method('isMerge')
            ->willReturn(false);
        $tableObj->expects(self::once())
            ->method('isView')
            ->willReturn(false);

        $dbi->expects(self::any())
            ->method('getTable')
            ->willReturn($tableObj);

        DatabaseInterface::$instance = $dbi;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql_include_comments' => 'On']);

        $this->object->setExportOptions($request, []);

        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('Error reading data for table db.table: err');

        self::assertTrue(
            $this->object->exportData('db', 'table', 'SELECT'),
        );
    }

    public function testMakeCreateTableMSSQLCompatible(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS (\" date DEFAULT NULL,\n"
            . "\" date DEFAULT NULL\n\" date NOT NULL,\n\" date NOT NULL\n,"
            . " \" date NOT NULL DEFAULT 'asd',"
            . " ) unsigned NOT NULL\n, ) unsigned NOT NULL,\n"
            . " ) unsigned DEFAULT NULL\n, ) unsigned DEFAULT NULL,\n"
            . " ) unsigned NOT NULL DEFAULT 'dsa',\n"
            . " \" int(10) DEFAULT NULL,\n"
            . " \" tinyint(0) DEFAULT NULL\n"
            . " \" smallint(10) NOT NULL,\n"
            . " \" bigint(0) NOT NULL\n"
            . " \" bigint(0) NOT NULL DEFAULT '12'\n"
            . " \" float(22,2,) DEFAULT NULL,\n"
            . " \" double DEFAULT NULL\n"
            . " \" float(22,2,) NOT NULL,\n"
            . " \" double NOT NULL\n"
            . " \" double NOT NULL DEFAULT '213'\n";

        $method = new ReflectionMethod(ExportSql::class, 'makeCreateTableMSSQLCompatible');
        $result = $method->invoke($this->object, $query);

        self::assertSame(
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
            $result,
        );
    }

    public function testInitAlias(): void
    {
        $aliases = [
            'a' => [
                'alias' => 'aliastest',
                'tables' => ['foo' => ['alias' => 'qwerty'], 'bar' => ['alias' => 'f']],
            ],
        ];
        $db = 'a';
        $table = null;

        $this->object->initAlias($aliases, $db, $table);
        self::assertSame('aliastest', $db);
        self::assertNull($table);

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
                    'foo' => ['alias' => 'qwerty', 'columns' => ['baz' => 'p', 'pqr' => 'pphymdain']],
                    'bar' => ['alias' => 'f', 'columns' => ['xy' => 'n']],
                ],
            ],
        ];

        self::assertSame(
            'f',
            $this->object->getAlias($aliases, 'bar'),
        );

        self::assertSame(
            'aliastest',
            $this->object->getAlias($aliases, 'a'),
        );

        self::assertSame(
            'pphymdain',
            $this->object->getAlias($aliases, 'pqr'),
        );

        self::assertSame(
            '',
            $this->object->getAlias($aliases, 'abc'),
        );
    }

    public function testReplaceWithAlias(): void
    {
        $aliases = [
            'a' => [
                'alias' => 'aliastest',
                'tables' => [
                    'foo' => ['alias' => 'bartest', 'columns' => ['baz' => 'p', 'pqr' => 'pphymdain']],
                    'bar' => ['alias' => 'f', 'columns' => ['xy' => 'n']],
                ],
            ],
        ];

        $db = 'a';
        $sqlQuery = "CREATE TABLE IF NOT EXISTS foo (\n"
            . "baz tinyint(3) unsigned NOT NULL COMMENT 'Primary Key',\n"
            . 'xyz varchar(255) COLLATE latin1_general_ci NOT NULL '
            . "COMMENT 'xyz',\n"
            . 'pqr varchar(10) COLLATE latin1_general_ci NOT NULL '
            . "COMMENT 'pqr',\n"
            . 'CONSTRAINT fk_om_dept FOREIGN KEY (baz) '
            . "REFERENCES dept_master (baz)\n"
            . ') ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE='
            . "latin1_general_ci COMMENT='List' AUTO_INCREMENT=5";
        $flag = false;
        $result = $this->object->replaceWithAliases(null, $sqlQuery, $aliases, $db, $flag);

        self::assertSame(
            "CREATE TABLE IF NOT EXISTS `bartest` (\n" .
            "  `p` tinyint(3) UNSIGNED NOT NULL COMMENT 'Primary Key',\n" .
            "  `xyz` varchar(255) COLLATE latin1_general_ci NOT NULL COMMENT 'xyz',\n" .
            "  `pphymdain` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'pqr',\n" .
            "  CONSTRAINT `fk_om_dept` FOREIGN KEY (`p`) REFERENCES dept_master (`baz`)\n" .
            ") ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='List'",
            $result,
        );

        $flag = false;
        $result = $this->object->replaceWithAliases(null, $sqlQuery, [], '', $flag);

        self::assertSame(
            "CREATE TABLE IF NOT EXISTS foo (\n" .
            "  `baz` tinyint(3) UNSIGNED NOT NULL COMMENT 'Primary Key',\n" .
            "  `xyz` varchar(255) COLLATE latin1_general_ci NOT NULL COMMENT 'xyz',\n" .
            "  `pqr` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'pqr',\n" .
            "  CONSTRAINT `fk_om_dept` FOREIGN KEY (`baz`) REFERENCES dept_master (`baz`)\n" .
            ") ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='List'",
            $result,
        );

        $sqlQuery = 'CREATE TRIGGER `BEFORE_bar_INSERT` '
            . 'BEFORE INSERT ON `bar` '
            . 'FOR EACH ROW BEGIN '
            . 'SET @cnt=(SELECT count(*) FROM bar WHERE '
            . 'xy=NEW.xy AND id=NEW.id AND '
            . 'abc=NEW.xy LIMIT 1); '
            . 'IF @cnt<>0 THEN '
            . 'SET NEW.xy=1; '
            . 'END IF; END';
        $flag = false;
        $result = $this->object->replaceWithAliases('$$', $sqlQuery, $aliases, $db, $flag);

        self::assertSame(
            'CREATE TRIGGER `BEFORE_bar_INSERT` BEFORE INSERT ON `f` FOR EACH ROW BEGIN ' .
            'SET @cnt=(SELECT count(*) FROM `f` WHERE `n`=NEW.`n` AND id=NEW.id AND abc=NEW.`n` LIMIT 1); ' .
            'IF @cnt<>0 THEN ' .
            'SET NEW.`n`=1; ' .
            'END IF; ' .
            'END',
            $result,
        );

        $sqlQuery = <<<'SQL'
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

        $flag = false;
        $result = $this->object->replaceWithAliases('$$', $sqlQuery, $aliases, $db, $flag);

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
