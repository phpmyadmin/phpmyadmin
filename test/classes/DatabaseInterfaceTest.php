<?php
/**
 * Test for faked database access
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Database\DatabaseList;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Utils\SessionCache;

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
}
