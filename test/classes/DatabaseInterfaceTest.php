<?php
/**
 * Test for faked database access
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Database\DatabaseList;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Relation;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Utils\SessionCache;
use stdClass;

/**
 * Tests basic functionality of dummy dbi driver
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
        parent::defineVersionConstants();
        $GLOBALS['server'] = 0;
        $extension = new DbiDummy();
        $this->dbi = new DatabaseInterface($extension);
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
        parent::setGlobalDbi();

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
        $extension = $this->getMockBuilder(DbiDummy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extension->expects($this->any())
            ->method('realQuery')
            ->will($this->returnValue(true));

        $meta1 = new stdClass();
        $meta1->table = 'meta1_table';
        $meta1->name = 'meta1_name';

        $meta2 = new stdClass();
        $meta2->table = 'meta2_table';
        $meta2->name = 'meta2_name';

        $extension->expects($this->any())
            ->method('getFieldsMeta')
            ->will(
                $this->returnValue(
                    [
                        $meta1,
                        $meta2,
                    ]
                )
            );

        $dbi = new DatabaseInterface($extension);

        $sql_query = 'PMA_sql_query';
        $view_columns = [
            'view_columns1',
            'view_columns2',
        ];

        $column_map = $dbi->getColumnMapFromSql(
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
        parent::setGlobalDbi();

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
     * Tests for DBI::setCollationl() method.
     */
    public function testSetCollation(): void
    {
        $extension = $this->getMockBuilder(DbiDummy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extension->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(1));

        $extension->expects($this->exactly(4))
            ->method('realQuery')
            ->withConsecutive(
                ["SET collation_connection = 'utf8_czech_ci';"],
                ["SET collation_connection = 'utf8mb4_bin_ci';"],
                ["SET collation_connection = 'utf8_czech_ci';"],
                ["SET collation_connection = 'utf8_bin_ci';"]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                true,
                true
            );

        $dbi = new DatabaseInterface($extension);

        $GLOBALS['charset_connection'] = 'utf8mb4';
        $dbi->setCollation('utf8_czech_ci');
        $dbi->setCollation('utf8mb4_bin_ci');
        $GLOBALS['charset_connection'] = 'utf8';
        $dbi->setCollation('utf8_czech_ci');
        $dbi->setCollation('utf8mb4_bin_ci');
    }

    /**
     * Tests for DBI::getForeignKeyConstrains() method.
     */
    public function testGetForeignKeyConstrains(): void
    {
        $this->assertEquals([
            [
                'TABLE_NAME' => 'table2',
                'COLUMN_NAME' => 'idtable2',
                'REFERENCED_TABLE_NAME' => 'table1',
                'REFERENCED_COLUMN_NAME' => 'idtable1',
            ],
        ], $this->dbi->getForeignKeyConstrains('test', ['table1', 'table2']));
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
            'PMA_VERSION' => $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'],
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

        $this->assertSame([
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
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $this->dbi->initRelationParamsCache();

        $this->assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled');

        // Should all be false for server = 0
        $this->assertSame([
            'PMA_VERSION' => $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'],
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
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            false
        );

        $this->dbi->initRelationParamsCache();

        $this->assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled');

        $this->assertSame([
            'PMA_VERSION' => $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'],
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
            'SELECT NULL FROM `pma__userconfig_custom` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $this->dbi->initRelationParamsCache();

        $this->assertArrayHasKey(
            'relation',
            $_SESSION,
            'The cache is expected to be filled because the custom override'
            . 'was undertood (pma__userconfig vs pma__userconfig_custom)'
        );

        $this->assertAllQueriesConsumed();

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
            'SELECT NULL FROM `pma__userconfig_custom` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $relationData = (new Relation($this->dbi))->checkRelationsParam();
        $this->assertSame([
            'PMA_VERSION' => $relationData['PMA_VERSION'],
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
