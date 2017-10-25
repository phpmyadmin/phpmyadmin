<?php
/**
 * Tests for PhpMyAdmin\Dbi\DbiMysql class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Dbi;

use PhpMyAdmin\Dbi\DbiMysql;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Dbi\DbiMysql class
 *
 * @package PhpMyAdmin-test
 */
class DbiMysqlTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        if (! extension_loaded('mysql')) {
            $this->markTestSkipped('The MySQL extension is not available.');
        }
        $GLOBALS['cfg']['Server']['ssl'] = true;
        $GLOBALS['cfg']['Server']['compress'] = true;
        $this->object = new DbiMysql();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for realMultiQuery
     *
     * @return void
     *
     * @group medium
     */
    public function testRealMultiQuery()
    {
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->realMultiQuery(null, "select * from PMA")
        );
    }

    /**
     * Test for mysql related functions, using runkit_function_redefine
     *
     * @return void
     *
     * @group medium
     */
    public function testMysqlDBI()
    {
        if (! PMA_HAS_RUNKIT || ! $GLOBALS['runkit_internal_override']) {
            $this->markTestSkipped("Cannot redefine function");
        }
        //FOR UT, we just test the right mysql client API is called
        runkit_function_redefine('mysql_pconnect', '', 'return "mysql_pconnect";');
        runkit_function_redefine('mysql_connect', '', 'return "mysql_connect";');
        runkit_function_redefine('mysql_query', '', 'return "mysql_query";');
        runkit_function_redefine(
            'mysql_fetch_array', '', 'return "mysql_fetch_array";'
        );
        runkit_function_redefine(
            'mysql_data_seek', '', 'return "mysql_data_seek";'
        );
        runkit_function_redefine(
            'mysql_get_host_info', '', 'return "mysql_get_host_info";'
        );
        runkit_function_redefine(
            'mysql_get_proto_info', '', 'return "mysql_get_proto_info";'
        );
        runkit_function_redefine(
            'mysql_field_flags', '', 'return "mysql_field_flags";'
        );
        runkit_function_redefine(
            'mysql_field_name', '', 'return "mysql_field_name";'
        );
        runkit_function_redefine(
            'mysql_field_len', '', 'return "mysql_field_len";'
        );
        runkit_function_redefine(
            'mysql_num_fields', '', 'return "mysql_num_fields";'
        );
        runkit_function_redefine(
            'mysql_affected_rows', '', 'return "mysql_affected_rows";'
        );

        //test for fieldFlags
        $result = array("table1", "table2");
        $ret = $this->object->numFields($result);
        $this->assertEquals(
            'mysql_num_fields',
            $ret
        );

        //test for fetchRow
        $result = array("table1", "table2");
        $ret = $this->object->fetchRow($result);
        $this->assertEquals(
            'mysql_fetch_array',
            $ret
        );

        //test for fetchRow
        $result = array("table1", "table2");
        $ret = $this->object->fetchAssoc($result);
        $this->assertEquals(
            'mysql_fetch_array',
            $ret
        );

        //test for affectedRows
        $link = "PMA_link";
        $get_from_cache = false;
        $ret = $this->object->affectedRows($link, $get_from_cache);
        $this->assertEquals(
            "mysql_affected_rows",
            $ret
        );

        //test for connect
        $user = 'PMA_user';
        $password = 'PMA_password';
        $server = array(
            'port' => 8080,
            'socket' => 123,
            'host' => 'locahost',
            'compress' => false,
            'ssl' => false,
        );

        //test for connect
        $ret = $this->object->connect(
            $user, $password, $server
        );
        $this->assertEquals(
            'mysql_connect',
            $ret
        );

        $GLOBALS['cfg']['PersistentConnections'] = true;
        $ret = $this->object->connect(
            $user, $password, $server
        );
        $this->assertEquals(
            'mysql_pconnect',
            $ret
        );

        //test for realQuery
        $query = 'select * from DBI';
        $link = $ret;
        $options = 0;
        $ret = $this->object->realQuery($query, $link, $options);
        $this->assertEquals(
            'mysql_query',
            $ret
        );

        //test for fetchArray
        $result = $ret;
        $ret = $this->object->fetchArray($result);
        $this->assertEquals(
            'mysql_fetch_array',
            $ret
        );

        //test for dataSeek
        $result = $ret;
        $offset = 12;
        $ret = $this->object->dataSeek($result, $offset);
        $this->assertEquals(
            'mysql_data_seek',
            $ret
        );

        //test for getHostInfo
        $ret = $this->object->getHostInfo($ret);
        $this->assertEquals(
            'mysql_get_host_info',
            $ret
        );

        //test for getProtoInfo
        $ret = $this->object->getProtoInfo($ret);
        $this->assertEquals(
            'mysql_get_proto_info',
            $ret
        );

        //test for fieldLen
        $ret = $this->object->fieldLen($ret, $offset);
        $this->assertEquals(
            'mysql_field_len',
            $ret
        );

        //test for fieldName
        $ret = $this->object->fieldName($ret, $offset);
        $this->assertEquals(
            'mysql_field_name',
            $ret
        );

        //test for fieldFlags
        $ret = $this->object->fieldFlags($ret, $offset);
        $this->assertEquals(
            'mysql_field_flags',
            $ret
        );
    }

    /**
     * Test for selectDb
     *
     * @return void
     *
     * @group medium
     */
    public function testSelectDb()
    {
        $this->markTestIncomplete('Not testing anything');
        //$link is empty
        $this->assertEquals(
            false,
            $this->object->selectDb("PMA", null)
        );
    }

    /**
     * Test for moreResults
     *
     * @return void
     *
     * @group medium
     */
    public function testMoreResults()
    {
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->moreResults(null)
        );
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->nextResult(null)
        );
    }

    /**
     * Test for getClientInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testGetClientInfo()
    {
        $this->assertEquals(
            mysql_get_client_info(),
            $this->object->getClientInfo()
        );
    }

    /**
     * Test for numRows
     *
     * @return void
     *
     * @group medium
     */
    public function testNumRows()
    {
        $this->assertEquals(
            false,
            $this->object->numRows(true)
        );
    }

    /**
     * Test for storeResult
     *
     * @return void
     *
     * @group medium
     */
    public function testStoreResult()
    {
        $this->assertEquals(
            false,
            $this->object->storeResult(null)
        );
    }
}
