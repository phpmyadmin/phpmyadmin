<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\Statement;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Utils\SessionCache;

/** @covers \PhpMyAdmin\DatabaseInterface */
class DatabaseInterfaceTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
    }

    /**
     * Tear down function for mockResponse method
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['lang']);
        unset($GLOBALS['cfg']['Server']['SessionTimeZone']);
        Context::load();
    }

    /**
     * Tests for DBI::getCurrentUser() method.
     *
     * @param string[][]|false $value           value
     * @param string           $string          string
     * @param mixed[]          $expected        expected result
     * @param bool             $needsSecondCall The test will need to call another time the DB
     * @psalm-param list<non-empty-list<string>>|false $value
     *
     * @dataProvider currentUserData
     */
    public function testGetCurrentUser(array|false $value, string $string, array $expected, bool $needsSecondCall): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        SessionCache::remove('mysql_cur_user');

        $dummyDbi->addResult('SELECT CURRENT_USER();', $value);
        if ($needsSecondCall) {
            $dummyDbi->addResult('SELECT CURRENT_USER();', $value);
        }

        $this->assertEquals($expected, $dbi->getCurrentUserAndHost());

        $this->assertEquals($string, $dbi->getCurrentUser());

        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Data provider for getCurrentUser() tests.
     *
     * @return mixed[]
     */
    public static function currentUserData(): array
    {
        return [
            [[['pma@localhost']], 'pma@localhost', ['pma', 'localhost'], false],
            [[['@localhost']], '@localhost', ['', 'localhost'], false],
            [false, '@', ['', ''], true],
        ];
    }

    /**
     * Tests for DBI::getSystemDatabase() method.
     */
    public function testGetSystemDatabase(): void
    {
        $dbi = $this->createDatabaseInterface();
        $sd = $dbi->getSystemDatabase();
        $this->assertInstanceOf(SystemDatabase::class, $sd);
    }

    public function testPostConnectControlWithZeroConf(): void
    {
        $GLOBALS['cfg']['ZeroConf'] = true;
        $dbi = $this->createDatabaseInterface();
        $relationMock = $this->createMock(Relation::class);
        $relationMock->expects($this->once())->method('initRelationParamsCache');
        $dbi->postConnectControl($relationMock);
    }

    public function testPostConnectControlWithoutZeroConf(): void
    {
        $GLOBALS['cfg']['ZeroConf'] = false;
        $dbi = $this->createDatabaseInterface();
        $relationMock = $this->createMock(Relation::class);
        $relationMock->expects($this->never())->method('initRelationParamsCache');
        $dbi->postConnectControl($relationMock);
    }

    /**
     * Tests for DBI::postConnect() method.
     * should not call setVersion method if cannot fetch version
     */
    public function testPostConnectShouldNotCallSetVersionIfNoVersion(): void
    {
        $GLOBALS['lang'] = 'en';
        LanguageManager::getInstance()->availableLanguages();

        $mock = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchSingleRow', 'query', 'setVersion'])
            ->getMock();

        $mock->expects($this->once())
            ->method('fetchSingleRow')
            ->will($this->returnValue(null));

        $mock->expects($this->never())->method('setVersion');

        $mock->postConnect(new Server(['SessionTimeZone' => '']));
    }

    /**
     * Tests for DBI::postConnect() method.
     * should call setVersion method if $version has value
     */
    public function testPostConnectShouldCallSetVersionOnce(): void
    {
        $GLOBALS['lang'] = 'en';
        $versionQueryResult = [
            '@@version' => '10.20.7-MariaDB-1:10.9.3+maria~ubu2204',
            '@@version_comment' => 'mariadb.org binary distribution',
        ];
        LanguageManager::getInstance()->availableLanguages();

        $mock = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchSingleRow', 'query', 'setVersion'])
            ->getMock();

        $mock->expects($this->once())
            ->method('fetchSingleRow')
            ->will($this->returnValue($versionQueryResult));

        $mock->expects($this->once())->method('setVersion')->with($versionQueryResult);

        $mock->postConnect(new Server(['SessionTimeZone' => '']));
    }

    /**
     * Tests for DBI::postConnect() method.
     * should set version int, isMariaDB and isPercona
     *
     * @param array $version    Database version
     * @param int   $versionInt Database version as integer
     * @param bool  $isMariaDb  True if mariadb
     * @param bool  $isPercona  True if percona
     * @phpstan-param array<array-key, mixed> $version
     *
     * @dataProvider provideDatabaseVersionData
     */
    public function testPostConnectShouldSetVersion(
        array $version,
        int $versionInt,
        bool $isMariaDb,
        bool $isPercona,
    ): void {
        $GLOBALS['lang'] = 'en';
        LanguageManager::getInstance()->availableLanguages();

        $mock = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchSingleRow', 'query'])
            ->getMock();

        $mock->expects($this->once())
            ->method('fetchSingleRow')
            ->will($this->returnValue($version));

        $mock->postConnect(new Server(['SessionTimeZone' => '']));

        $this->assertEquals($mock->getVersion(), $versionInt);
        $this->assertEquals($mock->isMariaDB(), $isMariaDb);
        $this->assertEquals($mock->isPercona(), $isPercona);
    }

    /**
     * Test for getDbCollation
     */
    public function testGetDbCollation(): void
    {
        $dbi = $this->createDatabaseInterface();

        $GLOBALS['server'] = 1;
        // test case for system schema
        $this->assertEquals(
            'utf8_general_ci',
            $dbi->getDbCollation('information_schema'),
        );

        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $this->assertEquals(
            'utf8_general_ci',
            $dbi->getDbCollation('pma_test'),
        );
    }

    /**
     * Test for getServerCollation
     */
    public function testGetServerCollation(): void
    {
        $dbi = $this->createDatabaseInterface();
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['DBG']['sql'] = true;
        $this->assertEquals('utf8_general_ci', $dbi->getServerCollation());
    }

    /**
     * Test error formatting
     *
     * @param int    $errorNumber  Error code
     * @param string $errorMessage Error message as returned by server
     * @param string $match        Expected text
     *
     * @dataProvider errorData
     */
    public function testFormatError(int $errorNumber, string $errorMessage, string $match): void
    {
        $this->assertStringContainsString(
            $match,
            Utilities::formatError($errorNumber, $errorMessage),
        );
    }

    /** @return mixed[][] */
    public static function errorData(): array
    {
        return [
            [2002, 'msg', 'The server is not responding'],
            [2003, 'msg', 'The server is not responding'],
            [1698, 'msg', 'index.php?route=/logout'],
            [1005, 'msg', 'index.php?route=/server/engines'],
            [1005, 'errno: 13', 'Please check privileges'],
            [-1, 'error message', 'error message'],
        ];
    }

    /**
     * Tests for DBI::isAmazonRds() method.
     *
     * @param string[][] $value    value
     * @param bool       $expected expected result
     * @psalm-param list<non-empty-list<string>> $value
     *
     * @dataProvider isAmazonRdsData
     */
    public function testIsAmazonRdsData(array $value, bool $expected): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        SessionCache::remove('is_amazon_rds');

        $dummyDbi->addResult('SELECT @@basedir', $value);

        $this->assertEquals(
            $expected,
            $dbi->isAmazonRds(),
        );

        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Data provider for isAmazonRds() tests.
     *
     * @return mixed[]
     */
    public static function isAmazonRdsData(): array
    {
        return [
            [[['/usr']], false],
            [[['E:/mysql']], false],
            [[['/rdsdbbin/mysql/']], true],
            [[['/rdsdbbin/mysql-5.7.18/']], true],
        ];
    }

    /**
     * Test for version parsing
     *
     * @param string $version  version to parse
     * @param int    $expected expected numeric version
     * @param int    $major    expected major version
     * @param bool   $upgrade  whether upgrade should ne needed
     *
     * @dataProvider versionData
     */
    public function testVersion(string $version, int $expected, int $major, bool $upgrade): void
    {
        $verInt = Utilities::versionToInt($version);
        $this->assertEquals($expected, $verInt);
        $this->assertEquals($major, (int) ($verInt / 10000));
        $mysqlMinVersion = 50500;
        $this->assertEquals($upgrade, $verInt < $mysqlMinVersion);
    }

    /** @return mixed[][] */
    public static function versionData(): array
    {
        return [
            ['5.0.5', 50005, 5, true],
            ['5.05.01', 50501, 5, false],
            ['5.6.35', 50635, 5, false],
            ['10.1.22-MariaDB-', 100122, 10, false],
        ];
    }

    /**
     * Tests for DBI::setCollation() method.
     */
    public function testSetCollation(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->addResult('SET collation_connection = \'utf8_czech_ci\';', true);
        $dummyDbi->addResult('SET collation_connection = \'utf8mb4_bin_ci\';', true);
        $dummyDbi->addResult('SET collation_connection = \'utf8_czech_ci\';', true);
        $dummyDbi->addResult('SET collation_connection = \'utf8_bin_ci\';', true);

        $GLOBALS['charset_connection'] = 'utf8mb4';
        $dbi->setCollation('utf8_czech_ci');
        $dbi->setCollation('utf8mb4_bin_ci');
        $GLOBALS['charset_connection'] = 'utf8';
        $dbi->setCollation('utf8_czech_ci');
        $dbi->setCollation('utf8mb4_bin_ci');

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testGetTablesFull(): void
    {
        $dbi = $this->createDatabaseInterface();

        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $expected = [
            'test_table' => [
                'Name' => 'test_table',
                'Engine' => 'InnoDB',
                'Version' => '10',
                'Row_format' => 'Dynamic',
                'Rows' => '3',
                'Avg_row_length' => '5461',
                'Data_length' => '16384',
                'Max_data_length' => '0',
                'Index_length' => '0',
                'Data_free' => '0',
                'Auto_increment' => '4',
                'Create_time' => '2011-12-13 14:15:16',
                'Update_time' => null,
                'Check_time' => null,
                'Collation' => 'utf8mb4_general_ci',
                'Checksum' => null,
                'Create_options' => '',
                'Comment' => '',
                'Max_index_length' => '0',
                'Temporary' => 'N',
                'Type' => 'InnoDB',
                'TABLE_SCHEMA' => 'test_db',
                'TABLE_NAME' => 'test_table',
                'ENGINE' => 'InnoDB',
                'VERSION' => '10',
                'ROW_FORMAT' => 'Dynamic',
                'TABLE_ROWS' => '3',
                'AVG_ROW_LENGTH' => '5461',
                'DATA_LENGTH' => '16384',
                'MAX_DATA_LENGTH' => '0',
                'INDEX_LENGTH' => '0',
                'DATA_FREE' => '0',
                'AUTO_INCREMENT' => '4',
                'CREATE_TIME' => '2011-12-13 14:15:16',
                'UPDATE_TIME' => null,
                'CHECK_TIME' => null,
                'TABLE_COLLATION' => 'utf8mb4_general_ci',
                'CHECKSUM' => null,
                'CREATE_OPTIONS' => '',
                'TABLE_COMMENT' => '',
                'TABLE_TYPE' => 'BASE TABLE',
            ],
        ];

        $actual = $dbi->getTablesFull('test_db');
        $this->assertEquals($expected, $actual);
    }

    public function testGetTablesFullWithInformationSchema(): void
    {
        $dbi = $this->createDatabaseInterface();

        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $expected = [
            'test_table' => [
                'TABLE_CATALOG' => 'def',
                'TABLE_SCHEMA' => 'test_db',
                'TABLE_NAME' => 'test_table',
                'TABLE_TYPE' => 'BASE TABLE',
                'ENGINE' => 'InnoDB',
                'VERSION' => '10',
                'ROW_FORMAT' => 'Dynamic',
                'TABLE_ROWS' => '3',
                'AVG_ROW_LENGTH' => '5461',
                'DATA_LENGTH' => '16384',
                'MAX_DATA_LENGTH' => '0',
                'INDEX_LENGTH' => '0',
                'DATA_FREE' => '0',
                'AUTO_INCREMENT' => '4',
                'CREATE_TIME' => '2011-12-13 14:15:16',
                'UPDATE_TIME' => null,
                'CHECK_TIME' => null,
                'TABLE_COLLATION' => 'utf8mb4_general_ci',
                'CHECKSUM' => null,
                'CREATE_OPTIONS' => '',
                'TABLE_COMMENT' => '',
                'MAX_INDEX_LENGTH' => '0',
                'TEMPORARY' => 'N',
                'Db' => 'test_db',
                'Name' => 'test_table',
                'Engine' => 'InnoDB',
                'Type' => 'InnoDB',
                'Version' => '10',
                'Row_format' => 'Dynamic',
                'Rows' => '3',
                'Avg_row_length' => '5461',
                'Data_length' => '16384',
                'Max_data_length' => '0',
                'Index_length' => '0',
                'Data_free' => '0',
                'Auto_increment' => '4',
                'Create_time' => '2011-12-13 14:15:16',
                'Update_time' => null,
                'Check_time' => null,
                'Collation' => 'utf8mb4_general_ci',
                'Checksum' => null,
                'Create_options' => '',
                'Comment' => '',
            ],
        ];

        $actual = $dbi->getTablesFull('test_db');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for queryAsControlUser
     */
    public function testQueryAsControlUser(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $sql = 'insert into PMA_bookmark A,B values(1, 2)';
        $dummyDbi->addResult($sql, true);
        $dummyDbi->addResult($sql, true);
        $dummyDbi->addResult('Invalid query', false);

        $this->assertInstanceOf(
            ResultInterface::class,
            $dbi->queryAsControlUser($sql),
        );
        $this->assertInstanceOf(
            ResultInterface::class,
            $dbi->tryQueryAsControlUser($sql),
        );
        $this->assertFalse($dbi->tryQueryAsControlUser('Invalid query'));
    }

    public function testGetDatabasesFullDisabledISAndSortIntColumn(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['Server']['only_db'] = '';
        $GLOBALS['cfg']['NaturalOrder'] = true;
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult('SELECT CURRENT_USER();', []);
        $dummyDbi->addResult('SHOW DATABASES', [['db1'], ['db2']], ['Database']);
        $dummyDbi->addResult(
            'SELECT @@collation_database',
            [['utf8_general_ci']],
            ['@@collation_database'],
        );
        $dummyDbi->addResult(
            'SELECT @@collation_database',
            [['utf8_general_ci']],
            ['@@collation_database'],
        );
        $dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `db1`;',
            [
                [
                    'pma__bookmark',
                    'InnoDB',
                    10,
                    'Dynamic',
                    0,
                    0,
                    16384,
                    0,
                    0,
                    0,
                    1,
                    '2021-08-27 14:11:52',
                    null,
                    null,
                    'utf8_bin',
                    null,
                    'Bookmarks',
                ],
                [
                    'pma__central_columns',
                    'InnoDB',
                    10,
                    'Dynamic',
                    0,
                    0,
                    16384,
                    0,
                    0,
                    0,
                    null,
                    '2021-08-27 14:11:52',
                    null,
                    null,
                    'utf8_bin',
                    null,
                    'Central list of columns',
                ],
            ],
            [
                'Name',
                'Engine',
                'Version',
                'Row_format',
                'Rows',
                'Avg_row_length',
                'Data_length',
                'Max_data_length',
                'Index_length',
                'Data_free',
                'Auto_increment',
                'Create_time',
                'Update_time',
                'Check_time',
                'Collation',
                'Checksum',
                'Create_options',
                'Comment',
            ],
        );

        $dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `db2`;',
            [
                [
                    'pma__bookmark',
                    'InnoDB',
                    10,
                    'Dynamic',
                    0,
                    0,
                    16324,
                    0,
                    0,
                    0,
                    1,
                    '2021-08-27 14:11:52',
                    null,
                    null,
                    'utf8_bin',
                    null,
                    'Bookmarks',
                ],
                [
                    'pma__central_columns',
                    'InnoDB',
                    10,
                    'Dynamic',
                    0,
                    0,
                    14384,
                    0,
                    0,
                    0,
                    null,
                    '2021-08-27 14:11:52',
                    null,
                    null,
                    'utf8_bin',
                    null,
                    'Central list of columns',
                ],
            ],
            [
                'Name',
                'Engine',
                'Version',
                'Row_format',
                'Rows',
                'Avg_row_length',
                'Data_length',
                'Max_data_length',
                'Index_length',
                'Data_free',
                'Auto_increment',
                'Create_time',
                'Update_time',
                'Check_time',
                'Collation',
                'Checksum',
                'Create_options',
                'Comment',
            ],
        );
        $dummyDbi->addSelectDb('');
        $dummyDbi->addSelectDb('');
        $dummyDbi->addSelectDb('db1');
        $dummyDbi->addSelectDb('db2');

        $databaseList = $dbi->getDatabasesFull(null, true, Connection::TYPE_USER, 'SCHEMA_DATA_LENGTH', 'ASC', 0, 100);

        $this->assertSame([
            [
                'SCHEMA_NAME' => 'db2',
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'SCHEMA_TABLES' => 2,
                'SCHEMA_TABLE_ROWS' => 0,
                'SCHEMA_DATA_LENGTH' => 30708,
                'SCHEMA_MAX_DATA_LENGTH' => 0,
                'SCHEMA_INDEX_LENGTH' => 0,
                'SCHEMA_LENGTH' => 30708,
                'SCHEMA_DATA_FREE' => 0,
            ],
            [
                'SCHEMA_NAME' => 'db1',
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'SCHEMA_TABLES' => 2,
                'SCHEMA_TABLE_ROWS' => 0,
                'SCHEMA_DATA_LENGTH' => 32768,
                'SCHEMA_MAX_DATA_LENGTH' => 0,
                'SCHEMA_INDEX_LENGTH' => 0,
                'SCHEMA_LENGTH' => 32768,
                'SCHEMA_DATA_FREE' => 0,
            ],
        ], $databaseList);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testPrepare(): void
    {
        $query = 'SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;';
        $stmtStub = $this->createStub(Statement::class);
        $dummyDbi = $this->createMock(DbiExtension::class);
        $dummyDbi->expects($this->once())->method('prepare')
            ->with($this->isType('object'), $this->equalTo($query))
            ->willReturn($stmtStub);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $stmt = $dbi->prepare($query, Connection::TYPE_CONTROL);
        $this->assertSame($stmtStub, $stmt);
    }

    /**
     * Tests for setVersion method.
     *
     * @param array $version    Database version
     * @param int   $versionInt Database version as integer
     * @param bool  $isMariaDb  True if mariadb
     * @param bool  $isPercona  True if percona
     * @phpstan-param array<array-key, mixed> $version
     *
     * @dataProvider provideDatabaseVersionData
     */
    public function testSetVersion(
        array $version,
        int $versionInt,
        bool $isMariaDb,
        bool $isPercona,
    ): void {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion($version);

        $this->assertEquals($versionInt, $dbi->getVersion());
        $this->assertEquals($isMariaDb, $dbi->isMariaDB());
        $this->assertEquals($isPercona, $dbi->isPercona());
        $this->assertEquals($version['@@version'], $dbi->getVersionString());
    }

    /**
     * Data provider for setVersion() tests.
     *
     * @return mixed[]
     * @psalm-return array<int, array{array<array-key, mixed>, int, bool, bool}>
     */
    public static function provideDatabaseVersionData(): array
    {
        return [
            [
                [
                    '@@version' => '6.1.0',
                    '@@version_comment' => "Percona Server (GPL), Release '11', Revision 'c1y2gr1df4a'",
                ],
                60100,
                false,
                true,
            ],
            [
                [
                    '@@version' => '10.01.40-MariaDB-1:10.01.40+maria~ubu2204',
                    '@@version_comment' => 'mariadb.org binary distribution',
                ],
                100140,
                true,
                false,
            ],
            [['@@version' => '7.10.3', '@@version_comment' => 'MySQL Community Server (GPL)'], 71003, false, false],
            [['@@version' => '5.5.0', '@@version_comment' => ''], 50500, false, false],
        ];
    }

    /** @dataProvider providerForTestGetLowerCaseNames */
    public function testGetLowerCaseNames(string|false|null $result, int $expected): void
    {
        $dbiDummy = $this->createDbiDummy();
        $expectedResult = $result !== false ? [[$result]] : [];
        $dbiDummy->addResult('SELECT @@lower_case_table_names', $expectedResult, ['@@lower_case_table_names']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $this->assertSame($expected, $dbi->getLowerCaseNames());
        $dbiDummy->assertAllQueriesConsumed();
    }

    /** @return iterable<string, array{string|false|null, int}> */
    public static function providerForTestGetLowerCaseNames(): iterable
    {
        yield 'string 0' => ['0', 0];
        yield 'string 1' => ['1', 1];
        yield 'string 2' => ['2', 2];
        yield 'invalid lower value' => ['-1', 0];
        yield 'invalid higher value' => ['3', 0];
        yield 'empty string' => ['', 0];
        yield 'null' => [null, 0];
        yield 'false' => [false, 0];
    }
}
