<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Database\DatabaseList;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Relation;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Utils\SessionCache;

/**
 * @covers \PhpMyAdmin\DatabaseInterface
 */
class DatabaseInterfaceTest extends AbstractTestCase
{
    /**
     * Configures test parameters.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        parent::setGlobalDbi();
        $GLOBALS['server'] = 0;
    }

    /**
     * Tests for DBI::getCurrentUser() method.
     *
     * @param array|false $value           value
     * @param string      $string          string
     * @param array       $expected        expected result
     * @param bool        $needsSecondCall The test will need to call another time the DB
     *
     * @dataProvider currentUserData
     */
    public function testGetCurrentUser($value, string $string, array $expected, bool $needsSecondCall): void
    {
        SessionCache::remove('mysql_cur_user');

        $this->dummyDbi->addResult('SELECT CURRENT_USER();', $value);
        if ($needsSecondCall) {
            $this->dummyDbi->addResult('SELECT CURRENT_USER();', $value);
        }

        $this->assertEquals(
            $expected,
            $this->dbi->getCurrentUserAndHost()
        );

        $this->assertEquals(
            $string,
            $this->dbi->getCurrentUser()
        );

        $this->assertAllQueriesConsumed();
    }

    /**
     * Data provider for getCurrentUser() tests.
     *
     * @return array
     */
    public function currentUserData(): array
    {
        return [
            [
                [['pma@localhost']],
                'pma@localhost',
                [
                    'pma',
                    'localhost',
                ],
                false,
            ],
            [
                [['@localhost']],
                '@localhost',
                [
                    '',
                    'localhost',
                ],
                false,
            ],
            [
                false,
                '@',
                [
                    '',
                    '',
                ],
                true,
            ],
        ];
    }

    /**
     * Tests for DBI::getColumnMapFromSql() method.
     */
    public function testPMAGetColumnMap(): void
    {
        $this->dummyDbi->addResult(
            'PMA_sql_query',
            [true],
            [],
            [
                (object) [
                    'table' => 'meta1_table',
                    'name' => 'meta1_name',
                ],
                (object) [
                    'table' => 'meta2_table',
                    'name' => 'meta2_name',
                ],
            ]
        );

        $sql_query = 'PMA_sql_query';
        $view_columns = [
            'view_columns1',
            'view_columns2',
        ];

        $column_map = $this->dbi->getColumnMapFromSql(
            $sql_query,
            $view_columns
        );

        $this->assertEquals(
            [
                'table_name' => 'meta1_table',
                'refering_column' => 'meta1_name',
                'real_column' => 'view_columns1',
            ],
            $column_map[0]
        );
        $this->assertEquals(
            [
                'table_name' => 'meta2_table',
                'refering_column' => 'meta2_name',
                'real_column' => 'view_columns2',
            ],
            $column_map[1]
        );

        $this->assertAllQueriesConsumed();
    }

    /**
     * Tests for DBI::getSystemDatabase() method.
     */
    public function testGetSystemDatabase(): void
    {
        $sd = $this->dbi->getSystemDatabase();
        $this->assertInstanceOf(SystemDatabase::class, $sd);
    }

    /**
     * Tests for DBI::postConnectControl() method.
     */
    public function testPostConnectControl(): void
    {
        parent::setGlobalDbi();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            []
        );
        $GLOBALS['db'] = '';
        $GLOBALS['cfg']['Server']['only_db'] = [];
        $this->dbi->postConnectControl();
        $this->assertInstanceOf(DatabaseList::class, $GLOBALS['dblist']);
    }

    /**
     * Test for getDbCollation
     */
    public function testGetDbCollation(): void
    {
        $GLOBALS['server'] = 1;
        // test case for system schema
        $this->assertEquals(
            'utf8_general_ci',
            $this->dbi->getDbCollation('information_schema')
        );

        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $this->assertEquals(
            'utf8_general_ci',
            $this->dbi->getDbCollation('pma_test')
        );
    }

    /**
     * Test for getServerCollation
     */
    public function testGetServerCollation(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['DBG']['sql'] = true;
        $this->assertEquals('utf8_general_ci', $this->dbi->getServerCollation());
    }

    /**
     * Test error formatting
     *
     * @param int    $error_number  Error code
     * @param string $error_message Error message as returned by server
     * @param string $match         Expected text
     *
     * @dataProvider errorData
     */
    public function testFormatError(int $error_number, string $error_message, string $match): void
    {
        $this->assertStringContainsString(
            $match,
            Utilities::formatError($error_number, $error_message)
        );
    }

    public function errorData(): array
    {
        return [
            [
                2002,
                'msg',
                'The server is not responding',
            ],
            [
                2003,
                'msg',
                'The server is not responding',
            ],
            [
                1698,
                'msg',
                'index.php?route=/logout',
            ],
            [
                1005,
                'msg',
                'index.php?route=/server/engines',
            ],
            [
                1005,
                'errno: 13',
                'Please check privileges',
            ],
            [
                -1,
                'error message',
                'error message',
            ],
        ];
    }

    /**
     * Tests for DBI::isAmazonRds() method.
     *
     * @param array $value    value
     * @param bool  $expected expected result
     *
     * @dataProvider isAmazonRdsData
     */
    public function testIsAmazonRdsData(array $value, bool $expected): void
    {
        SessionCache::remove('is_amazon_rds');

        $this->dummyDbi->addResult('SELECT @@basedir', $value);

        $this->assertEquals(
            $expected,
            $this->dbi->isAmazonRds()
        );

        $this->assertAllQueriesConsumed();
    }

    /**
     * Data provider for isAmazonRds() tests.
     *
     * @return array
     */
    public function isAmazonRdsData(): array
    {
        return [
            [
                [['/usr']],
                false,
            ],
            [
                [['E:/mysql']],
                false,
            ],
            [
                [['/rdsdbbin/mysql/']],
                true,
            ],
            [
                [['/rdsdbbin/mysql-5.7.18/']],
                true,
            ],
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
        $ver_int = Utilities::versionToInt($version);
        $this->assertEquals($expected, $ver_int);
        $this->assertEquals($major, (int) ($ver_int / 10000));
        $this->assertEquals($upgrade, $ver_int < $GLOBALS['cfg']['MysqlMinVersion']['internal']);
    }

    public function versionData(): array
    {
        return [
            [
                '5.0.5',
                50005,
                5,
                true,
            ],
            [
                '5.05.01',
                50501,
                5,
                false,
            ],
            [
                '5.6.35',
                50635,
                5,
                false,
            ],
            [
                '10.1.22-MariaDB-',
                100122,
                10,
                false,
            ],
        ];
    }

    /**
     * Tests for DBI::setCollation() method.
     */
    public function testSetCollation(): void
    {
        $this->dummyDbi->addResult('SET collation_connection = \'utf8_czech_ci\';', [true]);
        $this->dummyDbi->addResult('SET collation_connection = \'utf8mb4_bin_ci\';', [true]);
        $this->dummyDbi->addResult('SET collation_connection = \'utf8_czech_ci\';', [true]);
        $this->dummyDbi->addResult('SET collation_connection = \'utf8_bin_ci\';', [true]);

        $GLOBALS['charset_connection'] = 'utf8mb4';
        $this->dbi->setCollation('utf8_czech_ci');
        $this->dbi->setCollation('utf8mb4_bin_ci');
        $GLOBALS['charset_connection'] = 'utf8';
        $this->dbi->setCollation('utf8_czech_ci');
        $this->dbi->setCollation('utf8mb4_bin_ci');

        $this->assertAllQueriesConsumed();
    }

    public function testGetTablesFull(): void
    {
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

        $actual = $this->dbi->getTablesFull('test_db');
        $this->assertEquals($expected, $actual);
    }

    public function testGetTablesFullWithInformationSchema(): void
    {
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

        $actual = $this->dbi->getTablesFull('test_db');
        $this->assertEquals($expected, $actual);
    }

    public function testInitRelationParamsCacheDefaultDbNameDbDoesNotExist(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 0;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            false
        );

        $this->dbi->initRelationParamsCache();

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsServerZero(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 0;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dbi->initRelationParamsCache();

        $this->assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled');

        // Should all be false for server = 0
        $this->assertSame([
            'version' => $_SESSION['relation'][$GLOBALS['server']]['version'],
            'relwork' => false,
            'displaywork' => false,
            'bookmarkwork' => false,
            'pdfwork' => false,
            'commwork' => false,
            'mimework' => false,
            'historywork' => false,
            'recentwork' => false,
            'favoritework' => false,
            'uiprefswork' => false,
            'trackingwork' => false,
            'userconfigwork' => false,
            'menuswork' => false,
            'navwork' => false,
            'savedsearcheswork' => false,
            'centralcolumnswork' => false,
            'designersettingswork' => false,
            'exporttemplateswork' => false,
            'allworks' => false,
            'user' => null,
            'db' => null,
        ], $_SESSION['relation'][$GLOBALS['server']]);

        $this->assertEquals([
            'userconfig' => 'pma__userconfig',
            'pmadb' => false,// This is the expected value for server = 0
        ], $GLOBALS['cfg']['Server']);
        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServer(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM pma__userconfig LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->dbi->initRelationParamsCache();
        $this->assertAllSelectsConsumed();

        $this->assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled');

        // Should all be false for server = 0
        $this->assertSame([
            'version' => $_SESSION['relation'][$GLOBALS['server']]['version'],
            'relwork' => false,
            'displaywork' => false,
            'bookmarkwork' => false,
            'pdfwork' => false,
            'commwork' => false,
            'mimework' => false,
            'historywork' => false,
            'recentwork' => false,
            'favoritework' => false,
            'uiprefswork' => false,
            'trackingwork' => false,
            'userconfigwork' => true,
            'menuswork' => false,
            'navwork' => false,
            'savedsearcheswork' => false,
            'centralcolumnswork' => false,
            'designersettingswork' => false,
            'exporttemplateswork' => false,
            'allworks' => false,
            'user' => '',
            'db' => 'phpmyadmin',
            'userconfig' => 'pma__userconfig',
        ], $_SESSION['relation'][$GLOBALS['server']]);

        $this->assertSame([
            'user' => '',
            'pmadb' => 'phpmyadmin',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => '',
            'userconfig' => 'pma__userconfig',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServerNotWorkingTable(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM pma__userconfig LIMIT 0',
            false
        );

        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->dbi->initRelationParamsCache();
        $this->assertAllSelectsConsumed();

        $this->assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled');

        $this->assertSame([
            'version' => $_SESSION['relation'][$GLOBALS['server']]['version'],
            'relwork' => false,
            'displaywork' => false,
            'bookmarkwork' => false,
            'pdfwork' => false,
            'commwork' => false,
            'mimework' => false,
            'historywork' => false,
            'recentwork' => false,
            'favoritework' => false,
            'uiprefswork' => false,
            'trackingwork' => false,
            'userconfigwork' => false,// Expected value for not working table
            'menuswork' => false,
            'navwork' => false,
            'savedsearcheswork' => false,
            'centralcolumnswork' => false,
            'designersettingswork' => false,
            'exporttemplateswork' => false,
            'allworks' => false,
            'user' => '',
            'db' => 'phpmyadmin',
            'userconfig' => 'pma__userconfig',
        ], $_SESSION['relation'][$GLOBALS['server']]);

        $this->assertSame([
            'user' => '',
            'pmadb' => 'phpmyadmin',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => '',
            'userconfig' => 'pma__userconfig',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServerOverride(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = 'PMA-storage';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = 'pma__userconfig_custom';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM pma__userconfig_custom LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $this->dummyDbi->addSelectDb('PMA-storage');
        $this->dbi->initRelationParamsCache();
        $this->assertAllSelectsConsumed();

        $this->assertArrayNotHasKey(
            'relation',
            $_SESSION,
            'The cache is NOT expected to be filled because no default phpMyAdmin storage tables'
            . ' with a default name where found (pma__userconfig vs pma__userconfig_custom)'
        );

        $this->dummyDbi->addSelectDb('PMA-storage');
        $relationData = (new Relation($this->dbi))->checkRelationsParam();
        $this->assertAllSelectsConsumed();

        $this->assertSame([
            'version' => $relationData['version'],
            'relwork' => false,
            'displaywork' => false,
            'bookmarkwork' => false,
            'pdfwork' => false,
            'commwork' => false,
            'mimework' => false,
            'historywork' => false,
            'recentwork' => false,
            'favoritework' => false,
            'uiprefswork' => false,
            'trackingwork' => false,
            'userconfigwork' => true,
            'menuswork' => false,
            'navwork' => false,
            'savedsearcheswork' => false,
            'centralcolumnswork' => false,
            'designersettingswork' => false,
            'exporttemplateswork' => false,
            'allworks' => false,
            'user' => '',
            'db' => 'PMA-storage',
            'userconfig' => 'pma__userconfig_custom',
        ], $relationData);

        $this->assertSame([
            'user' => '',
            'pmadb' => 'PMA-storage',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => '',
            'userconfig' => 'pma__userconfig_custom',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }
}
