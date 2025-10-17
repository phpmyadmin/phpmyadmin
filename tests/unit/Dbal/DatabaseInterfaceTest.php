<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionProperty;

use function array_keys;

#[CoversClass(DatabaseInterface::class)]
#[CoversClass(Column::class)]
#[CoversClass(Column::class)]
final class DatabaseInterfaceTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    /**
     * Tear down function for mockResponse method
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Current::$lang = '';
        unset(Config::getInstance()->selectedServer['SessionTimeZone']);
        Context::load();
    }

    public function testUniqueness(): void
    {
        DatabaseInterface::$instance = null;
        $instanceOne = DatabaseInterface::getInstance();
        $instanceTwo = DatabaseInterface::getInstance();
        self::assertSame($instanceOne, $instanceTwo);
    }

    /**
     * Tests for DBI::getCurrentUser() method.
     *
     * @param string[][]|false $value           value
     * @param string           $string          string
     * @param string[]         $expected        expected result
     * @param bool             $needsSecondCall The test will need to call another time the DB
     * @psalm-param list<non-empty-list<string>>|false $value
     */
    #[DataProvider('currentUserData')]
    public function testGetCurrentUser(array|false $value, string $string, array $expected, bool $needsSecondCall): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        SessionCache::remove('mysql_cur_user');

        $dummyDbi->addResult('SELECT CURRENT_USER();', $value);
        if ($needsSecondCall) {
            $dummyDbi->addResult('SELECT CURRENT_USER();', $value);
        }

        self::assertSame($expected, $dbi->getCurrentUserAndHost());

        self::assertSame($string, $dbi->getCurrentUser());

        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Data provider for getCurrentUser() tests.
     *
     * @return array{list<non-empty-list<string>>|false, string, string[], bool}[]
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
     * Tests for DBI::getCurrentRole() method.
     *
     * @param string[][]|false $value
     * @param string[]         $string
     * @param string[][]       $expected
     */
    #[DataProvider('currentRolesData')]
    public function testGetCurrentRoles(
        string $version,
        bool $isRoleSupported,
        array|false $value,
        array $string,
        array $expected,
    ): void {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->setVersion(['@@version' => $version]);

        SessionCache::remove('mysql_cur_role');

        if ($isRoleSupported) {
            $dbiDummy->addResult('SELECT CURRENT_ROLE();', $value);
        }

        self::assertSame($expected, $dbi->getCurrentRolesAndHost());

        self::assertSame($string, $dbi->getCurrentRoles());

        $dbiDummy->assertAllQueriesConsumed();
    }

    /**
     * Data provider for getCurrentRole() tests.
     *
     * @return array{string, bool, string[][]|false, string[], string[][]}[]
     */
    public static function currentRolesData(): array
    {
        return [
            ['10.4.99-MariaDB', false, false, [], []],
            ['5.7.35 - MySQL Community Server (GPL)', false, false, [], []],
            [
                '8.0.0 - MySQL Community Server - GPL',
                true,
                [['`role`@`localhost`']],
                ['role@localhost'],
                [['role', 'localhost']],
            ],
            [
                '8.0.0 - MySQL Community Server - GPL',
                true,
                [['`role`@`localhost`, `role2`@`localhost`']],
                ['role@localhost', 'role2@localhost'],
                [['role', 'localhost'], ['role2', 'localhost']],
            ],
            ['8.0.0 - MySQL Community Server - GPL', true, [['@`localhost`']], ['@localhost'], [['', 'localhost']]],
            ['10.5.0-MariaDB', true, [['`role`@`localhost`']], ['role@localhost'], [['role', 'localhost']]],
            ['10.5.0-MariaDB', true, [['`role`']], ['role'], [['role', '']]],
            [
                '10.5.0-MariaDB',
                true,
                [['`role`@`localhost`, `role2`@`localhost`']],
                ['role@localhost', 'role2@localhost'],
                [['role', 'localhost'], ['role2', 'localhost']],
            ],
            ['10.5.0-MariaDB', true, [['@`localhost`']], ['@localhost'], [['', 'localhost']]],
        ];
    }

    /**
     * Tests for DBI::postConnect() method.
     * should not call setVersion method if cannot fetch version
     */
    public function testPostConnectShouldNotCallSetVersionIfNoVersion(): void
    {
        Current::$lang = 'en';
        LanguageManager::getInstance()->availableLanguages();

        $mock = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchSingleRow', 'query', 'setVersion'])
            ->getMock();

        $mock->expects(self::once())
            ->method('fetchSingleRow')
            ->willReturn([]);

        $mock->expects(self::never())->method('setVersion');

        $mock->postConnect(new Server(['SessionTimeZone' => '']));
    }

    /**
     * Tests for DBI::postConnect() method.
     * should call setVersion method if $version has value
     */
    public function testPostConnectShouldCallSetVersionOnce(): void
    {
        Current::$lang = 'en';
        $versionQueryResult = [
            '@@version' => '10.20.7-MariaDB-1:10.9.3+maria~ubu2204',
            '@@version_comment' => 'mariadb.org binary distribution',
        ];
        LanguageManager::getInstance()->availableLanguages();

        $mock = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchSingleRow', 'query', 'setVersion'])
            ->getMock();

        $mock->expects(self::once())
            ->method('fetchSingleRow')
            ->willReturn($versionQueryResult);

        $mock->expects(self::once())->method('setVersion')->with($versionQueryResult);

        $mock->postConnect(new Server(['SessionTimeZone' => '']));
    }

    /**
     * Tests for DBI::postConnect() method.
     * should set version int, isMariaDB and isPercona
     *
     * @param array<string, string> $version    Database version
     * @param int                   $versionInt Database version as integer
     * @param bool                  $isMariaDb  True if mariadb
     * @param bool                  $isPercona  True if percona
     * @phpstan-param array<array-key, mixed> $version
     */
    #[DataProvider('provideDatabaseVersionData')]
    public function testPostConnectShouldSetVersion(
        array $version,
        int $versionInt,
        bool $isMySql,
        bool $isMariaDb,
        bool $isPercona,
    ): void {
        Current::$lang = 'en';
        LanguageManager::getInstance()->availableLanguages();

        $mock = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchSingleRow', 'query'])
            ->getMock();

        $mock->expects(self::once())
            ->method('fetchSingleRow')
            ->willReturn($version);

        $mock->postConnect(new Server(['SessionTimeZone' => '']));

        self::assertSame($versionInt, $mock->getVersion());
        self::assertSame($isMySql, $mock->isMySql());
        self::assertSame($isMariaDb, $mock->isMariaDB());
        self::assertSame($isPercona, $mock->isPercona());
        self::assertSame($version['@@version'], $mock->getVersionString());
        self::assertSame($version['@@version_comment'], $mock->getVersionComment());
    }

    /**
     * Test for getDbCollation
     */
    public function testGetDbCollation(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        self::assertSame(
            'utf8_general_ci',
            $dbi->getDbCollation('pma_test'),
        );

        $config->selectedServer['DisableIS'] = true;

        $dbiDummy->addSelectDb('information_schema');
        Current::$database = 'information_schema';

        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_database', [['utf8mb3_general_ci']], ['@@collation_database']);

        self::assertSame('utf8mb3_general_ci', $dbi->getDbCollation('information_schema'));
    }

    /**
     * Test for getServerCollation
     */
    public function testGetServerCollation(): void
    {
        $dbi = $this->createDatabaseInterface();
        $config = Config::getInstance();
        $config->config->debug->sql = true;
        self::assertSame('utf8_general_ci', $dbi->getServerCollation());
        $config->config->debug->sql = false;
    }

    /**
     * Test error formatting
     *
     * @param int    $errorNumber  Error code
     * @param string $errorMessage Error message as returned by server
     * @param string $match        Expected text
     */
    #[DataProvider('errorData')]
    public function testFormatError(int $errorNumber, string $errorMessage, string $match): void
    {
        self::assertStringContainsString(
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
     */
    #[DataProvider('isAmazonRdsData')]
    public function testIsAmazonRdsData(array $value, bool $expected): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        SessionCache::remove('is_amazon_rds');

        $dummyDbi->addResult('SELECT @@basedir', $value);

        self::assertSame(
            $expected,
            $dbi->isAmazonRds(),
        );

        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Data provider for isAmazonRds() tests.
     *
     * @return array{list<non-empty-list<string>>, bool}[]
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
     */
    #[DataProvider('versionData')]
    public function testVersion(string $version, int $expected, int $major, bool $upgrade): void
    {
        $verInt = Utilities::versionToInt($version);
        self::assertSame($expected, $verInt);
        self::assertSame($major, (int) ($verInt / 10000));
        $mysqlMinVersion = 50500;
        self::assertSame($upgrade, $verInt < $mysqlMinVersion);
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

        (new ReflectionProperty(DatabaseInterface::class, 'versionInt'))->setValue($dbi, 50504);
        $dbi->setCollation('utf8_czech_ci');
        $dbi->setCollation('utf8mb4_bin_ci');
        (new ReflectionProperty(DatabaseInterface::class, 'versionInt'))->setValue($dbi, 50503);
        $dbi->setCollation('utf8_czech_ci');
        $dbi->setCollation('utf8mb4_bin_ci');

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testGetTablesFull(): void
    {
        $dbi = $this->createDatabaseInterface();

        Config::getInstance()->selectedServer['DisableIS'] = true;

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
        self::assertSame($expected, $actual);
    }

    public function testGetTablesFullWithInformationSchema(): void
    {
        $dbi = $this->createDatabaseInterface();

        Config::getInstance()->selectedServer['DisableIS'] = false;

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
        self::assertSame($expected, $actual);
    }

    public function testGetTablesFullBug18913(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->settings['NaturalOrder'] = false;

        $expected = ['0', '1', '42'];

        $dbiDummy->addResult('SHOW TABLE STATUS FROM `test_db_bug_18913`', [
            ['0', ''],
            ['1', ''],
            ['42', ''],
        ], ['Name', 'Engine']);

        $actual = $dbi->getTablesFull('test_db_bug_18913');
        self::assertEquals($expected, array_keys($actual));
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

        $dbi->queryAsControlUser($sql);
        self::assertInstanceOf(
            ResultInterface::class,
            $dbi->tryQueryAsControlUser($sql),
        );
        self::assertFalse($dbi->tryQueryAsControlUser('Invalid query'));
    }

    public function testGetDatabasesFullDisabledISAndSortIntColumn(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        Current::$database = '';
        Current::$table = '';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->selectedServer['only_db'] = '';
        $config->settings['NaturalOrder'] = true;
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

        $databaseList = $dbi->getDatabasesFull(null, true, ConnectionType::User, 'SCHEMA_DATA_LENGTH', 'ASC', 0, 100);

        self::assertSame([
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

    public function testExecuteQuery(): void
    {
        $query = 'SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;';
        $resultStub = self::createStub(ResultInterface::class);
        $dummyDbi = $this->createMock(DbiExtension::class);
        $dummyDbi->expects(self::once())->method('executeQuery')
            ->with(self::isObject(), self::equalTo($query), self::equalTo(['root', 'localhost']))
            ->willReturn($resultStub);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $stmt = $dbi->executeQuery($query, ['root', 'localhost'], ConnectionType::ControlUser);
        self::assertSame($resultStub, $stmt);
    }

    /**
     * Tests for setVersion method.
     *
     * @param array<string, string> $version    Database version
     * @param int                   $versionInt Database version as integer
     * @param bool                  $isMariaDb  True if mariadb
     * @param bool                  $isPercona  True if percona
     * @phpstan-param array<array-key, mixed> $version
     */
    #[DataProvider('provideDatabaseVersionData')]
    public function testSetVersion(
        array $version,
        int $versionInt,
        bool $isMySql,
        bool $isMariaDb,
        bool $isPercona,
    ): void {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion($version);

        self::assertSame($versionInt, $dbi->getVersion());
        self::assertSame($isMySql, $dbi->isMySql());
        self::assertSame($isMariaDb, $dbi->isMariaDB());
        self::assertSame($isPercona, $dbi->isPercona());
        self::assertSame($version['@@version'], $dbi->getVersionString());
        self::assertSame($version['@@version_comment'], $dbi->getVersionComment());
    }

    /**
     * Data provider for setVersion() tests.
     *
     * @return array<int, array{array<array-key, mixed>, int, bool, bool, bool}>
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
                true,
                false,
                true,
            ],
            [
                [
                    '@@version' => '10.01.40-MariaDB-1:10.01.40+maria~ubu2204',
                    '@@version_comment' => 'mariadb.org binary distribution',
                ],
                100140,
                false,
                true,
                false,
            ],
            [
                ['@@version' => '7.10.3', '@@version_comment' => 'MySQL Community Server (GPL)'],
                71003,
                true,
                false,
                false,
            ],
            [['@@version' => '5.5.0', '@@version_comment' => ''], 50500, true, false, false],
        ];
    }

    #[DataProvider('providerForTestGetLowerCaseNames')]
    public function testGetLowerCaseNames(string|false|null $result, int $expected): void
    {
        $dbiDummy = $this->createDbiDummy();
        $expectedResult = $result !== false ? [[$result]] : [];
        $dbiDummy->addResult('SELECT @@lower_case_table_names', $expectedResult, ['@@lower_case_table_names']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        self::assertSame($expected, $dbi->getLowerCaseNames());
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

    public function testGetColumn(): void
    {
        (new ReflectionProperty(Index::class, 'registry'))->setValue(null, []);
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'test_db\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'test_table\''
                . ' AND `COLUMN_NAME` = \'test_column\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [['test_column', 'varchar(45)', null, 'NO', '', null, '', '', '']],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $dbiDummy->addResult('SHOW INDEXES FROM `test_db`.`test_table`', []);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $column = new Column('test_column', 'varchar(45)', null, false, '', null, '', '', '');
        self::assertEquals($column, $dbi->getColumn('test_db', 'test_table', 'test_column'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetColumnWithFullColumn(): void
    {
        (new ReflectionProperty(Index::class, 'registry'))->setValue(null, []);
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'test_db\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'test_table\''
                . ' AND `COLUMN_NAME` = \'test_column\''
                . ' ORDER BY `ORDINAL_POSITION`',
            // phpcs:ignore Generic.Files.LineLength.TooLong
            [['test_column', 'varchar(45)', 'utf8mb4_general_ci', 'NO', '', null, '', 'select,insert,update,references', '']],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $dbiDummy->addResult('SHOW INDEXES FROM `test_db`.`test_table`', []);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $column = new Column(
            'test_column',
            'varchar(45)',
            'utf8mb4_general_ci',
            false,
            '',
            null,
            '',
            'select,insert,update,references',
            '',
        );
        self::assertEquals($column, $dbi->getColumn('test_db', 'test_table', 'test_column'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    /**
     * @param list<non-empty-string>             $expected
     * @param list<array{non-empty-string}>|bool $result
     */
    #[DataProvider('getTablesProvider')]
    public function testGetTables(array $expected, array|bool $result, bool $isNaturalOrder): void
    {
        $config = new Config();
        $config->settings['NaturalOrder'] = $isNaturalOrder;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', $result, ['Tables_in_test_db']);
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        self::assertSame($expected, $dbi->getTables('test_db'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    /** @return iterable<string, array{list<non-empty-string>, list<array{non-empty-string}>|bool, bool}> */
    public static function getTablesProvider(): iterable
    {
        yield 'false' => [[], false, true];
        yield 'no tables' => [[], [], true];
        yield 'tables with NaturalOrder sort' => [
            ['table1', 'table2', 'Table3', 'table10', 'table20', 'table200'],
            [['Table3'], ['table1'], ['table10'], ['table2'], ['table20'], ['table200']],
            true,
        ];

        yield 'tables without NaturalOrder sort' => [
            ['Table3', 'table1', 'table10', 'table2', 'table20', 'table200'],
            [['Table3'], ['table1'], ['table10'], ['table2'], ['table20'], ['table200']],
            false,
        ];
    }

    public function testGetTablesWithEmptyDatabaseName(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy, new Config());

        self::assertSame([], $dbi->getTables(''));
        $dbiDummy->assertAllQueriesConsumed();
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([true, [['1']]])]
    #[TestWith([false, []])]
    #[TestWith([false, false])]
    public function testIsSuperUser(bool $expected, array|false $result): void
    {
        SessionCache::remove('is_superuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT 1 FROM mysql.user LIMIT 1', $result);
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame($expected, $dbi->isSuperUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_superuser'));
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testIsSuperUserFromCache(bool $expected): void
    {
        SessionCache::set('is_superuser', $expected);
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame($expected, $dbi->isSuperUser());
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testIsSuperUserWhenNotConnected(): void
    {
        SessionCache::remove('is_superuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        (new ReflectionProperty(DatabaseInterface::class, 'connections'))->setValue($dbi, []);

        self::assertFalse($dbi->isSuperUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertFalse(SessionCache::has('is_superuser'));
    }

    #[TestWith([true, ' WITH GRANT OPTION'])]
    #[TestWith([false, ''])]
    public function testIsGrantUserWithISDisabled(bool $expected, string $grantOption): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = true;

        SessionCache::remove('is_grantuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult(
            'SHOW GRANTS FOR CURRENT_USER();',
            [
                ['GRANT ALL PRIVILEGES ON `test_db_one`.* TO `test_user`@`localhost`'],
                ['GRANT ALL PRIVILEGES ON `test_db_two`.* TO `test_user`@`localhost`' . $grantOption],
            ],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        self::assertSame($expected, $dbi->isGrantUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_grantuser'));
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([true, [['1']]])]
    #[TestWith([false, []])]
    #[TestWith([false, false])]
    public function testIsGrantUserWithISEnabled(bool $expected, array|false $result): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;

        SessionCache::remove('is_grantuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_server', [['utf8_general_ci']]);
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''test_user''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'test_user' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            $result,
        );
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        self::assertSame($expected, $dbi->isGrantUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_grantuser'));
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([true, [['1']]])]
    #[TestWith([false, []])]
    #[TestWith([false, false])]
    public function testIsGrantUserWithGrantOnRole(bool $expected, array|false $result): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;

        SessionCache::remove('mysql_cur_role');
        SessionCache::remove('is_grantuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_server', [['utf8_general_ci']]);
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''test_user''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'test_user' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            [],
        );
        $dbiDummy->addResult('SELECT CURRENT_ROLE();', [['`role`@`localhost`']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''role''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'role' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            $result,
        );
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $dbi->setVersion(['@@version' => '10.5.0-MariaDB']);

        self::assertSame($expected, $dbi->isGrantUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_grantuser'));
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([[['NONE']]])]
    #[TestWith([[[null]]])]
    #[TestWith([[]])]
    #[TestWith([false])]
    public function testIsGrantUserWithoutGrantOnRole(array|false $result): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;

        SessionCache::remove('mysql_cur_role');
        SessionCache::remove('is_grantuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_server', [['utf8_general_ci']]);
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''test_user''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'test_user' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            [],
        );
        $dbiDummy->addResult('SELECT CURRENT_ROLE();', $result);
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $dbi->setVersion(['@@version' => '10.5.0-MariaDB']);

        self::assertFalse($dbi->isGrantUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertFalse(SessionCache::get('is_grantuser'));
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testIsGrantUserFromCache(bool $expected): void
    {
        SessionCache::set('is_grantuser', $expected);
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame($expected, $dbi->isGrantUser());
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testIsGrantUserWhenNotConnected(): void
    {
        SessionCache::remove('is_grantuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        (new ReflectionProperty(DatabaseInterface::class, 'connections'))->setValue($dbi, []);

        self::assertFalse($dbi->isGrantUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertFalse(SessionCache::has('is_grantuser'));
    }

    #[TestWith([true, 'CREATE USER ON *.*'])]
    #[TestWith([true, 'ALL PRIVILEGES ON *.*'])]
    #[TestWith([false, 'ALL PRIVILEGES ON `test_db_one`.*'])]
    #[TestWith([false, 'SELECT ON *.*'])]
    public function testIsCreateUserWithISDisabled(bool $expected, string $privilege): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = true;

        SessionCache::remove('is_createuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult(
            'SHOW GRANTS FOR CURRENT_USER();',
            [
                ['GRANT ' . $privilege . ' TO `test_user`@`localhost`'],
                ['GRANT SELECT ON `test_db_two`.* TO `test_user`@`localhost`'],
            ],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        self::assertSame($expected, $dbi->isCreateUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_createuser'));
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([true, [['1']]])]
    #[TestWith([false, []])]
    #[TestWith([false, false])]
    public function testIsCreateUserWithISEnabled(bool $expected, array|false $result): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;

        SessionCache::remove('is_createuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_server', [['utf8_general_ci']]);
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''test_user''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'test_user' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            $result,
        );
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        self::assertSame($expected, $dbi->isCreateUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_createuser'));
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([true, [['1']]])]
    #[TestWith([false, []])]
    #[TestWith([false, false])]
    public function testIsCreateUserWithPrivilegeOnRole(bool $expected, array|false $result): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;

        SessionCache::remove('mysql_cur_role');
        SessionCache::remove('is_createuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_server', [['utf8_general_ci']]);
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''test_user''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'test_user' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            [],
        );
        $dbiDummy->addResult('SELECT CURRENT_ROLE();', [['`role`@`localhost`']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''role''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'role' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            $result,
        );
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $dbi->setVersion(['@@version' => '10.5.0-MariaDB']);

        self::assertSame($expected, $dbi->isCreateUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame($expected, SessionCache::get('is_createuser'));
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([[['NONE']]])]
    #[TestWith([[[null]]])]
    #[TestWith([[]])]
    #[TestWith([false])]
    public function testIsCreateUserWithoutPrivilegeOnRole(array|false $result): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;

        SessionCache::remove('mysql_cur_role');
        SessionCache::remove('is_createuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@collation_server', [['utf8_general_ci']]);
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''test_user''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'test_user' LIKE `User` AND '' LIKE `Host` LIMIT 1",
            [],
        );
        $dbiDummy->addResult('SELECT CURRENT_ROLE();', $result);
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $dbi->setVersion(['@@version' => '10.5.0-MariaDB']);

        self::assertFalse($dbi->isCreateUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertFalse(SessionCache::get('is_createuser'));
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testIsCreateUserFromCache(bool $expected): void
    {
        SessionCache::set('is_createuser', $expected);
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame($expected, $dbi->isCreateUser());
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testIsCreateUserWhenNotConnected(): void
    {
        SessionCache::remove('is_createuser');
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        (new ReflectionProperty(DatabaseInterface::class, 'connections'))->setValue($dbi, []);

        self::assertFalse($dbi->isCreateUser());
        $dbiDummy->assertAllQueriesConsumed();
        self::assertFalse(SessionCache::has('is_createuser'));
    }

    public function testGetCompatibilities(): void
    {
        self::assertEquals(
            ['NONE', 'ANSI', 'DB2', 'MAXDB', 'MYSQL323', 'MYSQL40', 'MSSQL', 'ORACLE', 'TRADITIONAL'],
            $this->createDatabaseInterface()->getCompatibilities(),
        );
    }

    public function testGetWarnings(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult(
            'SHOW WARNINGS',
            [['Note', '1051', "Unknown table 'test.no_such_table'"], ['Error', '1046', 'No database selected']],
            ['Level', 'Code', 'Message'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $warnings = $dbi->getWarnings();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertCount(2, $warnings);
        $warning = $warnings[0];
        self::assertSame('Note', $warning->level);
        self::assertSame(1051, $warning->code);
        self::assertSame("Unknown table 'test.no_such_table'", $warning->message);
        $warning = $warnings[1];
        self::assertSame('Error', $warning->level);
        self::assertSame(1046, $warning->code);
        self::assertSame('No database selected', $warning->message);
    }

    /** @param list<non-empty-list<string>>|false $result */
    #[TestWith([[]])]
    #[TestWith([false])]
    public function testGetWarningsReturnsEmpty(array|false $result): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SHOW WARNINGS', $result, ['Level', 'Code', 'Message']);
        self::assertSame([], $this->createDatabaseInterface($dbiDummy)->getWarnings());
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetTable(): void
    {
        $table = $this->createDatabaseInterface()->getTable('sakila', 'actor');
        self::assertSame('sakila', $table->getDbName());
        self::assertSame('actor', $table->getName());
    }

    #[TestWith(['KILL 1234;', false, false])]
    #[TestWith(['KILL 1234;', true, false])]
    #[TestWith(['KILL 1234;', false, true])]
    #[TestWith(['CALL mysql.rds_kill(1234);', true, true])]
    public function testGetKillQuery(string $expected, bool $isSuperUser, bool $isAmazonRds): void
    {
        SessionCache::set('is_superuser', $isSuperUser);
        SessionCache::set('is_amazon_rds', $isAmazonRds);
        self::assertSame($expected, $this->createDatabaseInterface()->getKillQuery(1234));
    }

    #[TestWith(['test\\\string\\\\', 'test\string\\'])]
    #[TestWith(['test\_string\_', 'test_string_'])]
    #[TestWith(['test\%string\%', 'test%string%'])]
    #[TestWith(['\%test\\\string\_', '%test\string_'])]
    public function testEscapeMysqlWildcards(string $expected, string $unescapedString): void
    {
        self::assertSame($expected, $this->createDatabaseInterface()->escapeMysqlWildcards($unescapedString));
    }

    public function testInsertId(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT LAST_INSERT_ID();', [['1234']], ['LAST_INSERT_ID()']);
        self::assertSame(1234, $this->createDatabaseInterface($dbiDummy)->insertId());
        $dbiDummy->assertAllQueriesConsumed();
    }
}
