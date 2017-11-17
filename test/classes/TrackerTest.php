<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Tracker
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Util;
use PHPUnit_Framework_Assert as Assert;
use ReflectionClass;

/**
 * Tests for PhpMyAdmin\Tracker
 *
 * @package PhpMyAdmin-test
 */
class TrackerTest extends PmaTestCase
{
    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['tracking_add_drop_table'] = '';
        $GLOBALS['cfg']['Server']['tracking_add_drop_view'] = '';
        $GLOBALS['cfg']['Server']['tracking_add_drop_database'] = '';
        $GLOBALS['cfg']['Server']['tracking_default_statements'] = '';
        $GLOBALS['cfg']['Server']['tracking_version_auto_create'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'tracking' => 'tracking'
        );

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $cfg['dbi'] = $dbi;
    }
    /**
     * Test for Tracker::enable
     *
     * @return void
     * @test
     */
    public function testEnabled()
    {
        Tracker::enable();
        $this->assertTrue(
            Assert::readAttribute('PhpMyAdmin\Tracker', 'enabled')
        );
    }

    /**
     * Test for Tracker::isActive()
     *
     * @return void
     * @test
     */
    public function testIsActive()
    {
        $attr = new \ReflectionProperty('PhpMyAdmin\Tracker', 'enabled');
        $attr->setAccessible(true);
        $attr->setValue(false);

        $this->assertFalse(
            Tracker::isActive()
        );

        Tracker::enable();

        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'trackingwork' => false
        );

        $this->assertFalse(
            Tracker::isActive()
        );

        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'trackingwork' => true,
            'db' => 'pmadb',
            'tracking' => 'tracking'
        );

        $this->assertTrue(
            Tracker::isActive()
        );
    }

    /**
     * Test for Tracker::getTableName()
     *
     * @param string $string   String to test against
     * @param string $expected Expected Table Name
     *
     * @return void
     * @test
     * @dataProvider getTableNameData
     */
    public function testGetTableName($string, $expected)
    {
        $reflection = new ReflectionClass('PhpMyAdmin\Tracker');
        $method = $reflection->getMethod("getTableName");
        $method->setAccessible(true);

        $this->assertEquals(
            $expected,
            $method->invokeArgs(null, array($string))
        );
    }

    /**
     * Data Provider for testGetTableName
     *
     * @return array Test data
     *
     */
    public function getTableNameData()
    {
        return array(
            array("`tbl`;", "tbl"),
            array(" `pma.table` ", "table"),
            array(" `pma.table\nfoobar` ", "table")
        );
    }

    /**
     * Test for Tracker::isTracked()
     *
     * @return void
     * @test
     */
    public function testIsTracked()
    {
        $attr = new \ReflectionProperty('PhpMyAdmin\Tracker', 'enabled');
        $attr->setAccessible(true);
        $attr->setValue(false);

        $this->assertFalse(
            Tracker::isTracked("", "")
        );

        Tracker::enable();

        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['trackingwork'] = false;

        $this->assertFalse(
            Tracker::isTracked("", "")
        );

        $_SESSION['relation'][$GLOBALS['server']]['trackingwork'] = true;

        $this->assertTrue(
            Tracker::isTracked("pma_test_db", "pma_test_table")
        );

        $this->assertFalse(
            Tracker::isTracked("pma_test_db", "pma_test_table2")
        );
    }

    /**
     * Test for Tracker::getLogComment()
     *
     * @return void
     * @test
     */
    public function testGetLogComment()
    {
        $date = Util::date('Y-m-d H:i:s');
        $GLOBALS['cfg']['Server']['user'] = "pma_test_user";

        $this->assertEquals(
            "# log $date pma_test_user\n",
            Tracker::getLogComment()
        );
    }

    /**
     * Test for Tracker::createVersion()
     *
     * @return void
     * @test
     */
    public function testCreateVersion()
    {
        $GLOBALS['cfg']['Server']['tracking_add_drop_table'] = true;
        $GLOBALS['cfg']['Server']['tracking_add_drop_view'] = true;
        $GLOBALS['cfg']['Server']['user'] = "pma_test_user";

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * set up mock objects
         * passing null to with() for an argument is equivalent
         * to passing $this->anything()
         */

        $getColumnsResult = array(
            array(
                'Field' => 'field1',
                'Type' => 'int(11)',
                'Key' => 'PRI'
            ),
            array(
                'Field' => 'field2',
                'Type' => 'text',
                'Key' => ''
            )
        );
        $dbi->expects($this->once())->method('getColumns')
            ->with('pma_test', 'pma_tbl')
            ->will($this->returnValue($getColumnsResult));

        $getIndexesResult = array(
            array(
                'Table' => 'pma_tbl',
                'Field' => 'field1',
                'Key' => 'PRIMARY'
            )
        );
        $dbi->expects($this->once())->method('getTableIndexes')
            ->with('pma_test', 'pma_tbl')
            ->will($this->returnValue($getIndexesResult));

        $tableStatusArray = array(
            array(
                'Name' => 'pma_tbl',
                'Rows' => '1',
                'Create_time' => '2013-02-22 02:04:04',
                'Update_time' => '2013-02-22 21:46:48'
            )
        );
        $dbi->expects($this->exactly(2))
            ->method('tryQuery')
            ->withConsecutive(
                array("SHOW TABLE STATUS FROM `pma_test` WHERE Name = 'pma_tbl'"),
                array('SHOW CREATE TABLE `pma_test`.`pma_tbl`')
            )
            ->willReturnOnConsecutiveCalls(
                'res',
                'res'
            );

        $date = Util::date('Y-m-d H:i:s');

        $expectedMainQuery = "/*NOTRACK*/" .
        "\nINSERT INTO `pmadb`.`tracking` (db_name, table_name, version, date_created, date_updated," .
        " schema_snapshot, schema_sql, data_sql, tracking ) values (
        'pma_test',
        'pma_tbl',
        '1',
        '" . $date . "',
        '" . $date . "',
        'a:2:{s:7:\"COLUMNS\";a:2:{" .
        "i:0;a:3:{s:5:\"Field\";s:6:\"field1\";s:4:\"Type\";s:7:\"int(11)\";" .
        "s:3:\"Key\";s:3:\"PRI\";}" .
        "i:1;a:3:{s:5:\"Field\";s:6:\"field2\";s:4:\"Type\";s:4:\"text\";" .
        "s:3:\"Key\";s:0:\"\";}}" .
        "s:7:\"INDEXES\";a:1:{" .
        "i:0;a:3:{s:5:\"Table\";s:7:\"pma_tbl\";s:5:\"Field\";s:6:\"field1\";" .
        "s:3:\"Key\";s:7:\"PRIMARY\";}}}',
        '# log " . $date . " pma_test_user" .
        "\nDROP VIEW IF EXISTS `pma_tbl`;" .
        "\n# log " . $date . " pma_test_user" .
        "\n\n;" .
        "\n',
        '" .
        "\n',
        '11' )";

        $queryResults = array(
            array(
                $expectedMainQuery,
                DatabaseInterface::CONNECT_CONTROL,
                0,
                false,
                'executed'
            )
        );

        $dbi->expects($this->any())->method('query')
            ->will($this->returnValueMap($queryResults));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $dbi->expects($this->any())->method('getCompatibilities')
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;
        $this->assertEquals(
            'executed',
            Tracker::createVersion('pma_test', 'pma_tbl', '1', '11', true)
        );
    }

    /**
     * Test for Tracker::deleteTracking()
     *
     * @return void
     * @test
     */
    public function testDeleteTracking()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $sql_query = "/*NOTRACK*/\n"
            . "DELETE FROM `pmadb`.`tracking`"
            . " WHERE `db_name` = 'testdb'"
            . " AND `table_name` = 'testtable'";

        $dbi->expects($this->exactly(1))
            ->method('query')
            ->with($sql_query)
            ->will($this->returnValue('executed'));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertEquals(
            Tracker::deleteTracking("testdb", "testtable"),
            'executed'
        );
    }

    /**
     * Test for Tracker::createDatabaseVersion()
     *
     * @return void
     * @test
     */
    public function testCreateDatabaseVersion()
    {
        $GLOBALS['cfg']['Server']['tracking_add_drop_table'] = true;
        $GLOBALS['cfg']['Server']['tracking_add_drop_view'] = true;
        $GLOBALS['cfg']['Server']['user'] = "pma_test_user";

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $date = Util::date('Y-m-d H:i:s');

        $expectedMainQuery = "/*NOTRACK*/" .
        "\nINSERT INTO `pmadb`.`tracking` (db_name, table_name, version, date_created, date_updated," .
        " schema_snapshot, schema_sql, data_sql, tracking ) values (
        'pma_test',
        '',
        '1',
        '" . $date . "',
        '" . $date . "',
        '',
        '# log " . $date . " pma_test_user" .
        "\nSHOW DATABASES',
        '" .
        "\n',
        'CREATE DATABASE,ALTER DATABASE,DROP DATABASE' )";

        $dbi->expects($this->exactly(1))
            ->method('query')
            ->with($expectedMainQuery, DatabaseInterface::CONNECT_CONTROL, 0, false)
            ->will($this->returnValue("executed"));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertEquals(
            'executed',
            Tracker::createDatabaseVersion('pma_test', '1', 'SHOW DATABASES')
        );
    }

    /**
     * Test for Tracker::changeTracking(). This test is also invoked by two
     * other tests: testActivateTracking() and testDeactivateTracking()
     *
     * @param string $dbname    Database name
     * @param string $tablename Table name
     * @param string $version   Version
     * @param string $new_state State to change to
     * @param string $type      Type of test
     *
     * @return void
     *
     * @test
     *
     */
    public function testChangeTracking($dbname = 'pma_db', $tablename = 'pma_tbl',
        $version = '0.1', $new_state = '1', $type = null
    ) {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $sql_query = " UPDATE `pmadb`.`tracking` SET `tracking_active` = " .
        "'" . $new_state . "' " .
        " WHERE `db_name` = '" . $dbname . "' " .
        " AND `table_name` = '" . $tablename . "' " .
        " AND `version` = '" . $version . "' ";

        $dbi->expects($this->exactly(1))
            ->method('query')
            ->with($sql_query, DatabaseInterface::CONNECT_CONTROL, 0, false)
            ->will($this->returnValue("executed"));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        if ($type == null) {
            $method = new \ReflectionMethod('PhpMyAdmin\Tracker', '_changeTracking');
            $method->setAccessible(true);
            $result = $method->invoke(
                null,
                $dbname,
                $tablename,
                $version,
                $new_state
            );
        } elseif ($type == "activate") {
            $result = Tracker::activateTracking($dbname, $tablename, $version);
        } elseif ($type == "deactivate") {
            $result = Tracker::deactivateTracking($dbname, $tablename, $version);
        }

        $this->assertEquals(
            'executed',
            $result
        );
    }

    /**
     * Test for Tracker::testChangeTrackingData()
     *
     * @return void
     * @test
     */
    public function testChangeTrackingData()
    {
        $this->assertFalse(
            Tracker::changeTrackingData("", "", "", "", "")
        );

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $sql_query_1 = " UPDATE `pmadb`.`tracking`" .
        " SET `schema_sql` = '# new_data_processed' " .
        " WHERE `db_name` = 'pma_db' " .
        " AND `table_name` = 'pma_table' " .
        " AND `version` = '1.0' ";

        $date  = Util::date('Y-m-d H:i:s');

        $new_data = array(
            array(
                'username' => 'user1',
                'statement' => 'test_statement1'
            ),
            array(
                'username' => 'user2',
                'statement' => 'test_statement2'
            )
        );

        $sql_query_2 = " UPDATE `pmadb`.`tracking`" .
        " SET `data_sql` = '# log $date user1test_statement1\n" .
        "# log $date user2test_statement2\n' " .
        " WHERE `db_name` = 'pma_db' " .
        " AND `table_name` = 'pma_table' " .
        " AND `version` = '1.0' ";

        $dbi->method('query')
            ->will(
                $this->returnValueMap(
                    array(
                        array($sql_query_1, DatabaseInterface::CONNECT_CONTROL, 0, false, "executed_1"),
                        array($sql_query_2, DatabaseInterface::CONNECT_CONTROL, 0, false, "executed_2")
                    )
                )
            );

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $this->assertEquals(
            true,
            Tracker::changeTrackingData(
                'pma_db',
                'pma_table',
                '1.0',
                'DDL',
                "# new_data_processed"
            )
        );

        $this->assertEquals(
            true,
            Tracker::changeTrackingData(
                'pma_db',
                'pma_table',
                '1.0',
                'DML',
                $new_data
            )
        );
    }

    /**
     * Test for Tracker::activateTracking()
     *
     * @return void
     * @test
     */
    public function testActivateTracking()
    {
        $this->testChangeTracking('pma_db', 'pma_tbl', '0.1', 1, 'activate');
    }

    /**
     * Test for Tracker::deactivateTracking()
     *
     * @return void
     * @test
     */
    public function testDeactivateTracking()
    {
        $this->testChangeTracking('pma_db', 'pma_tbl', '0.1', '0', 'deactivate');
    }

    /**
     * Test for PMA_Tracker::getTrackedData()
     *
     * @param array $fetchArrayReturn Value to be returned by mocked fetchArray
     * @param array $expectedArray    Expected array
     *
     * @return void
     * @test
     * @dataProvider getTrackedDataProvider
     */
    public function testGetTrackedData($fetchArrayReturn, $expectedArray)
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->will($this->returnValue("executed_1"));

        $dbi->expects($this->once())
            ->method('fetchAssoc')
            ->with("executed_1")
            ->will($this->returnValue($fetchArrayReturn));

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will(
                $this->returnValueMap(
                    array(
                        array("pma'db", "pma\'db"),
                        array("pma'table", "pma\'table"),
                        array("1.0", "1.0"),
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $result = Tracker::getTrackedData("pma'db", "pma'table", "1.0");

        $this->assertEquals(
            $expectedArray,
            $result
        );
    }

    /**
     * Data provider for testGetTrackedData
     *
     * @return array Test data
     */
    public function getTrackedDataProvider()
    {
        $fetchArrayReturn = array(
            array(
                "schema_sql" => "# log 20-03-2013 23:33:58 user1\nstat1" .
                "# log 20-03-2013 23:39:58 user2\n",
                "data_sql" => "# log ",
                "schema_snapshot" => "dataschema",
                "tracking" => "SELECT, DELETE"
            )
        );

        $data = array(
            array(
                'date_from' => '20-03-2013 23:33:58',
                'date_to' => '20-03-2013 23:39:58',
                'ddlog' => array(
                                array(
                                    'date' => '20-03-2013 23:33:58',
                                    'username' => 'user1',
                                    'statement' => "\nstat1"
                                ),
                                array(
                                    'date' => '20-03-2013 23:39:58',
                                    'username' => 'user2',
                                    'statement' => ""
                                )
                            ),
                'dmlog' => array(),
                "schema_snapshot" => "dataschema",
                "tracking" => "SELECT, DELETE"
            )
        );

        $fetchArrayReturn[1] = array(
            "schema_sql" => "# log 20-03-2012 23:33:58 user1\n" .
            "# log 20-03-2012 23:39:58 user2\n",
            "data_sql" => "# log 20-03-2013 23:33:58 user3\n" .
            "# log 20-03-2013 23:39:58 user4\n",
            "schema_snapshot" => "dataschema",
            "tracking" => "SELECT, DELETE"
        );

        $data[1] = array(
            'date_from' => '20-03-2012 23:33:58',
            'date_to' => '20-03-2013 23:39:58',
            'ddlog' => array(
                            array(
                                'date' => '20-03-2012 23:33:58',
                                'username' => 'user1',
                                'statement' => ""
                            ),
                            array(
                                'date' => '20-03-2012 23:39:58',
                                'username' => 'user2',
                                'statement' => ""
                            )
                        ),
            'dmlog' => array(
                            array(
                                'date' => '20-03-2013 23:33:58',
                                'username' => 'user3',
                                'statement' => ""
                            ),
                            array(
                                'date' => '20-03-2013 23:39:58',
                                'username' => 'user4',
                                'statement' => ""
                            )
                        ),
            "schema_snapshot" => "dataschema",
            "tracking" => "SELECT, DELETE"
        );
        return array(
            array($fetchArrayReturn[0], $data[0]),
            array($fetchArrayReturn[1], $data[1])
        );
    }

    /**
     * Test for Tracker::parseQuery
     *
     * @param string $query                  Query to parse
     * @param string $type                   Expected type
     * @param string $identifier             Expected identifier
     * @param string $tablename              Expected tablename
     * @param string $db                     Expected dbname
     * @param string $tablename_after_rename Expected name after rename
     *
     * @return void
     *
     * @test
     * @dataProvider parseQueryData
     */
    public function testParseQuery($query, $type, $identifier, $tablename,
        $db = null, $tablename_after_rename = null
    ) {
        $result = Tracker::parseQuery($query);

        $this->assertEquals(
            $type,
            $result['type']
        );

        $this->assertEquals(
            $identifier,
            $result['identifier']
        );

        $this->assertEquals(
            $tablename,
            $result['tablename']
        );

        if ($db) {
            $this->assertEquals(
                $db,
                $GLOBALS['db']
            );
        }

        if ($tablename_after_rename) {
            $this->assertEquals(
                $result['tablename_after_rename'],
                $tablename_after_rename
            );
        }
    }

    /**
     * Data provider for testParseQuery
     *
     * @return array Test data
     */
    public function parseQueryData()
    {
        $query = array();
        /** TODO: Should test fail when USE is in conjunction with * identifiers?
        $query[] = array(
            " - USE db1;\n- CREATE VIEW db1.v AS SELECT * FROM t;",
            "DDL",
            "CREATE VIEW",
            "v",
            "db1"
        );
        */
        $query[] = array(
            "CREATE VIEW v AS SELECT * FROM t;",
            "DDL",
            "CREATE VIEW",
            "v",
        );
        $query[] = array(
            "ALTER VIEW db1.v AS SELECT col1, col2, col3, col4 FROM t",
            "DDL",
            "ALTER VIEW",
            "v"
        );
        $query[] = array(
            "DROP VIEW db1.v;",
            "DDL",
            "DROP VIEW",
            "v"
        );
        $query[] = array(
            "DROP VIEW IF EXISTS db1.v;",
            "DDL",
            "DROP VIEW",
            "v"
        );
        $query[] = array(
            "CREATE DATABASE db1;",
            "DDL",
            "CREATE DATABASE",
            "",
            "db1"
        );
        $query[] = array(
            "ALTER DATABASE db1;",
            "DDL",
            "ALTER DATABASE",
            ""
        );
        $query[] = array(
            "DROP DATABASE db1;",
            "DDL",
            "DROP DATABASE",
            "",
            "db1"
        );
        $query[] = array(
            "CREATE TABLE db1.t1 (c1 INT);",
            "DDL",
            "CREATE TABLE",
            "t1"
        );
        $query[] =  array(
            "ALTER TABLE db1.t1 ADD c2 TEXT;",
            "DDL",
            "ALTER TABLE",
            "t1"
        );
        $query[] =  array(
            "DROP TABLE db1.t1",
            "DDL",
            "DROP TABLE",
            "t1"
        );
        $query[] =  array(
            "DROP TABLE IF EXISTS db1.t1",
            "DDL",
            "DROP TABLE",
            "t1"
        );
        $query[] =  array(
            "CREATE INDEX ind ON db1.t1 (c2(10));",
            "DDL",
            "CREATE INDEX",
            "t1"
        );
        $query[] =  array(
            "CREATE UNIQUE INDEX ind ON db1.t1 (c2(10));",
            "DDL",
            "CREATE INDEX",
            "t1"
        );
        $query[] =  array(
            "CREATE SPATIAL INDEX ind ON db1.t1 (c2(10));",
            "DDL",
            "CREATE INDEX",
            "t1"
        );
        $query[] =  array(
            "DROP INDEX ind ON db1.t1;",
            "DDL",
            "DROP INDEX",
            "t1"
        );
        $query[] =  array(
            "RENAME TABLE db1.t1 TO db1.t2",
            "DDL",
            "RENAME TABLE",
            "t1",
            "",
            "t2"
        );
        $query[] =  array(
            "UPDATE db1.t1 SET a = 2",
            "DML",
            "UPDATE",
            "t1"
        );
        $query[] =  array(
            "INSERT INTO db1.t1 (a, b, c) VALUES(1, 2, 3)",
            "DML",
            "INSERT",
            "t1"
        );
        $query[] =  array(
            "DELETE FROM db1.t1",
            "DML",
            "DELETE",
            "t1"
        );
        $query[] =  array(
            "TRUNCATE db1.t1",
            "DML",
            "TRUNCATE",
            "t1"
        );

        return $query;
    }
}
