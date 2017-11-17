<?php
/**
 * Tests for PhpMyAdmin\Dbi\DbiMysqli class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Dbi;

use PhpMyAdmin\Dbi\DbiMysqli;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Dbi\DbiMysqli class
 *
 * @package PhpMyAdmin-test
 */
class DbiMysqliTest extends PmaTestCase
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
        $GLOBALS['cfg']['Server']['ssl'] = false;
        $GLOBALS['cfg']['Server']['compress'] = true;

        //$_SESSION
        $this->object = new DbiMysqli();
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
     * Test for mysqli related functions, using runkit_function_redefine
     *
     * @return void
     *
     * @group medium
     */
    public function testMysqliDBI()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped("Cannot redefine function");
        }

        //FOR UT, we just test the right mysql client API is called
        runkit_function_redefine(
            'mysqli_real_connect', '', 'return "mysqli_real_connect";'
        );
        runkit_function_redefine('mysqli_init', '', 'return "mysqli_init";');
        runkit_function_redefine('mysqli_options', '', 'return "mysqli_options";');
        runkit_function_redefine('mysqli_query', '', 'return "mysqli_query";');
        runkit_function_redefine(
            'mysqli_multi_query', '', 'return "mysqli_multi_query";'
        );
        runkit_function_redefine(
            'mysqli_fetch_array', '', 'return "mysqli_fetch_array";'
        );
        runkit_function_redefine(
            'mysqli_data_seek', '', 'return "mysqli_data_seek";'
        );
        runkit_function_redefine(
            'mysqli_more_results', '', 'return "mysqli_more_results";'
        );
        runkit_function_redefine(
            'mysqli_next_result', '', 'return "mysqli_next_result";'
        );
        runkit_function_redefine(
            'mysqli_get_host_info', '', 'return "mysqli_get_host_info";'
        );
        runkit_function_redefine(
            'mysqli_get_proto_info', '', 'return "mysqli_get_proto_info";'
        );
        runkit_function_redefine(
            'mysqli_get_client_info', '', 'return "mysqli_get_client_info";'
        );

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
            'mysqli_init',
            $ret
        );

        //test for realQuery
        $query = 'select * from DBI';
        $link = $ret;
        $options = 0;
        $ret = $this->object->realQuery($query, $link, $options);
        $this->assertEquals(
            'mysqli_query',
            $ret
        );

        //test for realMultiQuery
        $ret = $this->object->realMultiQuery($link, $query);
        $this->assertEquals(
            'mysqli_multi_query',
            $ret
        );

        //test for fetchArray
        $result = $ret;
        $ret = $this->object->fetchArray($result);
        $this->assertEquals(
            'mysqli_fetch_array',
            $ret
        );

        //test for fetchAssoc
        $result = $ret;
        $ret = $this->object->fetchAssoc($result);
        $this->assertEquals(
            'mysqli_fetch_array',
            $ret
        );

        //test for fetchRow
        $result = $ret;
        $ret = $this->object->fetchRow($result);
        $this->assertEquals(
            'mysqli_fetch_array',
            $ret
        );

        //test for dataSeek
        $result = $ret;
        $offset = 10;
        $ret = $this->object->dataSeek($result, $offset);
        $this->assertEquals(
            'mysqli_data_seek',
            $ret
        );

        //test for moreResults
        $link = $ret;
        $ret = $this->object->moreResults($link);
        $this->assertEquals(
            'mysqli_more_results',
            $ret
        );

        //test for nextResult
        $link = $ret;
        $ret = $this->object->nextResult($link);
        $this->assertEquals(
            'mysqli_next_result',
            $ret
        );

        //test for getHostInfo
        $link = $ret;
        $ret = $this->object->getHostInfo($link);
        $this->assertEquals(
            'mysqli_get_host_info',
            $ret
        );

        //test for getProtoInfo
        $link = $ret;
        $ret = $this->object->getProtoInfo($link);
        $this->assertEquals(
            'mysqli_get_proto_info',
            $ret
        );

        //test for getClientInfo
        $ret = $this->object->getClientInfo();
        $this->assertEquals(
            'mysqli_get_client_info',
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
     * Test for numRows
     *
     * @return void
     *
     * @group medium
     */
    public function testNumRows()
    {
        $this->assertEquals(
            0,
            $this->object->numRows(true)
        );
    }
}
